<?php
require_once '../includes/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT p.*, fs.name as species_name, fs.scientific_name, fs.habitat, fs.season,
          pt.name as processing_name, pt.description as processing_description,
          pt.shelf_life_days, pt.storage_temperature,
          pkg.name as packaging_name, pkg.capacity_kg as packaging_capacity
          FROM products p 
          LEFT JOIN fish_species fs ON p.species_id = fs.id
          LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
          LEFT JOIN packaging_types pkg ON p.packaging_type_id = pkg.id
          WHERE p.id = '$product_id' AND p.status = 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: products.php');
    exit();
}

$product = mysqli_fetch_assoc($result);

// Get related products
$related_query = "SELECT p.*, fs.name as species_name 
                  FROM products p
                  LEFT JOIN fish_species fs ON p.species_id = fs.id
                  WHERE p.species_id = '{$product['species_id']}' 
                  AND p.id != '$product_id' AND p.status = 1
                  LIMIT 4";
$related_products = mysqli_query($conn, $related_query);

// Get inventory batches
$batches_query = "SELECT * FROM inventory_batches 
                  WHERE product_id = '$product_id' 
                  AND quality_check_status = 'Passed'
                  AND current_quantity_kg > 0
                  ORDER BY catch_date DESC";
$batches = mysqli_query($conn, $batches_query);
?>

<?php include '../includes/header.php'; ?>

<style>
    .product-detail {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .product-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        margin-bottom: 40px;
    }
    
    .product-gallery {
        position: relative;
    }
    
    .main-image {
        width: 100%;
        height: 400px;
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .main-image i {
        font-size: 8rem;
        color: #00d4ff;
        opacity: 0.5;
    }
    
    .product-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 10px 25px;
        border-radius: 30px;
        font-weight: 600;
        z-index: 10;
    }
    
    .badge-premium {
        background: linear-gradient(135deg, #f1c40f, #f39c12);
        color: white;
    }
    
    .badge-grade-a {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }
    
    .badge-standard {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }
    
    .product-info h1 {
        font-size: 2.5rem;
        color: #0a3147;
        margin-bottom: 15px;
    }
    
    .product-species {
        display: inline-block;
        background: #e8f5e9;
        color: #00d4ff;
        padding: 8px 20px;
        border-radius: 30px;
        margin-bottom: 20px;
    }
    
    .product-price {
        font-size: 3rem;
        font-weight: bold;
        color: #00d4ff;
        margin: 20px 0;
    }
    
    .product-price small {
        font-size: 1rem;
        color: #7f8c8d;
    }
    
    .product-meta {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 25px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .meta-item i {
        font-size: 1.5rem;
        color: #00d4ff;
    }
    
    .meta-item h4 {
        color: #0a3147;
        font-size: 0.9rem;
        margin-bottom: 3px;
    }
    
    .meta-item p {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .certification {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 20px 0;
    }
    
    .cert-badge {
        background: #0a3147;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 25px 0;
    }
    
    .quantity-label {
        font-weight: 600;
        color: #0a3147;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .quantity-btn {
        width: 45px;
        height: 45px;
        background: white;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .quantity-btn:hover {
        background: #00d4ff;
        color: white;
    }
    
    .quantity-input {
        width: 80px;
        height: 45px;
        border: none;
        border-left: 2px solid #ecf0f1;
        border-right: 2px solid #ecf0f1;
        text-align: center;
        font-size: 1.1rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
    }
    
    .btn-large {
        flex: 1;
        padding: 15px;
        font-size: 1.1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    .btn-secondary {
        background: #0a3147;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #1b4b6c;
        transform: translateY(-2px);
    }
    
    .stock-info {
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stock-bar {
        height: 10px;
        background: #ecf0f1;
        border-radius: 5px;
        margin: 10px 0;
    }
    
    .stock-fill {
        height: 100%;
        background: linear-gradient(90deg, #00d4ff, #0077be);
        border-radius: 5px;
    }
    
    .batches-table {
        width: 100%;
        margin: 20px 0;
        border-collapse: collapse;
    }
    
    .batches-table th {
        background: #0a3147;
        color: white;
        padding: 10px;
        text-align: left;
    }
    
    .batches-table td {
        padding: 10px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .related-products {
        margin-top: 40px;
    }
    
    .related-title {
        text-align: center;
        font-size: 2rem;
        color: #0a3147;
        margin-bottom: 30px;
    }
    
    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }
    
    .related-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .related-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    
    .related-image {
        height: 150px;
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .related-image i {
        font-size: 3rem;
        color: #00d4ff;
        opacity: 0.5;
    }
    
    .related-info {
        padding: 15px;
    }
    
    .related-info h4 {
        color: #0a3147;
        margin-bottom: 5px;
    }
    
    .related-info .price {
        color: #00d4ff;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .product-container {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="product-detail">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px;">
        <a href="products.php" style="color: #7f8c8d; text-decoration: none;">Products</a>
        <i class="fas fa-chevron-right" style="color: #7f8c8d; margin: 0 10px;"></i>
        <span style="color: #0a3147;"><?php echo $product['name']; ?></span>
    </div>
    
    <!-- Product Detail -->
    <div class="product-container">
        <div class="product-gallery">
            <div class="main-image">
                <i class="fas fa-fish"></i>
            </div>
            
            <?php
            $badge_class = 'badge-standard';
            if ($product['grade'] == 'Premium') $badge_class = 'badge-premium';
            elseif ($product['grade'] == 'A') $badge_class = 'badge-grade-a';
            ?>
            <span class="product-badge <?php echo $badge_class; ?>">
                <?php echo $product['grade']; ?> Grade
            </span>
        </div>
        
        <div class="product-info">
            <span class="product-species">
                <i class="fas fa-fish"></i> <?php echo $product['species_name']; ?> (<?php echo $product['scientific_name']; ?>)
            </span>
            
            <h1><?php echo $product['name']; ?></h1>
            
            <div class="product-price">
                ₹<?php echo number_format($product['price_per_kg'], 2); ?><small>/kg</small>
            </div>
            
            <div class="product-meta">
                <div class="meta-item">
                    <i class="fas fa-weight"></i>
                    <div>
                        <h4>Size Range</h4>
                        <p><?php echo $product['size_range'] ?? 'Standard'; ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-thermometer-half"></i>
                    <div>
                        <h4>Storage</h4>
                        <p><?php echo $product['storage_temperature']; ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <h4>Shelf Life</h4>
                        <p><?php echo $product['shelf_life_days']; ?> days</p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-box"></i>
                    <div>
                        <h4>Packaging</h4>
                        <p><?php echo $product['packaging_name']; ?> (<?php echo $product['packaging_capacity']; ?> kg)</p>
                    </div>
                </div>
            </div>
            
            <?php if ($product['certification']): ?>
                <div class="certification">
                    <?php 
                    $certs = explode(',', $product['certification']);
                    foreach ($certs as $cert): 
                    ?>
                        <span class="cert-badge"><?php echo trim($cert); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <p style="color: #7f8c8d; line-height: 1.6;"><?php echo nl2br($product['description']); ?></p>
            
            <div class="stock-info">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Available Stock: <strong><?php echo $product['stock_kg']; ?> kg</strong></span>
                    <span>Minimum Order: <strong><?php echo $product['minimum_order_kg']; ?> kg</strong></span>
                </div>
                <div class="stock-bar">
                    <div class="stock-fill" style="width: <?php echo min(($product['stock_kg'] / 1000) * 100, 100); ?>%;"></div>
                </div>
            </div>
            
            <!-- Batch Selection -->
            <?php if (mysqli_num_rows($batches) > 0): ?>
                <div style="margin: 20px 0;">
                    <label style="font-weight: 600; color: #0a3147;">Select Batch:</label>
                    <select id="batchSelect" style="width: 100%; padding: 10px; margin-top: 5px; border: 2px solid #ecf0f1; border-radius: 8px;">
                        <option value="">Select Batch (by catch date)</option>
                        <?php while ($batch = mysqli_fetch_assoc($batches)): ?>
                            <option value="<?php echo $batch['id']; ?>" data-quantity="<?php echo $batch['current_quantity_kg']; ?>">
                                Batch <?php echo $batch['batch_number']; ?> - Catch: <?php echo date('d M Y', strtotime($batch['catch_date'])); ?> (<?php echo $batch['current_quantity_kg']; ?> kg available)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Quantity Selector -->
            <div class="quantity-selector">
                <span class="quantity-label">Quantity (kg):</span>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="decrement()">-</button>
                    <input type="number" id="quantity" class="quantity-input" value="<?php echo $product['minimum_order_kg']; ?>" min="<?php echo $product['minimum_order_kg']; ?>" max="<?php echo $product['stock_kg']; ?>" step="0.5">
                    <button class="quantity-btn" onclick="increment()">+</button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="action-buttons">
                    <button class="btn-large btn-primary" onclick="addToCart(<?php echo $product['id']; ?>, false)">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn-large btn-secondary" onclick="addToCart(<?php echo $product['id']; ?>, true)">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                </div>
            <?php else: ?>
                <div class="action-buttons">
                    <a href="login.php" class="btn-large btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login to Purchase
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (mysqli_num_rows($related_products) > 0): ?>
        <div class="related-products">
            <h2 class="related-title">Related Products</h2>
            <div class="related-grid">
                <?php while ($related = mysqli_fetch_assoc($related_products)): ?>
                    <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="related-card">
                        <div class="related-image">
                            <i class="fas fa-fish"></i>
                        </div>
                        <div class="related-info">
                            <h4><?php echo $related['name']; ?></h4>
                            <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo $related['species_name']; ?></p>
                            <div class="price">₹<?php echo number_format($related['price_per_kg'], 2); ?>/kg</div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function increment() {
    const input = document.getElementById('quantity');
    const max = parseFloat(input.max);
    let value = parseFloat(input.value) || 0;
    if (value < max) {
        input.value = (value + 0.5).toFixed(1);
    }
}

function decrement() {
    const input = document.getElementById('quantity');
    const min = parseFloat(input.min);
    let value = parseFloat(input.value) || 0;
    if (value > min) {
        input.value = (value - 0.5).toFixed(1);
    }
}

function addToCart(productId, buyNow) {
    const quantity = document.getElementById('quantity').value;
    const batchId = document.getElementById('batchSelect')?.value || null;
    
    fetch('add_to_cart_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            product_id: productId, 
            quantity_kg: quantity,
            batch_id: batchId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            if (buyNow) {
                setTimeout(() => window.location.href = 'cart.php', 1000);
            }
        } else {
            showNotification(data.message || 'Failed to add product', 'error');
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>