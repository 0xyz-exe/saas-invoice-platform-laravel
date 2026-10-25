<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->currentBusiness();
        $subscription = $business->subscription ?? $business->subscription()->create([
            'plan_name' => 'Free',
            'status' => 'active',
            'monthly_price_cents' => 0,
            'current_period_end' => now()->addMonth(),
        ]);

        $plans = [
            'Free' => ['price' => 0, 'invoices_limit' => 5, 'features' => ['5 Invoices/month', 'Basic Reports', 'Email Support']],
            'Starter' => ['price' => 19, 'invoices_limit' => 50, 'features' => ['50 Invoices/month', 'Client Portal', 'Custom Branding']],
            'Professional' => ['price' => 49, 'invoices_limit' => 500, 'features' => ['500 Invoices/month', 'Automated Reminders', 'Multi-Currency']],
            'Enterprise' => ['price' => 99, 'invoices_limit' => -1, 'features' => ['Unlimited Invoices', 'Dedicated Support', 'API Access']],
        ];

        return view('subscriptions.index', compact('business', 'subscription', 'plans'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_name' => 'required|in:Free,Starter,Professional,Enterprise',
        ]);

        $prices = [
            'Free' => 0,
            'Starter' => 1900,
            'Professional' => 4900,
            'Enterprise' => 9900,
        ];

        $business = $request->user()->currentBusiness();
        $sub = $business->subscription()->updateOrCreate(
            ['business_id' => $business->id],
            [
                'plan_name' => $validated['plan_name'],
                'status' => 'active',
                'monthly_price_cents' => $prices[$validated['plan_name']],
                'current_period_end' => now()->addMonth(),
            ]
        );

        return back()->with('success', "Subscribed to {$validated['plan_name']} plan successfully.");
    }

    public function cancel(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        if ($sub = $business->subscription) {
            $sub->update(['status' => 'cancelled']);
        }

        return back()->with('success', 'Subscription cancelled.');
    }
}
