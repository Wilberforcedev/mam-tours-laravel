@extends('layouts.app')

@section('title', 'Complete Payment')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Complete Payment</h1>
            
            <!-- Booking Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-3">Booking Summary</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Booking ID:</span>
                        <span class="font-medium">#{{ $booking->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vehicle:</span>
                        <span class="font-medium">{{ $booking->car->brand }} {{ $booking->car->model }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Rental Period:</span>
                        <span class="font-medium">
                            {{ $booking->startDate->format('M j, Y') }} - {{ $booking->endDate->format('M j, Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Duration:</span>
                        <span class="font-medium">{{ $booking->startDate->diffInDays($booking->endDate) }} days</span>
                    </div>
                    <hr class="my-3">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total Amount:</span>
                        <span class="text-blue-600">UGX {{ number_format($booking->pricing['total'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-700">Choose Payment Method</h2>
                
                <!-- Stripe Payment -->
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <input type="radio" id="stripe" name="payment_method" value="stripe" class="mr-3" checked>
                        <label for="stripe" class="text-lg font-medium">Credit/Debit Card</label>
                    </div>
                    <div id="stripe-payment" class="payment-method-content">
                        <div id="card-element" class="p-3 border rounded bg-gray-50">
                            <!-- Stripe Elements will create form elements here -->
                        </div>
                        <div id="card-errors" role="alert" class="text-red-600 text-sm mt-2"></div>
                        <button id="stripe-submit" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors mt-4">
                            <span id="stripe-button-text">Pay UGX {{ number_format($booking->pricing['total'] ?? 0) }}</span>
                            <div id="stripe-spinner" class="hidden inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin ml-2"></div>
                        </button>
                    </div>
                </div>

                <!-- Mobile Money Payment -->
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <input type="radio" id="mobile_money" name="payment_method" value="mobile_money" class="mr-3">
                        <label for="mobile_money" class="text-lg font-medium">Mobile Money</label>
                    </div>
                    <div id="mobile-money-payment" class="payment-method-content hidden">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                                <select id="mm-provider" class="w-full p-3 border rounded-lg">
                                    <option value="mtn">MTN Mobile Money</option>
                                    <option value="airtel">Airtel Money</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="mm-phone" placeholder="256XXXXXXXXX" class="w-full p-3 border rounded-lg">
                            </div>
                            <button id="mobile-money-submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors">
                                <span id="mm-button-text">Pay UGX {{ number_format($booking->pricing['total'] ?? 0) }}</span>
                                <div id="mm-spinner" class="hidden inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin ml-2"></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">Secure Payment</h3>
                        <p class="text-sm text-blue-700 mt-1">Your payment information is encrypted and secure. We never store your card details.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stripe JS -->
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Stripe
    const stripe = Stripe('{{ config("services.stripe.key") }}');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });
    
    cardElement.mount('#card-element');
    
    // Handle payment method selection
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const paymentContents = document.querySelectorAll('.payment-method-content');
    
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            paymentContents.forEach(content => content.classList.add('hidden'));
            
            if (this.value === 'stripe') {
                document.getElementById('stripe-payment').classList.remove('hidden');
            } else if (this.value === 'mobile_money') {
                document.getElementById('mobile-money-payment').classList.remove('hidden');
            }
        });
    });
    
    // Handle Stripe payment
    document.getElementById('stripe-submit').addEventListener('click', async function(e) {
        e.preventDefault();
        
        const button = this;
        const buttonText = document.getElementById('stripe-button-text');
        const spinner = document.getElementById('stripe-spinner');
        
        button.disabled = true;
        buttonText.textContent = 'Processing...';
        spinner.classList.remove('hidden');
        
        try {
            // Create payment intent
            const response = await fetch('{{ route("payments.stripe", $booking) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Payment failed');
            }
            
            // Confirm payment
            const {error} = await stripe.confirmCardPayment(data.client_secret, {
                payment_method: {
                    card: cardElement,
                }
            });
            
            if (error) {
                throw new Error(error.message);
            }
            
            // Payment succeeded
            window.location.href = '{{ route("bookings.show", $booking) }}';
            
        } catch (error) {
            document.getElementById('card-errors').textContent = error.message;
            button.disabled = false;
            buttonText.textContent = 'Pay UGX {{ number_format($booking->pricing["total"] ?? 0) }}';
            spinner.classList.add('hidden');
        }
    });
    
    // Handle Mobile Money payment
    document.getElementById('mobile-money-submit').addEventListener('click', async function(e) {
        e.preventDefault();
        
        const button = this;
        const buttonText = document.getElementById('mm-button-text');
        const spinner = document.getElementById('mm-spinner');
        const provider = document.getElementById('mm-provider').value;
        const phone = document.getElementById('mm-phone').value;
        
        if (!phone) {
            alert('Please enter your phone number');
            return;
        }
        
        button.disabled = true;
        buttonText.textContent = 'Processing...';
        spinner.classList.remove('hidden');
        
        try {
            const response = await fetch('{{ route("payments.mobile-money", $booking) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    phone_number: phone,
                    provider: provider,
                }),
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route("bookings.show", $booking) }}';
            } else {
                throw new Error(data.error || 'Payment failed');
            }
            
        } catch (error) {
            alert(error.message);
            button.disabled = false;
            buttonText.textContent = 'Pay UGX {{ number_format($booking->pricing["total"] ?? 0) }}';
            spinner.classList.add('hidden');
        }
    });
});
</script>
@endsection