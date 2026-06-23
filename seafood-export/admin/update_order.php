<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$order_id = $_GET['id'] ?? 0;

// Get order details
$order_query = "SELECT o.*, u.name, u.email, u.phone, u.address 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = '$order_id'";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header('Location: view_orders.php');
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = '$order_id'";
$items_result = mysqli_query($conn, $items_query);

// Handle order update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'];
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    
    $update_query = "UPDATE orders SET 
                     order_status = '$order_status',
                     payment_status = '$payment_status',
                     delivery_date = " . ($delivery_date ? "'$delivery_date'" : "NULL") . "
                     WHERE id = '$order_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = "Order updated successfully!";
        header("Location: view_orders.php");
        exit();
    } else {
        $error = "Error updating order: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order - PMBJK Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header i {
            font-size: 3rem;
            color: #2ecc71;
            margin-bottom: 10px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        .sidebar-menu li {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .sidebar-menu li:hover, .sidebar-menu li.active {
            background: rgba(46, 204, 113, 0.2);
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: #f5f5f5;
        }
        .page-header {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2ecc71;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item label {
            font-weight: bold;
            color: #7f8c8d;
            display: block;
            margin-bottom: 5px;
        }
        .info-item p {
            color: #2c3e50;
            margin: 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #2ecc71;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .form-group select:focus,
        .form-group input:focus {
            border-color: #2ecc71;
            outline: none;
        }
        .btn-update {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        .btn-back {
            background: #95a5a6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #7f8c8d;
        }
        .total-amount {
            font-size: 1.5rem;
            color: #2ecc71;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #2ecc71;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-clinic-medical"></i>
                <h3>PMBJK Pharmacy</h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li>
                    <a href="manage_category.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li>
                    <a href="manage_product.php"><i class="fas fa-pills"></i> Products</a>
                </li>
                <li class="active">
                    <a href="view_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                </li>
                <li>
                    <a href="report.php"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-edit"></i> Update Order #<?php echo $order['order_number']; ?></h2>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="order-details">
                <!-- Left Column - Order Information -->
                <div>
                    <!-- Customer Information -->
                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Customer Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Name</label>
                                <p><?php echo $order['name']; ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo $order['email']; ?></p>
                            </div>
                            <div class="info-item">
                                <label>Phone</label>
                                <p><?php echo $order['phone']; ?></p>
                            </div>
                            <div class="info-item">
                                <label>Order Date</label>
                                <p><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="info-item" style="grid-column: span 2;">
                                <label>Shipping Address</label>
                                <p><?php echo nl2br($order['shipping_address']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="info-card">
                        <h3><i class="fas fa-box"></i> Order Items</h3>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while ($item = mysqli_fetch_assoc($items_result)): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                    <tr>
                                        <td><?php echo $item['product_name']; ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item_total, 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div class="total-amount">
                            Total Amount: ₹<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Update Form -->
                <div>
                    <div class="info-card">
                        <h3><i class="fas fa-edit"></i> Update Order Status</h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label>Order Status</label>
                                <select name="order_status" required>
                                    <option value="Pending" <?php echo $order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Confirmed" <?php echo $order['order_status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Processing" <?php echo $order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="Shipped" <?php echo $order['order_status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="Delivered" <?php echo $order['order_status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="Cancelled" <?php echo $order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select name="payment_status" required>
                                    <option value="Pending" <?php echo $order['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Completed" <?php echo $order['payment_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Failed" <?php echo $order['payment_status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Delivery Date (Optional)</label>
                                <input type="date" name="delivery_date" value="<?php echo $order['delivery_date']; ?>" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <a href="view_orders.php" class="btn-back">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                                <button type="submit" class="btn-update">
                                    <i class="fas fa-save"></i> Update Order
                                </button>
                            </div>
                        </form>
                        
                        <!-- Current Status Display -->
                        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                            <h4 style="margin-bottom: 15px; color: #2c3e50;">Current Status</h4>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div>
                                    <span style="color: #7f8c8d;">Order:</span>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>" style="margin-left: 5px;">
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </div>
                                <div>
                                    <span style="color: #7f8c8d;">Payment:</span>
                                    <span style="color: <?php echo $order['payment_status'] == 'Completed' ? '#2ecc71' : '#f39c12'; ?>;">
                                        <?php echo $order['payment_status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Timeline -->
                    <div class="info-card">
                        <h3><i class="fas fa-history"></i> Order Timeline</h3>
                        <div style="position: relative; padding-left: 30px;">
                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #2ecc71;"></div>
                            
                            <div style="position: relative; margin-bottom: 20px;">
                                <div style="position: absolute; left: -34px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #2ecc71; border: 2px solid white;"></div>
                                <p style="margin: 0;"><strong>Order Placed</strong></p>
                                <small style="color: #7f8c8d;"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></small>
                            </div>
                            
                            <?php if ($order['order_status'] != 'Pending'): ?>
                            <div style="position: relative; margin-bottom: 20px;">
                                <div style="position: absolute; left: -34px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #2ecc71; border: 2px solid white;"></div>
                                <p style="margin: 0;"><strong>Order Confirmed</strong></p>
                                <small style="color: #7f8c8d;">Status updated to <?php echo $order['order_status']; ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($order['delivery_date']): ?>
                            <div style="position: relative;">
                                <div style="position: absolute; left: -34px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #2ecc71; border: 2px solid white;"></div>
                                <p style="margin: 0;"><strong>Expected Delivery</strong></p>
                                <small style="color: #7f8c8d;"><?php echo date('d M Y', strtotime($order['delivery_date'])); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>