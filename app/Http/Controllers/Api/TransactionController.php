<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'region_id' => 'nullable|exists:regions,id',
            'sales' => 'required|numeric|min:0',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::create([
            'product_id' => $request->product_id,
            'customer_id' => $request->customer_id,
            'region_id' => $request->region_id,
            'sales' => $request->sales,
            'transaction_date' => $request->transaction_date ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['product', 'customer', 'region']),
        ], 201);
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $transactions = Transaction::with(['product', 'customer', 'region'])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}
