<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard —');
    }

    public function test_user_can_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/customers', [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '+1234567890',
            'address' => '123 Market St',
        ]);

        $response->assertRedirect('/customers');
        $this->assertDatabaseHas('customers', [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
        ]);
    }

    public function test_user_cannot_access_other_users_customer(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $customerA = $userA->currentBusiness()->customers()->create([
            'name' => 'Secret Customer',
            'email' => 'secret@corp.com',
        ]);

        $response = $this->actingAs($userB)->get("/customers/{$customerA->id}/edit");
        $response->assertStatus(403);
    }

    public function test_user_can_create_invoice_with_accurate_financial_calculations(): void
    {
        $user = User::factory()->create();
        $business = $user->currentBusiness();

        $customer = $business->customers()->create([
            'name' => 'Beta Client',
            'email' => 'client@beta.com',
        ]);

        $response = $this->actingAs($user)->post('/invoices', [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'discount' => 10.00, // $10.00 discount
            'notes' => 'Net 14 payment terms.',
            'items' => [
                [
                    'description' => 'Development Sprint',
                    'quantity' => 2,
                    'unit_price' => 100.00, // 2 * $100 = $200.00
                ],
                [
                    'description' => 'Architecture Review',
                    'quantity' => 1,
                    'unit_price' => 50.00, // 1 * $50 = $50.00
                ],
            ],
        ]);

        $response->assertRedirect('/invoices');

        $invoice = Invoice::where('invoice_number', 'INV-TEST-001')->firstOrFail();

        // subtotal = $250.00 (25000 cents)
        // discount = $10.00 (1000 cents)
        // taxable = $240.00 (24000 cents)
        // tax @ 10% = $24.00 (2400 cents)
        // total = $264.00 (26400 cents)
        $this->assertEquals(25000, $invoice->subtotal_cents);
        $this->assertEquals(1000, $invoice->discount_cents);
        $this->assertEquals(2400, $invoice->tax_cents);
        $this->assertEquals(26400, $invoice->total_cents);
        $this->assertEquals(26400, $invoice->dueAmountCents());
        $this->assertEquals(0, $invoice->paidAmountCents());
    }

    public function test_invoice_payment_records_and_updates_status(): void
    {
        $user = User::factory()->create();
        $business = $user->currentBusiness();

        $customer = $business->customers()->create([
            'name' => 'Gamma Client',
            'email' => 'gamma@client.com',
        ]);

        $invoice = $business->invoices()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PAY-01',
            'status' => 'issued',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal_cents' => 10000,
            'discount_cents' => 0,
            'tax_cents' => 1000,
            'total_cents' => 11000, // $110.00
        ]);

        // Partial payment $50.00
        $response = $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => 50.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'notes' => 'Initial deposit',
        ]);

        $response->assertSessionHas('success');
        $invoice->refresh();
        $this->assertEquals(5000, $invoice->paidAmountCents());
        $this->assertEquals(6000, $invoice->dueAmountCents());
        $this->assertEquals('issued', $invoice->status);

        // Full remaining payment $60.00
        $response2 = $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => 60.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
        ]);

        $response2->assertSessionHas('success');
        $invoice->refresh();
        $this->assertEquals(11000, $invoice->paidAmountCents());
        $this->assertEquals(0, $invoice->dueAmountCents());
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_payment_cannot_exceed_due_balance(): void
    {
        $user = User::factory()->create();
        $business = $user->currentBusiness();

        $customer = $business->customers()->create([
            'name' => 'Delta Client',
            'email' => 'delta@client.com',
        ]);

        $invoice = $business->invoices()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-LIMIT-01',
            'status' => 'issued',
            'issue_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal_cents' => 5000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 5000, // $50.00
        ]);

        $response = $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => 100.00, // Exceeds $50
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, $invoice->paidAmountCents());
    }

    public function test_user_can_manage_subscription_plans(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/subscriptions', [
            'plan_name' => 'Professional',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('subscriptions', [
            'plan_name' => 'Professional',
            'status' => 'active',
            'monthly_price_cents' => 4900,
        ]);

        $cancelResponse = $this->actingAs($user)->post('/subscriptions/cancel');
        $cancelResponse->assertSessionHas('success');
        $this->assertDatabaseHas('subscriptions', [
            'plan_name' => 'Professional',
            'status' => 'cancelled',
        ]);
    }
}
