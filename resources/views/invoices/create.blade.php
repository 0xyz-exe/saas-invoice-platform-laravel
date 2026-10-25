<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" :value="__('Select Customer')" />
                            <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">-- Choose Customer --</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->email }})</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('customer_id')" />
                        </div>

                        <div>
                            <x-input-label for="invoice_number" :value="__('Invoice Number')" />
                            <x-text-input id="invoice_number" name="invoice_number" type="text" class="mt-1 block w-full" :value="old('invoice_number', $nextNumber)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('invoice_number')" />
                        </div>

                        <div>
                            <x-input-label for="issue_date" :value="__('Issue Date')" />
                            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1 block w-full" :value="old('issue_date', date('Y-m-d'))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('issue_date')" />
                        </div>

                        <div>
                            <x-input-label for="due_date" :value="__('Due Date')" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', date('Y-m-d', strtotime('+30 days')))" required />
                            <x-input-error class="mt-2" :messages="$errors->get('due_date')" />
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Invoice Line Items</h3>
                        
                        <div class="space-y-4" id="items-container">
                            <div class="grid grid-cols-12 gap-3 items-center">
                                <div class="col-span-6">
                                    <x-input-label :value="__('Description / Product')" />
                                    <input type="text" name="items[0][description]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Web Design & Consulting" required>
                                </div>
                                <div class="col-span-2">
                                    <x-input-label :value="__('Qty')" />
                                    <input type="number" name="items[0][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" value="1" min="1" required>
                                </div>
                                <div class="col-span-4">
                                    <x-input-label :value="__('Unit Price ($)')" />
                                    <input type="number" step="0.01" name="items[0][unit_price]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="100.00" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <button type="button" onclick="addItemRow()" class="text-sm text-indigo-600 font-semibold hover:text-indigo-900">+ Add Another Item</button>
                            <div class="w-1/3">
                                <x-input-label for="discount" :value="__('Discount Amount ($)')" />
                                <x-text-input id="discount" name="discount" type="number" step="0.01" class="mt-1 block w-full" :value="old('discount', 0)" min="0" />
                            </div>
                        </div>
                    </div>

                    <!-- Template for additional item row -->
                    <template id="item-row-template">
                        <div class="grid grid-cols-12 gap-3 items-center item-row">
                            <div class="col-span-6">
                                <input type="text" class="item-desc mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Service or Item" required>
                            </div>
                            <div class="col-span-2">
                                <input type="number" class="item-qty mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" value="1" min="1" required>
                            </div>
                            <div class="col-span-4 flex items-center space-x-2">
                                <input type="number" step="0.01" class="item-price mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="0.00" min="0" required>
                                <button type="button" onclick="this.closest('.item-row').remove()" class="text-red-500 font-bold hover:text-red-700">&times;</button>
                            </div>
                        </div>
                    </template>

                    <div class="border-t border-gray-200 pt-6">
                        <x-input-label for="notes" :value="__('Notes / Terms & Conditions')" />
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Thank you for your business. Payment due within 30 days.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <a href="{{ route('invoices.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">Cancel</a>
                        <x-primary-button>{{ __('Create Invoice') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = 1;
        function addItemRow() {
            const template = document.getElementById('item-row-template');
            const clone = template.content.cloneNode(true);
            const desc = clone.querySelector('.item-desc');
            const qty = clone.querySelector('.item-qty');
            const price = clone.querySelector('.item-price');

            desc.name = `items[${itemIndex}][description]`;
            qty.name = `items[${itemIndex}][quantity]`;
            price.name = `items[${itemIndex}][unit_price]`;

            document.getElementById('items-container').appendChild(clone);
            itemIndex++;
        }
    </script>
</x-app-layout>
