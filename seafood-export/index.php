<?php
require_once 'includes/config.php';
require_once 'includes/header.php';


$featured_query = "SELECT p.*, fs.name as species_name, pt.name as processing_name 
                  FROM products p 
                  LEFT JOIN fish_species fs ON p.species_id = fs.id
                  LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
                  WHERE p.status = 1 AND p.stock_kg > 0 AND p.featured = 1
                  ORDER BY RAND() 
                  LIMIT 8";
$featured_result = mysqli_query($conn, $featured_query);

$species_query = "SELECT * FROM fish_species WHERE status = 1 LIMIT 6";
$species_result = mysqli_query($conn, $species_query);

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT species_id) as total_species,
                COUNT(*) as total_products,
                SUM(stock_kg) as total_stock
                FROM products WHERE status = 1";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #0a3147, #1b4b6c, #0a3147);
        color: white;
        padding: 120px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(0,212,255,0.2) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .hero-content h1 {
        font-size: 3.5rem;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .hero-content p {
        font-size: 1.3rem;
        margin-bottom: 40px;
        opacity: 0.95;
    }
    
    .search-box {
        max-width: 600px;
        margin: 0 auto;
        display: flex;
        gap: 10px;
        background: white;
        padding: 5px;
        border-radius: 50px;
    }
    
    .search-box input {
        flex: 1;
        padding: 15px 25px;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        outline: none;
    }
    
    .search-box button {
        padding: 15px 35px;
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .search-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
    
    /* Stats Section */
    .stats-section {
        padding: 60px 0;
        background: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        text-align: center;
    }
    
    .stat-item {
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: bold;
        color: #00d4ff;
    }
    
    /* Features */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin: 60px 0;
    }
    
    .feature-card {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
    }
    
    .feature-card i {
        font-size: 3rem;
        color: #00d4ff;
        margin-bottom: 20px;
    }
    
    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin: 40px 0;
    }
    
    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    
    .product-image {
        height: 200px;
        background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image i {
        font-size: 4rem;
        color: #00d4ff;
        opacity: 0.5;
    }
    
    .product-info {
        padding: 20px;
    }
    
    .product-info h3 {
        margin-bottom: 10px;
        color: #0a3147;
    }
    
    .product-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,212,255,0.4);
    }
    
    .btn-secondary {
        background: #0a3147;
        color: white;
    }
    
    .section-title {
        text-align: center;
        font-size: 2.5rem;
        color: #0a3147;
        margin-bottom: 50px;
        position: relative;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(90deg, #00d4ff, #0077be);
        border-radius: 2px;
    }
    
    @media (max-width: 768px) {
        .hero-content h1 { font-size: 2rem; }
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="hero-content">
            <h1>Premium Quality Seafood<br>Direct from Ocean to Export</h1>
            <p>HACCP Certified | Global Export | Fresh & Frozen Seafood</p>
            
            <form action="user/products.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search for seafood products...">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_species']; ?>+</div>
                <div style="color: #7f8c8d;">Fish Species</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_products']; ?>+</div>
                <div style="color: #7f8c8d;">Product Varieties</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['total_stock']); ?>kg</div>
                <div style="color: #7f8c8d;">Daily Processing</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section style="padding: 60px 0;">
    <div class="container">
        <h2 class="section-title">Why Choose Us?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-certificate"></i>
                <h3>HACCP Certified</h3>
                <p>International quality standards</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-globe"></i>
                <h3>Global Export</h3>
                <p>Shipping to 20+ countries</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-snowflake"></i>
                <h3>IQF Technology</h3>
                <p>Advanced freezing methods</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-truck"></i>
                <h3>Cold Chain</h3>
                <p>Temperature controlled logistics</p>
            </div>
        </div>
    </div>
</section>

<!-- Species Section -->
<section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
        <h2 class="section-title">Our Fish Species</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php while ($species = mysqli_fetch_assoc($species_result)): ?>
                <a href="user/products.php?species=<?php echo $species['id']; ?>" style="text-decoration: none;">
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                        <i class="fas fa-fish" style="font-size: 3rem; color: #00d4ff; margin-bottom: 15px;"></i>
                        <h3 style="color: #0a3147;"><?php echo $species['name']; ?></h3>
                        <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo $species['scientific_name']; ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section style="padding: 60px 0;">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        
        <?php if (mysqli_num_rows($featured_result) > 0): ?>
            <div class="products-grid">
                <?php while ($product = mysqli_fetch_assoc($featured_result)): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-fish"></i>
                        </div>
                        <div class="product-info">
                            <span style="background: #00d4ff; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.8rem;"><?php echo $product['processing_name']; ?></span>
                            <h3><?php echo $product['name']; ?></h3>
                            <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo $product['species_name']; ?> | <?php echo $product['size_range']; ?></p>
                            <div class="product-price">₹<?php echo number_format($product['price_per_kg'], 2); ?>/kg</div>
                            <div style="display: flex; gap: 10px;">
                                <a href="user/product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-secondary add-to-cart" data-product-id="<?php echo $product['id']; ?>" style="flex: 1;">Add to Cart</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="user/products.php" class="btn btn-primary" style="padding: 15px 40px;">View All Products</a>
        </div>
    </div>
</section>

<script>
// Add to cart functionality
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        fetch('user/add_to_cart_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity_kg: 1 })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product added to cart!', 'success');
            } else {
                showNotification(data.message || 'Failed to add product', 'error');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>