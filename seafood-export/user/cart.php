<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle remove item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    mysqli_query($conn, "DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'");
    $_SESSION['success'] = "Item removed from cart";
    header('Location: cart.php');
    exit();
}

// Handle clear cart
if (isset($_GET['clear'])) {
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
    $_SESSION['success'] = "Cart cleared";
    header('Location: cart.php');
    exit();
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.product_code, p.price_per_kg, p.stock_kg, p.minimum_order_kg,
               fs.name as species_name, pt.name as processing_name
               FROM cart c 
               JOIN products p ON c.product_id = p.id
               LEFT JOIN fish_species fs ON p.species_id = fs.id
               LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
               WHERE c.user_id = '$user_id'";
$cart_items = mysqli_query($conn, $cart_query);

// Calculate totals
$subtotal = 0;
$total_kg = 0;
while ($item = mysqli_fetch_assoc($cart_items)) {
    $subtotal += $item['price_per_kg'] * $item['quantity_kg'];
    $total_kg += $item['quantity_kg'];
}
mysqli_data_seek($cart_items, 0);

$shipping = 5000; // Base shipping
$total = $subtotal + $shipping;
?>

<?php include '../includes/header.php'; ?>

<style>
    .cart-page {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .cart-title {
        font-size: 1.8rem;
        color: #0a3147;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .cart-title i {
        color: #00d4ff;
    }
    
    .clear-cart {
        background: #ff4757;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .clear-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255,71,87,0.4);
    }
    
    .cart-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    .cart-items {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto;
        gap: 20px;
        padding: 20px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .item-image {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .item-image i {
        font-size: 3rem;
        color: #00d4ff;
        opacity: 0.5;
    }
    
    .item-details h3 {
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .item-details p {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }
    
    .item-price {
        color: #00d4ff;
        font-weight: bold;
        font-size: 1.2rem;
    }
    
    .item-actions {
        text-align: right;
    }
    
    .quantity-input {
        width: 80px;
        padding: 8px;
        border: 2px solid #ecf0f1;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    
    .remove-btn {
        background: none;
        border: none;
        color: #ff4757;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    .cart-summary {
        background: white;
        padding: 25px;
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
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #7f8c8d;
    }
    
    .summary-row.total {
        border-top: 2px solid #ecf0f1;
        margin-top: 15px;
        padding-top: 15px;
        font-size: 1.3rem;
        font-weight: bold;
        color: #0a3147;
    }
    
    .checkout-btn {
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
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .checkout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .continue-shopping {
        display: block;
        text-align: center;
        color: #7f8c8d;
        text-decoration: none;
    }
    
    .empty-cart {
        text-align: center;
        padding: 80px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .empty-cart i {
        font-size: 5rem;
        color: #bdc3c7;
        margin-bottom: 20px;
    }
    
    .empty-cart h2 {
        color: #7f8c8d;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .cart-content {
            grid-template-columns: 1fr;
        }
        
        .cart-item {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .item-image {
            margin: 0 auto;
        }
        
        .item-actions {
            text-align: center;
        }
    }
</style>

<div class="cart-page">
    <?php if (mysqli_num_rows($cart_items) > 0): ?>
        <div class="cart-header">
            <div class="cart-title">
                <i class="fas fa-shopping-cart"></i>
                Shopping Cart (<?php echo $total_kg; ?> kg)
            </div>
            <a href="?clear=1" class="clear-cart" onclick="return confirm('Clear entire cart?')">
                <i class="fas fa-trash"></i> Clear Cart
            </a>
        </div>
        
        <div class="cart-content">
            <div class="cart-items">
                <?php while ($item = mysqli_fetch_assoc($cart_items)): 
                    $item_total = $item['price_per_kg'] * $item['quantity_kg'];
                ?>
                    <div class="cart-item">
                        <div class="item-image">
                            <i class="fas fa-fish"></i>
                        </div>
                        <div class="item-details">
                            <h3><?php echo $item['name']; ?></h3>
                            <p><?php echo $item['species_name']; ?> | <?php echo $item['processing_name']; ?></p>
                            <p>Product Code: <?php echo $item['product_code']; ?></p>
                            <div class="item-price">₹<?php echo number_format($item['price_per_kg'], 2); ?>/kg</div>
                        </div>
                        <div class="item-actions">
                            <form method="POST" action="update_cart.php" style="display: inline;">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity_kg" class="quantity-input" value="<?php echo $item['quantity_kg']; ?>" min="<?php echo $item['minimum_order_kg']; ?>" max="<?php echo $item['stock_kg']; ?>" step="0.5">
                                <button type="submit" class="btn btn-primary" style="padding: 8px 15px;">Update</button>
                            </form>
                            <br>
                            <a href="?remove=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('Remove this item?')">
                                <i class="fas fa-trash-alt"></i> Remove
                            </a>
                            <div style="margin-top: 10px; font-weight: bold; color: #00d4ff;">
                                Total: ₹<?php echo number_format($item_total, 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="cart-summary">
                <h3 class="summary-title">Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal (<?php echo $total_kg; ?> kg)</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping & Handling</span>
                    <span>₹<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
                
                <a href="checkout.php" class="checkout-btn">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                
                <a href="products.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h2>Your cart is empty</h2>
            <p>Browse our products and add items to your cart</p>
            <a href="products.php" class="btn btn-primary" style="padding: 15px 40px; text-decoration: none;">
                <i class="fas fa-fish"></i> Browse Products
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>