<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $query = $business->invoices()->with(['customer', 'payments'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->paginate(15);
        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $customers = $business->customers()->orderBy('name')->get();
        $products = $business->products()->orderBy('name')->get();
        $nextNumber = 'INV-' . strtoupper(substr(uniqid(), -6));

        return view('invoices.create', compact('customers', 'products', 'nextNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
        ]);

        // Customer must belong to user's business
        $customer = Customer::where('id', $validated['customer_id'])
            ->where('business_id', $business->id)
            ->firstOrFail();

        $discountCents = (int) round(($validated['discount'] ?? 0) * 100);

        DB::transaction(function () use ($business, $customer, $validated, $discountCents) {
            $invoice = $business->invoices()->create([
                'customer_id' => $customer->id,
                'invoice_number' => $validated['invoice_number'],
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'discount_cents' => $discountCents,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $unitPriceCents = (int) round($item['unit_price'] * 100);
                $totalCents = $qty * $unitPriceCents;

                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price_cents' => $unitPriceCents,
                    'total_cents' => $totalCents,
                ]);
            }

            $invoice->recalculateTotals();
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $this->authorizeBusiness($request, $invoice);
        $invoice->load(['customer', 'items.product', 'payments', 'business']);
        return view('invoices.show', compact('invoice'));
    }

    public function print(Request $request, Invoice $invoice): View
    {
        $this->authorizeBusiness($request, $invoice);
        $invoice->load(['customer', 'items.product', 'payments', 'business']);
        return view('invoices.print', compact('invoice'));
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeBusiness($request, $invoice);

        $validated = $request->validate([
            'status' => 'required|in:draft,issued,paid,overdue,cancelled',
        ]);

        $invoice->update(['status' => $validated['status']]);

        return back()->with('success', 'Invoice status updated to ' . $validated['status']);
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeBusiness($request, $invoice);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot delete finalized paid invoice.');
        }

        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    protected function authorizeBusiness(Request $request, Invoice $invoice): void
    {
        if ($invoice->business_id !== $request->user()->currentBusiness()->id) {
            abort(403, 'Unauthorized access to invoice.');
        }
    }
}
