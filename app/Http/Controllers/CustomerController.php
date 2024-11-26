<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    /**
     * Display a listing of customers with query filtering and pagination.
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'revenue_min' => 'nullable|numeric|min:0',
            'revenue_max' => 'nullable|numeric|min:0',
            'since' => 'nullable|date',
            'orderBy' => 'nullable|string|in:name,revenue,since,created_at',
            'orderType' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Query customers with filters
        $query = Customer::query();

        $query->when($request->filled('revenue_min'), fn($q) => $q->where('revenue', '>=', $validated['revenue_min']))
            ->when($request->filled('revenue_max'), fn($q) => $q->where('revenue', '<=', $validated['revenue_max']))
            ->when($request->filled('since'), fn($q) => $q->whereDate('since', '>=', $validated['since']));

        // Apply ordering
        if ($validated['orderBy'] ?? false) {
            $query->orderBy($validated['orderBy'], $validated['orderType'] ?? 'asc');
        }

        // Paginate results
        $customers = $query->paginate($validated['per_page'] ?? 10);

        // Return the response
        return $this->success($customers, 'Customers retrieved successfully');
    }
}
