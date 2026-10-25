<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $customers = $business->customers()->withCount('invoices')->latest()->paginate(15);
        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $business = $request->user()->currentBusiness();
        $business->customers()->create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(Request $request, Customer $customer): View
    {
        $this->authorizeBusiness($request, $customer);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeBusiness($request, $customer);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeBusiness($request, $customer);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    protected function authorizeBusiness(Request $request, Customer $customer): void
    {
        if ($customer->business_id !== $request->user()->currentBusiness()->id) {
            abort(403, 'Unauthorized access to customer.');
        }
    }
}
