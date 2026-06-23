<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get filter parameters
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'orders';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="pmbjk_export_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Create Excel file
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'th { background-color: #2ecc71; color: white; font-weight: bold; padding: 8px; }';
echo 'td { padding: 6px; border: 1px solid #ddd; }';
echo '.pending { background-color: #f39c12; color: white; }';
echo '.confirmed { background-color: #3498db; color: white; }';
echo '.processing { background-color: #9b59b6; color: white; }';
echo '.shipped { background-color: #3498db; color: white; }';
echo '.delivered { background-color: #2ecc71; color: white; }';
echo '.cancelled { background-color: #e74c3c; color: white; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Company Header
echo '<table width="100%" style="border: none; margin-bottom: 20px;">';
echo '<tr>';
echo '<td align="center" style="border: none;">';
echo '<h1 style="color: #2ecc71; margin: 0;">PMBJK Pharmacy</h1>';
echo '<h3 style="color: #34495e; margin: 5px 0;">Pradhan Mantri Bhartiya Janaushadhi Kendra</h3>';
echo '<p style="color: #7f8c8d;">Export Date: ' . date('d M Y, h:i A') . '</p>';
echo '</td>';
echo '</tr>';
echo '</table>';

if ($report_type == 'orders') {
    // Export Orders
    exportOrders($conn, $status, $date_from, $date_to);
} elseif ($report_type == 'products') {
    // Export Products
    exportProducts($conn);
} elseif ($report_type == 'customers') {
    // Export Customers
    exportCustomers($conn);
} elseif ($report_type == 'inventory') {
    // Export Inventory
    exportInventory($conn);
} elseif ($report_type == 'sales') {
    // Export Sales Report
    exportSalesReport($conn, $date_from, $date_to);
}

echo '</body>';
echo '</html>';

// Function to export orders
function exportOrders($conn, $status, $date_from, $date_to) {
    // Build query
    $where_conditions = [];
    
    if (!empty($status)) {
        $where_conditions[] = "order_status = '$status'";
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(order_date) >= '$date_from'";
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(order_date) <= '$date_to'";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $query = "SELECT o.*, u.name, u.email, u.phone, u.address 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.order_date DESC";
    $result = mysqli_query($conn, $query);
    
    // Title
    echo '<h2>Orders Report</h2>';
    
    // Summary
    $total_orders = mysqli_num_rows($result);
    $total_amount = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_amount += $row['total_amount'];
    }
    mysqli_data_seek($result, 0);
    
    echo '<table width="100%" style="margin-bottom: 20px; background: #f8f9fa;">';
    echo '<tr><td><strong>Total Orders:</strong> ' . $total_orders . '</td>';
    echo '<td><strong>Total Amount:</strong> ₹' . number_format($total_amount, 2) . '</td></tr>';
    echo '</table>';
    
    // Orders Table
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Order #</th>';
    echo '<th>Date</th>';
    echo '<th>Customer</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Amount</th>';
    echo '<th>Payment</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($order = mysqli_fetch_assoc($result)) {
        $status_class = strtolower($order['order_status']);
        echo '<tr>';
        echo '<td>' . $order['order_number'] . '</td>';
        echo '<td>' . date('d M Y', strtotime($order['order_date'])) . '</td>';
        echo '<td>' . $order['name'] . '</td>';
        echo '<td>' . $order['email'] . '</td>';
        echo '<td>' . $order['phone'] . '</td>';
        echo '<td align="right">₹' . number_format($order['total_amount'], 2) . '</td>';
        echo '<td>' . $order['payment_method'] . ' (' . $order['payment_status'] . ')</td>';
        echo '<td class="' . $status_class . '">' . $order['order_status'] . '</td>';
        echo '</tr>';
        
        // Get order items
        $items_query = "SELECT * FROM order_items WHERE order_id = '{$order['id']}'";
        $items_result = mysqli_query($conn, $items_query);
        
        if (mysqli_num_rows($items_result) > 0) {
            echo '<tr>';
            echo '<td colspan="8">';
            echo '<table width="100%" style="margin: 5px 0; background: #f5f5f5;">';
            echo '<tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>';
            while ($item = mysqli_fetch_assoc($items_result)) {
                echo '<tr>';
                echo '<td>' . $item['product_name'] . '</td>';
                echo '<td align="right">₹' . number_format($item['price'], 2) . '</td>';
                echo '<td align="center">' . $item['quantity'] . '</td>';
                echo '<td align="right">₹' . number_format($item['price'] * $item['quantity'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Function to export products
function exportProducts($conn) {
    $query = "SELECT p.*, c.name as category_name,
              (SELECT COUNT(*) FROM order_items WHERE product_id = p.id) as times_sold,
              (SELECT SUM(quantity) FROM order_items WHERE product_id = p.id) as total_sold
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.id DESC";
    $result = mysqli_query($conn, $query);
    
    echo '<h2>Products Report</h2>';
    
    // Summary
    $total_products = mysqli_num_rows($result);
    $total_value = 0;
    $total_stock = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_value += $row['price'] * $row['stock'];
        $total_stock += $row['stock'];
    }
    mysqli_data_seek($result, 0);
    
    echo '<table width="100%" style="margin-bottom: 20px; background: #f8f9fa;">';
    echo '<tr><td><strong>Total Products:</strong> ' . $total_products . '</td>';
    echo '<td><strong>Total Stock:</strong> ' . $total_stock . ' units</td>';
    echo '<td><strong>Inventory Value:</strong> ₹' . number_format($total_value, 2) . '</td></tr>';
    echo '</table>';
    
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Category</th>';
    echo '<th>Price</th>';
    echo '<th>Stock</th>';
    echo '<th>Times Sold</th>';
    echo '<th>Total Sold</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($product = mysqli_fetch_assoc($result)) {
        $status = $product['stock'] > 0 ? 'In Stock' : 'Out of Stock';
        $status_color = $product['stock'] > 0 ? '#2ecc71' : '#e74c3c';
        
        echo '<tr>';
        echo '<td>' . $product['id'] . '</td>';
        echo '<td>' . $product['name'] . '</td>';
        echo '<td>' . $product['category_name'] . '</td>';
        echo '<td align="right">₹' . number_format($product['price'], 2) . '</td>';
        echo '<td align="center">' . $product['stock'] . '</td>';
        echo '<td align="center">' . ($product['times_sold'] ?? 0) . '</td>';
        echo '<td align="center">' . ($product['total_sold'] ?? 0) . '</td>';
        echo '<td style="color: ' . $status_color . ';">' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Function to export customers
function exportCustomers($conn) {
    $query = "SELECT u.*, 
              COUNT(o.id) as total_orders,
              SUM(o.total_amount) as total_spent,
              MAX(o.order_date) as last_order
              FROM users u
              LEFT JOIN orders o ON u.id = o.user_id
              GROUP BY u.id
              ORDER BY u.id DESC";
    $result = mysqli_query($conn, $query);
    
    echo '<h2>Customers Report</h2>';
    
    // Summary
    $total_customers = mysqli_num_rows($result);
    $total_spent_all = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_spent_all += $row['total_spent'] ?? 0;
    }
    mysqli_data_seek($result, 0);
    
    echo '<table width="100%" style="margin-bottom: 20px; background: #f8f9fa;">';
    echo '<tr><td><strong>Total Customers:</strong> ' . $total_customers . '</td>';
    echo '<td><strong>Total Revenue:</strong> ₹' . number_format($total_spent_all, 2) . '</td>';
    echo '<td><strong>Avg per Customer:</strong> ₹' . number_format($total_spent_all / max($total_customers, 1), 2) . '</td></tr>';
    echo '</table>';
    
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Name</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Address</th>';
    echo '<th>Orders</th>';
    echo '<th>Total Spent</th>';
    echo '<th>Last Order</th>';
    echo '<th>Joined</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($user = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . $user['name'] . '</td>';
        echo '<td>' . $user['email'] . '</td>';
        echo '<td>' . $user['phone'] . '</td>';
        echo '<td>' . $user['address'] . '</td>';
        echo '<td align="center">' . ($user['total_orders'] ?? 0) . '</td>';
        echo '<td align="right">₹' . number_format($user['total_spent'] ?? 0, 2) . '</td>';
        echo '<td>' . ($user['last_order'] ? date('d M Y', strtotime($user['last_order'])) : 'Never') . '</td>';
        echo '<td>' . date('d M Y', strtotime($user['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Function to export inventory
function exportInventory($conn) {
    $query = "SELECT p.*, c.name as category_name,
              CASE 
                  WHEN p.stock = 0 THEN 'Out of Stock'
                  WHEN p.stock <= 10 THEN 'Critical Low'
                  WHEN p.stock <= 50 THEN 'Low Stock'
                  WHEN p.stock <= 100 THEN 'Moderate'
                  ELSE 'Sufficient'
              END as stock_status
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY 
                  CASE 
                      WHEN p.stock = 0 THEN 1
                      WHEN p.stock <= 10 THEN 2
                      WHEN p.stock <= 50 THEN 3
                      ELSE 4
                  END, p.stock ASC";
    $result = mysqli_query($conn, $query);
    
    echo '<h2>Inventory Report</h2>';
    
    // Summary
    $total_products = mysqli_num_rows($result);
    $total_stock = 0;
    $low_stock = 0;
    $out_of_stock = 0;
    $total_value = 0;
    
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_stock += $row['stock'];
        $total_value += $row['price'] * $row['stock'];
        if ($row['stock'] == 0) $out_of_stock++;
        if ($row['stock'] > 0 && $row['stock'] <= 10) $low_stock++;
    }
    mysqli_data_seek($result, 0);
    
    echo '<table width="100%" style="margin-bottom: 20px; background: #f8f9fa;">';
    echo '<tr>';
    echo '<td><strong>Total Products:</strong> ' . $total_products . '</td>';
    echo '<td><strong>Total Stock:</strong> ' . $total_stock . ' units</td>';
    echo '<td><strong>Inventory Value:</strong> ₹' . number_format($total_value, 2) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Out of Stock:</strong> ' . $out_of_stock . '</td>';
    echo '<td><strong>Low Stock (≤10):</strong> ' . $low_stock . '</td>';
    echo '<td><strong>Healthy Stock:</strong> ' . ($total_products - $out_of_stock - $low_stock) . '</td>';
    echo '</tr>';
    echo '</table>';
    
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Product</th>';
    echo '<th>Category</th>';
    echo '<th>Price</th>';
    echo '<th>Stock</th>';
    echo '<th>Status</th>';
    echo '<th>Action Needed</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($product = mysqli_fetch_assoc($result)) {
        $status_color = '';
        $action = '';
        
        switch($product['stock_status']) {
            case 'Out of Stock':
                $status_color = '#e74c3c';
                $action = 'URGENT: Reorder immediately';
                break;
            case 'Critical Low':
                $status_color = '#e67e22';
                $action = 'Place order soon';
                break;
            case 'Low Stock':
                $status_color = '#f39c12';
                $action = 'Monitor and reorder';
                break;
            case 'Moderate':
                $status_color = '#3498db';
                $action = 'Stock is adequate';
                break;
            default:
                $status_color = '#2ecc71';
                $action = 'Stock is sufficient';
        }
        
        echo '<tr>';
        echo '<td>' . $product['id'] . '</td>';
        echo '<td>' . $product['name'] . '</td>';
        echo '<td>' . $product['category_name'] . '</td>';
        echo '<td align="right">₹' . number_format($product['price'], 2) . '</td>';
        echo '<td align="center" style="font-weight: bold; color: ' . $status_color . ';">' . $product['stock'] . '</td>';
        echo '<td style="color: ' . $status_color . ';">' . $product['stock_status'] . '</td>';
        echo '<td>' . $action . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Function to export sales report
function exportSalesReport($conn, $date_from, $date_to) {
    if (empty($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
    if (empty($date_to)) $date_to = date('Y-m-d');
    
    echo '<h2>Sales Report: ' . date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to)) . '</h2>';
    
    // Daily sales
    $daily_query = "SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as orders,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order
                    FROM orders 
                    WHERE DATE(order_date) BETWEEN '$date_from' AND '$date_to'
                    AND order_status != 'Cancelled'
                    GROUP BY DATE(order_date)
                    ORDER BY date DESC";
    $daily_result = mysqli_query($conn, $daily_query);
    
    // Summary
    $summary_query = "SELECT 
                      COUNT(*) as total_orders,
                      SUM(total_amount) as total_revenue,
                      AVG(total_amount) as avg_order_value,
                      COUNT(DISTINCT user_id) as unique_customers,
                      SUM(CASE WHEN payment_method = 'COD' THEN 1 ELSE 0 END) as cod_orders,
                      SUM(CASE WHEN payment_method = 'Online' THEN 1 ELSE 0 END) as online_orders
                      FROM orders 
                      WHERE DATE(order_date) BETWEEN '$date_from' AND '$date_to'
                      AND order_status != 'Cancelled'";
    $summary_result = mysqli_query($conn, $summary_query);
    $summary = mysqli_fetch_assoc($summary_result);
    
    echo '<table width="100%" style="margin-bottom: 20px; background: #f8f9fa;">';
    echo '<tr><th colspan="4">Sales Summary</th></tr>';
    echo '<tr>';
    echo '<td><strong>Total Orders:</strong> ' . ($summary['total_orders'] ?? 0) . '</td>';
    echo '<td><strong>Total Revenue:</strong> ₹' . number_format($summary['total_revenue'] ?? 0, 2) . '</td>';
    echo '<td><strong>Avg Order Value:</strong> ₹' . number_format($summary['avg_order_value'] ?? 0, 2) . '</td>';
    echo '<td><strong>Unique Customers:</strong> ' . ($summary['unique_customers'] ?? 0) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>COD Orders:</strong> ' . ($summary['cod_orders'] ?? 0) . '</td>';
    echo '<td><strong>Online Orders:</strong> ' . ($summary['online_orders'] ?? 0) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    echo '</table>';
    
    // Daily breakdown
    echo '<h3>Daily Breakdown</h3>';
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Orders</th>';
    echo '<th>Revenue</th>';
    echo '<th>Avg Order</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $total_revenue = 0;
    $total_orders = 0;
    
    while ($day = mysqli_fetch_assoc($daily_result)) {
        $total_revenue += $day['revenue'];
        $total_orders += $day['orders'];
        
        echo '<tr>';
        echo '<td>' . date('d M Y', strtotime($day['date'])) . '</td>';
        echo '<td align="center">' . $day['orders'] . '</td>';
        echo '<td align="right">₹' . number_format($day['revenue'], 2) . '</td>';
        echo '<td align="right">₹' . number_format($day['avg_order'], 2) . '</td>';
        echo '</tr>';
    }
    
    // Footer row
    echo '<tr style="font-weight: bold; background: #f0f0f0;">';
    echo '<td>Total</td>';
    echo '<td align="center">' . $total_orders . '</td>';
    echo '<td align="right">₹' . number_format($total_revenue, 2) . '</td>';
    echo '<td align="right">₹' . number_format($total_revenue / max($total_orders, 1), 2) . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    // Top products
    $products_query = "SELECT 
                      oi.product_name,
                      SUM(oi.quantity) as quantity_sold,
                      SUM(oi.price * oi.quantity) as revenue
                      FROM order_items oi
                      JOIN orders o ON oi.order_id = o.id
                      WHERE DATE(o.order_date) BETWEEN '$date_from' AND '$date_to'
                      AND o.order_status != 'Cancelled'
                      GROUP BY oi.product_id
                      ORDER BY revenue DESC
                      LIMIT 20";
    $products_result = mysqli_query($conn, $products_query);
    
    echo '<h3>Top Selling Products</h3>';
    echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Product</th>';
    echo '<th>Quantity Sold</th>';
    echo '<th>Revenue</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($product = mysqli_fetch_assoc($products_result)) {
        echo '<tr>';
        echo '<td>' . $product['product_name'] . '</td>';
        echo '<td align="center">' . $product['quantity_sold'] . '</td>';
        echo '<td align="right">₹' . number_format($product['revenue'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}
?>