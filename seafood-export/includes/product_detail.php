<?php
$page_title = 'Product Details';
require_once '../includes/config.php';

$product_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Get product details
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = $product_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Product not found";
    redirect('products.php');
}

$product = mysqli_fetch_assoc($result);

// Get related products (same category)
$related_query = "SELECT * FROM products 
                  WHERE category_id = {$product['category_id']} 
                  AND id != $product_id 
                  AND stock > 0 
                  LIMIT 4";
$related_products = mysqli_query($conn, $related_query);

require_once '../includes/header.php';
?>

<div class="product-detail-page">
    <div class="product-detail-container">
        <div class="product-image">
            <img src="<?php echo SITE_URL; ?>assets/images/<?php echo $product['image']; ?>" 
                 alt="<?php echo $product['name']; ?>"
                 onerror="this.src='https://via.placeholder.com/400'">
        </div>
        
        <div class="product-info">
            <h1><?php echo $product['name']; ?></h1>
            <p class="category">Category: <?php echo $product['category_name']; ?></p>
            
            <div class="price-section">
                <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
                <?php if ($product['stock'] > 0): ?>
                    <span class="stock in-stock">In Stock (<?php echo $product['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="stock out-of-stock">Out of Stock</span>
                <?php endif; ?>
            </div>
            
            <div class="description">
                <h3>Description</h3>
                <p><?php echo nl2br($product['description']); ?></p>
            </div>
            
            <?php if ($product['disease_tags']): ?>
            <div class="disease-tags">
                <h3>Used for:</h3>
                <div class="tags">
                    <?php 
                    $tags = explode(',', $product['disease_tags']);
                    foreach ($tags as $tag): 
                    ?>
                        <a href="products.php?disease=<?php echo urlencode(trim($tag)); ?>" class="tag">
                            <?php echo trim($tag); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($product['stock'] > 0): ?>
                <form action="cart.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-large">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary btn-large" disabled>Out of Stock</button>
            <?php endif; ?>
            
            <div class="product-meta">
                <p><strong>Product ID:</strong> PMBJK-<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Added on:</strong> <?php echo date('d-m-Y', strtotime($product['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <?php if (mysqli_num_rows($related_products) > 0): ?>
    <div class="related-products">
        <h2>Related Products</h2>
        <div class="products-grid">
            <?php while ($related = mysqli_fetch_assoc($related_products)): ?>
                <div class="product-card">
                    <img src="<?php echo SITE_URL; ?>assets/images/<?php echo $related['image']; ?>" 
                         alt="<?php echo $related['name']; ?>"
                         onerror="this.src='https://via.placeholder.com/200'">
                    <h3><?php echo $related['name']; ?></h3>
                    <p class="price">₹<?php echo number_format($related['price'], 2); ?></p>
                    <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="btn btn-primary">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>