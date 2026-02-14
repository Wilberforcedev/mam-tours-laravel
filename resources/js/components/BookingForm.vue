<template>
  <div class="booking-form">
    <form @submit.prevent="submitBooking" class="space-y-6">
      <!-- Car Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Select Vehicle
        </label>
        <select v-model="form.car_id" class="form-select" required>
          <option value="">Choose a vehicle...</option>
          <option v-for="car in cars" :key="car.id" :value="car.id">
            {{ car.brand }} {{ car.model }} ({{ car.year }}) - UGX {{ formatCurrency(car.price_per_day) }}/day
          </option>
        </select>
      </div>

      <!-- Date Selection -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Start Date
          </label>
          <input 
            type="date" 
            v-model="form.start_date" 
            :min="minDate"
            class="form-input" 
            required
            @change="calculatePricing"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            End Date
          </label>
          <input 
            type="date" 
            v-model="form.end_date" 
            :min="form.start_date || minDate"
            class="form-input" 
            required
            @change="calculatePricing"
          >
        </div>
      </div>

      <!-- Add-ons -->
      <div v-if="addOns.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Additional Services
        </label>
        <div class="space-y-2">
          <div v-for="addOn in addOns" :key="addOn.id" class="flex items-center">
            <input 
              type="checkbox" 
              :id="`addon-${addOn.id}`"
              :value="addOn.id"
              v-model="form.add_ons"
              class="mr-3"
              @change="calculatePricing"
            >
            <label :for="`addon-${addOn.id}`" class="flex-1">
              {{ addOn.name }} - UGX {{ formatCurrency(addOn.price) }}
            </label>
          </div>
        </div>
      </div>

      <!-- Pricing Summary -->
      <div v-if="pricing.total > 0" class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Pricing Summary</h3>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span>Base Price ({{ pricing.days }} days)</span>
            <span>UGX {{ formatCurrency(pricing.basePrice) }}</span>
          </div>
          <div v-if="pricing.addOnsTotal > 0" class="flex justify-between">
            <span>Add-ons</span>
            <span>UGX {{ formatCurrency(pricing.addOnsTotal) }}</span>
          </div>
          <div v-if="pricing.tax > 0" class="flex justify-between">
            <span>Tax</span>
            <span>UGX {{ formatCurrency(pricing.tax) }}</span>
          </div>
          <hr class="my-2">
          <div class="flex justify-between text-lg font-bold">
            <span>Total</span>
            <span class="text-blue-600">UGX {{ formatCurrency(pricing.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        :disabled="!canSubmit || loading"
        class="w-full btn btn-primary"
        :class="{ 'opacity-50 cursor-not-allowed': !canSubmit || loading }"
      >
        <span v-if="loading">Processing...</span>
        <span v-else>Book Now - UGX {{ formatCurrency(pricing.total) }}</span>
      </button>
    </form>
  </div>
</template>

<script>
export default {
  name: 'BookingForm',
  props: {
    cars: {
      type: Array,
      default: () => []
    },
    addOns: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      form: {
        car_id: '',
        start_date: '',
        end_date: '',
        add_ons: []
      },
      pricing: {
        days: 0,
        basePrice: 0,
        addOnsTotal: 0,
        tax: 0,
        total: 0
      }
    }
  },
  computed: {
    minDate() {
      return new Date().toISOString().split('T')[0];
    },
    selectedCar() {
      return this.cars.find(car => car.id == this.form.car_id);
    },
    canSubmit() {
      return this.form.car_id && this.form.start_date && this.form.end_date && this.pricing.total > 0;
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-UG').format(amount);
    },
    calculatePricing() {
      if (!this.selectedCar || !this.form.start_date || !this.form.end_date) {
        this.pricing = { days: 0, basePrice: 0, addOnsTotal: 0, tax: 0, total: 0 };
        return;
      }

      const startDate = new Date(this.form.start_date);
      const endDate = new Date(this.form.end_date);
      const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

      if (days <= 0) {
        this.pricing = { days: 0, basePrice: 0, addOnsTotal: 0, tax: 0, total: 0 };
        return;
      }

      const basePrice = this.selectedCar.price_per_day * days;
      
      const addOnsTotal = this.form.add_ons.reduce((total, addOnId) => {
        const addOn = this.addOns.find(a => a.id == addOnId);
        return total + (addOn ? addOn.price : 0);
      }, 0);

      const subtotal = basePrice + addOnsTotal;
      const tax = subtotal * 0.18; // 18% VAT
      const total = subtotal + tax;

      this.pricing = {
        days,
        basePrice,
        addOnsTotal,
        tax,
        total
      };
    },
    async submitBooking() {
      if (!this.canSubmit) return;

      this.loading = true;
      
      try {
        const response = await axios.post('/api/bookings', {
          ...this.form,
          pricing: this.pricing
        });

        if (response.data.success) {
          window.utils.showNotification('Booking created successfully!', 'success');
          window.location.href = `/bookings/${response.data.booking.id}`;
        }
      } catch (error) {
        if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          Object.values(errors).flat().forEach(error => {
            window.utils.showNotification(error, 'error');
          });
        } else {
          window.utils.showNotification('Failed to create booking. Please try again.', 'error');
        }
      } finally {
        this.loading = false;
      }
    }
  },
  watch: {
    'form.car_id': 'calculatePricing',
    'form.start_date': 'calculatePricing',
    'form.end_date': 'calculatePricing'
  }
}
</script>