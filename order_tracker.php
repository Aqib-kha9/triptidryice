<?php
require_once "db.php";

// Get order ID from URL parameter
$order_id = $_GET['order_id'] ?? null;
$action = $_GET['action'] ?? 'view';

// Handle actions
if ($action === 'list') {
    $orders = getAllOrders();
} elseif ($action === 'track' && $order_id) {
    $order = getOrderDetails($order_id);
    $tracking = getPorterTracking($order_id);
} else {
    $order = getOrderDetails($order_id);
    $items = getOrderItems($order_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracker - Tripti Dry Ice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tracking-timeline {
            position: relative;
            padding-left: 30px;
        }
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .tracking-step {
            position: relative;
            margin-bottom: 20px;
        }
        .tracking-step::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6c757d;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }
        .tracking-step.active::before {
            background: #007bff;
            box-shadow: 0 0 0 2px #007bff;
        }
        .tracking-step.completed::before {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a745;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if ($action === 'list'): ?>
            <!-- Order List -->
            <div class="row">
                <div class="col-12">
                    <h2><i class="fas fa-list me-2"></i>All Orders</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Payment Status</th>
                                    <th>Porter Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_id']) ?></strong></td>
                                    <td>
                                        <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_phone']) ?></small>
                                    </td>
                                    <td>₹<?= number_format($order['total'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $order['payment_status'] === 'Success' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($order['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getPorterStatusColor($order['porter_status']) ?>">
                                            <?= htmlspecialchars($order['porter_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="?order_id=<?= $order['order_id'] ?>&action=track" class="btn btn-sm btn-info">
                                            <i class="fas fa-truck"></i> Track
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($action === 'track' && $order_id): ?>
            <!-- Order Tracking -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-truck me-2"></i>Order Tracking</h4>
                            <p class="mb-0">Order ID: <strong><?= htmlspecialchars($order_id) ?></strong></p>
                        </div>
                        <div class="card-body">
                            <div class="tracking-timeline">
                                <?php if ($tracking): ?>
                                    <div class="tracking-step completed">
                                        <h6>Order Placed</h6>
                                        <p class="text-muted">Order has been successfully placed</p>
                                        <small><?= date('d M Y H:i', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    
                                    <div class="tracking-step <?= $tracking['status'] !== 'Pending' ? 'completed' : 'active' ?>">
                                        <h6>Porter Booking</h6>
                                        <p class="text-muted">Delivery partner assigned</p>
                                        <small><?= date('d M Y H:i', strtotime($tracking['created_at'])) ?></small>
                                    </div>
                                    
                                    <div class="tracking-step <?= in_array($tracking['status'], ['Picked', 'In Transit', 'Delivered']) ? 'completed' : '' ?>">
                                        <h6>Pickup</h6>
                                        <p class="text-muted">Package picked up from warehouse</p>
                                        <?php if ($tracking['estimated_pickup_time']): ?>
                                            <small>Estimated: <?= date('d M Y H:i', strtotime($tracking['estimated_pickup_time'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="tracking-step <?= in_array($tracking['status'], ['In Transit', 'Delivered']) ? 'completed' : '' ?>">
                                        <h6>In Transit</h6>
                                        <p class="text-muted">Package is on the way</p>
                                    </div>
                                    
                                    <div class="tracking-step <?= $tracking['status'] === 'Delivered' ? 'completed' : '' ?>">
                                        <h6>Delivered</h6>
                                        <p class="text-muted">Package delivered successfully</p>
                                        <?php if ($tracking['estimated_delivery_time']): ?>
                                            <small>Estimated: <?= date('d M Y H:i', strtotime($tracking['estimated_delivery_time'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Tracking information not available yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                            <p><strong>Total:</strong> ₹<?= number_format($order['total'], 2) ?></p>
                            <?php if ($tracking && $tracking['tracking_url']): ?>
                                <a href="<?= htmlspecialchars($tracking['tracking_url']) ?>" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>Track on Porter
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Order Details -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4><i class="fas fa-receipt me-2"></i>Order Details</h4>
                            <div>
                                <a href="?action=list" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-list me-1"></i>All Orders
                                </a>
                                <a href="?order_id=<?= $order_id ?>&action=track" class="btn btn-info btn-sm">
                                    <i class="fas fa-truck me-1"></i>Track Order
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($order): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Order Information</h6>
                                        <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                                        <p><strong>Payment ID:</strong> <?= htmlspecialchars($order['payment_id']) ?></p>
                                        <p><strong>Created:</strong> <?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-<?= $order['payment_status'] === 'Success' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars($order['payment_status']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Customer Information</h6>
                                        <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                                        <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                                        <p><strong>PIN Code:</strong> <?= htmlspecialchars($order['pin_code']) ?></p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h6>Order Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                <td>₹<?= number_format($item['item_price'], 2) ?></td>
                                                <td><?= $item['item_quantity'] ?></td>
                                                <td>₹<?= number_format($item['item_price'] * $item['item_quantity'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 offset-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Subtotal:</strong></td>
                                                <td>₹<?= number_format($order['subtotal'], 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>GST (18%):</strong></td>
                                                <td>₹<?= number_format($order['gst'], 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Shipping:</strong></td>
                                                <td>₹<?= number_format($order['shipping'], 2) ?></td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td><strong>Total:</strong></td>
                                                <td><strong>₹<?= number_format($order['total'], 2) ?></strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <?php if ($order['porter_order_id']): ?>
                                <hr>
                                <h6>Porter Information</h6>
                                <p><strong>Porter Order ID:</strong> <?= htmlspecialchars($order['porter_order_id']) ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?= getPorterStatusColor($order['porter_status']) ?>">
                                        <?= htmlspecialchars($order['porter_status']) ?>
                                    </span>
                                </p>
                                <?php if ($order['porter_tracking_url']): ?>
                                    <a href="<?= htmlspecialchars($order['porter_tracking_url']) ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt me-2"></i>Track on Porter
                                    </a>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Order not found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <a href="?action=list" class="btn btn-secondary w-100 mb-2">
                                <i class="fas fa-list me-2"></i>View All Orders
                            </a>
                            <?php if ($order_id): ?>
                            <a href="?order_id=<?= $order_id ?>&action=track" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-truck me-2"></i>Track Order
                            </a>
                            <button class="btn btn-success w-100 mb-2" onclick="printOrder()">
                                <i class="fas fa-print me-2"></i>Print Order
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>

<?php
// Helper functions
function getAllOrders() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(CONCAT(oi.item_name, ' x', oi.item_quantity) SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderDetails($order_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrderItems($order_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPorterTracking($order_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM porter_tracking WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPorterStatusColor($status) {
    switch ($status) {
        case 'Booked':
        case 'Picked':
            return 'info';
        case 'In Transit':
            return 'primary';
        case 'Delivered':
            return 'success';
        case 'Failed':
        case 'Cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?> 