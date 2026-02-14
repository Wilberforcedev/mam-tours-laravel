// API Base URL
const API_BASE = '/api';

// State
let bookings = [];
let cars = [];
let allKyc = [];
let activeTab = 'bookings';
let activeInnerTab = 'pending';
let activeKycInnerTab = 'pending';
let currentPage = 1;
let currentVehiclePage = 1;
let currentKycPage = 1;
let currentKycId = null;
const itemsPerPage = 5;
const vehiclesPerPage = 12;

// DOM Elements
const addVehicleBtn = document.getElementById('addVehicleBtn');
const addVehicleModal = document.getElementById('addVehicleModal');
const closeModal = document.getElementById('closeModal');
const addVehicleForm = document.getElementById('addVehicleForm');
const pendingBookingsDiv = document.getElementById('pendingBookings');
const activeBookingsDiv = document.getElementById('activeBookings');
const completedBookingsDiv = document.getElementById('completedBookings');
const pendingCountSpan = document.getElementById('pendingCount');
const activeCountSpan = document.getElementById('activeCount');
const completedCountSpan = document.getElementById('completedCount');
const tabs = document.querySelectorAll('.tab');
const toast = document.getElementById('toast');
const toastTitle = document.getElementById('toastTitle');
const toastMessage = document.getElementById('toastMessage');
const paginationContainer = document.getElementById('paginationContainer');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const pageInfo = document.getElementById('pageInfo');
const bookingsContainer = document.getElementById('bookingsContainer');
const vehiclesContainer = document.getElementById('vehiclesContainer');
const vehiclesGrid = document.getElementById('vehiclesGrid');
const vehiclesCountSpan = document.getElementById('vehiclesCount');
const bookingInnerTabs = document.querySelectorAll('#bookingsContainer .tab-inner');
const innerTabs = document.querySelectorAll('.tab-inner');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadCars();
    loadBookings();
    loadKyc();
    setupEventListeners();
    setupKycEventListeners();
});

// Event Listeners
function setupEventListeners() {
    addVehicleBtn.addEventListener('click', () => {
        addVehicleModal.classList.add('active');
    });

    closeModal.addEventListener('click', () => {
        addVehicleModal.classList.remove('active');
        addVehicleForm.reset();
    });

    addVehicleModal.addEventListener('click', (e) => {
        if (e.target === addVehicleModal) {
            addVehicleModal.classList.remove('active');
            addVehicleForm.reset();
        }
    });

    addVehicleForm.addEventListener('submit', handleAddVehicle);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            switchTab(tabName);
        });
    });

    bookingInnerTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tabInner;
            switchInnerTab(tabName);
        });
    });

    // Vehicle pagination buttons
    document.getElementById('vehiclePrevBtn').addEventListener('click', () => {
        if (currentVehiclePage > 1) {
            currentVehiclePage--;
            renderVehicles();
        }
    });

    document.getElementById('vehicleNextBtn').addEventListener('click', () => {
        const totalPages = Math.ceil(cars.length / vehiclesPerPage);
        if (currentVehiclePage < totalPages) {
            currentVehiclePage++;
            renderVehicles();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (activeTab === 'bookings') {
            if (currentPage > 1) {
                currentPage--;
                renderBookings();
            }
        } else if (activeTab === 'kyc') {
            if (currentKycPage > 1) {
                currentKycPage--;
                renderKyc();
            }
        }
    });

    nextBtn.addEventListener('click', () => {
        if (activeTab === 'bookings') {
            const pending = bookings.filter(b => b.status === 'pending' || b.status === 'reserved');
            const active = bookings.filter(b => b.status === 'in_use' || b.status === 'confirmed');
            const completed = bookings.filter(b => b.status === 'completed' || b.status === 'returned' || b.status === 'canceled');
            const currentBookings = activeInnerTab === 'pending' ? pending : (activeInnerTab === 'active' ? active : completed);
            const totalPages = Math.ceil(currentBookings.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderBookings();
            }
        } else if (activeTab === 'kyc') {
            const filtered = filterKycByStatus(activeKycInnerTab);
            const totalPages = Math.ceil(filtered.length / itemsPerPage);
            if (currentKycPage < totalPages) {
                currentKycPage++;
                renderKyc();
            }
        }
    });
}

// Tab Switching
function switchTab(tabName) {
    activeTab = tabName;
    currentPage = 1; // Reset to first page when switching tabs
    
    tabs.forEach(t => {
        if (t.dataset.tab === tabName) {
            t.classList.add('active');
        } else {
            t.classList.remove('active');
        }
    });

    if (tabName === 'bookings') {
        bookingsContainer.style.display = 'block';
        vehiclesContainer.style.display = 'none';
        document.getElementById('kycContainer').style.display = 'none';
        paginationContainer.style.display = 'flex';
        renderBookings();
    } else if (tabName === 'vehicles') {
        bookingsContainer.style.display = 'none';
        vehiclesContainer.style.display = 'block';
        document.getElementById('kycContainer').style.display = 'none';
        paginationContainer.style.display = 'none';
        currentVehiclePage = 1; // Reset to first page
        renderVehicles();
    } else if (tabName === 'kyc') {
        bookingsContainer.style.display = 'none';
        vehiclesContainer.style.display = 'none';
        document.getElementById('kycContainer').style.display = 'block';
        paginationContainer.style.display = 'flex';
        currentKycPage = 1;
        activeKycInnerTab = 'pending';
        setupKycEventListeners();
        renderKyc();
    }
}

// Inner Tab Switching (for bookings)
function switchInnerTab(tabName) {
    activeInnerTab = tabName;
    currentPage = 1;

    bookingInnerTabs.forEach(t => {
        t.classList.toggle('active', t.dataset.tabInner === tabName);
    });

    pendingBookingsDiv.style.display = tabName === 'pending' ? 'block' : 'none';
    activeBookingsDiv.style.display = tabName === 'active' ? 'block' : 'none';
    completedBookingsDiv.style.display = tabName === 'completed' ? 'block' : 'none';

    renderBookings();
}

// Load Cars
async function loadCars() {
    try {
        const response = await fetch(`${API_BASE}/cars`);
        cars = await response.json();
        vehiclesCountSpan.textContent = cars.length;
        if (activeTab === 'vehicles') {
            renderVehicles();
        }
    } catch (error) {
        console.error('Error loading cars:', error);
        showToast('Error', 'Failed to load vehicles.');
    }
}

// Render Vehicles
function renderVehicles() {
    if (cars.length === 0) {
        vehiclesGrid.innerHTML = '<div class="empty-state"><p>No vehicles in the fleet. Add your first vehicle using the "Add Vehicle" button.</p></div>';
        document.getElementById('vehiclePaginationContainer').style.display = 'none';
        return;
    }

    // Calculate pagination for vehicles
    const totalVehiclePages = Math.ceil(cars.length / vehiclesPerPage);
    const startIndex = (currentVehiclePage - 1) * vehiclesPerPage;
    const endIndex = startIndex + vehiclesPerPage;
    const paginatedCars = cars.slice(startIndex, endIndex);

    vehiclesGrid.innerHTML = paginatedCars.map(car => createVehicleCard(car)).join('');
    
    // Update vehicle pagination
    updateVehiclePagination(cars.length, totalVehiclePages);
    
    // Attach event listeners
    document.querySelectorAll('.toggle-availability-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const carId = parseInt(e.target.dataset.carId);
            toggleCarAvailability(carId);
        });
    });

    document.querySelectorAll('.delete-vehicle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const carId = parseInt(e.target.dataset.carId);
            if (confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
                deleteVehicle(carId);
            }
        });
    });
}

// Update Vehicle Pagination Controls
function updateVehiclePagination(totalItems, totalPages) {
    const vehiclePaginationContainer = document.getElementById('vehiclePaginationContainer');
    const vehiclePrevBtn = document.getElementById('vehiclePrevBtn');
    const vehicleNextBtn = document.getElementById('vehicleNextBtn');
    const vehiclePageInfo = document.getElementById('vehiclePageInfo');
    
    if (totalItems === 0 || totalPages <= 1) {
        vehiclePaginationContainer.style.display = 'none';
        return;
    }

    vehiclePaginationContainer.style.display = 'flex';
    vehiclePageInfo.textContent = `Page ${currentVehiclePage} of ${totalPages} (${totalItems} total)`;
    
    vehiclePrevBtn.disabled = currentVehiclePage === 1;
    vehicleNextBtn.disabled = currentVehiclePage === totalPages;
}

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

// Create Vehicle Card
function createVehicleCard(car) {
    const statusClass = car.isAvailable ? 'available' : 'unavailable';
    const statusText = car.isAvailable ? 'Available' : 'Unavailable';
    const category = car.category || 'Other';
    
    // Use uploaded image if available, otherwise use default image
    const carImage = car.carPicture 
        ? (car.carPicture.startsWith('images/') ? car.carPicture : `/storage/${car.carPicture}`)
        : getCarImage(car.brand, car.model, car.category);
    
    return `
        <div class="vehicle-card">
            <div class="vehicle-image-container">
                <img src="${carImage}" alt="${car.brand} ${car.model}" class="vehicle-image" onerror="this.src='images/car logo.png'">
            </div>
            <div class="vehicle-header">
                <div class="vehicle-info">
                    <h3>${car.brand} ${car.model}</h3>
                    <span class="vehicle-plate">${car.numberPlate}</span>
                    <span class="vehicle-category">${category}</span>
                </div>
                <div class="vehicle-status ${statusClass}">${statusText}</div>
            </div>
            <div class="vehicle-details">
                <div class="vehicle-detail">
                    <label>Seats</label>
                    <span>${car.seats}</span>
                </div>
                <div class="vehicle-detail">
                    <label>Daily Rate</label>
                    <span>UGX ${car.dailyRate.toLocaleString()}</span>
                </div>
                <div class="vehicle-detail">
                    <label>Added</label>
                    <span>${new Date(car.createdAt).toLocaleDateString()}</span>
                </div>
            </div>
            <div class="vehicle-actions">
                <button class="toggle-availability-btn ${statusClass}" data-car-id="${car.id}">
                    ${car.isAvailable ? 'Mark Unavailable' : 'Mark Available'}
                </button>
                <button class="delete-vehicle-btn" data-car-id="${car.id}">Delete</button>
            </div>
        </div>
    `;
}

// Toggle Car Availability
async function toggleCarAvailability(carId) {
    try {
        const car = cars.find(c => c.id === carId);
        if (!car) return;

        const response = await fetch(`${API_BASE}/cars/${carId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                isAvailable: !car.isAvailable
            })
        });

        if (response.ok) {
            showToast('Success', `Vehicle ${!car.isAvailable ? 'marked as available' : 'marked as unavailable'}.`);
            await loadCars();
        } else {
            const result = await response.json();
            showToast('Error', result.error || 'Failed to update vehicle availability.');
        }
    } catch (error) {
        console.error('Error toggling availability:', error);
        showToast('Error', 'Failed to update vehicle availability.');
    }
}

// Delete Vehicle
async function deleteVehicle(carId) {
    try {
        const response = await fetch(`${API_BASE}/cars/${carId}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            showToast('Success', 'Vehicle deleted successfully.');
            await loadCars();
        } else {
            const result = await response.json();
            showToast('Error', result.error || 'Failed to delete vehicle.');
        }
    } catch (error) {
        console.error('Error deleting vehicle:', error);
        showToast('Error', 'Failed to delete vehicle.');
    }
}

// Load Bookings
async function loadBookings() {
    try {
        const response = await fetch(`${API_BASE}/bookings`);
        bookings = await response.json();
        renderBookings();
        updateCounts();
    } catch (error) {
        console.error('Error loading bookings:', error);
        showToast('Error', 'Failed to load bookings.');
    }
}

// Render Bookings
function renderBookings() {
    const pending = bookings.filter(b => b.status === 'pending' || b.status === 'reserved');
    const active = bookings.filter(b => b.status === 'in_use' || b.status === 'confirmed');
    const completed = bookings.filter(b => b.status === 'completed' || b.status === 'returned' || b.status === 'canceled');

    const currentBookings = activeInnerTab === 'pending' ? pending : (activeInnerTab === 'active' ? active : completed);
    const totalPages = Math.ceil(currentBookings.length / itemsPerPage);
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedBookings = currentBookings.slice(startIndex, endIndex);

    const emptyPending = '<div class="empty-state"><p>No pending bookings</p></div>';
    const emptyActive = '<div class="empty-state"><p>No active bookings</p></div>';
    const emptyCompleted = '<div class="empty-state"><p>No completed bookings</p></div>';

    if (activeInnerTab === 'pending') {
        pendingBookingsDiv.innerHTML = paginatedBookings.length > 0
            ? paginatedBookings.map(booking => createPendingBookingCard(booking)).join('')
            : emptyPending;
    } else if (activeInnerTab === 'active') {
        activeBookingsDiv.innerHTML = paginatedBookings.length > 0 
            ? paginatedBookings.map(booking => createBookingCard(booking)).join('')
            : emptyActive;
    } else {
        completedBookingsDiv.innerHTML = paginatedBookings.length > 0
            ? paginatedBookings.map(booking => createBookingCard(booking)).join('')
            : emptyCompleted;
    }

    updatePagination(currentBookings.length, totalPages);

    document.querySelectorAll('.mark-returned-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const bookingId = e.target.dataset.bookingId;
            markAsReturned(bookingId);
        });
    });

    document.querySelectorAll('.confirm-booking-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const bookingId = e.target.dataset.bookingId;
            setPendingCardButtonsState(e.target, true, 'confirming');
            confirmBooking(bookingId);
        });
    });

    document.querySelectorAll('.decline-booking-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const bookingId = e.target.dataset.bookingId;
            declineBooking(bookingId, e);
        });
    });
}

// Update Pagination Controls
function updatePagination(totalItems, totalPages) {
    if (totalItems === 0) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${totalItems} total)`;
    
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;
}

// Create Booking Card
function createBookingCard(booking) {
    const car = cars.find(c => c.id === booking.car_id);
    const vehicleName = car ? `${car.brand || ''} ${car.model || ''}`.trim() : 'Unknown Vehicle';
    const licensePlate = car ? car.numberPlate : 'N/A';
    
    // Safe date formatting function
    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } catch (e) {
            return 'N/A';
        }
    };
    
    const startDate = formatDate(booking.startDate);
    const endDate = formatDate(booking.endDate);
    const hiredAt = formatDate(booking.created_at || booking.createdAt || booking.hiredAt);

    const statusClass = booking.status === 'confirmed' || booking.status === 'in_use' 
        ? 'confirmed' 
        : 'completed';
    
    const statusText = booking.status === 'in_use' ? 'IN USE' : (booking.status === 'confirmed' ? 'CONFIRMED' : (booking.status === 'canceled' ? 'CANCELED' : 'COMPLETED'));

    const actionButton = (booking.status === 'in_use' || booking.status === 'confirmed')
        ? `<button class="mark-returned-btn" data-booking-id="${booking.id}">Mark as Returned</button>`
        : '';

    return `
        <div class="booking-card">
            <div class="booking-header">
                <div class="vehicle-info">
                    <h3>${vehicleName}</h3>
                    <span class="license-plate">${licensePlate}</span>
                </div>
                <div class="booking-status ${statusClass}">${statusText}</div>
            </div>
            <div class="booking-details">
                <div class="booking-detail">
                    <label>Customer</label>
                    <span>${booking.customerName || 'N/A'}</span>
                </div>
                <div class="booking-detail">
                    <label>Hired At</label>
                    <span>${hiredAt}</span>
                </div>
                <div class="booking-detail">
                    <label>Booking Period</label>
                    <span>${startDate} to ${endDate}</span>
                </div>
            </div>
            <div class="booking-actions">
                ${actionButton}
            </div>
        </div>
    `;
}

// Create Pending Booking Card (Confirm / Decline)
function createPendingBookingCard(booking) {
    const car = cars.find(c => c.id === booking.car_id);
    const vehicleName = car ? `${car.brand || ''} ${car.model || ''}`.trim() : 'Unknown Vehicle';
    const licensePlate = car ? car.numberPlate : 'N/A';
    
    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } catch (e) {
            return 'N/A';
        }
    };
    
    const startDate = formatDate(booking.startDate);
    const endDate = formatDate(booking.endDate);
    const hiredAt = formatDate(booking.created_at || booking.createdAt || booking.hiredAt);

    return `
        <div class="booking-card">
            <div class="booking-header">
                <div class="vehicle-info">
                    <h3>${vehicleName}</h3>
                    <span class="license-plate">${licensePlate}</span>
                </div>
                <div class="booking-status pending">PENDING</div>
            </div>
            <div class="booking-details">
                <div class="booking-detail">
                    <label>Customer</label>
                    <span>${booking.customerName || 'N/A'}</span>
                </div>
                <div class="booking-detail">
                    <label>Requested At</label>
                    <span>${hiredAt}</span>
                </div>
                <div class="booking-detail">
                    <label>Booking Period</label>
                    <span>${startDate} to ${endDate}</span>
                </div>
            </div>
            <div class="booking-actions">
                <button class="confirm-booking-btn" data-booking-id="${booking.id}">Confirm</button>
                <button class="decline-booking-btn" data-booking-id="${booking.id}">Decline</button>
            </div>
        </div>
    `;
}

// Disable or re-enable Confirm/Decline buttons on a pending booking card
function setPendingCardButtonsState(clickedButton, disabled, state) {
    const card = clickedButton && clickedButton.closest ? clickedButton.closest('.booking-card') : null;
    if (!card) return;
    const confirmBtn = card.querySelector('.confirm-booking-btn');
    const declineBtn = card.querySelector('.decline-booking-btn');
    if (confirmBtn) {
        confirmBtn.disabled = disabled;
        if (disabled && state === 'confirming') confirmBtn.textContent = 'Confirming...';
        else if (!disabled) confirmBtn.textContent = 'Confirm';
    }
    if (declineBtn) {
        declineBtn.disabled = disabled;
        if (disabled && state === 'declining') declineBtn.textContent = 'Declining...';
        else if (!disabled) declineBtn.textContent = 'Decline';
    }
}

// Confirm Booking
async function confirmBooking(bookingId) {
    try {
        const response = await fetch(`${API_BASE}/bookings/${bookingId}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            showToast('Success', 'Booking confirmed.');
            await loadBookings();
        } else {
            const result = await response.json();
            showToast('Error', result.error || result.message || 'Failed to confirm booking.');
            document.querySelectorAll(`.confirm-booking-btn[data-booking-id="${bookingId}"], .decline-booking-btn[data-booking-id="${bookingId}"]`).forEach(btn => {
                const card = btn.closest('.booking-card');
                if (card) setPendingCardButtonsState(btn, false);
            });
        }
    } catch (error) {
        console.error('Error confirming booking:', error);
        showToast('Error', 'Failed to confirm booking.');
        document.querySelectorAll(`.confirm-booking-btn[data-booking-id="${bookingId}"], .decline-booking-btn[data-booking-id="${bookingId}"]`).forEach(btn => {
            const card = btn.closest('.booking-card');
            if (card) setPendingCardButtonsState(btn, false);
        });
    }
}

// Decline Booking
async function declineBooking(bookingId, clickEvent) {
    if (!confirm('Are you sure you want to decline this booking?')) return;
    if (clickEvent && clickEvent.target) setPendingCardButtonsState(clickEvent.target, true, 'declining');
    try {
        const response = await fetch(`${API_BASE}/bookings/${bookingId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            showToast('Success', 'Booking declined.');
            await loadBookings();
        } else {
            const result = await response.json();
            showToast('Error', result.error || result.message || 'Failed to decline booking.');
            if (clickEvent && clickEvent.target) setPendingCardButtonsState(clickEvent.target, false);
        }
    } catch (error) {
        console.error('Error declining booking:', error);
        showToast('Error', 'Failed to decline booking.');
        if (clickEvent && clickEvent.target) setPendingCardButtonsState(clickEvent.target, false);
    }
}

// Update Counts
function updateCounts() {
    const pending = bookings.filter(b => b.status === 'pending' || b.status === 'reserved').length;
    const active = bookings.filter(b => b.status === 'in_use' || b.status === 'confirmed').length;
    const completed = bookings.filter(b => b.status === 'completed' || b.status === 'returned' || b.status === 'canceled').length;
    
    if (pendingCountSpan) pendingCountSpan.textContent = pending;
    activeCountSpan.textContent = active;
    completedCountSpan.textContent = completed;
}

// Handle Add Vehicle
async function handleAddVehicle(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Validate number plate format
    const numberPlate = formData.get('numberPlate').trim().toUpperCase();
    const platePattern = /^(U[A-Z]{2}\s?\d{3}[A-Z]|UG\s?\d{2}\s?\d{5})$/;
    
    // Test the pattern and provide specific feedback
    if (!platePattern.test(numberPlate)) {
        // Check if it's close to a valid format and provide helpful feedback
        const legacyPattern = /^U[A-Z]{2}\s?\d{3}[A-Z]$/;
        const digitalPattern = /^UG\s?\d{2}\s?\d{5}$/;
        
        let errorMessage = 'Invalid number plate format. ';
        
        if (numberPlate.match(/^U[A-Z]{2}/)) {
            errorMessage += 'Legacy format should be: UAJ 979B (3 letters starting with U, 3 digits, 1 letter)';
        } else if (numberPlate.match(/^UG/)) {
            errorMessage += 'Digital format should be: UG 32 00042 (UG followed by 2 digits and 5 digits)';
        } else {
            errorMessage += 'Use UAJ 979B (legacy) or UG 32 00042 (digital) format.';
        }
        
        showToast('Error', errorMessage);
        return;
    }

    // Update the numberPlate in formData
    formData.set('numberPlate', numberPlate);

    try {
        const response = await fetch(`${API_BASE}/cars`, {
            method: 'POST',
            body: formData // Send FormData directly (includes file)
        });

        const result = await response.json();

        if (response.ok) {
            showToast('Vehicle added.', 'The vehicle has been successfully added to Mam Tours and Travel.');
            addVehicleModal.classList.remove('active');
            addVehicleForm.reset();
            await loadCars();
            if (activeTab === 'vehicles') {
                renderVehicles();
            }
        } else {
            showToast('Error', result.error || 'Failed to add vehicle.');
        }
    } catch (error) {
        console.error('Error adding vehicle:', error);
        showToast('Error', 'Failed to add vehicle.');
    }
}

// Mark as Returned
async function markAsReturned(bookingId) {
    try {
        const response = await fetch(`${API_BASE}/bookings/${bookingId}/return`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            showToast('Success', 'Booking marked as returned.');
            loadBookings();
        } else {
            const result = await response.json();
            showToast('Error', result.error || 'Failed to mark booking as returned.');
        }
    } catch (error) {
        console.error('Error marking as returned:', error);
        showToast('Error', 'Failed to mark booking as returned.');
    }
}

// Show Toast
function showToast(title, message) {
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}



// ===== KYC MANAGEMENT =====

// Load KYC
async function loadKyc() {
    try {
        const response = await fetch(`${API_BASE}/kyc`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        allKyc = await response.json();
        updateKycCounts();
        if (activeTab === 'kyc') {
            renderKyc();
        }
    } catch (error) {
        console.error('Error loading KYC:', error);
        allKyc = [];
        updateKycCounts();
        if (activeTab === 'kyc') {
            renderKyc();
        }
        // Don't show error toast on initial load - just show empty state
    }
}

// Render KYC
function renderKyc() {
    const filtered = filterKycByStatus(activeKycInnerTab);
    const totalPages = Math.ceil(filtered.length / itemsPerPage);

    const startIndex = (currentKycPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedKyc = filtered.slice(startIndex, endIndex);

    const html = paginatedKyc.length > 0
        ? paginatedKyc.map(kyc => createKycCard(kyc)).join('')
        : '<div class="empty-state"><p>No KYC submissions in this category.</p></div>';

    const targetDiv = activeKycInnerTab === 'pending' ? document.getElementById('kycPendingList') :
                     activeKycInnerTab === 'verified' ? document.getElementById('kycVerifiedList') :
                     document.getElementById('kycRejectedList');
    targetDiv.innerHTML = html;

    updateKycPagination(filtered.length, totalPages);

    document.querySelectorAll('.kyc-view-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const kycId = parseInt(e.target.dataset.kycId);
            openKycDetail(kycId);
        });
    });
}

// Filter KYC by status
function filterKycByStatus(status) {
    return allKyc.filter(kyc => kyc.status === status);
}

// Create KYC Card
function createKycCard(kyc) {
    const user = kyc.user || {};
    const statusClass = kyc.status;
    const statusText = kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1);

    return `
        <div class="kyc-card">
            <div class="kyc-card-info">
                <div class="kyc-card-header">
                    <span class="kyc-card-name">${user.name || 'Unknown'}</span>
                    <span class="kyc-card-status ${statusClass}">${statusText}</span>
                </div>
                <div class="kyc-card-detail">
                    <label>Email:</label> ${user.email || 'N/A'}
                </div>
                <div class="kyc-card-detail">
                    <label>ID Type:</label> ${kyc.id_type === 'nin' ? 'NIN' : 'Passport'} - ${kyc.id_number}
                </div>
                <div class="kyc-card-detail">
                    <label>Submitted:</label> ${new Date(kyc.created_at).toLocaleDateString()}
                </div>
            </div>
            <div class="kyc-card-actions">
                <button class="kyc-view-btn" data-kyc-id="${kyc.id}">View Details</button>
            </div>
        </div>
    `;
}

// Open KYC Detail
function openKycDetail(kycId) {
    const kyc = allKyc.find(k => k.id === kycId);
    if (!kyc) return;

    currentKycId = kycId;
    const user = kyc.user || {};

    document.getElementById('detailName').textContent = user.name || 'N/A';
    document.getElementById('detailEmail').textContent = user.email || 'N/A';
    document.getElementById('detailPhone').textContent = user.phone || 'N/A';
    document.getElementById('detailIdType').textContent = kyc.id_type === 'nin' ? 'NIN (National ID)' : 'Passport';
    document.getElementById('detailIdNumber').textContent = kyc.id_number;
    document.getElementById('detailPermitNumber').textContent = kyc.permit_number;

    const statusBadge = document.getElementById('detailStatus');
    statusBadge.textContent = kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1);
    statusBadge.className = `status-badge ${kyc.status}`;

    const rejectionReasonRow = document.getElementById('rejectionReasonRow');
    if (kyc.status === 'rejected' && kyc.rejection_reason) {
        rejectionReasonRow.style.display = 'grid';
        document.getElementById('detailRejectionReason').textContent = kyc.rejection_reason;
    } else {
        rejectionReasonRow.style.display = 'none';
    }

    const verifiedAtRow = document.getElementById('verifiedAtRow');
    if (kyc.status === 'verified' && kyc.verified_at) {
        verifiedAtRow.style.display = 'grid';
        document.getElementById('detailVerifiedAt').textContent = new Date(kyc.verified_at).toLocaleString();
    } else {
        verifiedAtRow.style.display = 'none';
    }

    const actionSection = document.getElementById('actionSection');
    if (kyc.status === 'pending') {
        actionSection.style.display = 'block';
    } else {
        actionSection.style.display = 'none';
    }

    // Load document previews
    loadDocumentPreview('id', kyc.id_document_path);
    loadDocumentPreview('permit', kyc.permit_document_path);

    hideKycRejectForm();
    document.getElementById('kycDetailModal').classList.add('active');
}

// Approve KYC
async function approveKyc() {
    if (!currentKycId) return;

    try {
        const response = await fetch(`${API_BASE}/kyc/${currentKycId}/verify`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            showToast('Success', 'KYC approved successfully.');
            document.getElementById('kycDetailModal').classList.remove('active');
            await loadKyc();
        } else {
            const result = await response.json();
            showToast('Error', result.message || 'Failed to approve KYC.');
        }
    } catch (error) {
        console.error('Error approving KYC:', error);
        showToast('Error', 'Failed to approve KYC.');
    }
}

// Show KYC Reject Form
function showKycRejectForm() {
    document.getElementById('actionSection').style.display = 'none';
    document.getElementById('rejectFormSection').style.display = 'block';
    document.getElementById('rejectionReasonInput').focus();
}

// Hide KYC Reject Form
function hideKycRejectForm() {
    document.getElementById('actionSection').style.display = 'block';
    document.getElementById('rejectFormSection').style.display = 'none';
    document.getElementById('rejectionReasonInput').value = '';
}

// Submit KYC Rejection
async function submitKycReject() {
    if (!currentKycId) return;

    const reason = document.getElementById('rejectionReasonInput').value.trim();
    if (!reason) {
        showToast('Error', 'Please enter a rejection reason.');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/kyc/${currentKycId}/reject`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason })
        });

        if (response.ok) {
            showToast('Success', 'KYC rejected successfully.');
            document.getElementById('kycDetailModal').classList.remove('active');
            await loadKyc();
        } else {
            const result = await response.json();
            showToast('Error', result.message || 'Failed to reject KYC.');
        }
    } catch (error) {
        console.error('Error rejecting KYC:', error);
        showToast('Error', 'Failed to reject KYC.');
    }
}

// View KYC Document
// Update KYC Counts
function updateKycCounts() {
    document.getElementById('kycCount').textContent = allKyc.length;
    document.getElementById('kycPendingCount').textContent = allKyc.filter(k => k.status === 'pending').length;
    document.getElementById('kycVerifiedCount').textContent = allKyc.filter(k => k.status === 'verified').length;
    document.getElementById('kycRejectedCount').textContent = allKyc.filter(k => k.status === 'rejected').length;
}

// Update KYC Pagination
function updateKycPagination(totalItems, totalPages) {
    if (totalItems === 0) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    pageInfo.textContent = `Page ${currentKycPage} of ${totalPages} (${totalItems} total)`;

    prevBtn.disabled = currentKycPage === 1;
    nextBtn.disabled = currentKycPage === totalPages;
}

// Switch KYC Inner Tab
function switchKycInnerTab(tabName) {
    activeKycInnerTab = tabName;
    currentKycPage = 1;

    const kycInnerTabs = document.querySelectorAll('#kycContainer .tab-inner');
    kycInnerTabs.forEach(t => {
        if (t.dataset.tabInner === tabName) {
            t.classList.add('active');
        } else {
            t.classList.remove('active');
        }
    });

    document.getElementById('kycPendingList').style.display = tabName === 'pending' ? 'flex' : 'none';
    document.getElementById('kycVerifiedList').style.display = tabName === 'verified' ? 'flex' : 'none';
    document.getElementById('kycRejectedList').style.display = tabName === 'rejected' ? 'flex' : 'none';

    renderKyc();
}

// Setup KYC Event Listeners
function setupKycEventListeners() {
    const kycInnerTabs = document.querySelectorAll('#kycContainer .tab-inner');
    kycInnerTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tabInner;
            switchKycInnerTab(tabName);
        });
    });

    const closeKycModal = document.getElementById('closeKycModal');
    if (closeKycModal) {
        closeKycModal.addEventListener('click', () => {
            document.getElementById('kycDetailModal').classList.remove('active');
        });
    }

    const kycDetailModal = document.getElementById('kycDetailModal');
    if (kycDetailModal) {
        kycDetailModal.addEventListener('click', (e) => {
            if (e.target === kycDetailModal) {
                kycDetailModal.classList.remove('active');
            }
        });
    }

    const approveBtn = document.getElementById('approveBtn');
    if (approveBtn) {
        approveBtn.addEventListener('click', approveKyc);
    }

    const rejectBtn = document.getElementById('rejectBtn');
    if (rejectBtn) {
        rejectBtn.addEventListener('click', showKycRejectForm);
    }

    const submitRejectBtn = document.getElementById('submitRejectBtn');
    if (submitRejectBtn) {
        submitRejectBtn.addEventListener('click', submitKycReject);
    }

    const cancelRejectBtn = document.getElementById('cancelRejectBtn');
    if (cancelRejectBtn) {
        cancelRejectBtn.addEventListener('click', hideKycRejectForm);
    }

    const downloadIdDocBtn = document.getElementById('downloadIdDocBtn');
    if (downloadIdDocBtn) {
        downloadIdDocBtn.addEventListener('click', () => downloadKycDocument('id'));
    }

    const downloadPermitDocBtn = document.getElementById('downloadPermitDocBtn');
    if (downloadPermitDocBtn) {
        downloadPermitDocBtn.addEventListener('click', () => downloadKycDocument('permit'));
    }
}


// Load document preview for KYC
function loadDocumentPreview(type, filePath) {
    const previewDiv = type === 'id' ? document.getElementById('idDocumentPreview') : document.getElementById('permitDocumentPreview');
    
    if (!previewDiv) return; // Element doesn't exist in admin panel
    
    if (!filePath) {
        previewDiv.innerHTML = '<div class="preview-loading">No document uploaded</div>';
        return;
    }

    const fileUrl = `/storage/${filePath}`;
    const fileExtension = filePath.split('.').pop().toLowerCase();

    // Check if it's an image or PDF
    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
        previewDiv.innerHTML = `<img src="${fileUrl}" alt="Document preview" />`;
    } else if (fileExtension === 'pdf') {
        previewDiv.innerHTML = `<iframe src="${fileUrl}"></iframe>`;
    } else {
        previewDiv.innerHTML = '<div class="preview-loading">Document format not supported for preview</div>';
    }
}

// Download KYC document
function downloadKycDocument(type) {
    if (!currentKycId) return;
    const url = `${API_BASE}/kyc/${currentKycId}/document/${type}`;
    const link = document.createElement('a');
    link.href = url;
    link.download = `kyc_${type}_document`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


// File input preview handler
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('car_picture');
    const fileLabel = document.querySelector('.file-input-label');
    const fileText = document.querySelector('.file-input-text');
    
    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Update label appearance
                fileLabel.classList.add('has-file');
                
                // Update text
                fileText.textContent = file.name;
                
                // Show preview if it's an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Remove existing preview if any
                        const existingPreview = fileLabel.querySelector('.file-input-preview');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        
                        // Create new preview
                        const preview = document.createElement('div');
                        preview.className = 'file-input-preview';
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        fileLabel.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            } else {
                // Reset to default state
                fileLabel.classList.remove('has-file');
                fileText.textContent = 'Click to upload or drag and drop';
                
                const existingPreview = fileLabel.querySelector('.file-input-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }
            }
        });
    }
});


// Reports functionality
const reportsContainer = document.getElementById('reportsContainer');
const reportTypeSelect = document.getElementById('reportType');
const customDateGroup = document.getElementById('customDateGroup');
const customEndDateGroup = document.getElementById('customEndDateGroup');
const generateReportBtn = document.getElementById('generateReportBtn');
const exportReportBtn = document.getElementById('exportReportBtn');
let currentReportData = [];

// Show/hide custom date fields
if (reportTypeSelect) {
    reportTypeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateGroup.style.display = 'flex';
            customEndDateGroup.style.display = 'flex';
        } else {
            customDateGroup.style.display = 'none';
            customEndDateGroup.style.display = 'none';
        }
    });
}

// Generate report
if (generateReportBtn) {
    generateReportBtn.addEventListener('click', generateReport);
}

// Export report
if (exportReportBtn) {
    exportReportBtn.addEventListener('click', exportReportToCSV);
}

async function generateReport() {
    const reportType = reportTypeSelect.value;
    let startDate, endDate;
    
    const today = new Date();
    
    if (reportType === 'weekly') {
        // Last 7 days
        endDate = new Date(today);
        startDate = new Date(today);
        startDate.setDate(startDate.getDate() - 7);
    } else if (reportType === 'monthly') {
        // Last 30 days
        endDate = new Date(today);
        startDate = new Date(today);
        startDate.setDate(startDate.getDate() - 30);
    } else if (reportType === 'custom') {
        const startInput = document.getElementById('startDate').value;
        const endInput = document.getElementById('endDate').value;
        
        if (!startInput || !endInput) {
            showToast('Error', 'Please select both start and end dates');
            return;
        }
        
        startDate = new Date(startInput);
        endDate = new Date(endInput);
    }
    
    // Filter bookings by date range
    const filteredBookings = bookings.filter(booking => {
        const bookingDate = new Date(booking.startDate);
        return bookingDate >= startDate && bookingDate <= endDate;
    });
    
    currentReportData = filteredBookings;
    
    // Calculate statistics
    const totalBookings = filteredBookings.length;
    const totalRevenue = filteredBookings.reduce((sum, b) => sum + (b.totalCost || 0), 0);
    const avgBookingValue = totalBookings > 0 ? totalRevenue / totalBookings : 0;
    
    // Find most booked car
    const carCounts = {};
    filteredBookings.forEach(booking => {
        const carName = booking.carBrand + ' ' + booking.carModel;
        carCounts[carName] = (carCounts[carName] || 0) + 1;
    });
    
    let mostBookedCar = '-';
    let maxCount = 0;
    for (const [car, count] of Object.entries(carCounts)) {
        if (count > maxCount) {
            maxCount = count;
            mostBookedCar = car;
        }
    }
    
    // Update stats display
    document.getElementById('totalBookings').textContent = totalBookings;
    document.getElementById('totalRevenue').textContent = 'UGX ' + totalRevenue.toLocaleString();
    document.getElementById('mostBookedCar').textContent = mostBookedCar;
    document.getElementById('avgBookingValue').textContent = 'UGX ' + Math.round(avgBookingValue).toLocaleString();
    
    // Render table
    renderReportsTable(filteredBookings);
    
    showToast('Report Generated', `Found ${totalBookings} bookings in the selected period`);
}

function renderReportsTable(reportBookings) {
    const tbody = document.getElementById('reportsTableBody');
    
    if (reportBookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #999;">No bookings found for the selected period</td></tr>';
        return;
    }
    
    tbody.innerHTML = reportBookings.map(booking => {
        const startDate = new Date(booking.startDate);
        const endDate = new Date(booking.endDate);
        const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        
        return `
            <tr>
                <td>#${booking.id}</td>
                <td>${booking.customerName}</td>
                <td>${booking.carBrand} ${booking.carModel}</td>
                <td>${formatDate(booking.startDate)}</td>
                <td>${formatDate(booking.endDate)}</td>
                <td>${days} days</td>
                <td>UGX ${(booking.totalCost || 0).toLocaleString()}</td>
                <td><span class="status-badge-report ${booking.status}">${booking.status}</span></td>
            </tr>
        `;
    }).join('');
}

function exportReportToCSV() {
    if (currentReportData.length === 0) {
        showToast('Error', 'Please generate a report first');
        return;
    }
    
    // Create CSV content
    let csv = 'Booking ID,Customer,Vehicle,Start Date,End Date,Days,Amount,Status\n';
    
    currentReportData.forEach(booking => {
        const startDate = new Date(booking.startDate);
        const endDate = new Date(booking.endDate);
        const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        
        csv += `${booking.id},"${booking.customerName}","${booking.carBrand} ${booking.carModel}",${formatDate(booking.startDate)},${formatDate(booking.endDate)},${days},${booking.totalCost || 0},${booking.status}\n`;
    });
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bookings_report_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showToast('Export Successful', 'Report has been downloaded as CSV');
}

// Update switchTab function to handle reports tab
const originalSwitchTab = window.switchTab || switchTab;
function switchTab(tabName) {
    activeTab = tabName;
    
    tabs.forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });
    
    bookingsContainer.style.display = tabName === 'bookings' ? 'block' : 'none';
    vehiclesContainer.style.display = tabName === 'vehicles' ? 'block' : 'none';
    document.getElementById('kycContainer').style.display = tabName === 'kyc' ? 'block' : 'none';
    reportsContainer.style.display = tabName === 'reports' ? 'block' : 'none';
    
    if (tabName === 'bookings') {
        renderBookings();
        paginationContainer.style.display = 'flex';
    } else if (tabName === 'vehicles') {
        paginationContainer.style.display = 'none';
        currentVehiclePage = 1;
        renderVehicles();
    } else if (tabName === 'kyc') {
        paginationContainer.style.display = 'none';
        renderKycList();
    } else if (tabName === 'reports') {
        paginationContainer.style.display = 'none';
    }
}
