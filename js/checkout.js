// Checkout.js - Main checkout functionality
// This file contains all the JavaScript functions for the checkout process

// Global variables
let currentStep = 1;
let cart = [];
let map = null;
let customerDetails = {};

// Valid PIN codes (Mumbai area)
const validPinCodes = [
    "400001", "400002", "400003", "400004", "400005", "400006", "400007", "400008", "400009", "400010",
    "400011", "400012", "400013", "400014", "400015", "400016", "400017", "400018", "400019", "400020",
    "400021", "400022", "400023", "400024", "400025", "400026", "400027", "400028", "400029", "400030",
    "400031", "400032", "400033", "400034", "400035", "400036", "400037", "400038", "400039", "400041",
    "400042", "400043", "400049", "400050", "400051", "400052", "400054", "400055", "400056", "400057",
    "400058", "400059", "400060", "400061", "400062", "400063", "400064", "400065", "400066", "400067",
    "400068", "400069", "400070", "400071", "400072", "400073", "400074", "400075", "400076", "400077",
    "400078", "400079", "400080", "400081", "400082", "400083", "400084", "400085", "400086", "400087",
    "400088", "400089", "400090", "400091", "400092", "400093", "400094", "400095", "400096", "400097",
    "400098", "400099", "401074", "401101", "401104", "401105", "401106", "401107", "401201", "401202",
    "401203", "401207", "401208", "401209", "401210", "401303", "401305", "410106", "410206", "410208",
    "410209", "410210", "410211", "410218", "410221", "421005", "421301", "421306", "421308", "421309",
    "421311", "421501", "421502", "421503", "421504", "421505", "421506", "400046", "400609", "400611",
    "400612", "400613", "400614", "401205", "401301", "401302", "421001", "421002", "421003", "421004",
    "421101", "421102", "421103", "421201", "421202", "421203", "421204", "421302", "421304", "421305",
    "421604", "421605"
];

// Initialize the checkout
document.addEventListener('DOMContentLoaded', function() {
    loadCartFromStorage();
    updateStep(1);
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    // PIN code input
    document.getElementById('pinCode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            validatePinCode();
        }
    });

    // Customer form submission
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        proceedToOrderSummary();
    });
}

// Load cart from localStorage
function loadCartFromStorage() {
    const stored = localStorage.getItem('triptiCart');
    try {
        cart = stored ? JSON.parse(stored) : [];
    } catch {
        cart = [];
    }
}

// Update step indicator
function updateStep(step) {
    currentStep = step;
    
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show current section
    document.getElementById(getSectionId(step)).classList.add('active');
    
    // Update step indicators
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        stepEl.classList.remove('active', 'completed');
        if (index + 1 === step) {
            stepEl.classList.add('active');
        } else if (index + 1 < step) {
            stepEl.classList.add('completed');
        }
    });
}

// Get section ID based on step
function getSectionId(step) {
    const sections = ['pin-section', 'details-section', 'order-summary-section', 'payment-section', 'payment-success-section'];
    return sections[step - 1];
}

// Validate PIN code
function validatePinCode() {
    const pinCode = document.getElementById('pinCode').value.trim();
    const pinCodeError = document.getElementById('pinCodeError');

    if (validPinCodes.includes(pinCode)) {
        pinCodeError.classList.add('hidden');
        localStorage.setItem('deliveryPinCode', pinCode);
        updateStep(2);
        initializeLocationDetection();
    } else {
        pinCodeError.classList.remove('hidden');
        document.getElementById('pinCode').focus();
    }
}

// Initialize location detection
function initializeLocationDetection() {
    const status = document.getElementById('location-status');
    const manualBtn = document.getElementById('manual-location-btn');

    if (!navigator.geolocation) {
        status.className = 'location-status error';
        status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Geolocation is not supported by your browser.';
        manualBtn.classList.remove('hidden');
        return;
    }

    // Auto-detect location
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            
            status.className = 'location-status success';
            status.innerHTML = `<i class="fas fa-check-circle me-2"></i>Location detected: ${lat}, ${lng}`;
            
            setUserLocation(lat, lng);
            reverseGeocode(lat, lng);
        },
        (error) => {
            console.warn('Location auto-fetch failed:', error);
            manualBtn.classList.remove('hidden');
            
            status.className = 'location-status error';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Location access denied. Please click "Use My Location"';
                    break;
                case error.POSITION_UNAVAILABLE:
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Location unavailable.';
                    break;
                case error.TIMEOUT:
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Location request timed out.';
                    break;
                default:
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>An unknown error occurred.';
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

        // Set user location and initialize map
        function setUserLocation(lat, lng) {
            // Validate if location is within Mumbai area
            if (!isValidMumbaiLocation(lat, lng)) {
                console.warn('Location outside Mumbai area detected:', lat, lng);
                // Show warning to user
                const status = document.getElementById('location-status');
                status.className = 'location-status error';
                status.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>Location detected outside Mumbai area. Please ensure you're in Mumbai for delivery.`;
            }

            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;

            const mapContainer = document.getElementById('map-container');
            mapContainer.classList.remove('hidden');

            // Initialize Leaflet map
            if (!map) {
                map = L.map('leaflet-map').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
            } else {
                map.setView([lat, lng], 15);
            }

            // Add marker
            L.marker([lat, lng]).addTo(map)
                .bindPopup('Your Location')
                .openPopup();
        }

        // Validate if coordinates are within Mumbai area
        function isValidMumbaiLocation(lat, lng) {
            return (lat >= 18.8 && lat <= 19.5 && lng >= 72.7 && lng <= 73.2);
        }

        // Reverse geocoding using OpenCage API
        async function reverseGeocode(lat, lng) {
            try {
                // Note: You need to replace YOUR_OPENCAGE_API_KEY with your actual API key
                const response = await fetch(`https://api.opencagedata.com/geocode/v1/json?q=${lat}+${lng}&key=YOUR_OPENCAGE_API_KEY`);
                const data = await response.json();
                
                if (data.results && data.results.length > 0) {
                    const result = data.results[0];
                    const components = result.components;
                    
                    // Pre-fill address fields
                    const address = [
                        components.house_number,
                        components.road,
                        components.suburb,
                        components.city_district,
                        components.city,
                        components.state,
                        components.postcode
                    ].filter(Boolean).join(', ');
                    
                    document.getElementById('address').value = address;
                }
            } catch (error) {
                console.error('Reverse geocoding failed:', error);
            }
        }

// Manual location detection
function getBrowserLocation() {
    const status = document.getElementById('location-status');
    const manualBtn = document.getElementById('manual-location-btn');

    status.className = 'location-status detecting';
    status.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Retrying location detection...';
    manualBtn.classList.add('hidden');

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            
            status.className = 'location-status success';
            status.innerHTML = `<i class="fas fa-check-circle me-2"></i>Location detected: ${lat}, ${lng}`;
            
            setUserLocation(lat, lng);
            reverseGeocode(lat, lng);
        },
        (error) => {
            console.error('Manual location failed:', error);
            manualBtn.classList.remove('hidden');
            
            status.className = 'location-status error';
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Failed to get location. Please try again.';
        }
    );
}

// Proceed to order summary
async function proceedToOrderSummary() {
    const name = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();
    const address = document.getElementById('address').value.trim();
    const pinCode = localStorage.getItem('deliveryPinCode');
    const apartment = document.getElementById('apartment').value.trim();
    const landmark = document.getElementById('landmark').value.trim();
    const lat = document.getElementById('lat').value;
    const lng = document.getElementById('lng').value;

    if (!name || !phone || !email || !address || !pinCode) {
        alert('Please fill in all required fields');
        return;
    }

    if (!lat || !lng) {
        alert('Please allow location access to proceed');
        return;
    }

    // Store customer details
    customerDetails = {
        name, phone, email, address, pinCode, apartment, landmark, lat, lng
    };
    localStorage.setItem('customerDetails', JSON.stringify(customerDetails));

    // Get shipping estimate
    const shippingCost = await getShippingEstimate(customerDetails);
    if (shippingCost === null) return;

    localStorage.setItem('shippingCost', shippingCost);
    updateStep(3);
    calculateOrderSummary();
}

// Get shipping estimate from backend
async function getShippingEstimate(customer) {
    try {
        const response = await fetch('get_shipping_estimate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(customer)
        });

        const data = await response.json();
        if (data.success) {
            return parseFloat(data.estimate);
        } else {
            throw new Error(data.message || 'Failed to fetch shipping cost');
        }
    } catch (err) {
        console.error('Shipping estimate error:', err);
        alert('Unable to fetch shipping cost. Please try again.');
        return null;
    }
}

// Calculate and display order summary
function calculateOrderSummary() {
    loadCartFromStorage();
    
    if (!cart.length) {
        alert('Your cart is empty.');
        return;
    }

    const cartItems = document.getElementById('cartItems');
    cartItems.innerHTML = '';

    let subtotal = 0;
    cart.forEach(item => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.qty}</td>
            <td>₹${item.price.toFixed(2)}</td>
            <td>₹${itemTotal.toFixed(2)}</td>
        `;
        cartItems.appendChild(row);
    });

    const gst = subtotal * 0.18;
    const shipping = parseFloat(localStorage.getItem('shippingCost')) || 50;
    const total = subtotal + gst + shipping;

    document.getElementById('subtotal').textContent = `₹${subtotal.toFixed(2)}`;
    document.getElementById('gst').textContent = `₹${gst.toFixed(2)}`;
    document.getElementById('shipping').textContent = `₹${shipping.toFixed(2)}`;
    document.getElementById('total').textContent = `₹${total.toFixed(2)}`;
}

// Proceed to payment
function proceedToPayment() {
    updateStep(4);
}

// Initiate payment
function initiatePayment() {
    loadCartFromStorage();
    
    if (!cart.length) {
        alert('Your cart is empty.');
        return;
    }

    const orderData = {
        customer: customerDetails,
        items: cart.map(item => ({
            name: item.name,
            price: item.price,
            quantity: item.qty
        })),
        subtotal: parseFloat(document.getElementById('subtotal').textContent.replace('₹', '')),
        gst: parseFloat(document.getElementById('gst').textContent.replace('₹', '')),
        shipping: parseFloat(document.getElementById('shipping').textContent.replace('₹', '')),
        total: parseFloat(document.getElementById('total').textContent.replace('₹', '')),
        webhook_urls: {
            razorpay: window.location.origin + '/webhook.php',
            porter: window.location.origin + '/webhook.php'
        }
    };

    const options = {
        key: 'rzp_live_zEkVtK7a6pfmAT', // Replace with your Razorpay key
        amount: orderData.total * 100,
        currency: 'INR',
        name: 'Tripti Dry Ice',
        description: 'Payment for Dry Ice Products',
        image: 'images/logo.png',
        handler: function(response) {
            saveOrderToBackend(orderData, response.razorpay_payment_id);
        },
        prefill: {
            name: orderData.customer.name,
            email: orderData.customer.email,
            contact: orderData.customer.phone
        },
        theme: {
            color: '#007bff'
        }
    };

    // For development/testing
    const devMode = true;
    if (devMode) {
        const fakePaymentId = 'pay_DEV_FAKE_' + Date.now();
        saveOrderToBackend(orderData, fakePaymentId);
    } else {
        const rzp = new Razorpay(options);
        rzp.open();
    }
}

// Save order to backend and send to Porter
function saveOrderToBackend(orderData, paymentId) {
    // First try the main save_order.php
    fetch('save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...orderData, payment_id: paymentId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('porterOrderData', JSON.stringify(data));
            updateStep(5);
        } else if (data.porter_success === false) {
            // Porter failed, try fallback
            console.log('Porter API failed, trying fallback system...');
            return fetch('save_order_fallback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...orderData, payment_id: paymentId })
            });
        } else {
            throw new Error('Order save failed');
        }
    })
    .then(res => {
        if (res && res.ok) {
            return res.json();
        }
        return null;
    })
    .then(fallbackData => {
        if (fallbackData && fallbackData.success) {
            localStorage.setItem('porterOrderData', JSON.stringify(fallbackData));
            updateStep(5);
        } else {
            throw new Error('Both main and fallback systems failed');
        }
    })
    .catch(err => {
        console.error('Error saving order:', err);
        alert('An error occurred. Please try again later or contact customer support.');
    });
}

// Navigation functions
function goBackToPin() {
    updateStep(1);
}

function goBackToDetails() {
    updateStep(2);
}

function goBackToSummary() {
    updateStep(3);
} 