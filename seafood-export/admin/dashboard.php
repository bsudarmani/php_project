<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get statistics
$total_species = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM fish_species"))['count'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total_inr) as total FROM orders WHERE order_status='Delivered'"))['total'] ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE order_status='Pending'"))['count'];
$total_inventory = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock_kg) as total FROM products"))['total'] ?? 0;
$export_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE export_destination_id != 7"))['count'];

// Get recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, u.company_name, ed.country as destination 
                                      FROM orders o 
                                      LEFT JOIN users u ON o.user_id = u.id 
                                      LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id 
                                      ORDER BY o.order_date DESC LIMIT 10");

// Get low stock products
$low_stock = mysqli_query($conn, "SELECT * FROM products WHERE stock_kg < 100 ORDER BY stock_kg ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SeaFood Export</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .welcome-text h2 {
            color: #0a3147;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stat-info h3 {
            font-size: 2rem;
            margin: 0;
            color: #00d4ff;
        }
        
        .stat-info p {
            margin: 5px 0 0;
            color: #7f8c8d;
        }
        
        .stat-icon i {
            font-size: 3rem;
            color: #0a3147;
            opacity: 0.3;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #0a3147;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-warning { background: #f39c12; color: white; }
        .badge-success { background: #2ecc71; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        
        .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .stock-bar {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .stock-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #0077be);
            border-radius: 4px;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <div class="welcome-text">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                <p>Welcome back, <?php echo $_SESSION['admin_name']; ?>!</p>
            </div>
            <div>
                <span class="badge badge-info"><?php echo date('d M Y, h:i A'); ?></span>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_species; ?></h3>
                    <p>Fish Species</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-fish"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Products</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo number_format($total_inventory); ?>kg</h3>
                    <p>Total Inventory</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-weight"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-ship"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $export_orders; ?></h3>
                    <p>Export Orders</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-history"></i> Recent Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Destination</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): 
                            $status_class = '';
                            switch($order['order_status']) {
                                case 'Pending': $status_class = 'badge-warning'; break;
                                case 'Confirmed': $status_class = 'badge-info'; break;
                                case 'Shipped': $status_class = 'badge-info'; break;
                                case 'Delivered': $status_class = 'badge-success'; break;
                                case 'Cancelled': $status_class = 'badge-danger'; break;
                                default: $status_class = 'badge-warning';
                            }
                        ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo $order['company_name']; ?></td>
                                <td><?php echo $order['destination'] ?? 'Domestic'; ?></td>
                                <td>₹<?php echo number_format($order['grand_total_inr'], 2); ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="view_orders.php" class="btn" style="background: #00d4ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View All Orders</a>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle" style="color: #ff4757;"></i> Low Stock Alert</h3>
                <?php if (mysqli_num_rows($low_stock) > 0): ?>
                    <?php while ($product = mysqli_fetch_assoc($low_stock)): 
                        $percentage = ($product['stock_kg'] / 1000) * 100;
                    ?>
                        <div class="low-stock-item">
                            <div style="flex: 1;">
                                <strong><?php echo $product['name']; ?></strong>
                                <div class="stock-bar">
                                    <div class="stock-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                                </div>
                                <small><?php echo $product['stock_kg']; ?> kg remaining</small>
                            </div>
                            <span class="badge badge-danger">Critical</span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No low stock items</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>