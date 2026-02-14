// API Base URL
const API_BASE = '/api';

// DOM Elements
const bookingForm = document.getElementById('bookingForm');
const carSelect = document.getElementById('carSelect');
const selectedCarIdInput = document.getElementById('selectedCarId');
const carInfo = document.getElementById('carInfo');
const submitBtn = document.getElementById('submitBtn');
const submitText = document.getElementById('submitText');
const submitLoader = document.getElementById('submitLoader');
const toast = document.getElementById('toast');
const toastTitle = document.getElementById('toastTitle');
const toastMessage = document.getElementById('toastMessage');
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');

// State
let allCars = [];
let selectedCar = null;
let selectedCategory = 'all';
let categories = [];
const isBookingPage = !!bookingForm;
const isHome = !isBookingPage;

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

window.getCarImage = getCarImage;

// Enhanced vehicle rendering
function renderVehicleCard(vehicle) {
    const availability = vehicle.isAvailable ? 'Available' : 'Unavailable';
    const badgeClass = vehicle.isAvailable ? '' : 'unavailable';
    const buttonDisabled = !vehicle.isAvailable ? 'disabled' : '';
    
    // Use carPicture if available, otherwise fall back to getCarImage
    const carImage = vehicle.carPicture 
        ? (vehicle.carPicture.startsWith('images/') ? vehicle.carPicture : `/storage/${vehicle.carPicture}`)
        : getCarImage(vehicle.brand, vehicle.model, vehicle.category);
    
    return `
        <div class="vehicle-card" data-vehicle-id="${vehicle.id}">
            <div class="vehicle-image-container">
                <img src="${carImage}" 
                     alt="${vehicle.brand} ${vehicle.model}" 
                     class="vehicle-image"
                     onerror="this.src='images/car logo.png'">
                <span class="vehicle-badge ${badgeClass}">${availability}</span>
            </div>
            
            <div class="vehicle-info">
                <h3 class="vehicle-title">
                    ${vehicle.brand} ${vehicle.model}
                </h3>
                
                <span class="vehicle-category">${vehicle.category || 'Other'}</span>
                
                <div class="vehicle-specs">
                    <div class="vehicle-spec">
                        <span class="vehicle-spec-icon">👥</span>
                        <span>${vehicle.seats} Seats</span>
                    </div>
                    <div class="vehicle-spec">
                        <span class="vehicle-spec-icon">🏷️</span>
                        <span>${vehicle.numberPlate}</span>
                    </div>
                </div>
                
                <div class="vehicle-details">
                    <div class="vehicle-detail">
                        <span class="vehicle-detail-label">Daily Rate</span>
                        <span class="vehicle-price">UGX ${vehicle.dailyRate.toLocaleString()}</span>
                    </div>
                    <div class="vehicle-detail">
                        <span class="vehicle-detail-label">Seats</span>
                        <span class="vehicle-detail-value">${vehicle.seats}</span>
                    </div>
                </div>
                
                <div class="vehicle-actions">
                    <button class="select-vehicle-btn" 
                            onclick="selectVehicle(${vehicle.id}, '${vehicle.brand} ${vehicle.model}')"
                            ${buttonDisabled}>
                        ${vehicle.isAvailable ? 'Select Vehicle' : 'Not Available'}
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Render vehicles
function renderVehicles(vehicles = allCars) {
    const container = document.getElementById('vehiclesByCategory') || document.getElementById('vehiclesGrid');
    if (!container) return;
    
    if (vehicles.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🚗</div>
                <h3 class="empty-state-title">No Vehicles Found</h3>
                <p class="empty-state-text">Try adjusting your filters or search terms</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = vehicles.map(car => renderVehicleCard(car)).join('');
}

// Filter and search functionality
function filterVehicles() {
    const searchTerm = document.getElementById('vehicleSearch')?.value.toLowerCase() || '';
    const selectedCategory = document.querySelector('.category-filter.active')?.dataset.category || 'all';
    
    const filtered = allCars.filter(car => {
        const matchesSearch = car.brand.toLowerCase().includes(searchTerm) || 
                            car.model.toLowerCase().includes(searchTerm);
        const matchesCategory = selectedCategory === 'all' || 
                              (car.category && car.category.toLowerCase() === selectedCategory);
        return matchesSearch && matchesCategory;
    });
    
    renderVehicles(filtered);
}

// Sort functionality
function sortVehicles() {
    const sortBy = document.getElementById('vehicleSort')?.value;
    let sorted = [...allCars];
    
    switch(sortBy) {
        case 'price-low':
            sorted.sort((a, b) => a.dailyRate - b.dailyRate);
            break;
        case 'price-high':
            sorted.sort((a, b) => b.dailyRate - a.dailyRate);
            break;
        case 'seats':
            sorted.sort((a, b) => b.seats - a.seats);
            break;
        default:
            sorted.sort((a, b) => a.brand.localeCompare(b.brand));
    }
    
    renderVehicles(sorted);
}

// Select vehicle
function selectVehicle(carId, carName) {
    selectedCar = allCars.find(c => c.id === carId);
    if (!selectedCar) return;
    
    selectedCarIdInput.value = carId;
    carSelect.value = carName;
    carInfo.textContent = `${carName} selected - UGX ${selectedCar.dailyRate.toLocaleString()}/day`;
    
    // Scroll to booking form
    if (bookingForm) {
        bookingForm.scrollIntoView({ behavior: 'smooth' });
    }
}

// Load cars
async function loadCars() {
    try {
        const response = await fetch(`${API_BASE}/cars`);
        allCars = await response.json();
        
        // Extract categories
        categories = [...new Set(allCars.map(c => c.category).filter(Boolean))];
        
        renderVehicles();
        setupCategoryFilters();
    } catch (error) {
        console.error('Error loading cars:', error);
        showToast('Error', 'Failed to load vehicles.');
    }
}

// Setup category filters
function setupCategoryFilters() {
    const filterContainer = document.getElementById('categoryFilters');
    if (!filterContainer) return;
    
    let html = '<button class="category-filter active" data-category="all">All Vehicles</button>';
    
    categories.forEach(cat => {
        html += `<button class="category-filter" data-category="${cat.toLowerCase()}">${cat}</button>`;
    });
    
    filterContainer.innerHTML = html;
    
    // Add event listeners
    document.querySelectorAll('.category-filter').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.category-filter').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            filterVehicles();
        });
    });
}

// Show toast
function showToast(title, message) {
    if (!toast) return;
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadCars();
    
    // Setup search
    document.getElementById('vehicleSearch')?.addEventListener('input', filterVehicles);
    
    // Setup sort
    document.getElementById('vehicleSort')?.addEventListener('change', sortVehicles);
    
    // Setup booking form
    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!selectedCar) {
                showToast('Error', 'Please select a vehicle');
                return;
            }
            
            const formData = {
                carId: selectedCar.id,
                customerName: document.getElementById('customerName').value,
                startDate: startDateInput.value,
                endDate: endDateInput.value
            };
            
            try {
                const response = await fetch(`${API_BASE}/bookings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                if (response.ok) {
                    showToast('Success', 'Booking created successfully!');
                    bookingForm.reset();
                    selectedCar = null;
                    selectedCarIdInput.value = '';
                    carSelect.value = '';
                    carInfo.textContent = 'Click on a vehicle above to select it';
                } else {
                    const error = await response.json();
                    showToast('Error', error.error || 'Failed to create booking');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error', 'Failed to create booking');
            }
        });
    }
});
