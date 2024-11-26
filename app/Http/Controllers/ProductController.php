<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    /**
    * Display a listing of the products with query filtering and validation.
    */
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'category' => 'nullable|integer',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'in_stock' => 'nullable|boolean',
            'orderBy' => 'nullable|string|in:name,price,stock,created_at',
            'orderType' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Query the products with filters
        $query = Product::query();

        $query->when($request->filled('category'), fn($q) => $q->where('category', $validated['category']))
            ->when($request->filled('price_min'), fn($q) => $q->where('price', '>=', $validated['price_min']))
            ->when($request->filled('price_max'), fn($q) => $q->where('price', '<=', $validated['price_max']))
            ->when($request->filled('in_stock'), fn($q) => $q->where('stock', '>', 0));

        // Apply ordering
        if ($validated['orderBy'] ?? false) {
            $query->orderBy($validated['orderBy'], $validated['orderType'] ?? 'asc');
        }

        // Paginate results
        $products = $query->paginate($validated['per_page'] ?? 10);

        // Return the response
        return $this->success($products, 'Products retrieved successfully');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|integer',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        return $this->success($product, 'Product created successfully', 201);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', null, 404);
        }

        return $this->success($product, 'Product retrieved successfully');
    }
}
