<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $invoices = $business->invoices()->with(['customer', 'payments'])->latest()->get();

        $totalRevenueCents = (int) $business->invoices()->where('status', 'paid')->sum('total_cents');
        $outstandingCents = 0;
        foreach ($business->invoices()->whereIn('status', ['issued', 'overdue'])->get() as $inv) {
            $outstandingCents += $inv->dueAmountCents();
        }

        $metrics = [
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $invoices->where('status', 'paid')->count(),
            'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
            'total_revenue_cents' => $totalRevenueCents,
            'outstanding_cents' => $outstandingCents,
            'customers_count' => $business->customers()->count(),
            'products_count' => $business->products()->count(),
            'subscription' => $business->subscription ?? $business->subscription()->create(['plan_name' => 'Free', 'status' => 'active']),
        ];

        return view('dashboard', compact('business', 'invoices', 'metrics'));
    }
}
