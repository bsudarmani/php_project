<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get date range from request
$report_type = $_GET['type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Build date condition
$date_condition = "DATE(order_date) BETWEEN '$start_date' AND '$end_date'";

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT user_id) as unique_customers,
                SUM(CASE WHEN payment_method = 'COD' THEN 1 ELSE 0 END) as cod_orders,
                SUM(CASE WHEN payment_method = 'Online' THEN 1 ELSE 0 END) as online_orders,
                SUM(CASE WHEN order_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                FROM orders 
                WHERE $date_condition";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get daily sales for chart
$daily_query = "SELECT 
                DATE(order_date) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
                FROM orders 
                WHERE $date_condition
                GROUP BY DATE(order_date)
                ORDER BY date DESC";
$daily_sales = mysqli_query($conn, $daily_query);

// Get top products
$products_query = "SELECT 
                   oi.product_name,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.price * oi.quantity) as total_revenue
                   FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   WHERE $date_condition
                   GROUP BY oi.product_id
                   ORDER BY total_quantity DESC
                   LIMIT 10";
$top_products = mysqli_query($conn, $products_query);

// Get category performance
$category_query = "SELECT 
                   c.name as category_name,
                   COUNT(DISTINCT oi.product_id) as products_sold,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.price * oi.quantity) as revenue
                   FROM categories c
                   LEFT JOIN products p ON c.id = p.category_id
                   LEFT JOIN order_items oi ON p.id = oi.product_id
                   LEFT JOIN orders o ON oi.order_id = o.id AND $date_condition
                   GROUP BY c.id
                   ORDER BY revenue DESC";
$category_stats = mysqli_query($conn, $category_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PMBJK Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .date-input {
            flex: 1;
            min-width: 200px;
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
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .stat-icon {
            font-size: 2rem;
            color: #bdc3c7;
            margin-bottom: 10px;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 400px;
        }
        .report-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2ecc71;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th {
            background: #2ecc71;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .report-table td {
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        .report-table tr:hover {
            background: #f8f9fa;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .btn-export {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-excel {
            background: #27ae60;
            color: white;
        }
        .btn-pdf {
            background: #e74c3c;
            color: white;
        }
        .btn-print {
            background: #3498db;
            color: white;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
                <li>
                    <a href="view_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                </li>
                <li class="active">
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
                <h2><i class="fas fa-chart-bar"></i> Sales Reports</h2>
            </div>
            
            <!-- Date Filter -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="date-input">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
                    </div>
                    <div class="date-input">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Export Buttons -->
            <div class="export-buttons">
                <button class="btn-export btn-excel" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button class="btn-export btn-pdf" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
                <button class="btn-export btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $stats['unique_customers'] ?? 0; ?></div>
                    <div class="stat-label">Unique Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-value">₹<?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-truck"></i></div>
                    <div class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['cancelled_orders'] ?? 0; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
            
            <!-- Sales Chart -->
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Top Products -->
                <div class="report-section">
                    <h3 class="section-title"><i class="fas fa-crown"></i> Top Selling Products</h3>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = mysqli_fetch_assoc($top_products)): ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td>₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Category Performance -->
                <div class="report-section">
                    <h3 class="section-title"><i class="fas fa-chart-pie"></i> Category Performance</h3>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($category = mysqli_fetch_assoc($category_stats)): ?>
                                <tr>
                                    <td><?php echo $category['category_name']; ?></td>
                                    <td><?php echo $category['total_quantity'] ?? 0; ?></td>
                                    <td>₹<?php echo number_format($category['revenue'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Payment Method Breakdown -->
            <div class="report-section">
                <h3 class="section-title"><i class="fas fa-credit-card"></i> Payment Method Analysis</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                    <div>
                        <h4 style="margin-bottom: 15px;">Cash on Delivery</h4>
                        <div style="font-size: 2rem; color: #2ecc71;"><?php echo $stats['cod_orders'] ?? 0; ?></div>
                        <p>orders</p>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 15px;">Online Payment</h4>
                        <div style="font-size: 2rem; color: #3498db;"><?php echo $stats['online_orders'] ?? 0; ?></div>
                        <p>orders</p>
                    </div>
                </div>
            </div>
            
            <!-- Daily Sales Table -->
            <div class="report-section">
                <h3 class="section-title"><i class="fas fa-calendar-alt"></i> Daily Sales Breakdown</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($daily_sales, 0);
                        while ($day = mysqli_fetch_assoc($daily_sales)): 
                        ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['order_count']; ?></td>
                                <td>₹<?php echo number_format($day['revenue'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Prepare data for chart
        const dates = [];
        const orders = [];
        const revenue = [];
        
        <?php 
        mysqli_data_seek($daily_sales, 0);
        while ($day = mysqli_fetch_assoc($daily_sales)): 
        ?>
            dates.unshift('<?php echo date('d M', strtotime($day['date'])); ?>');
            orders.unshift(<?php echo $day['order_count']; ?>);
            revenue.unshift(<?php echo $day['revenue']; ?>);
        <?php endwhile; ?>
        
        // Create sales chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Orders',
                    data: orders,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    yAxisID: 'y-orders'
                }, {
                    label: 'Revenue (₹)',
                    data: revenue,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    yAxisID: 'y-revenue'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Sales Performance'
                    }
                },
                scales: {
                    'y-orders': {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    },
                    'y-revenue': {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        function exportToExcel() {
            // Implement Excel export functionality
            window.location.href = 'export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
        }
        
        function exportToPDF() {
            // Implement PDF export functionality
            window.location.href = 'export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
        }
    </script>
</body>
</html>