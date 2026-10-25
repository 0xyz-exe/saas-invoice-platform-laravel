<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $products = $business->products()->latest()->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $business = $request->user()->currentBusiness();
        $business->products()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit_price_cents' => (int) round($validated['unit_price'] * 100),
        ]);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizeBusiness($request, $product);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeBusiness($request, $product);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit_price_cents' => (int) round($validated['unit_price'] * 100),
        ]);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeBusiness($request, $product);
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    protected function authorizeBusiness(Request $request, Product $product): void
    {
        if ($product->business_id !== $request->user()->currentBusiness()->id) {
            abort(403, 'Unauthorized access to product.');
        }
    }
}
