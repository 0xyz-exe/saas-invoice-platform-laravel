<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'tax_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmountCents(): int
    {
        return (int) $this->payments()->sum('amount_cents');
    }

    public function dueAmountCents(): int
    {
        $due = $this->total_cents - $this->paidAmountCents();
        return max(0, $due);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_cents');
        $tax = (int) round(($subtotal - $this->discount_cents) * 0.10); // ponytail: 10% standard tax ceiling, upgrade to dynamic tax rate matrix when multi-jurisdiction active
        if ($tax < 0) $tax = 0;
        $total = max(0, $subtotal - $this->discount_cents + $tax);

        $this->update([
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'total_cents' => $total,
        ]);
    }
}
