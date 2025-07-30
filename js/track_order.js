// Track Order JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const trackingForm = document.getElementById('trackingForm');
    const orderIdInput = document.getElementById('orderId');
    const loading = document.getElementById('loading');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');
    const orderDetails = document.getElementById('orderDetails');

    // Handle form submission
    trackingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const orderId = orderIdInput.value.trim();
        
        if (!orderId) {
            showError('Please enter a valid Order ID');
            return;
        }

        trackOrder(orderId);
    });

    // Track order function
    async function trackOrder(orderId) {
        showLoading();
        hideMessages();

        try {
            const response = await fetch('get_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();

            if (data.success) {
                showOrderDetails(data.order);
                showSuccess('Order details loaded successfully!');
            } else {
                showError(data.message || 'Order not found. Please check your Order ID.');
            }
        } catch (error) {
            console.error('Error tracking order:', error);
            showError('Failed to fetch order details. Please try again.');
        } finally {
            hideLoading();
        }
    }

    // Show order details
    function showOrderDetails(order) {
        // Basic order information
        document.getElementById('displayOrderId').textContent = order.order_id;
        document.getElementById('customerName').textContent = order.customer_name;
        document.getElementById('customerPhone').textContent = order.customer_phone;
        document.getElementById('customerEmail').textContent = order.customer_email;
        document.getElementById('totalAmount').textContent = `₹${order.total}`;
        document.getElementById('paymentStatus').textContent = order.payment_status;
        document.getElementById('orderDate').textContent = formatDate(order.created_at);
        document.getElementById('deliveryAddress').textContent = order.customer_address;

        // Order items
        displayOrderItems(order.items);

        // Tracking timeline
        displayTrackingTimeline(order);

        // Porter tracking (if available)
        if (order.porter_order_id) {
            displayPorterTracking(order);
        }

        orderDetails.style.display = 'block';
    }

    // Display order items
    function displayOrderItems(items) {
        const orderItemsContainer = document.getElementById('orderItems');
        let html = '<table class="table table-striped">';
        html += '<thead><tr><th>Item</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>';
        
        items.forEach(item => {
            const itemTotal = item.item_price * item.item_quantity;
            html += `<tr>
                <td>${item.item_name}</td>
                <td>${item.item_quantity}</td>
                <td>₹${item.item_price}</td>
                <td>₹${itemTotal.toFixed(2)}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        orderItemsContainer.innerHTML = html;
    }

    // Display tracking timeline
    function displayTrackingTimeline(order) {
        const timelineContainer = document.getElementById('trackingTimeline');
        const timeline = generateTimeline(order);
        
        let html = '';
        timeline.forEach((item, index) => {
            const isCompleted = index < order.current_step;
            const isCurrent = index === order.current_step;
            
            html += `<div class="timeline-item ${isCompleted ? 'completed' : ''} ${isCurrent ? 'current' : ''}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${item.title}</h6>
                        <p class="mb-0 text-muted">${item.description}</p>
                    </div>
                    <span class="status-badge status-${item.status}">${item.status}</span>
                </div>
                <small class="text-muted">${item.time}</small>
            </div>`;
        });
        
        timelineContainer.innerHTML = html;
    }

    // Generate timeline based on order status
    function generateTimeline(order) {
        const timeline = [
            {
                title: 'Order Placed',
                description: 'Your order has been successfully placed',
                status: 'completed',
                time: formatDate(order.created_at)
            },
            {
                title: 'Payment Confirmed',
                description: 'Payment has been processed successfully',
                status: order.payment_status === 'Success' ? 'completed' : 'pending',
                time: formatDate(order.created_at)
            },
            {
                title: 'Order Confirmed',
                description: 'We have confirmed your order and preparing for delivery',
                status: 'completed',
                time: formatDate(order.created_at)
            }
        ];

        // Add Porter-specific timeline items
        if (order.porter_order_id) {
            timeline.push({
                title: 'Porter Booking',
                description: 'Delivery partner has been assigned',
                status: 'completed',
                time: formatDate(order.created_at)
            });

            if (order.porter_status) {
                timeline.push({
                    title: 'Out for Delivery',
                    description: 'Your order is on its way to you',
                    status: order.porter_status === 'Delivered' ? 'completed' : 'current',
                    time: order.estimated_delivery_time ? formatDate(order.estimated_delivery_time) : 'In Progress'
                });
            }

            if (order.porter_status === 'Delivered') {
                timeline.push({
                    title: 'Delivered',
                    description: 'Your order has been successfully delivered',
                    status: 'completed',
                    time: formatDate(order.updated_at || order.created_at)
                });
            }
        } else {
            // Manual delivery timeline
            timeline.push({
                title: 'Manual Delivery',
                description: 'Our team will contact you for delivery arrangement',
                status: 'current',
                time: 'In Progress'
            });
        }

        return timeline;
    }

    // Display Porter tracking details
    function displayPorterTracking(order) {
        document.getElementById('porterOrderId').textContent = order.porter_order_id;
        document.getElementById('deliveryCharge').textContent = order.delivery_charge ? `₹${order.delivery_charge}` : 'N/A';
        document.getElementById('estimatedPickup').textContent = order.estimated_pickup_time ? formatDate(order.estimated_pickup_time) : 'N/A';
        document.getElementById('estimatedDelivery').textContent = order.estimated_delivery_time ? formatDate(order.estimated_delivery_time) : 'N/A';
        document.getElementById('porterStatus').textContent = order.porter_status || 'Pending';
        
        if (order.porter_tracking_url) {
            const trackingLink = document.getElementById('trackingUrl');
            trackingLink.href = order.porter_tracking_url;
            trackingLink.textContent = 'View on Porter';
        }

        document.getElementById('porterTracking').style.display = 'block';
    }

    // Utility functions
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showLoading() {
        loading.style.display = 'block';
        orderDetails.style.display = 'none';
    }

    function hideLoading() {
        loading.style.display = 'none';
    }

    function showError(message) {
        errorMessage.style.display = 'block';
        document.getElementById('errorText').textContent = message;
        orderDetails.style.display = 'none';
    }

    function showSuccess(message) {
        successMessage.style.display = 'block';
        document.getElementById('successText').textContent = message;
    }

    function hideMessages() {
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
    }

    // Auto-focus on order ID input
    orderIdInput.focus();
}); 