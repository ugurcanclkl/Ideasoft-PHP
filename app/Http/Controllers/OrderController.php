<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Models\Order;
use App\Models\Product;

class OrderController extends BaseController
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the orders with query filtering and validation.
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'orderBy' => 'nullable|string|in:created_at,total',
            'orderType' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Query the orders with filters
        $query = Order::query();

        $query->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $validated['customer_id']))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $validated['date_from']))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $validated['date_to']));

        // Apply ordering
        if ($validated['orderBy'] ?? false) {
            $query->orderBy($validated['orderBy'], $validated['orderType'] ?? 'asc');
        }

        // Paginate results
        $orders = $query->paginate($validated['per_page'] ?? 10);

        // Return the response
        return $this->success($orders, 'Orders retrieved successfully');
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->orderService->createOrder($validated);
            return $this->success($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->error('Order not found', null, 404);
        }

        $order->load('orderItems', 'discounts', 'orderItems.product');
        return $this->success($order, 'Order retrieved successfully');
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return $this->error('Order not found', null, 404);
        }

        try {
            // Update customer_id if provided
            if (isset($validated['customer_id'])) {
                $order->customer_id = $validated['customer_id'];
            }

            // Update order items if provided
            if (isset($validated['items'])) {
                $order->orderItems()->delete(); // Clear existing items
                $total = 0;

                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product ID {$item['product_id']}");
                    }

                    // Decrement stock for new items
                    $product->decrement('stock', $item['quantity']);

                    // Add the new items to the order
                    $order->orderItems()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'total' => $product->price * $item['quantity'],
                    ]);

                    $total += $product->price * $item['quantity'];
                }

                // Update the total for the order
                $order->total = $total;
            }

            $order->save();

            $order->load('orderItems');
            return $this->success($order, 'Order updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->error('Order not found', null, 404);
        }

        try {
            // Delete associated order items
            $order->orderItems()->delete();

            // Delete the order
            $order->delete();

            return $this->success(null, 'Order deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->error('Failed to delete order: ' . $e->getMessage(), null, 400);
        }
    }
}
