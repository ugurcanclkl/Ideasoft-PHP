<?php

namespace App\Http\Controllers;

use App\Services\DiscountService;
use App\Models\Order;
use Illuminate\Http\Request;

class DiscountController extends BaseController
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function calculate(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Fetch the order
        $order = Order::find($validated['order_id']);

        // Calculate discounts
        try {
            $discounts = $this->discountService->calculateDiscounts($order);
            return $this->success($discounts, 'Discounts calculated successfully');
        } catch (\Exception $e) {
            return $this->error('Error calculating discounts', ['details' => $e->getMessage()], 500);
        }
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
