<template>
  <div class="payment-processor">
    <div class="space-y-6">
      <!-- Payment Method Selection -->
      <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Choose Payment Method</h3>
        <div class="space-y-3">
          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
            <input 
              type="radio" 
              v-model="selectedMethod" 
              value="stripe" 
              class="mr-3"
            >
            <div class="flex-1">
              <div class="font-medium">Credit/Debit Card</div>
              <div class="text-sm text-gray-600">Visa, Mastercard, American Express</div>
            </div>
            <div class="text-right">
              <svg class="w-8 h-5" viewBox="0 0 32 20" fill="none">
                <!-- Credit card icons -->
                <rect width="32" height="20" rx="4" fill="#1a365d"/>
                <rect x="2" y="2" width="28" height="16" rx="2" fill="#2d3748"/>
              </svg>
            </div>
          </label>

          <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
            <input 
              type="radio" 
              v-model="selectedMethod" 
              value="mobile_money" 
              class="mr-3"
            >
            <div class="flex-1">
              <div class="font-medium">Mobile Money</div>
              <div class="text-sm text-gray-600">MTN Mobile Money, Airtel Money</div>
            </div>
            <div class="text-right">
              <span class="text-green-600 font-bold">MM</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Stripe Payment Form -->
      <div v-if="selectedMethod === 'stripe'" class="stripe-payment">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Card Information
            </label>
            <div id="card-element" class="p-3 border rounded-lg bg-gray-50">
              <!-- Stripe Elements will create form elements here -->
            </div>
            <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
          </div>
          
          <button 
            @click="processStripePayment"
            :disabled="processing"
            class="w-full btn btn-primary"
            :class="{ 'opacity-50 cursor-not-allowed': processing }"
          >
            <span v-if="processing">Processing Payment...</span>
            <span v-else>Pay UGX {{ formatCurrency(amount) }}</span>
          </button>
        </div>
      </div>

      <!-- Mobile Money Payment Form -->
      <div v-if="selectedMethod === 'mobile_money'" class="mobile-money-payment">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Mobile Money Provider
            </label>
            <select v-model="mobileMoneyForm.provider" class="form-select">
              <option value="mtn">MTN Mobile Money</option>
              <option value="airtel">Airtel Money</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Phone Number
            </label>
            <input 
              type="tel" 
              v-model="mobileMoneyForm.phone" 
              placeholder="256XXXXXXXXX"
              class="form-input"
              required
            >
          </div>
          
          <button 
            @click="processMobileMoneyPayment"
            :disabled="processing || !mobileMoneyForm.phone"
            class="w-full btn btn-success"
            :class="{ 'opacity-50 cursor-not-allowed': processing || !mobileMoneyForm.phone }"
          >
            <span v-if="processing">Initiating Payment...</span>
            <span v-else>Pay UGX {{ formatCurrency(amount) }}</span>
          </button>
        </div>
      </div>

      <!-- Payment Status -->
      <div v-if="paymentStatus" class="payment-status">
        <div 
          class="p-4 rounded-lg"
          :class="{
            'bg-green-100 text-green-800': paymentStatus.type === 'success',
            'bg-red-100 text-red-800': paymentStatus.type === 'error',
            'bg-yellow-100 text-yellow-800': paymentStatus.type === 'pending'
          }"
        >
          <div class="flex items-center">
            <svg v-if="paymentStatus.type === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <svg v-else-if="paymentStatus.type === 'error'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
              <div class="font-medium">{{ paymentStatus.title }}</div>
              <div class="text-sm">{{ paymentStatus.message }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PaymentProcessor',
  props: {
    bookingId: {
      type: [String, Number],
      required: true
    },
    amount: {
      type: Number,
      required: true
    },
    stripeKey: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      selectedMethod: 'stripe',
      processing: false,
      stripe: null,
      cardElement: null,
      mobileMoneyForm: {
        provider: 'mtn',
        phone: ''
      },
      paymentStatus: null
    }
  },
  async mounted() {
    await this.initializeStripe();
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-UG').format(amount);
    },
    
    async initializeStripe() {
      if (window.Stripe) {
        this.stripe = window.Stripe(this.stripeKey);
        const elements = this.stripe.elements();
        
        this.cardElement = elements.create('card', {
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
        
        this.cardElement.mount('#card-element');
        
        this.cardElement.on('change', ({error}) => {
          const displayError = document.getElementById('card-errors');
          if (error) {
            displayError.textContent = error.message;
          } else {
            displayError.textContent = '';
          }
        });
      }
    },
    
    async processStripePayment() {
      if (!this.stripe || !this.cardElement) {
        this.showPaymentStatus('error', 'Payment Error', 'Stripe not initialized properly');
        return;
      }
      
      this.processing = true;
      this.paymentStatus = null;
      
      try {
        // Create payment intent
        const response = await axios.post(`/api/payments/${this.bookingId}/stripe`, {});
        
        if (!response.data.success) {
          throw new Error(response.data.error || 'Failed to create payment');
        }
        
        // Confirm payment
        const {error} = await this.stripe.confirmCardPayment(response.data.client_secret, {
          payment_method: {
            card: this.cardElement,
          }
        });
        
        if (error) {
          throw new Error(error.message);
        }
        
        // Payment succeeded
        this.showPaymentStatus('success', 'Payment Successful', 'Your payment has been processed successfully!');
        
        // Redirect after delay
        setTimeout(() => {
          window.location.href = `/bookings/${this.bookingId}`;
        }, 2000);
        
      } catch (error) {
        this.showPaymentStatus('error', 'Payment Failed', error.message);
      } finally {
        this.processing = false;
      }
    },
    
    async processMobileMoneyPayment() {
      this.processing = true;
      this.paymentStatus = null;
      
      try {
        const response = await axios.post(`/api/payments/${this.bookingId}/mobile-money`, {
          phone_number: this.mobileMoneyForm.phone,
          provider: this.mobileMoneyForm.provider
        });
        
        if (response.data.success) {
          this.showPaymentStatus('pending', 'Payment Initiated', response.data.message);
          
          // Poll for payment status
          this.pollPaymentStatus();
        } else {
          throw new Error(response.data.error || 'Payment failed');
        }
        
      } catch (error) {
        this.showPaymentStatus('error', 'Payment Failed', error.response?.data?.error || error.message);
      } finally {
        this.processing = false;
      }
    },
    
    async pollPaymentStatus() {
      const maxAttempts = 30; // 5 minutes
      let attempts = 0;
      
      const poll = async () => {
        try {
          const response = await axios.get(`/api/bookings/${this.bookingId}/payment-status`);
          
          if (response.data.payment_status === 'completed') {
            this.showPaymentStatus('success', 'Payment Completed', 'Your mobile money payment has been confirmed!');
            setTimeout(() => {
              window.location.href = `/bookings/${this.bookingId}`;
            }, 2000);
            return;
          }
          
          attempts++;
          if (attempts < maxAttempts) {
            setTimeout(poll, 10000); // Poll every 10 seconds
          } else {
            this.showPaymentStatus('error', 'Payment Timeout', 'Payment verification timed out. Please contact support.');
          }
          
        } catch (error) {
          console.error('Payment status polling error:', error);
        }
      };
      
      setTimeout(poll, 10000); // Start polling after 10 seconds
    },
    
    showPaymentStatus(type, title, message) {
      this.paymentStatus = { type, title, message };
    }
  }
}
</script>