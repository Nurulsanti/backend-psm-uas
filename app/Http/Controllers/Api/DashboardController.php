<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DashboardMetric;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $totalSales = Transaction::sum('sales') ?? 0;
        $totalProfit = Transaction::sum('profit') ?? 0;
        $totalOrders = Transaction::count() ?? 0;
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        $totalCustomers = DB::table('customers')->count() ?? 0;

        return response()->json([
            'total_sales' => (float)$totalSales,
            'total_profit' => (float)$totalProfit,
            'total_orders' => (int)$totalOrders,
            'avg_order_value' => (float)$avgOrderValue,
            'total_customers' => (int)$totalCustomers,
        ]);
    }

    public function categorySales()
    {
        $categorySales = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->select('products.category', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('products.category')
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($categorySales);
    }

    public function bestSelling()
    {
        $bestSelling = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->select('products.product_name as name', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('sales')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($bestSelling);
    }
    public function regionSales()
    {
        $regionSales = DB::table('transactions')
            ->join('regions', 'transactions.region_id', '=', 'regions.id')
            ->select('regions.region', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('regions.region')
            ->get()
            ->map(function($item) {
                return [
                    'region' => $item->region,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($regionSales);
    }

    public function stateSales()
    {
        $stateSales = DB::table('transactions')
            ->join('regions', 'transactions.region_id', '=', 'regions.id')
            ->select('regions.state', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('regions.state')
            ->get()
            ->map(function($item) {
                return [
                    'state' => $item->state,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($stateSales);
    }
    public function salesTrend(Request $request)
    {
        $salesTrend = DB::table('transactions')
            ->select(
                DB::raw("strftime('%Y-%m', transaction_date) as period"),
                DB::raw('COALESCE(SUM(sales), 0) as sales')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function($item) {
                return [
                    'period' => $item->period,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($salesTrend);
    }

    public function complete()
    {
        return response()->json([
            'summary' => $this->summary()->getData(),
            'sales_by_category' => $this->categorySales()->getData(),
            'best_selling' => $this->bestSelling()->getData(),
            'sales_trend' => $this->salesTrend(request())->getData(),
        ]);
    }

    public function dailyTrend(Request $request)
    {
        $days = $request->get('days', 7);
        
        $dailySales = DB::table('transactions')
            ->select(
                DB::raw("DATE(transaction_date) as date"),
                DB::raw('COALESCE(SUM(sales), 0) as sales')
            )
            ->where('transaction_date', '>=', DB::raw("DATE('now', '-{$days} days')"))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($dailySales);
    }

    public function segmentSales()
    {
        $segmentSales = DB::table('transactions')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->select('customers.segment', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('customers.segment')
            ->get()
            ->map(function($item) {
                return [
                    'segment' => $item->segment,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($segmentSales);
    }

    public function citySales()
    {
        $citySales = DB::table('transactions')
            ->join('regions', 'transactions.region_id', '=', 'regions.id')
            ->select('regions.city', DB::raw('COALESCE(SUM(transactions.sales), 0) as sales'))
            ->groupBy('regions.city')
            ->orderByDesc('sales')
            ->get()
            ->map(function($item) {
                return [
                    'city' => $item->city,
                    'sales' => (float)($item->sales ?? 0),
                ];
            });

        return response()->json($citySales);
    }

}
