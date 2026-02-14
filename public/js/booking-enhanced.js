// Booking Enhanced Script
const API_BASE = '/api';

// DOM Elements
const bookingForm = document.getElementById('bookingForm');
const carSelect = document.getElementById('carSelect');
const selectedCarIdInput = document.getElementById('selectedCarId');
const carInfo = document.getElementById('carInfo');
const submitBtn = document.getElementById('submitBooking');
const toast = document.getElementById('toast');
const toastTitle = document.getElementById('toastTitle');
const toastMessage = document.getElementById('toastMessage');
const vehicleSearch = document.getElementById('vehicleSearch');
const vehicleSort = document.getElementById('vehicleSort');
const categoryFilters = document.getElementById('categoryFilters');
const vehiclesByCategory = document.getElementById('vehiclesByCategory');
const quickBookingForm = document.getElementById('quickBookingForm');

// State
let allCars = [];
let selectedCar = null;
let selectedCategory = 'all';
let categories = [];

// Get car image
function getCarImage(brand, model, category) {
    const m = (model || '').toLowerCase().trim();
    const b = (brand || '').toLowerCase().trim();
    const c = (category || '').toLowerCase().trim();
    const map = {
        'noah': 'images/Toyota Noah.jpg',
        'alphard': 'images/Alphard.jpeg',
        'rav4': 'images/Rav 4.jpeg',
        'rav 4': 'images/Rav 4.jpeg',
        'land cruiser': 'images/Land cruiser.jpg',
        'landcruiser': 'images/Land cruiser.jpg',
        'hilux': 'images/Hilux.jpg',
        'hilux surf': 'images/Hilux Surf.jpg',
        'harrier': 'images/Harrier.jpg',
        'kluger': 'images/Kruger.jpg',
        'vanguard': 'images/Vangurad Toyota.jpg',
        'auris': 'images/Auris.jpg',
        'avensis': 'images/Toyota Avensis.jpg',
        'allex': 'images/Toyota Allex.jpg',
        'fielder': 'images/Toyota Fielder.jpg',
        'fortuner': 'images/Toyota Fortuner.jpg',
        'hiace': 'images/Toyota Hiace.jpg',
        'isis': 'images/Toyota Isis.jpg',
        'rumion': 'images/Rumion.jpg',
        'spacio': 'images/Spacio.jpg',
        'runx': 'images/Toyota Runx.jpg',
        'wrangler': 'images/jeep wrangler.jpg',
        'grand cherokee': 'images/Jeep Grand Cherokee.jpg',
        's500': 'images/s class.jpeg',
        's class': 'images/s class.jpeg',
        's-class': 'images/s class.jpeg'
    };
    if (map[m]) return map[m];
    if (m === 'xf' && b.includes('jaguar')) return 'images/Jaguar xf 2015.jpg';
    if (b.includes('jeep')) {
        if (m.includes('wrangler')) return 'images/jeep wrangler.jpg';
        if (m.includes('grand cherokee')) return 'images/Jeep Grand Cherokee.jpg';
    }
    if (c === 'suv') return 'images/Rav 4.jpeg';
    if (c === 'minivan') return 'images/Toyota Noah.jpg';
    if (c === 'pickup') return 'images/Toyota.jpeg';
    if (c === 'luxury') return 'images/s class.jpeg';
    if (c === 'sedan' || c === 'hatchback') return 'images/Sedan car.jpg';
    if (b.includes('toyota')) return 'images/Toyota.jpeg';
    return 'images/car logo.png';
}

// Show toast notification
function showToast(title, message, type = 'success') {
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.className = 'toast show ' + type;
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// Load cars from API
async function loadCars() {
    console.log('Loading cars from API...');
    try {
        const response = await fetch(`${API_BASE}/cars`);
        console.log('API response status:', response.status);
        if (!response.ok) throw new Error('Failed to load cars');
        
        allCars = await response.json();
        console.log('Loaded cars:', allCars.length, 'vehicles');
        
        if (!allCars || allCars.length === 0) {
            vehiclesByCategory.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999; padding: 2rem;">No vehicles available at the moment. Please check back later.</p>';
            return;
        }
        
        // Extract categories
        categories = [...new Set(allCars.map(car => car.category || 'Other'))];
        console.log('Categories found:', categories);
        
        // Render category filters
        renderCategoryFilters();
        
        // Render vehicles
        renderVehicles(allCars);
        console.log('Vehicles rendered successfully');
    } catch (error) {
        console.error('Error loading cars:', error);
        vehiclesByCategory.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #e74c3c; padding: 2rem;">Error loading vehicles. Please refresh the page.</p>';
    }
}

// Render category filters
function renderCategoryFilters() {
    if (!categoryFilters) return; // Skip if element doesn't exist
    
    categoryFilters.innerHTML = '';
    
    // Add "All" filter
    const allBtn = document.createElement('button');
    allBtn.type = 'button';
    allBtn.className = 'category-filter active';
    allBtn.textContent = 'All';
    allBtn.onclick = (e) => {
        e.preventDefault();
        filterByCategory('all', allBtn);
    };
    categoryFilters.appendChild(allBtn);
    
    // Add category filters
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'category-filter';
        btn.textContent = cat;
        btn.onclick = (e) => {
            e.preventDefault();
            filterByCategory(cat, btn);
        };
        categoryFilters.appendChild(btn);
    });
}

// Filter by category
function filterByCategory(category, buttonElement) {
    selectedCategory = category;
    
    // Update active button
    document.querySelectorAll('.category-filter').forEach(btn => {
        btn.classList.remove('active');
    });
    buttonElement.classList.add('active');
    
    // Filter and render
    const filtered = category === 'all' ? allCars : allCars.filter(car => car.category === category);
    renderVehicles(filtered);
}

// Render vehicles
function renderVehicles(cars) {
    if (!cars || cars.length === 0) {
        vehiclesByCategory.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999; padding: 2rem;">No vehicles found in this category.</p>';
        return;
    }
    
    vehiclesByCategory.innerHTML = cars.map(car => {
        // Use carPicture if available, otherwise fall back to getCarImage
        const carImage = car.carPicture 
            ? (car.carPicture.startsWith('images/') ? car.carPicture : `/storage/${car.carPicture}`)
            : getCarImage(car.brand, car.model, car.category);
        
        return `
        <div class="vehicle-card" data-vehicle-id="${car.id}">
            <div class="vehicle-image-container">
                <img src="${carImage}" 
                     alt="${car.brand} ${car.model}" 
                     class="vehicle-image"
                     onerror="this.src='images/car logo.png'">
                <span class="vehicle-badge ${car.isAvailable ? '' : 'unavailable'}">
                    ${car.isAvailable ? 'Available' : 'Unavailable'}
                </span>
            </div>
            
            <div class="vehicle-info">
                <h3 class="vehicle-title">${car.brand} ${car.model}</h3>
                <span class="vehicle-category">${car.category || 'Other'}</span>
                
                <div class="vehicle-details">
                    <div class="vehicle-detail">
                        <span class="vehicle-detail-label">Seats</span>
                        <span class="vehicle-detail-value">${car.seats || 'N/A'}</span>
                    </div>
                    <div class="vehicle-detail">
                        <span class="vehicle-detail-label">Daily Rate</span>
                        <span class="vehicle-detail-value">UGX ${car.dailyRate ? car.dailyRate.toLocaleString() : 'N/A'}</span>
                    </div>
                </div>
                
                <div class="vehicle-specs">
                    <div class="vehicle-spec">
                        <span class="vehicle-spec-icon">✓</span>
                        <span>Air Conditioned</span>
                    </div>
                    <div class="vehicle-spec">
                        <span class="vehicle-spec-icon">✓</span>
                        <span>Full Insurance</span>
                    </div>
                    <div class="vehicle-spec">
                        <span class="vehicle-spec-icon">✓</span>
                        <span>Free Delivery</span>
                    </div>
                </div>
                
                <div class="vehicle-actions">
                    <button type="button" class="select-vehicle-btn" ${car.isAvailable ? '' : 'disabled'} onclick="selectVehicle(${car.id}, '${car.brand} ${car.model}', ${car.dailyRate})">
                        ${car.isAvailable ? 'Select Vehicle' : 'Not Available'}
                    </button>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

// Select vehicle
function selectVehicle(carId, carName, dailyRate) {
    selectedCar = { id: carId, name: carName, dailyRate: dailyRate };
    carSelect.value = carName;
    selectedCarIdInput.value = carId;
    carInfo.textContent = `${carName} selected - UGX ${dailyRate.toLocaleString()}/day`;
    
    // Scroll to booking form
    document.querySelector('.booking-section').scrollIntoView({ behavior: 'smooth' });
}

// Handle search
if (vehicleSearch) {
    vehicleSearch.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const filtered = allCars.filter(car => 
            car.brand.toLowerCase().includes(searchTerm) || 
            car.model.toLowerCase().includes(searchTerm)
        );
        renderVehicles(filtered);
    });
}

// Handle sort
if (vehicleSort) {
    vehicleSort.addEventListener('change', (e) => {
        const sortType = e.target.value;
        let sorted = [...allCars];
        
        switch(sortType) {
            case 'price-low':
                sorted.sort((a, b) => a.dailyRate - b.dailyRate);
                break;
            case 'price-high':
                sorted.sort((a, b) => b.dailyRate - a.dailyRate);
                break;
            case 'seats':
                sorted.sort((a, b) => (b.seats || 0) - (a.seats || 0));
                break;
            default:
                sorted.sort((a, b) => a.brand.localeCompare(b.brand));
        }
        
        renderVehicles(sorted);
    });
}

// Handle booking form submission
if (bookingForm) {
    bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!selectedCar) {
            showToast('Error', 'Please select a vehicle first', 'error');
            return;
        }
        
        // Check if user is authenticated and has verified KYC
        try {
            const userResponse = await fetch('/api/user', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            if (userResponse.ok) {
                const user = await userResponse.json();
                if (user && !user.kyc_verified) {
                    showToast('KYC Required', 'Please complete your KYC verification before booking. Redirecting to KYC page...', 'error');
                    setTimeout(() => {
                        window.location.href = '/kyc';
                    }, 2000);
                    return;
                }
            }
        } catch (error) {
            console.log('Could not verify KYC status, proceeding with booking');
        }
        
        const formData = {
            carId: selectedCar.id,
            customerName: document.getElementById('customerName').value,
            startDate: document.getElementById('startDate').value,
            endDate: document.getElementById('endDate').value,
            payment_method: document.getElementById('paymentMethod').value,
            mobile_money_number: document.getElementById('mobileMoneyNumber')?.value || null,
            phone_number: document.getElementById('phoneNumber')?.value || null,
            id_type: document.getElementById('idType').value,
            id_number: document.getElementById('idNumber').value,
            permit_number: document.getElementById('permitNumber').value
        };
        
        try {
            submitBtn.disabled = true;
            console.log('Submitting booking with data:', formData);
            
            const response = await fetch(`${API_BASE}/bookings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(formData)
            });
            
            console.log('Response status:', response.status, response.statusText);
            
            let data;
            const responseText = await response.text();
            
            try {
                data = responseText ? JSON.parse(responseText) : {};
                console.log('Response data:', data);
            } catch (e) {
                console.error('Failed to parse response as JSON:', response.status, response.statusText);
                console.error('Response text:', responseText);
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            }
            
            if (!response.ok) {
                if (response.status === 403 && data.error === 'KYC verification required') {
                    showToast('KYC Required', data.message || 'Please complete your KYC verification before booking.', 'error');
                    setTimeout(() => {
                        window.location.href = '/kyc';
                    }, 2000);
                    return;
                }
                throw new Error(data.error || data.message || 'Booking failed');
            }
            
            showToast('Success', 'Booking created successfully! Our team will contact you soon.', 'success');
            bookingForm.reset();
            carSelect.value = '';
            selectedCarIdInput.value = '';
            carInfo.textContent = 'Click on a vehicle above to select it';
            selectedCar = null;
        } catch (error) {
            console.error('Booking error:', error);
            showToast('Error', error.message || 'Failed to create booking. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });
}

// Handle quick booking form
if (quickBookingForm) {
    quickBookingForm.addEventListener('submit', (e) => {
        e.preventDefault();
        // Just scroll to vehicles section
        document.querySelector('.cars-section').scrollIntoView({ behavior: 'smooth' });
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    console.log('Booking page loaded, loading cars...');
    loadCars();
    setupPaymentMethodListener();
});

// Setup payment method listener
function setupPaymentMethodListener() {
    const paymentMethod = document.getElementById('paymentMethod');
    const mobileMoneyDetails = document.getElementById('mobileMoneyDetails');
    
    if (paymentMethod) {
        paymentMethod.addEventListener('change', (e) => {
            if (e.target.value === 'mtn_mobile_money' || e.target.value === 'airtel_money') {
                mobileMoneyDetails.style.display = 'block';
                document.getElementById('mobileMoneyNumber').required = true;
                
                // Update placeholder based on provider
                const placeholder = e.target.value === 'mtn_mobile_money' 
                    ? '+256 77X XXX XXX (MTN)' 
                    : '+256 70X XXX XXX (Airtel)';
                document.getElementById('mobileMoneyNumber').placeholder = placeholder;
            } else {
                mobileMoneyDetails.style.display = 'none';
                document.getElementById('mobileMoneyNumber').required = false;
            }
        });
    }
}

// Enhanced booking form handling
document.addEventListener('DOMContentLoaded', function() {
    // File upload handling
    setupFileUploads();
    
    // Form submission
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleBookingSubmission);
    }
    
    // Auto-fill dates
    setMinDates();
    
    // Calculate pricing when dates change
    document.getElementById('startDate')?.addEventListener('change', calculatePricing);
    document.getElementById('endDate')?.addEventListener('change', calculatePricing);
});

function setupFileUploads() {
    const fileInputs = ['idDocument', 'permitDocument'];
    
    fileInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        const area = document.getElementById(inputId.replace('Document', 'UploadArea'));
        
        if (input && area) {
            input.addEventListener('change', function(e) {
                handleFileUpload(e, area);
            });
        }
    });
}

function handleFileUpload(event, uploadArea) {
    const file = event.target.files[0];
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    
    if (file) {
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            event.target.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload JPG, PNG, or PDF files only');
            event.target.value = '';
            return;
        }
        
        // Update UI
        uploadArea.classList.add('has-file');
        
        // Create file preview
        const preview = document.createElement('div');
        preview.className = 'file-preview';
        preview.innerHTML = `
            <i class="fas fa-file-${file.type.includes('pdf') ? 'pdf' : 'image'}"></i>
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
            </div>
            <button type="button" class="remove-file" onclick="removeFile('${event.target.id}', this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Replace placeholder with preview
        placeholder.style.display = 'none';
        uploadArea.appendChild(preview);
    }
}

function removeFile(inputId, button) {
    const input = document.getElementById(inputId);
    const uploadArea = input.closest('.file-upload-area');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    const preview = button.closest('.file-preview');
    
    input.value = '';
    uploadArea.classList.remove('has-file');
    placeholder.style.display = 'block';
    preview.remove();
}

function setMinDates() {
    const today = new Date().toISOString().split('T')[0];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    if (startDate) {
        startDate.min = today;
        startDate.addEventListener('change', function() {
            if (endDate) {
                endDate.min = this.value;
                if (endDate.value && endDate.value < this.value) {
                    endDate.value = this.value;
                }
            }
        });
    }
}

function calculatePricing() {
    const startDate = document.getElementById('startDate')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const pricingSummary = document.getElementById('pricingSummary');
    const pricingDetails = document.getElementById('pricingDetails');
    
    if (startDate && endDate && selectedCar) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (days > 0) {
            const subtotal = days * selectedCar.dailyRate;
            const tax = subtotal * 0.18; // 18% VAT
            const total = subtotal + tax;
            
            pricingDetails.innerHTML = `
                <div class="pricing-row">
                    <span>Vehicle: ${selectedCar.name}</span>
                    <span>UGX ${selectedCar.dailyRate.toLocaleString()}/day</span>
                </div>
                <div class="pricing-row">
                    <span>Duration: ${days} day${days > 1 ? 's' : ''}</span>
                    <span>UGX ${subtotal.toLocaleString()}</span>
                </div>
                <div class="pricing-row">
                    <span>VAT (18%)</span>
                    <span>UGX ${tax.toLocaleString()}</span>
                </div>
                <div class="pricing-row total">
                    <span><strong>Total Amount</strong></span>
                    <span><strong>UGX ${total.toLocaleString()}</strong></span>
                </div>
            `;
            pricingSummary.style.display = 'block';
        }
    }
}

async function handleBookingSubmission(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBooking');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    // Validate required files
    const idDocument = document.getElementById('idDocument').files[0];
    const permitDocument = document.getElementById('permitDocument').files[0];
    
    if (!idDocument) {
        alert('Please upload your ID document');
        return;
    }
    
    if (!permitDocument) {
        alert('Please upload your driving permit');
        return;
    }
    
    if (!selectedCar) {
        alert('Please select a vehicle');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    
    try {
        const formData = new FormData(e.target);
        formData.append('carId', selectedCar.id);
        formData.append('carBrand', selectedCar.name.split(' ')[0]);
        formData.append('carModel', selectedCar.name.split(' ').slice(1).join(' '));
        formData.append('dailyRate', selectedCar.dailyRate);
        
        const response = await fetch('/api/bookings', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showToast('Booking Successful!', 'Your booking has been submitted for verification. You will receive confirmation shortly.');
            e.target.reset();
            document.getElementById('pricingSummary').style.display = 'none';
            selectedCar = null;
            document.getElementById('carSelect').value = '';
            
            // Reset file uploads
            document.querySelectorAll('.file-upload-area').forEach(area => {
                area.classList.remove('has-file');
                const preview = area.querySelector('.file-preview');
                if (preview) preview.remove();
                area.querySelector('.upload-placeholder').style.display = 'block';
            });
            
        } else {
            showToast('Booking Failed', result.message || 'Please try again');
        }
    } catch (error) {
        console.error('Booking error:', error);
        showToast('Error', 'Something went wrong. Please try again.');
    } finally {
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

// Make removeFile function global
window.removeFile = removeFile;