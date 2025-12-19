<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Region;
use App\Models\Transaction;
use App\Models\DashboardMetric;

class ImportDataSeeder extends Seeder
{
    public function run(): void
    {
        // Path ke folder CSV dari project BI
        $csvPath = base_path('../dwh_tables');

        echo "Importing data from BI project...\n\n";

        // 1. Import Products
        $this->importProducts($csvPath . '/dim_product.csv');

        // 2. Import Customers
        $this->importCustomers($csvPath . '/dim_customer.csv');

        // 3. Import Regions
        $this->importRegions($csvPath . '/dim_region.csv');

        // 4. Import Transactions (dari fact_sales.csv)
        $this->importTransactions($csvPath . '/fact_sales.csv');

        // 5. Calculate and store dashboard metrics
        $this->storeDashboardMetrics();

        echo "\n✓ All data imported successfully!\n";
    }

    private function importProducts($filePath)
    {
        if (!file_exists($filePath)) {
            echo "⚠ File not found: $filePath\n";
            return;
        }

        echo "Importing products...\n";
        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv);

        $imported = 0;
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            Product::updateOrCreate(
                ['product_id' => $data['product_id']],
                [
                    'product_name' => $data['product_name'],
                    'category' => $data['category'],
                    'sub_category' => $data['sub_category'],
                ]
            );
            $imported++;
        }
        echo "✓ Imported $imported products\n";
    }

    private function importCustomers($filePath)
    {
        if (!file_exists($filePath)) {
            echo "⚠ File not found: $filePath\n";
            return;
        }

        echo "Importing customers...\n";
        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv);

        $imported = 0;
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            Customer::updateOrCreate(
                ['customer_id' => $data['customer_id']],
                [
                    'customer_name' => $data['customer_name'],
                    'segment' => $data['segment'],
                ]
            );
            $imported++;
        }
        echo "✓ Imported $imported customers\n";
    }

    private function importRegions($filePath)
    {
        if (!file_exists($filePath)) {
            echo "⚠ File not found: $filePath\n";
            return;
        }

        echo "Importing regions...\n";
        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv);

        $imported = 0;
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            Region::updateOrCreate(
                ['city' => $data['city'], 'state' => $data['state']],
                [
                    'country' => $data['country'],
                    'region' => $data['region'],
                ]
            );
            $imported++;
        }
        echo "✓ Imported $imported regions\n";
    }

    private function importTransactions($filePath)
    {
        if (!file_exists($filePath)) {
            echo "⚠ File not found: $filePath\n";
            return;
        }

        echo "Importing transactions (this may take a while)...\n";
        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv);

        // Load dimension CSVs to create mapping dari surrogate keys ke database IDs
        $productMap = [];
        $productCsv = array_map('str_getcsv', file(dirname($filePath) . '/dim_product.csv'));
        array_shift($productCsv); // Remove header
        foreach ($productCsv as $idx => $row) {
            $productId = Product::where('product_id', $row[1])->value('id');
            $productMap[$row[0]] = $productId; // product_key => db id
        }

        $customerMap = [];
        $customerCsv = array_map('str_getcsv', file(dirname($filePath) . '/dim_customer.csv'));
        array_shift($customerCsv);
        foreach ($customerCsv as $row) {
            $customerId = Customer::where('customer_id', $row[1])->value('id');
            $customerMap[$row[0]] = $customerId; // customer_key => db id
        }

        $regionMap = [];
        $regionCsv = array_map('str_getcsv', file(dirname($filePath) . '/dim_region.csv'));
        array_shift($regionCsv);
        foreach ($regionCsv as $row) {
            $regionId = Region::where('city', $row[4])->where('state', $row[3])->value('id');
            $regionMap[$row[0]] = $regionId; // region_key => db id
        }

        $imported = 0;
        $batch = [];
        
        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            // Map surrogate keys to database IDs
            $productKey = $data['product_key'] ?? null;
            $customerKey = $data['customer_key'] ?? null;
            $regionKey = $data['region_key'] ?? null;
            
            if (!$productKey || !isset($productMap[$productKey])) continue;

            $batch[] = [
                'product_id' => $productMap[$productKey],
                'customer_id' => $customerMap[$customerKey] ?? null,
                'region_id' => $regionMap[$regionKey] ?? null,
                'sales' => $data['sales'] ?? 0,
                'transaction_date' => now()->subDays(rand(1, 365)),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches of 500
            if (count($batch) >= 500) {
                DB::table('transactions')->insert($batch);
                $imported += count($batch);
                $batch = [];
                echo ".";
            }
        }

        // Insert remaining
        if (count($batch) > 0) {
            DB::table('transactions')->insert($batch);
            $imported += count($batch);
        }

        echo "\n✓ Imported $imported transactions\n";
    }

    private function storeDashboardMetrics()
    {
        echo "Calculating dashboard metrics...\n";

        // Total Sales & Orders
        $totalSales = Transaction::sum('sales');
        $totalOrders = Transaction::count();

        DashboardMetric::updateOrCreate(
            ['metric_key' => 'summary'],
            [
                'metric_value' => [
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                    'avg_order_value' => $totalOrders > 0 ? $totalSales / $totalOrders : 0,
                ],
                'last_updated' => now(),
            ]
        );

        // Sales by Category
        $categorySales = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->select('products.category', DB::raw('SUM(transactions.sales) as total'))
            ->groupBy('products.category')
            ->get()
            ->pluck('total', 'category');

        DashboardMetric::updateOrCreate(
            ['metric_key' => 'sales_by_category'],
            [
                'metric_value' => $categorySales,
                'last_updated' => now(),
            ]
        );

        // Best Selling Products
        $bestSelling = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->select('products.product_name', DB::raw('SUM(transactions.sales) as total'))
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        DashboardMetric::updateOrCreate(
            ['metric_key' => 'best_selling'],
            [
                'metric_value' => $bestSelling,
                'last_updated' => now(),
            ]
        );

        // Sales Trend (last 7 days)
        $salesTrend = DB::table('transactions')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(sales) as total')
            )
            ->where('transaction_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        DashboardMetric::updateOrCreate(
            ['metric_key' => 'sales_trend'],
            [
                'metric_value' => $salesTrend,
                'last_updated' => now(),
            ]
        );

        echo "✓ Dashboard metrics calculated and stored\n";
    }
}
