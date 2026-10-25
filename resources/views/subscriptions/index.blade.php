<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription & Billing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Current Plan Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Current Plan: <span class="text-indigo-600">{{ $subscription->plan_name }}</span></h3>
                    <p class="text-sm text-gray-500">Status: <span class="capitalize font-semibold text-green-600">{{ $subscription->status }}</span> | Renews / Renovation date: {{ $subscription->current_period_end ? $subscription->current_period_end->format('M d, Y') : 'N/A' }}</p>
                </div>
                @if ($subscription->status === 'active' && $subscription->plan_name !== 'Free')
                    <form action="{{ route('subscriptions.cancel') }}" method="POST" onsubmit="return confirm('Cancel subscription?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 hover:bg-red-50 text-sm font-semibold rounded-md">Cancel Plan</button>
                    </form>
                @endif
            </div>

            <!-- Available Plans -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach ($plans as $name => $plan)
                    <div class="bg-white rounded-lg shadow-sm border {{ $subscription->plan_name === $name ? 'border-indigo-600 ring-2 ring-indigo-600' : 'border-gray-200' }} p-6 flex flex-col justify-between">
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">{{ $name }}</h4>
                            <div class="mt-4 flex items-baseline text-gray-900">
                                <span class="text-4xl font-extrabold tracking-tight">${{ $plan['price'] }}</span>
                                <span class="ms-1 text-sm font-semibold text-gray-500">/month</span>
                            </div>

                            <ul class="mt-6 space-y-3 text-sm text-gray-600">
                                @foreach ($plan['features'] as $f)
                                    <li class="flex items-center">
                                        <svg class="h-4 w-4 text-green-500 mr-2 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $f }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-8">
                            @if ($subscription->plan_name === $name && $subscription->status === 'active')
                                <button disabled class="w-full py-2 bg-gray-100 text-gray-400 font-semibold rounded-md text-sm cursor-not-allowed">Active Plan</button>
                            @else
                                <form action="{{ route('subscriptions.update') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_name" value="{{ $name }}">
                                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md text-sm">
                                        {{ $plan['price'] == 0 ? 'Select Free' : 'Upgrade' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
