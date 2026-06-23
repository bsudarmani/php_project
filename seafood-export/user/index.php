<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN order_status = 'Processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN order_status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(grand_total_inr) as total_spent
                FROM orders 
                WHERE user_id = '$user_id'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, ed.country as destination 
                                      FROM orders o 
                                      LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id 
                                      WHERE o.user_id = '$user_id' 
                                      ORDER BY o.order_date DESC LIMIT 5");

// Get cart count
$cart_count = getCartTotalKg($user_id);

// Get unread notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
?>

<?php include '../includes/header.php'; ?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 40px;
        display: flex;
        align-items: center;
        gap: 30px;
        flex-wrap: wrap;
    }
    
    .user-avatar {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #0a3147;
    }
    
    .welcome-text h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
    }
    
    .welcome-text p {
        opacity: 0.9;
        margin-bottom: 5px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .stat-icon {
        font-size: 2.5rem;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #7f8c8d;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .card-title {
        color: #0a3147;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #00d4ff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .order-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .order-table th {
        background: #0a3147;
        color: white;
        padding: 12px;
        text-align: left;
    }
    
    .order-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }
    
    .status-pending { background: #f39c12; color: white; }
    .status-confirmed { background: #3498db; color: white; }
    .status-processing { background: #9b59b6; color: white; }
    .status-shipped { background: #3498db; color: white; }
    .status-delivered { background: #2ecc71; color: white; }
    .status-cancelled { background: #e74c3c; color: white; }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.9rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 2px;
    }
    
    .btn-primary { background: #00d4ff; color: white; }
    .btn-secondary { background: #0a3147; color: white; }
    
    .notification-item {
        padding: 15px;
        border-bottom: 1px solid #ecf0f1;
        transition: all 0.3s ease;
    }
    
    .notification-item:hover {
        background: #f8f9fa;
    }
    
    .notification-title {
        font-weight: 600;
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .notification-time {
        font-size: 0.8rem;
        color: #7f8c8d;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 20px;
    }
    
    .quick-action {
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .quick-action:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,212,255,0.3);
    }
    
    .quick-action i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .order-table {
            font-size: 0.9rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="user-avatar">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="welcome-text">
            <h1>Welcome back, <?php echo htmlspecialchars($user['contact_person']); ?>!</h1>
            <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($user['company_name']); ?></p>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-ship"></i></div>
            <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-value">₹<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
            <div class="stat-label">Total Spent</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-weight"></i></div>
            <div class="stat-value"><?php echo $cart_count; ?> kg</div>
            <div class="stat-label">In Cart</div>
        </div>
    </div>
    
    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Recent Orders -->
        <div class="card">
            <h3 class="card-title"><i class="fas fa-history"></i> Recent Orders</h3>
            
            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Destination</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): 
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
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['destination'] ?? 'Domestic'; ?></td>
                                <td>₹<?php echo number_format($order['grand_total_inr'], 2); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span></td>
                                <td>
                                    <a href="order_receipt.php?id=<?php echo $order['id']; ?>" class="btn-sm btn-primary">View</a>
                                    <?php if ($order['order_status'] == 'Shipped'): ?>
                                        <a href="track_shipment.php?id=<?php echo $order['id']; ?>" class="btn-sm btn-secondary">Track</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if ($stats['total_orders'] > 5): ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="my_orders.php" class="btn-sm btn-primary">View All Orders</a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 40px;">No orders yet. Start shopping!</p>
            <?php endif; ?>
        </div>
        
        <!-- Notifications & Quick Actions -->
        <div>
            <!-- Notifications -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-bell"></i> Notifications</h3>
                
                <?php if (mysqli_num_rows($notifications) > 0): ?>
                    <?php while ($notif = mysqli_fetch_assoc($notifications)): ?>
                        <div class="notification-item">
                            <div class="notification-title">
                                <i class="fas fa-info-circle" style="color: #00d4ff;"></i>
                                <?php echo $notif['title']; ?>
                            </div>
                            <p style="color: #7f8c8d; margin: 5px 0;"><?php echo $notif['message']; ?></p>
                            <div class="notification-time">
                                <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="#" onclick="markAllRead()" style="color: #00d4ff; text-decoration: none;">Mark all as read</a>
                    </div>
                    
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No new notifications</p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                
                <div class="quick-actions">
                    <a href="products.php" class="quick-action">
                        <i class="fas fa-fish"></i>
                        <h4>Browse Products</h4>
                    </a>
                    <a href="cart.php" class="quick-action">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>View Cart</h4>
                    </a>
                    <a href="my_orders.php" class="quick-action">
                        <i class="fas fa-box"></i>
                        <h4>My Orders</h4>
                    </a>
                    <a href="profile.php" class="quick-action">
                        <i class="fas fa-user-cog"></i>
                        <h4>Profile</h4>
                    </a>
                </div>
            </div>
            
            <!-- Company Info -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-building"></i> Company Details</h3>
                
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px; color: #7f8c8d;">GST Number:</td>
                        <td style="padding: 8px;"><strong><?php echo $user['gst_number'] ?? 'Not provided'; ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #7f8c8d;">Import License:</td>
                        <td style="padding: 8px;"><strong><?php echo $user['import_license'] ?? 'Not provided'; ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #7f8c8d;">Business Type:</td>
                        <td style="padding: 8px;"><strong><?php echo $user['business_type']; ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #7f8c8d;">Member Since:</td>
                        <td style="padding: 8px;"><strong><?php echo date('d M Y', strtotime($user['created_at'])); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function markAllRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>