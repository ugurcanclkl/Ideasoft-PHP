<?php

namespace App\Services;

use App\Jobs\UpdateCustomerRevenue;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Services\DiscountService;

class OrderService
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    public function createOrder($data)
    {
        // Validate stock for each item before transaction
        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                throw new \Exception("Product ID {$item['product_id']} not found.");
            }

            if ($product->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for product ID {$item['product_id']}");
            }
        }

        return DB::transaction(function () use ($data) {
            // Create the order
            $order = Order::create(['customer_id' => $data['customer_id'], 'total' => 0]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $total += $product->price * $item['quantity'];

                // Decrement stock
                $product->decrement('stock', $item['quantity']);

                // Create order item
                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total' => $product->price * $item['quantity'],
                ]);

                $order->update([
                    'total' => $order->total + ($product->price * $item['quantity']),
                ]);
            }

            // Calculate discounts
            $discountDetails = $this->discountService->calculateDiscounts($order);

            // Apply discounts to the total
            $discountedTotal = $total - $discountDetails['totalDiscount'];
            $order->update(['total' => $discountedTotal]);

            // Save discounts
            foreach ($discountDetails['discounts'] as $discount) {
                $order->discounts()->create([
                    'discount_reason' => $discount['discountReason'],
                    'discount_amount' => $discount['discountAmount'],
                ]);
            }

            // Dispatch the job to update customer's revenue
            UpdateCustomerRevenue::dispatch($order);

            return [
                'order' => $order,
                'discounts' => $discountDetails['discounts'],
                'totalDiscount' => $discountDetails['totalDiscount'],
                'discountedTotal' => $discountDetails['discountedTotal'],
            ];
        });
    }
}
