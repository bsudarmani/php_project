<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Check if TCPDF library exists, if not, provide HTML export option
if (!file_exists('../vendor/autoload.php') && !class_exists('TCPDF')) {
    // Fallback to HTML export if TCPDF not available
    header('Location: export_excel.php?' . $_SERVER['QUERY_STRING'] . '&format=html');
    exit();
}

// Try to include TCPDF
$pdf_available = false;
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    $pdf_available = true;
} elseif (class_exists('TCPDF')) {
    $pdf_available = true;
}

if (!$pdf_available) {
    // If TCPDF is not available, output HTML for printing
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head>';
    echo '<title>PMBJK Pharmacy Report</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 30px; }';
    echo 'h1 { color: #2ecc71; }';
    echo 'h2 { color: #34495e; border-bottom: 2px solid #2ecc71; padding-bottom: 10px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }';
    echo 'th { background: #2ecc71; color: white; padding: 10px; text-align: left; }';
    echo 'td { padding: 8px; border: 1px solid #ddd; }';
    echo 'tr:nth-child(even) { background: #f9f9f9; }';
    echo '.summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }';
    echo '@media print { .no-print { display: none; } }';
    echo '</style>';
    echo '</head><body>';
    echo '<div class="no-print" style="text-align: right; margin-bottom: 20px;">';
    echo '<button onclick="window.print()">Print</button> ';
    echo '<button onclick="window.close()">Close</button>';
    echo '</div>';
} else {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('PMBJK Pharmacy');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('PMBJK Pharmacy Report');
    $pdf->SetSubject('Sales Report');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Company Logo and Header
    $html = '<h1 style="color:#2ecc71; text-align:center;">PMBJK Pharmacy</h1>';
    $html .= '<h3 style="color:#34495e; text-align:center;">Pradhan Mantri Bhartiya Janaushadhi Kendra</h3>';
    $html .= '<p style="text-align:center; color:#7f8c8d;">Report Generated: ' . date('d M Y, h:i A') . '</p>';
    $html .= '<hr style="border:1px solid #2ecc71;">';
    
    $pdf->writeHTML($html, true, false, true, false, '');
}

// Get filter parameters
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'orders';

if ($report_type == 'orders') {
    generateOrdersPDF($conn, $status, $date_from, $date_to, $pdf_available);
} elseif ($report_type == 'products') {
    generateProductsPDF($conn, $pdf_available);
} elseif ($report_type == 'customers') {
    generateCustomersPDF($conn, $pdf_available);
} elseif ($report_type == 'inventory') {
    generateInventoryPDF($conn, $pdf_available);
} elseif ($report_type == 'sales') {
    generateSalesPDF($conn, $date_from, $date_to, $pdf_available);
}

if ($pdf_available) {
    // Output PDF
    $pdf->Output('PMBJK_Report_' . date('Y-m-d') . '.pdf', 'D');
} else {
    echo '</body></html>';
}

// Function to generate orders PDF
function generateOrdersPDF($conn, $status, $date_from, $date_to, $pdf_available) {
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
    
    $query = "SELECT o.*, u.name, u.email, u.phone 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.order_date DESC";
    $result = mysqli_query($conn, $query);
    
    $html = '<h2>Orders Report</h2>';
    
    // Summary
    $total_orders = mysqli_num_rows($result);
    $total_amount = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_amount += $row['total_amount'];
    }
    mysqli_data_seek($result, 0);
    
    $html .= '<div class="summary">';
    $html .= '<p><strong>Total Orders:</strong> ' . $total_orders . ' | ';
    $html .= '<strong>Total Amount:</strong> ₹' . number_format($total_amount, 2) . '</p>';
    $html .= '</div>';
    
    // Orders Table
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Order #</th>';
    $html .= '<th>Date</th>';
    $html .= '<th>Customer</th>';
    $html .= '<th>Amount</th>';
    $html .= '<th>Payment</th>';
    $html .= '<th>Status</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    while ($order = mysqli_fetch_assoc($result)) {
        $html .= '<tr>';
        $html .= '<td>' . $order['order_number'] . '</td>';
        $html .= '<td>' . date('d M Y', strtotime($order['order_date'])) . '</td>';
        $html .= '<td>' . $order['name'] . '<br><small>' . $order['phone'] . '</small></td>';
        $html .= '<td align="right">₹' . number_format($order['total_amount'], 2) . '</td>';
        $html .= '<td>' . $order['payment_method'] . '<br><small>' . $order['payment_status'] . '</small></td>';
        $html .= '<td>' . $order['order_status'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    outputHTML($html, $pdf_available);
}

// Function to generate products PDF
function generateProductsPDF($conn, $pdf_available) {
    $query = "SELECT p.*, c.name as category_name,
              (SELECT SUM(quantity) FROM order_items WHERE product_id = p.id) as total_sold
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.id DESC";
    $result = mysqli_query($conn, $query);
    
    $html = '<h2>Products Report</h2>';
    
    // Summary
    $total_products = mysqli_num_rows($result);
    $total_value = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_value += $row['price'] * $row['stock'];
    }
    mysqli_data_seek($result, 0);
    
    $html .= '<div class="summary">';
    $html .= '<p><strong>Total Products:</strong> ' . $total_products . ' | ';
    $html .= '<strong>Inventory Value:</strong> ₹' . number_format($total_value, 2) . '</p>';
    $html .= '</div>';
    
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>ID</th>';
    $html .= '<th>Name</th>';
    $html .= '<th>Category</th>';
    $html .= '<th>Price</th>';
    $html .= '<th>Stock</th>';
    $html .= '<th>Sold</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    while ($product = mysqli_fetch_assoc($result)) {
        $html .= '<tr>';
        $html .= '<td>' . $product['id'] . '</td>';
        $html .= '<td>' . $product['name'] . '</td>';
        $html .= '<td>' . $product['category_name'] . '</td>';
        $html .= '<td align="right">₹' . number_format($product['price'], 2) . '</td>';
        $html .= '<td align="center">' . $product['stock'] . '</td>';
        $html .= '<td align="center">' . ($product['total_sold'] ?? 0) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    outputHTML($html, $pdf_available);
}

// Function to generate customers PDF
function generateCustomersPDF($conn, $pdf_available) {
    $query = "SELECT u.*, 
              COUNT(o.id) as total_orders,
              SUM(o.total_amount) as total_spent
              FROM users u
              LEFT JOIN orders o ON u.id = o.user_id
              GROUP BY u.id
              ORDER BY total_spent DESC";
    $result = mysqli_query($conn, $query);
    
    $html = '<h2>Customers Report</h2>';
    
    // Summary
    $total_customers = mysqli_num_rows($result);
    $total_spent = 0;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_spent += $row['total_spent'] ?? 0;
    }
    mysqli_data_seek($result, 0);
    
    $html .= '<div class="summary">';
    $html .= '<p><strong>Total Customers:</strong> ' . $total_customers . ' | ';
    $html .= '<strong>Total Revenue:</strong> ₹' . number_format($total_spent, 2) . '</p>';
    $html .= '</div>';
    
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Name</th>';
    $html .= '<th>Email</th>';
    $html .= '<th>Phone</th>';
    $html .= '<th>Orders</th>';
    $html .= '<th>Total Spent</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    while ($user = mysqli_fetch_assoc($result)) {
        $html .= '<tr>';
        $html .= '<td>' . $user['name'] . '</td>';
        $html .= '<td>' . $user['email'] . '</td>';
        $html .= '<td>' . $user['phone'] . '</td>';
        $html .= '<td align="center">' . ($user['total_orders'] ?? 0) . '</td>';
        $html .= '<td align="right">₹' . number_format($user['total_spent'] ?? 0, 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    outputHTML($html, $pdf_available);
}

// Function to generate inventory PDF
function generateInventoryPDF($conn, $pdf_available) {
    $query = "SELECT p.*, c.name as category_name
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.stock ASC";
    $result = mysqli_query($conn, $query);
    
    $html = '<h2>Inventory Report</h2>';
    
    // Summary
    $total_products = mysqli_num_rows($result);
    $total_stock = 0;
    $out_of_stock = 0;
    $low_stock = 0;
    
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_stock += $row['stock'];
        if ($row['stock'] == 0) $out_of_stock++;
        if ($row['stock'] > 0 && $row['stock'] <= 10) $low_stock++;
    }
    mysqli_data_seek($result, 0);
    
    $html .= '<div class="summary">';
    $html .= '<p><strong>Total Products:</strong> ' . $total_products . ' | ';
    $html .= '<strong>Total Stock:</strong> ' . $total_stock . ' units<br>';
    $html .= '<strong>Out of Stock:</strong> ' . $out_of_stock . ' | ';
    $html .= '<strong>Low Stock (≤10):</strong> ' . $low_stock . '</p>';
    $html .= '</div>';
    
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Product</th>';
    $html .= '<th>Category</th>';
    $html .= '<th>Price</th>';
    $html .= '<th>Stock</th>';
    $html .= '<th>Status</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    while ($product = mysqli_fetch_assoc($result)) {
        $status = $product['stock'] == 0 ? 'Out of Stock' : 
                 ($product['stock'] <= 10 ? 'Low Stock' : 'In Stock');
        $color = $product['stock'] == 0 ? '#e74c3c' : 
                ($product['stock'] <= 10 ? '#f39c12' : '#2ecc71');
        
        $html .= '<tr>';
        $html .= '<td>' . $product['name'] . '</td>';
        $html .= '<td>' . $product['category_name'] . '</td>';
        $html .= '<td align="right">₹' . number_format($product['price'], 2) . '</td>';
        $html .= '<td align="center" style="color: ' . $color . ';">' . $product['stock'] . '</td>';
        $html .= '<td style="color: ' . $color . ';">' . $status . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    outputHTML($html, $pdf_available);
}

// Function to generate sales PDF
function generateSalesPDF($conn, $date_from, $date_to, $pdf_available) {
    if (empty($date_from)) $date_from = date('Y-m-d', strtotime('-30 days'));
    if (empty($date_to)) $date_to = date('Y-m-d');
    
    $html = '<h2>Sales Report: ' . date('d M Y', strtotime($date_from)) . ' to ' . date('d M Y', strtotime($date_to)) . '</h2>';
    
    // Summary
    $summary_query = "SELECT 
                      COUNT(*) as total_orders,
                      SUM(total_amount) as total_revenue,
                      AVG(total_amount) as avg_order,
                      COUNT(DISTINCT user_id) as unique_customers
                      FROM orders 
                      WHERE DATE(order_date) BETWEEN '$date_from' AND '$date_to'
                      AND order_status != 'Cancelled'";
    $summary_result = mysqli_query($conn, $summary_query);
    $summary = mysqli_fetch_assoc($summary_result);
    
    $html .= '<div class="summary">';
    $html .= '<p><strong>Total Orders:</strong> ' . ($summary['total_orders'] ?? 0) . ' | ';
    $html .= '<strong>Total Revenue:</strong> ₹' . number_format($summary['total_revenue'] ?? 0, 2) . '<br>';
    $html .= '<strong>Avg Order Value:</strong> ₹' . number_format($summary['avg_order'] ?? 0, 2) . ' | ';
    $html .= '<strong>Unique Customers:</strong> ' . ($summary['unique_customers'] ?? 0) . '</p>';
    $html .= '</div>';
    
    // Daily sales
    $daily_query = "SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as orders,
                    SUM(total_amount) as revenue
                    FROM orders 
                    WHERE DATE(order_date) BETWEEN '$date_from' AND '$date_to'
                    AND order_status != 'Cancelled'
                    GROUP BY DATE(order_date)
                    ORDER BY date DESC";
    $daily_result = mysqli_query($conn, $daily_query);
    
    $html .= '<h3>Daily Breakdown</h3>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Date</th>';
    $html .= '<th>Orders</th>';
    $html .= '<th>Revenue</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    while ($day = mysqli_fetch_assoc($daily_result)) {
        $html .= '<tr>';
        $html .= '<td>' . date('d M Y', strtotime($day['date'])) . '</td>';
        $html .= '<td align="center">' . $day['orders'] . '</td>';
        $html .= '<td align="right">₹' . number_format($day['revenue'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    outputHTML($html, $pdf_available);
}

// Helper function to output HTML
function outputHTML($html, $pdf_available) {
    global $pdf;
    
    if ($pdf_available) {
        $pdf->writeHTML($html, true, false, true, false, '');
    } else {
        echo $html;
    }
}
?>