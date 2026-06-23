<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_query = "SELECT c.*, p.name, p.product_code, p.price_per_kg, p.stock_kg,
               fs.name as species_name
               FROM cart c 
               JOIN products p ON c.product_id = p.id
               LEFT JOIN fish_species fs ON p.species_id = fs.id
               WHERE c.user_id = '$user_id'";
$cart_items = mysqli_query($conn, $cart_query);

if (mysqli_num_rows($cart_items) == 0) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
while ($item = mysqli_fetch_assoc($cart_items)) {
    $subtotal += $item['price_per_kg'] * $item['quantity_kg'];
}
mysqli_data_seek($cart_items, 0);

// Get user details
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get export destinations
$destinations = mysqli_query($conn, "SELECT * FROM export_destinations WHERE status = 1 ORDER BY country");

// Process order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination_id = (int)$_POST['destination_id'];
    $shipping_terms = $_POST['shipping_terms'];
    $payment_method = $_POST['payment_method'];
    $shipping_address = sanitize($_POST['shipping_address']);
    
    // Get destination details
    $dest_query = "SELECT * FROM export_destinations WHERE id = '$destination_id'";
    $dest_result = mysqli_query($conn, $dest_query);
    $destination = mysqli_fetch_assoc($dest_result);
    
    // Calculate amounts
    $base_amount = $subtotal;
    $shipping_cost = $base_amount * 0.1 * $destination['shipping_multiplier']; // 10% base shipping
    $duty_amount = $base_amount * ($destination['duty_percentage'] / 100);
    $insurance_amount = $base_amount * 0.02; // 2% insurance
    $total_inr = $base_amount + $shipping_cost + $duty_amount + $insurance_amount;
    $total_foreign = $total_inr / $destination['exchange_rate'];
    
    $order_number = generateOrderNumber();
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert order
        $order_query = "INSERT INTO orders (user_id, order_number, export_destination_id,
                        total_amount_inr, total_amount_foreign, currency, exchange_rate,
                        shipping_cost_inr, duty_amount, insurance_amount, grand_total_inr,
                        payment_method, shipping_terms, shipping_address, port_of_loading)
                        VALUES ('$user_id', '$order_number', '$destination_id',
                        '$base_amount', '$total_foreign', '{$destination['currency']}',
                        '{$destination['exchange_rate']}', '$shipping_cost', '$duty_amount',
                        '$insurance_amount', '$total_inr', '$payment_method',
                        '$shipping_terms', '$shipping_address', 'Mumbai')";
        mysqli_query($conn, $order_query);
        $order_id = mysqli_insert_id($conn);
        
        // Insert order items and update stock
        mysqli_data_seek($cart_items, 0);
        while ($item = mysqli_fetch_assoc($cart_items)) {
            $item_query = "INSERT INTO order_items (order_id, product_id, product_name,
                          product_code, quantity_kg, price_per_kg, total_price)
                          VALUES ('$order_id', '{$item['product_id']}', '{$item['name']}',
                          '{$item['product_code']}', '{$item['quantity_kg']}',
                          '{$item['price_per_kg']}', '{$item['price_per_kg']}' * '{$item['quantity_kg']}')";
            mysqli_query($conn, $item_query);
            
            // Update stock
            mysqli_query($conn, "UPDATE products SET stock_kg = stock_kg - {$item['quantity_kg']} 
                                WHERE id = '{$item['product_id']}'");
        }
        
        // Clear cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
        
        // Add notification
        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link)
                            VALUES ('$user_id', 'order', 'Order Placed',
                            'Your order #$order_number has been placed successfully.',
                            'order_receipt.php?id=$order_id')");
        
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Order placed successfully!";
        header("Location: order_receipt.php?id=$order_id");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Order failed: " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
    .checkout-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .checkout-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .form-section h3 {
        color: #0a3147;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-section h3 i {
        color: #00d4ff;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #00d4ff;
        outline: none;
    }
    
    .order-summary {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        height: fit-content;
        position: sticky;
        top: 100px;
    }
    
    .summary-title {
        color: #0a3147;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #00d4ff;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #7f8c8d;
    }
    
    .summary-item.total {
        border-top: 2px solid #ecf0f1;
        margin-top: 15px;
        padding-top: 15px;
        font-size: 1.2rem;
        font-weight: bold;
        color: #0a3147;
    }
    
    .summary-item.grand-total {
        font-size: 1.5rem;
        color: #00d4ff;
        font-weight: bold;
    }
    
    .btn-place-order {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        margin: 20px 0;
        transition: all 0.3s ease;
    }
    
    .btn-place-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .payment-methods {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 20px 0;
    }
    
    .payment-method {
        padding: 15px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .payment-method:hover,
    .payment-method.selected {
        border-color: #00d4ff;
        background: #e8f5e9;
    }
    
    .payment-method i {
        font-size: 2rem;
        color: #00d4ff;
        margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
        .checkout-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="checkout-container">
    <form method="POST" action="" class="checkout-form" id="checkoutForm">
        <div class="form-section">
            <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
            
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" value="<?php echo $user['company_name']; ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" value="<?php echo $user['contact_person']; ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Shipping Address *</label>
                <textarea name="shipping_address" required rows="4"><?php echo $user['address']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Port of Loading</label>
                <input type="text" value="Mumbai" readonly>
            </div>
        </div>
        
        <div class="form-section">
            <h3><i class="fas fa-globe"></i> Export Details</h3>
            
            <div class="form-group">
                <label>Destination Country *</label>
                <select name="destination_id" id="destination" required onchange="updatePrices()">
                    <option value="">Select Destination</option>
                    <?php while ($dest = mysqli_fetch_assoc($destinations)): ?>
                        <option value="<?php echo $dest['id']; ?>" 
                                data-exchange="<?php echo $dest['exchange_rate']; ?>"
                                data-shipping="<?php echo $dest['shipping_multiplier']; ?>"
                                data-duty="<?php echo $dest['duty_percentage']; ?>"
                                data-currency="<?php echo $dest['currency']; ?>"
                                data-symbol="<?php echo $dest['currency_symbol']; ?>">
                            <?php echo $dest['country']; ?> (<?php echo $dest['currency']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Shipping Terms *</label>
                <select name="shipping_terms" required>
                    <option value="FOB">FOB (Free on Board)</option>
                    <option value="CIF">CIF (Cost, Insurance & Freight)</option>
                    <option value="CFR">CFR (Cost and Freight)</option>
                    <option value="EXW">EXW (Ex Works)</option>
                    <option value="DDP">DDP (Delivered Duty Paid)</option>
                </select>
            </div>
        </div>
        
        <div class="form-section">
            <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
            
            <div class="payment-methods">
                <div class="payment-method" onclick="selectPayment('Bank Transfer')">
                    <i class="fas fa-university"></i>
                    <h4>Bank Transfer</h4>
                </div>
                <div class="payment-method" onclick="selectPayment('Letter of Credit')">
                    <i class="fas fa-file-invoice"></i>
                    <h4>Letter of Credit</h4>
                </div>
                <div class="payment-method" onclick="selectPayment('Documentary Credit')">
                    <i class="fas fa-file-signature"></i>
                    <h4>Documentary Credit</h4>
                </div>
                <div class="payment-method" onclick="selectPayment('Online')">
                    <i class="fas fa-mobile-alt"></i>
                    <h4>Online Payment</h4>
                </div>
            </div>
            <input type="hidden" name="payment_method" id="payment_method" required>
        </div>
        
        <button type="submit" class="btn-place-order">
            <i class="fas fa-check-circle"></i> Place Order
        </button>
    </form>
    
    <div class="order-summary">
        <h3 class="summary-title">Order Summary</h3>
        
        <div style="margin-bottom: 20px;">
            <?php while ($item = mysqli_fetch_assoc($cart_items)): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ecf0f1;">
                    <div>
                        <strong><?php echo $item['name']; ?></strong><br>
                        <small><?php echo $item['quantity_kg']; ?> kg x ₹<?php echo number_format($item['price_per_kg'], 2); ?></small>
                    </div>
                    <span>₹<?php echo number_format($item['price_per_kg'] * $item['quantity_kg'], 2); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="summary-item">
            <span>Subtotal:</span>
            <span id="subtotal">₹<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="summary-item">
            <span>Shipping:</span>
            <span id="shipping">Calculating...</span>
        </div>
        <div class="summary-item">
            <span>Duty/Taxes:</span>
            <span id="duty">Calculating...</span>
        </div>
        <div class="summary-item">
            <span>Insurance:</span>
            <span id="insurance">Calculating...</span>
        </div>
        <div class="summary-item total grand-total">
            <span>Total:</span>
            <span id="total">Calculating...</span>
        </div>
        <div class="summary-item" id="foreignTotal" style="color: #00d4ff;">
            <span>Total (Foreign):</span>
            <span id="foreignAmount">-</span>
        </div>
    </div>
</div>

<script>
let subtotal = <?php echo $subtotal; ?>;

function selectPayment(method) {
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    document.getElementById('payment_method').value = method;
}

function updatePrices() {
    const select = document.getElementById('destination');
    const option = select.options[select.selectedIndex];
    
    if (!option.value) {
        document.getElementById('shipping').textContent = 'Select destination';
        return;
    }
    
    const exchangeRate = parseFloat(option.dataset.exchange);
    const shippingMultiplier = parseFloat(option.dataset.shipping);
    const dutyPercentage = parseFloat(option.dataset.duty);
    const currency = option.dataset.currency;
    const symbol = option.dataset.symbol;
    
    const shipping = subtotal * 0.1 * shippingMultiplier;
    const duty = subtotal * (dutyPercentage / 100);
    const insurance = subtotal * 0.02;
    const total = subtotal + shipping + duty + insurance;
    const foreignTotal = total / exchangeRate;
    
    document.getElementById('shipping').textContent = '₹' + shipping.toFixed(2);
    document.getElementById('duty').textContent = '₹' + duty.toFixed(2);
    document.getElementById('insurance').textContent = '₹' + insurance.toFixed(2);
    document.getElementById('total').textContent = '₹' + total.toFixed(2);
    document.getElementById('foreignAmount').textContent = symbol + foreignTotal.toFixed(2) + ' ' + currency;
}
</script>

<?php include '../includes/footer.php'; ?>