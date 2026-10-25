<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice ') . $invoice->invoice_number }}
            </h2>
            <div class="space-x-2">
                <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Print / PDF View
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Invoice Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-start border-b border-gray-200 pb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ $invoice->business->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $invoice->business->email }} | {{ $invoice->business->phone }}</p>
                        <p class="text-sm text-gray-500">{{ $invoice->business->address }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            @if($invoice->status === 'paid') bg-green-100 text-green-800 
                            @elseif($invoice->status === 'issued') bg-blue-100 text-blue-800
                            @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                            @elseif($invoice->status === 'cancelled') bg-gray-100 text-gray-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ strtoupper($invoice->status) }}
                        </span>
                        <div class="mt-2 text-sm text-gray-600">Issued: {{ $invoice->issue_date->format('M d, Y') }}</div>
                        <div class="text-sm text-gray-600">Due: {{ $invoice->due_date->format('M d, Y') }}</div>
                    </div>
                </div>

                <div class="py-6 border-b border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Billed To</h4>
                    <div class="mt-1 text-lg font-bold text-gray-900">{{ $invoice->customer->name }}</div>
                    <div class="text-sm text-gray-600">{{ $invoice->customer->email }}</div>
                    <div class="text-sm text-gray-600">{{ $invoice->customer->phone }}</div>
                    <div class="text-sm text-gray-600">{{ $invoice->customer->address }}</div>
                </div>

                <!-- Items -->
                <div class="py-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="py-3 text-sm text-gray-900 font-medium">{{ $item->description }}</td>
                                    <td class="py-3 text-sm text-gray-600 text-center">{{ $item->quantity }}</td>
                                    <td class="py-3 text-sm text-gray-600 text-right">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                                    <td class="py-3 text-sm text-gray-900 font-semibold text-right">${{ number_format($item->total_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6 flex justify-end">
                        <div class="w-64 space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal:</span>
                                <span>${{ number_format($invoice->subtotal_cents / 100, 2) }}</span>
                            </div>
                            @if ($invoice->discount_cents > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>Discount:</span>
                                    <span>-${{ number_format($invoice->discount_cents / 100, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-gray-600">
                                <span>Tax (10%):</span>
                                <span>${{ number_format($invoice->tax_cents / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-900 border-t pt-2">
                                <span>Grand Total:</span>
                                <span>${{ number_format($invoice->total_cents / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold text-green-700">
                                <span>Paid:</span>
                                <span>${{ number_format($invoice->paidAmountCents() / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-amber-700 border-t pt-1">
                                <span>Balance Due:</span>
                                <span>${{ number_format($invoice->dueAmountCents() / 100, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($invoice->notes)
                    <div class="mt-4 p-4 bg-gray-50 rounded text-sm text-gray-600">
                        <strong>Notes:</strong> {{ $invoice->notes }}
                    </div>
                @endif
            </div>

            <!-- Manage Status & Payments -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Workflow -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Invoice Status</h4>
                    <form action="{{ route('invoices.status', $invoice) }}" method="POST" class="flex items-center space-x-3">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="block w-full border-gray-300 rounded-md text-sm">
                            <option value="draft" {{ $invoice->status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ $invoice->status === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="paid" {{ $invoice->status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ $invoice->status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="cancelled" {{ $invoice->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                    </form>
                </div>

                <!-- Record Payment -->
                @if ($invoice->dueAmountCents() > 0 && !in_array($invoice->status, ['draft', 'cancelled']))
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Record Payment</h4>
                        <form action="{{ route('invoices.payments.store', $invoice) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <input type="number" step="0.01" name="amount" class="block w-full border-gray-300 rounded-md text-sm" placeholder="Amount ($)" max="{{ $invoice->dueAmountCents() / 100 }}" value="{{ $invoice->dueAmountCents() / 100 }}" required>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="payment_date" class="block w-full border-gray-300 rounded-md text-sm" value="{{ date('Y-m-d') }}" required>
                                <select name="payment_method" class="block w-full border-gray-300 rounded-md text-sm">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-md text-sm">Record Payment</button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Payment History -->
            @if ($invoice->payments->count() > 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Payment History</h4>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td class="py-2 text-sm text-gray-900">{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td class="py-2 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="py-2 text-sm font-semibold text-green-700 text-right">${{ number_format($payment->amount_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
