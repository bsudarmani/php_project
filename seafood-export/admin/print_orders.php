<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get filter parameters
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id > 0) {
    // Print single order
    printSingleOrder($conn, $order_id);
} else {
    // Print multiple orders
    printOrdersList($conn, $status, $date_from, $date_to);
}

function printSingleOrder($conn, $order_id) {
    // Get order details
    $query = "SELECT o.*, u.name, u.email, u.phone, u.address 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = '$order_id'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        header('Location: view_orders.php');
        exit();
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // Get order items
    $items_query = "SELECT * FROM order_items WHERE order_id = '$order_id'";
    $items_result = mysqli_query($conn, $items_query);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Order #<?php echo $order['order_number']; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 30px;
                line-height: 1.6;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #2ecc71;
            }
            .header h1 {
                color: #2ecc71;
                margin: 0;
            }
            .header p {
                color: #7f8c8d;
                margin: 5px 0;
            }
            .order-info {
                margin-bottom: 30px;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .info-item label {
                font-weight: bold;
                color: #34495e;
                display: block;
                margin-bottom: 5px;
            }
            .info-item p {
                margin: 0;
                color: #7f8c8d;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th {
                background: #2ecc71;
                color: white;
                padding: 12px;
                text-align: left;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            .total {
                text-align: right;
                font-size: 1.2em;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 2px solid #2ecc71;
            }
            .total strong {
                color: #2ecc71;
                font-size: 1.5em;
            }
            .footer {
                text-align: center;
                margin-top: 50px;
                color: #7f8c8d;
                font-size: 0.9em;
            }
            @media print {
                .no-print {
                    display: none;
                }
            }
            .print-btn {
                background: #3498db;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1em;
                margin-bottom: 20px;
            }
            .print-btn:hover {
                background: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: right;">
            <button class="print-btn" onclick="window.print()">Print</button>
            <button class="print-btn" onclick="window.close()">Close</button>
        </div>
        
        <div class="header">
            <h1>PMBJK Pharmacy</h1>
            <p>Pradhan Mantri Bhartiya Janaushadhi Kendra</p>
            <h2>Order Invoice</h2>
        </div>
        
        <div class="order-info">
            <div class="info-grid">
                <div class="info-item">
                    <label>Order Number:</label>
                    <p><?php echo $order['order_number']; ?></p>
                </div>
                <div class="info-item">
                    <label>Order Date:</label>
                    <p><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <div class="info-item">
                    <label>Customer Name:</label>
                    <p><?php echo $order['name']; ?></p>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <p><?php echo $order['email']; ?></p>
                </div>
                <div class="info-item">
                    <label>Phone:</label>
                    <p><?php echo $order['phone']; ?></p>
                </div>
                <div class="info-item">
                    <label>Payment Method:</label>
                    <p><?php echo $order['payment_method']; ?> (<?php echo $order['payment_status']; ?>)</p>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <label>Shipping Address:</label>
                    <p><?php echo nl2br($order['address']); ?></p>
                </div>
            </div>
        </div>
        
        <h3>Order Items</h3>
        <table>
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
        
        <div class="total">
            <p>Subtotal: ₹<?php echo number_format($subtotal, 2); ?></p>
            <p>Shipping: ₹50.00</p>
            <p><strong>Grand Total: ₹<?php echo number_format($order['total_amount'], 2); ?></strong></p>
        </div>
        
        <div class="footer">
            <p>This is a computer generated invoice - no signature required.</p>
            <p>For any queries, contact: support@pmbjkpharmacy.com | Toll Free: 1800-XXX-XXXX</p>
        </div>
    </body>
    </html>
    <?php
}

function printOrdersList($conn, $status, $date_from, $date_to) {
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
    
    $query = "SELECT o.*, u.name, u.phone 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              $where_clause 
              ORDER BY o.order_date DESC";
    $result = mysqli_query($conn, $query);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Orders List</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 30px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2ecc71;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                background: #2ecc71;
                color: white;
                padding: 10px;
            }
            td {
                padding: 8px;
                border-bottom: 1px solid #ddd;
            }
            .summary {
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .print-btn {
                background: #3498db;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 20px;
            }
            @media print {
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: right;">
            <button class="print-btn" onclick="window.print()">Print</button>
            <button class="print-btn" onclick="window.close()">Close</button>
        </div>
        
        <div class="header">
            <h1>PMBJK Pharmacy</h1>
            <h2>Orders Report</h2>
            <p>Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
        
        <?php
        $total_orders = mysqli_num_rows($result);
        $total_amount = 0;
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            $total_amount += $row['total_amount'];
        }
        mysqli_data_seek($result, 0);
        ?>
        
        <div class="summary">
            <p><strong>Total Orders:</strong> <?php echo $total_orders; ?></p>
            <p><strong>Total Amount:</strong> ₹<?php echo number_format($total_amount, 2); ?></p>
            <?php if (!empty($status)): ?>
                <p><strong>Status Filter:</strong> <?php echo $status; ?></p>
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $order['order_number']; ?></td>
                        <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                        <td><?php echo $order['name']; ?><br><small><?php echo $order['phone']; ?></small></td>
                        <td align="right">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo $order['order_status']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>