<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$destination = isset($_GET['destination']) ? (int)$_GET['destination'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where = ["1=1"];
if ($status) $where[] = "o.order_status = '$status'";
if ($destination) $where[] = "o.export_destination_id = '$destination'";
if ($date_from) $where[] = "DATE(o.order_date) >= '$date_from'";
if ($date_to) $where[] = "DATE(o.order_date) <= '$date_to'";

$where_clause = implode(" AND ", $where);

// Get orders
$orders = mysqli_query($conn, "SELECT o.*, u.company_name, u.contact_person, u.email, u.phone,
                               ed.country as destination_country, ed.currency_symbol
                               FROM orders o
                               JOIN users u ON o.user_id = u.id
                               LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id
                               WHERE $where_clause
                               ORDER BY o.order_date DESC");

// Get destinations for filter
$destinations = mysqli_query($conn, "SELECT * FROM export_destinations ORDER BY country");

// Get order statistics
$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN order_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN order_status = 'Processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN order_status = 'Shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(grand_total_inr) as total_revenue
    FROM orders"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - SeaFood Export</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }
        
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 250px; padding: 30px; }
        
        .page-header {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .stat-label {
            color: #7f8c8d;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #7f8c8d;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }
        
        .btn-filter {
            background: #00d4ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
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
        
        .btn-view {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .export-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-print {
            background: #3498db;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-ship"></i> Export Orders</h2>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['shipped']; ?></div>
                <div class="stat-label">Shipped</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['delivered']; ?></div>
                <div class="stat-label">Delivered</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $status == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Processing" <?php echo $status == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo $status == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Delivered" <?php echo $status == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Destination</label>
                    <select name="destination">
                        <option value="">All</option>
                        <?php while ($d = mysqli_fetch_assoc($destinations)): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $destination == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo $d['country']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <button type="submit" class="btn-filter">Apply Filters</button>
                <a href="view_orders.php" class="btn-filter" style="background: #95a5a6; text-decoration: none; text-align: center;">Reset</a>
            </form>
        </div>
        
        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Buyer</th>
                        <th>Destination</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($orders)): 
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
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php echo $order['company_name']; ?><br>
                                    <small><?php echo $order['contact_person']; ?></small>
                                </td>
                                <td><?php echo $order['destination_country'] ?? 'Domestic'; ?></td>
                                <td>
                                    ₹<?php echo number_format($order['grand_total_inr'], 2); ?><br>
                                    <?php if ($order['currency'] != 'INR'): ?>
                                        <small><?php echo $order['currency_symbol'] . number_format($order['total_amount_foreign'], 2); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $order['order_status']; ?></span></td>
                                <td>
                                    <span style="color: <?php echo $order['payment_status'] == 'Completed' ? '#2ecc71' : '#f39c12'; ?>;">
                                        <?php echo $order['payment_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="update_order.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="export_excel.php?<?php echo http_build_query($_GET); ?>" class="btn-export">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="export_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn-export" style="background: #e74c3c;">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="print_orders.php?<?php echo http_build_query($_GET); ?>" class="btn-export btn-print" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
        </div>
    </div>
</body>
</html>