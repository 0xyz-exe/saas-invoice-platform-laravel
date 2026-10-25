<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #333; margin: 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #111; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #444; text-align: right; }
        .details-grid { display: flex; justify-content: space-between; margin-top: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .table th, .table td { border-bottom: 1px solid #ddd; padding: 12px 8px; }
        .table th { background: #f9f9f9; text-align: left; font-size: 13px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; float: right; width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; margin-top: 8px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-issued { background: #dbeafe; color: #1e40af; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #4f46e5; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="company-name">{{ $invoice->business->name }}</div>
            <div>{{ $invoice->business->email }}</div>
            <div>{{ $invoice->business->phone }}</div>
            <div>{{ $invoice->business->address }}</div>
        </div>
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="text-right"><strong>#{{ $invoice->invoice_number }}</strong></div>
            <div class="text-right" style="margin-top: 5px;">
                <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : 'badge-issued' }}">{{ strtoupper($invoice->status) }}</span>
            </div>
            <div class="text-right" style="margin-top: 5px; font-size: 13px;">Date: {{ $invoice->issue_date->format('M d, Y') }}</div>
            <div class="text-right" style="font-size: 13px;">Due: {{ $invoice->due_date->format('M d, Y') }}</div>
        </div>
    </div>

    <div class="details-grid">
        <div>
            <h4 style="margin: 0 0 5px 0; font-size: 12px; color: #888; text-transform: uppercase;">Billed To:</h4>
            <strong>{{ $invoice->customer->name }}</strong><br>
            {{ $invoice->customer->email }}<br>
            {{ $invoice->customer->phone }}<br>
            {{ $invoice->customer->address }}
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total_cents / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>${{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
        </div>
        @if ($invoice->discount_cents > 0)
            <div class="totals-row">
                <span>Discount:</span>
                <span>-${{ number_format($invoice->discount_cents / 100, 2) }}</span>
            </div>
        @endif
        <div class="totals-row">
            <span>Tax (10%):</span>
            <span>${{ number_format($invoice->tax_cents / 100, 2) }}</span>
        </div>
        <div class="totals-row grand-total">
            <span>Total:</span>
            <span>${{ number_format($invoice->total_cents / 100, 2) }}</span>
        </div>
        <div class="totals-row" style="color: #065f46; font-weight: bold;">
            <span>Paid:</span>
            <span>${{ number_format($invoice->paidAmountCents() / 100, 2) }}</span>
        </div>
        <div class="totals-row" style="color: #b45309; font-weight: bold; border-top: 1px solid #ccc; margin-top: 4px;">
            <span>Balance Due:</span>
            <span>${{ number_format($invoice->dueAmountCents() / 100, 2) }}</span>
        </div>
    </div>

    <div style="clear: both; margin-top: 50px; font-size: 13px; color: #666;">
        @if ($invoice->notes)
            <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
        @endif
    </div>
</body>
</html>
