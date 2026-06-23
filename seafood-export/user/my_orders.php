<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all orders
$orders_query = "SELECT o.*, ed.country as destination, ed.currency_symbol,
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                 FROM orders o 
                 LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id 
                 WHERE o.user_id = '$user_id' 
                 ORDER BY o.order_date DESC";
$orders = mysqli_query($conn, $orders_query);
?>

<?php include '../includes/header.php'; ?>

<style>
    .orders-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .page-title {
        color: #0a3147;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .page-title i {
        color: #00d4ff;
        font-size: 2rem;
    }
    
    .orders-grid {
        display: grid;
        gap: 20px;
    }
    
    .order-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    
    .order-header {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .order-number {
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .order-date {
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    .order-status {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .status-pending { background: #f39c12; color: white; }
    .status-confirmed { background: #3498db; color: white; }
    .status-processing { background: #9b59b6; color: white; }
    .status-shipped { background: #3498db; color: white; }
    .status-delivered { background: #2ecc71; color: white; }
    .status-cancelled { background: #e74c3c; color: white; }
    
    .order-body {
        padding: 20px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .order-items {
        border-right: 1px solid #ecf0f1;
        padding-right: 20px;
    }
    
    .order-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .order-item:last-child {
        border-bottom: none;
    }
    
    .item-name {
        font-weight: 500;
        color: #0a3147;
    }
    
    .item-quantity {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    .item-price {
        color: #00d4ff;
        font-weight: 500;
    }
    
    .order-details {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
    }
    
    .detail-label {
        color: #7f8c8d;
    }
    
    .detail-value {
        font-weight: 500;
        color: #0a3147;
    }
    
    .order-footer {
        background: #f8f9fa;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .total-amount {
        font-size: 1.3rem;
        font-weight: bold;
        color: #00d4ff;
    }
    
    .order-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #00d4ff;
        color: white;
    }
    
    .btn-secondary {
        background: #0a3147;
        color: white;
    }
    
    .btn-outline {
        background: transparent;
        border: 2px solid #00d4ff;
        color: #00d4ff;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .empty-orders {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .empty-orders i {
        font-size: 5rem;
        color: #bdc3c7;
        margin-bottom: 20px;
    }
    
    .empty-orders h3 {
        color: #7f8c8d;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .order-body {
            grid-template-columns: 1fr;
        }
        
        .order-items {
            border-right: none;
            padding-right: 0;
        }
        
        .order-footer {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="orders-container">
    <h1 class="page-title">
        <i class="fas fa-box"></i>
        My Orders
    </h1>
    
    <?php if (mysqli_num_rows($orders) > 0): ?>
        <div class="orders-grid">
            <?php while ($order = mysqli_fetch_assoc($orders)): 
                // Get order items
                $items_query = "SELECT * FROM order_items WHERE order_id = '{$order['id']}'";
                $items = mysqli_query($conn, $items_query);
                
                $status_class = '';
                switch($order['order_status']) {
                    case 'Pending': $status_class = 'status-pending'; break;
                    case 'Confirmed': $status_class = 'status-confirmed'; break;
                    case 'Processing': $status_class = 'status-processing'; break;
                    case 'Shipped': $status_class = 'status-shipped'; break;
                    case 'Delivered': $status_class = 'status-delivered'; break;
                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                }
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-number"><?php echo $order['order_number']; ?></span>
                            <span class="order-date"> | <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
                        </div>
                        <span class="order-status <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-items">
                            <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <div class="order-item">
                                    <div>
                                        <span class="item-name"><?php echo $item['product_name']; ?></span>
                                        <span class="item-quantity"> (<?php echo $item['quantity_kg']; ?> kg)</span>
                                    </div>
                                    <span class="item-price">₹<?php echo number_format($item['total_price'], 2); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Destination:</span>
                                <span class="detail-value"><?php echo $order['destination'] ?? 'Domestic'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Shipping Terms:</span>
                                <span class="detail-value"><?php echo $order['shipping_terms']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Method:</span>
                                <span class="detail-value"><?php echo $order['payment_method']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Status:</span>
                                <span class="detail-value"><?php echo $order['payment_status']; ?></span>
                            </div>
                            <?php if ($order['tracking_number']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Tracking:</span>
                                    <span class="detail-value"><?php echo $order['tracking_number']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div>
                            <span style="color: #7f8c8d;">Total Amount: </span>
                            <span class="total-amount">₹<?php echo number_format($order['grand_total_inr'], 2); ?></span>
                            <?php if ($order['currency'] != 'INR'): ?>
                                <span style="color: #7f8c8d; font-size: 0.9rem;">
                                    (<?php echo $order['currency_symbol'] . number_format($order['total_amount_foreign'], 2); ?> <?php echo $order['currency']; ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-actions">
                            <a href="order_receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-file-invoice"></i> Receipt
                            </a>
                            
                            <?php if ($order['order_status'] == 'Shipped'): ?>
                                <a href="track_shipment.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-ship"></i> Track
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($order['order_status'] == 'Pending'): ?>
                                <a href="cancel_order.php?id=<?php echo $order['id']; ?>" class="btn btn-outline" onclick="return confirm('Cancel this order?')">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($order['order_status'] == 'Delivered'): ?>
                                <a href="#" class="btn btn-outline" onclick="reorder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-redo"></i> Reorder
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
    <?php else: ?>
        <div class="empty-orders">
            <i class="fas fa-box-open"></i>
            <h3>No orders yet</h3>
            <p>Start exploring our products and place your first order!</p>
            <a href="products.php" class="btn btn-primary" style="padding: 12px 30px;">
                <i class="fas fa-fish"></i> Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function reorder(orderId) {
    fetch('reorder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Items added to cart!', 'success');
            setTimeout(() => window.location.href = 'cart.php', 1000);
        } else {
            showNotification(data.message || 'Failed to reorder', 'error');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>