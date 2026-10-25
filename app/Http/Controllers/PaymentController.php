<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->business_id !== $request->user()->currentBusiness()->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        if (in_array($invoice->status, ['cancelled', 'draft'])) {
            return back()->with('error', 'Cannot record payment for a draft or cancelled invoice.');
        }

        $dueCents = $invoice->dueAmountCents();
        if ($dueCents <= 0) {
            return back()->with('error', 'Invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:bank_transfer,credit_card,cash,other',
            'notes' => 'nullable|string',
        ]);

        $amountCents = (int) round($validated['amount'] * 100);
        if ($amountCents > $dueCents) {
            return back()->with('error', 'Payment amount cannot exceed remaining due balance.');
        }

        DB::transaction(function () use ($invoice, $validated, $amountCents) {
            $invoice->payments()->create([
                'amount_cents' => $amountCents,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($invoice->fresh()->dueAmountCents() === 0) {
                $invoice->update(['status' => 'paid']);
            }
        });

        return back()->with('success', 'Payment recorded successfully.');
    }
}
