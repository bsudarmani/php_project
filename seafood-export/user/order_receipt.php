<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$order_query = "SELECT o.*, u.company_name, u.contact_person, u.email, u.phone, u.address as user_address,
                u.gst_number, u.import_license, ed.country as destination, ed.country_code,
                ed.currency, ed.currency_symbol
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id 
                WHERE o.id = '$order_id' AND o.user_id = '$user_id'";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header('Location: my_orders.php');
    exit();
}

$order = mysqli_fetch_assoc($order_result);

$items_query = "SELECT * FROM order_items WHERE order_id = '$order_id'";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - SeaFood Export</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #00d4ff;
        }
        
        .receipt-header i {
            font-size: 4rem;
            color: #00d4ff;
            margin-bottom: 10px;
        }
        
        .receipt-header h1 {
            color: #0a3147;
            margin-bottom: 5px;
        }
        
        .receipt-header h2 {
            color: #00d4ff;
            font-size: 1.3rem;
            font-weight: normal;
        }
        
        .company-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .company-details h3,
        .buyer-details h3 {
            color: #0a3147;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .company-details p,
        .buyer-details p {
            color: #7f8c8d;
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .info-item label {
            display: block;
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            color: #0a3147;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
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
        
        .totals {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }
        
        .totals p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        
        .totals .grand-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #00d4ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,212,255,0.4);
        }
        
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print Receipt
    </button>
    
    <div class="receipt-container">
        <div class="receipt-header">
            <i class="fas fa-fish"></i>
            <h1>SeaFood Export System</h1>
            <h2>Export Order Receipt</h2>
        </div>
        
        <div class="company-info">
            <div class="company-details">
                <h3>Exporter:</h3>
                <p><strong>SeaFood Export Pvt Ltd</strong></p>
                <p>123 Fishing Harbor, Mumbai - 400001</p>
                <p>GST: 27AAAAA0000A1Z5</p>
                <p>IEC: 1234567890</p>
            </div>
            <div class="buyer-details">
                <h3>Buyer:</h3>
                <p><strong><?php echo $order['company_name']; ?></strong></p>
                <p><?php echo $order['contact_person']; ?></p>
                <p><?php echo $order['user_address']; ?></p>
                <p>GST: <?php echo $order['gst_number'] ?? 'Not provided'; ?></p>
                <p>Import License: <?php echo $order['import_license'] ?? 'Not provided'; ?></p>
            </div>
        </div>
        
        <div class="order-info">
            <div class="info-item">
                <label>Order Number</label>
                <span class="value"><?php echo $order['order_number']; ?></span>
            </div>
            <div class="info-item">
                <label>Order Date</label>
                <span class="value"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="info-item">
                <label>Payment Method</label>
                <span class="value"><?php echo $order['payment_method']; ?></span>
            </div>
            <div class="info-item">
                <label>Shipping Terms</label>
                <span class="value"><?php echo $order['shipping_terms']; ?></span>
            </div>
            <div class="info-item">
                <label>Destination</label>
                <span class="value"><?php echo $order['destination'] ?? 'Domestic'; ?></span>
            </div>
            <div class="info-item">
                <label>Port of Loading</label>
                <span class="value"><?php echo $order['port_of_loading'] ?? 'Mumbai'; ?></span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Product Code</th>
                    <th>Quantity (kg)</th>
                    <th>Unit Price (₹)</th>
                    <th>Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                while ($item = mysqli_fetch_assoc($items_result)): 
                    $subtotal += $item['total_price'];
                ?>
                    <tr>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['product_code']; ?></td>
                        <td><?php echo $item['quantity_kg']; ?></td>
                        <td>₹<?php echo number_format($item['price_per_kg'], 2); ?></td>
                        <td>₹<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <p>Subtotal: ₹<?php echo number_format($subtotal, 2); ?></p>
            <p>Shipping: ₹<?php echo number_format($order['shipping_cost_inr'], 2); ?></p>
            <p>Duty/Taxes: ₹<?php echo number_format($order['duty_amount'], 2); ?></p>
            <p>Insurance: ₹<?php echo number_format($order['insurance_amount'], 2); ?></p>
            <p class="grand-total">Grand Total: ₹<?php echo number_format($order['grand_total_inr'], 2); ?></p>
            
            <?php if ($order['currency'] != 'INR'): ?>
                <p style="color: #00d4ff;">
                    Total in Foreign Currency: <?php echo $order['currency_symbol'] . number_format($order['total_amount_foreign'], 2); ?> <?php echo $order['currency']; ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>This is a computer generated invoice - no signature required.</p>
            <p>For any queries, contact: accounts@seafoodexport.com | +91 98765 43210</p>
        </div>
    </div>
</body>
</html>