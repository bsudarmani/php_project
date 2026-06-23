<?php
require_once '../includes/config.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$species = isset($_GET['species']) ? (int)$_GET['species'] : 0;
$processing = isset($_GET['processing']) ? (int)$_GET['processing'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 5000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$where_conditions = ["p.status = 1"];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR fs.name LIKE '%$search%')";
}

if ($species > 0) {
    $where_conditions[] = "p.species_id = $species";
}

if ($processing > 0) {
    $where_conditions[] = "p.processing_type_id = $processing";
}

if ($min_price > 0) {
    $where_conditions[] = "p.price_per_kg >= $min_price";
}

if ($max_price < 5000) {
    $where_conditions[] = "p.price_per_kg <= $max_price";
}

$where_clause = implode(" AND ", $where_conditions);

// Sorting
$order_by = "p.created_at DESC";
switch($sort) {
    case 'price_low':
        $order_by = "p.price_per_kg ASC";
        break;
    case 'price_high':
        $order_by = "p.price_per_kg DESC";
        break;
    case 'name_asc':
        $order_by = "p.name ASC";
        break;
    default:
        $order_by = "p.created_at DESC";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $limit);

$query = "SELECT p.*, fs.name as species_name, fs.scientific_name, 
          pt.name as processing_name, pt.storage_temperature,
          pkg.name as packaging_name
          FROM products p 
          LEFT JOIN fish_species fs ON p.species_id = fs.id
          LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
          LEFT JOIN packaging_types pkg ON p.packaging_type_id = pkg.id
          WHERE $where_clause 
          ORDER BY $order_by 
          LIMIT $offset, $limit";
$products = mysqli_query($conn, $query);

// Get filters data
$species_list = mysqli_query($conn, "SELECT * FROM fish_species WHERE status = 1");
$processing_list = mysqli_query($conn, "SELECT * FROM processing_types");

// Define image paths
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$project_path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
$base_url .= $project_path;
$image_path = $base_url . '/assets/images/products/';
$default_image = $base_url . '/assets/images/no-image.png';
?>

<?php include '../includes/header.php'; ?>

<style>
    .products-page {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .page-header {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 60px 0;
        text-align: center;
        margin-bottom: 40px;
        border-radius: 0 0 50px 50px;
    }
    
    .products-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
    }
    
    /* Filter Sidebar */
    .filter-sidebar {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    
    .filter-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .filter-title {
        font-size: 1.1rem;
        color: #0a3147;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filter-option {
        margin-bottom: 10px;
    }
    
    .filter-option label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        color: #7f8c8d;
    }
    
    .filter-option input[type="radio"],
    .filter-option input[type="checkbox"] {
        accent-color: #00d4ff;
    }
    
    /* Products Grid */
    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .results-count strong {
        color: #00d4ff;
        font-size: 1.2rem;
    }
    
    .sort-select {
        padding: 10px 20px;
        border: 2px solid #ecf0f1;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
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
        position: relative;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.1);
    }
    
    .product-image i {
        font-size: 4rem;
        color: #00d4ff;
        opacity: 0.5;
    }
    
    .product-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #00d4ff;
        color: white;
        padding: 5px 15px;
        border-radius: 25px;
        font-size: 0.8rem;
        z-index: 2;
    }
    
    .product-info {
        padding: 20px;
    }
    
    .product-species {
        background: #e8f5e9;
        color: #00d4ff;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        display: inline-block;
        margin-bottom: 10px;
    }
    
    .product-info h3 {
        color: #0a3147;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }
    
    .product-details {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .product-price {
        font-size: 1.6rem;
        font-weight: bold;
        color: #00d4ff;
        margin-bottom: 15px;
    }
    
    .product-price small {
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    
    .product-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
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
    
    .btn-outline {
        background: transparent;
        border: 2px solid #00d4ff;
        color: #00d4ff;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }
    
    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: white;
        border-radius: 10px;
        color: #7f8c8d;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .page-link:hover,
    .page-link.active {
        background: #00d4ff;
        color: white;
    }
    
    .no-products {
        text-align: center;
        padding: 80px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .no-products i {
        font-size: 5rem;
        color: #bdc3c7;
        margin-bottom: 20px;
    }
    
    .no-products h3 {
        color: #7f8c8d;
        margin-bottom: 20px;
    }
    
    @media (max-width: 992px) {
        .products-layout {
            grid-template-columns: 1fr;
        }
        
        .filter-sidebar {
            position: static;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-fish"></i> Our Seafood Products</h1>
        <p>Premium quality, sustainably sourced seafood for global export</p>
    </div>
</div>

<div class="products-page">
    <div class="products-layout">
        <!-- Filter Sidebar -->
        <div class="filter-sidebar">
            <h3 style="margin-bottom: 20px; color: #0a3147;"><i class="fas fa-filter"></i> Filters</h3>
            
            <form method="GET" action="" id="filterForm">
                <!-- Search -->
                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-search"></i> Search
                    </div>
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 10px; border: 2px solid #ecf0f1; border-radius: 8px;">
                </div>
                
                <!-- Species -->
                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-fish"></i> Fish Species
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="radio" name="species" value="0" <?php echo $species == 0 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            All Species
                        </label>
                    </div>
                    <?php while ($sp = mysqli_fetch_assoc($species_list)): ?>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="species" value="<?php echo $sp['id']; ?>" <?php echo $species == $sp['id'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <?php echo $sp['name']; ?>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Processing Type -->
                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-industry"></i> Processing
                    </div>
                    <div class="filter-option">
                        <label>
                            <input type="radio" name="processing" value="0" <?php echo $processing == 0 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            All Types
                        </label>
                    </div>
                    <?php while ($pt = mysqli_fetch_assoc($processing_list)): ?>
                        <div class="filter-option">
                            <label>
                                <input type="radio" name="processing" value="<?php echo $pt['id']; ?>" <?php echo $processing == $pt['id'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <?php echo $pt['name']; ?>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Price Range -->
                <div class="filter-section">
                    <div class="filter-title">
                        <i class="fas fa-rupee-sign"></i> Price Range
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>" style="width: 50%; padding: 8px; border: 2px solid #ecf0f1; border-radius: 5px;">
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price; ?>" style="width: 50%; padding: 8px; border: 2px solid #ecf0f1; border-radius: 5px;">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                <a href="products.php" class="btn btn-outline" style="width: 100%; margin-top: 10px; text-align: center;">Reset</a>
            </form>
        </div>
        
        <!-- Products Grid -->
        <div>
            <div class="products-header">
                <div class="results-count">
                    Showing <strong><?php echo mysqli_num_rows($products); ?></strong> of <strong><?php echo $total_products; ?></strong> products
                </div>
                
                <select name="sort" class="sort-select" onchange="window.location.href='?sort='+this.value + '&search=<?php echo urlencode($search); ?>&species=<?php echo $species; ?>&processing=<?php echo $processing; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>'">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                </select>
            </div>
            
            <?php if (mysqli_num_rows($products) > 0): ?>
                <div class="products-grid">
                    <?php while ($product = mysqli_fetch_assoc($products)): 
                        // Check if image exists and build proper path
                        $image_url = $default_image; // Default image
                        if (!empty($product['image'])) {
                            // Check if file exists on server
                            $server_path = $_SERVER['DOCUMENT_ROOT'] . '/seafood-export/assets/images/products/' . $product['image'];
                            if (file_exists($server_path)) {
                                $image_url = $image_path . $product['image'];
                            }
                        }
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if ($image_url != $default_image): ?>
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="fas fa-fish"></i>
                                <?php endif; ?>
                                <span class="product-badge"><?php echo $product['grade']; ?></span>
                            </div>
                            <div class="product-info">
                                <span class="product-species"><?php echo $product['species_name']; ?></span>
                                <h3><?php echo $product['name']; ?></h3>
                                <div class="product-details">
                                    <i class="fas fa-weight"></i> <?php echo $product['size_range'] ?? 'Standard size'; ?><br>
                                    <i class="fas fa-thermometer-half"></i> <?php echo $product['storage_temperature'] ?? 'Cold storage'; ?>
                                </div>
                                <div class="product-price">
                                    ₹<?php echo number_format($product['price_per_kg'], 2); ?><small>/kg</small>
                                </div>
                                <div class="product-actions">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-secondary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i> Add
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-secondary">
                                            <i class="fas fa-sign-in-alt"></i> Login
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="page-link active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-fish"></i>
                    <h3>No Products Found</h3>
                    <p>Try adjusting your filters or search terms.</p>
                    <a href="products.php" class="btn btn-primary" style="padding: 12px 30px; text-decoration: none;">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add to cart functionality
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        // Disable button and show loading state
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        this.disabled = true;
        
        fetch('add_to_cart_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity_kg: 1 })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product added to cart!', 'success');
                
                // Update cart badge if exists
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge && data.cartCount) {
                    cartBadge.textContent = data.cartCount + 'kg';
                }
                
                // Reset button with success message
                this.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            } else {
                showNotification(data.message || 'Failed to add product', 'error');
                this.innerHTML = originalText;
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
            this.innerHTML = originalText;
            this.disabled = false;
        });
    });
});

// Show notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#2ecc71' : '#e74c3c'};
        color: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInRight 0.5s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>