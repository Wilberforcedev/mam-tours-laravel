<template>
  <div class="kyc-submission">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Processing your KYC submission...</p>
    </div>

    <div v-else-if="kycStatus && kycStatus !== 'not_submitted'" class="kyc-status-card">
      <div class="status-header" :class="`status-${kycStatus}`">
        <svg v-if="kycStatus === 'verified'" class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else-if="kycStatus === 'rejected'" class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3>{{ getStatusTitle() }}</h3>
      </div>

      <div class="status-body">
        <p class="status-message">{{ getStatusMessage() }}</p>
        
        <div v-if="kycData.rejection_reason" class="rejection-reason">
          <strong>Reason:</strong> {{ kycData.rejection_reason }}
        </div>

        <div v-if="kycData.additional_info_message" class="additional-info">
          <strong>Additional Information Required:</strong>
          <p>{{ kycData.additional_info_message }}</p>
        </div>

        <div v-if="kycData.risk_score !== null" class="risk-score">
          <strong>Risk Score:</strong>
          <div class="risk-bar">
            <div class="risk-fill" :style="{ width: `${kycData.risk_score}%` }" :class="getRiskClass()"></div>
          </div>
          <span class="risk-value">{{ kycData.risk_score }}/100</span>
        </div>

        <button v-if="canResubmit()" @click="showForm = true" class="btn btn-primary">
          Resubmit Documents
        </button>
      </div>
    </div>

    <form v-else @submit.prevent="submitKyc" class="kyc-form">
      <h2>KYC Verification</h2>
      <p class="form-description">Please provide your identification documents for verification.</p>

      <div class="form-group">
        <label for="id_type">ID Type *</label>
        <select v-model="form.id_type" id="id_type" required class="form-control">
          <option value="">Select ID Type</option>
          <option value="nin">National ID (NIN)</option>
          <option value="passport">Passport</option>
        </select>
      </div>

      <div class="form-group">
        <label for="id_number">ID Number *</label>
        <input 
          v-model="form.id_number" 
          type="text" 
          id="id_number" 
          required 
          class="form-control"
          placeholder="Enter your ID number"
        >
      </div>

      <div class="form-group">
        <label for="permit_number">Driving Permit Number *</label>
        <input 
          v-model="form.permit_number" 
          type="text" 
          id="permit_number" 
          required 
          class="form-control"
          placeholder="Enter your permit number"
        >
      </div>

      <div class="form-group">
        <label for="id_document">ID Document * (PDF, JPG, PNG - Max 5MB)</label>
        <input 
          @change="handleFileChange($event, 'id_document')" 
          type="file" 
          id="id_document" 
          required 
          accept=".pdf,.jpg,.jpeg,.png"
          class="form-control"
        >
        <small v-if="form.id_document">Selected: {{ form.id_document.name }}</small>
      </div>

      <div class="form-group">
        <label for="permit_document">Driving Permit Document * (PDF, JPG, PNG - Max 5MB)</label>
        <input 
          @change="handleFileChange($event, 'permit_document')" 
          type="file" 
          id="permit_document" 
          required 
          accept=".pdf,.jpg,.jpeg,.png"
          class="form-control"
        >
        <small v-if="form.permit_document">Selected: {{ form.permit_document.name }}</small>
      </div>

      <div class="form-group">
        <label for="id_original_document">ID Original Document (Optional)</label>
        <input 
          @change="handleFileChange($event, 'id_original_document')" 
          type="file" 
          id="id_original_document" 
          accept=".pdf,.jpg,.jpeg,.png"
          class="form-control"
        >
        <small v-if="form.id_original_document">Selected: {{ form.id_original_document.name }}</small>
      </div>

      <div class="form-group">
        <label for="permit_original_document">Permit Original Document (Optional)</label>
        <input 
          @change="handleFileChange($event, 'permit_original_document')" 
          type="file" 
          id="permit_original_document" 
          accept=".pdf,.jpg,.jpeg,.png"
          class="form-control"
        >
        <small v-if="form.permit_original_document">Selected: {{ form.permit_original_document.name }}</small>
      </div>

      <div v-if="error" class="alert alert-error">
        {{ error }}
      </div>

      <button type="submit" :disabled="submitting" class="btn btn-primary btn-lg">
        <span v-if="submitting">Submitting...</span>
        <span v-else>Submit for Verification</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const loading = ref(true);
const submitting = ref(false);
const showForm = ref(false);
const error = ref<string | null>(null);
const kycStatus = ref<string | null>(null);
const kycData = ref<any>({});

const form = ref({
  id_type: '',
  id_number: '',
  permit_number: '',
  id_document: null as File | null,
  permit_document: null as File | null,
  id_original_document: null as File | null,
  permit_original_document: null as File | null,
});

onMounted(async () => {
  await fetchKycStatus();
});

const fetchKycStatus = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v2/kyc/status', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      },
    });

    const data = await response.json();
    if (data.data.status !== 'not_submitted') {
      kycStatus.value = data.data.status;
      kycData.value = data.data;
    }
  } catch (err) {
    console.error('Failed to fetch KYC status:', err);
  } finally {
    loading.value = false;
  }
};

const handleFileChange = (event: Event, field: string) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    (form.value as any)[field] = target.files[0];
  }
};

const submitKyc = async () => {
  error.value = null;
  submitting.value = true;

  try {
    const formData = new FormData();
    formData.append('id_type', form.value.id_type);
    formData.append('id_number', form.value.id_number);
    formData.append('permit_number', form.value.permit_number);
    
    if (form.value.id_document) formData.append('id_document', form.value.id_document);
    if (form.value.permit_document) formData.append('permit_document', form.value.permit_document);
    if (form.value.id_original_document) formData.append('id_original_document', form.value.id_original_document);
    if (form.value.permit_original_document) formData.append('permit_original_document', form.value.permit_original_document);

    const response = await fetch('/api/v2/kyc/submit', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      },
      body: formData,
    });

    const data = await response.json();

    if (response.ok) {
      await fetchKycStatus();
      showForm.value = false;
    } else {
      error.value = data.error?.message || 'Failed to submit KYC documents';
    }
  } catch (err) {
    error.value = 'Network error. Please try again.';
  } finally {
    submitting.value = false;
  }
};

const getStatusTitle = (): string => {
  return {
    'pending': 'Verification Pending',
    'verified': 'Verified',
    'rejected': 'Verification Rejected',
    'additional_info_required': 'Additional Information Required',
    'processing_failed': 'Processing Failed',
    'auto_verified': 'Manual Review Required',
  }[kycStatus.value || ''] || 'Status Unknown';
};

const getStatusMessage = (): string => {
  return {
    'pending': 'Your documents are being reviewed. This usually takes 24-48 hours.',
    'verified': 'Your identity has been verified. You can now make bookings.',
    'rejected': 'Your verification was rejected. Please review the reason and resubmit.',
    'additional_info_required': 'We need more information to complete your verification.',
    'processing_failed': 'There was an error processing your documents. Please resubmit.',
    'auto_verified': 'This previous automated result requires manual review before your identity is considered verified.',
  }[kycStatus.value || ''] || '';
};

const canResubmit = (): boolean => {
  return ['rejected', 'additional_info_required', 'processing_failed'].includes(kycStatus.value || '');
};

const getRiskClass = (): string => {
  const score = kycData.value.risk_score || 0;
  if (score < 20) return 'risk-low';
  if (score < 50) return 'risk-medium';
  return 'risk-high';
};
</script>

<style scoped>
.kyc-submission {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.loading-state {
  text-align: center;
  padding: 40px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.kyc-status-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}

.status-header {
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.status-header.status-verified {
  background: #d4edda;
  color: #155724;
}

.status-header.status-rejected {
  background: #f8d7da;
  color: #721c24;
}

.status-header.status-pending {
  background: #fff3cd;
  color: #856404;
}

.status-icon {
  width: 32px;
  height: 32px;
}

.status-body {
  padding: 20px;
}

.status-message {
  margin-bottom: 16px;
  color: #666;
}

.rejection-reason,
.additional-info {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 16px;
}

.risk-score {
  margin: 16px 0;
}

.risk-bar {
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  margin: 8px 0;
}

.risk-fill {
  height: 100%;
  transition: width 0.3s;
}

.risk-low { background: #4caf50; }
.risk-medium { background: #ff9800; }
.risk-high { background: #f44336; }

.kyc-form {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-description {
  color: #666;
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-control:focus {
  outline: none;
  border-color: #3498db;
}

.form-group small {
  display: block;
  margin-top: 4px;
  color: #666;
  font-size: 12px;
}

.alert {
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 16px;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.2s;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2980b9;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-lg {
  width: 100%;
  padding: 14px;
  font-size: 18px;
}
</style>
