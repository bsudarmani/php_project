<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Product
if (isset($_POST['add_product'])) {
    $species_id = (int)$_POST['species_id'];
    $processing_type_id = (int)$_POST['processing_type_id'];
    $packaging_type_id = (int)$_POST['packaging_type_id'];
    $product_code = sanitize($_POST['product_code']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $grade = sanitize($_POST['grade']);
    $size_range = sanitize($_POST['size_range']);
    $catch_area = sanitize($_POST['catch_area']);
    $price_per_kg = (float)$_POST['price_per_kg'];
    $stock_kg = (float)$_POST['stock_kg'];
    $minimum_order_kg = (float)$_POST['minimum_order_kg'];
    $moisture_content = !empty($_POST['moisture_content']) ? (float)$_POST['moisture_content'] : 'NULL';
    $fat_content = !empty($_POST['fat_content']) ? (float)$_POST['fat_content'] : 'NULL';
    $protein_content = !empty($_POST['protein_content']) ? (float)$_POST['protein_content'] : 'NULL';
    $preservation_method = sanitize($_POST['preservation_method']);
    $certification = sanitize($_POST['certification']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Image uploaded successfully
        } else {
            $image = '';
        }
    }
    
    $query = "INSERT INTO products (species_id, processing_type_id, packaging_type_id, product_code, 
              name, description, grade, size_range, catch_area, price_per_kg, stock_kg, 
              minimum_order_kg, moisture_content, fat_content, protein_content, 
              preservation_method, certification, image, featured) 
              VALUES ('$species_id', '$processing_type_id', '$packaging_type_id', '$product_code', 
                      '$name', '$description', '$grade', '$size_range', '$catch_area', 
                      '$price_per_kg', '$stock_kg', '$minimum_order_kg', $moisture_content, 
                      $fat_content, $protein_content, '$preservation_method', '$certification', 
                      '$image', '$featured')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Product added successfully!";
    } else {
        $_SESSION['error'] = "Error adding product: " . mysqli_error($conn);
    }
    header('Location: manage_products.php');
    exit();
}

// Handle Edit Product
if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $species_id = (int)$_POST['species_id'];
    $processing_type_id = (int)$_POST['processing_type_id'];
    $packaging_type_id = (int)$_POST['packaging_type_id'];
    $product_code = sanitize($_POST['product_code']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $grade = sanitize($_POST['grade']);
    $size_range = sanitize($_POST['size_range']);
    $catch_area = sanitize($_POST['catch_area']);
    $price_per_kg = (float)$_POST['price_per_kg'];
    $minimum_order_kg = (float)$_POST['minimum_order_kg'];
    $moisture_content = !empty($_POST['moisture_content']) ? (float)$_POST['moisture_content'] : 'NULL';
    $fat_content = !empty($_POST['fat_content']) ? (float)$_POST['fat_content'] : 'NULL';
    $protein_content = !empty($_POST['protein_content']) ? (float)$_POST['protein_content'] : 'NULL';
    $preservation_method = sanitize($_POST['preservation_method']);
    $certification = sanitize($_POST['certification']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    $query = "UPDATE products SET 
              species_id='$species_id', processing_type_id='$processing_type_id',
              packaging_type_id='$packaging_type_id', product_code='$product_code',
              name='$name', description='$description', grade='$grade',
              size_range='$size_range', catch_area='$catch_area',
              price_per_kg='$price_per_kg', minimum_order_kg='$minimum_order_kg',
              moisture_content=$moisture_content, fat_content=$fat_content,
              protein_content=$protein_content, preservation_method='$preservation_method',
              certification='$certification', featured='$featured'
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Product updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating product: " . mysqli_error($conn);
    }
    header('Location: manage_products.php');
    exit();
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if product is used in orders
    $check_query = "SELECT id FROM order_items WHERE product_id = '$id' LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Cannot delete product as it has order history. Consider deactivating it instead.";
    } else {
        // Check if product has batches
        $batch_check = mysqli_query($conn, "SELECT id FROM inventory_batches WHERE product_id = '$id' LIMIT 1");
        if (mysqli_num_rows($batch_check) > 0) {
            // Soft delete - just deactivate
            mysqli_query($conn, "UPDATE products SET status = 0 WHERE id = '$id'");
            $_SESSION['success'] = "Product deactivated successfully!";
        } else {
            // Hard delete
            mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
            $_SESSION['success'] = "Product deleted successfully!";
        }
    }
    header('Location: manage_products.php');
    exit();
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM products WHERE id='$id'"));
    $new_status = $current['status'] ? 0 : 1;
    mysqli_query($conn, "UPDATE products SET status='$new_status' WHERE id='$id'");
    $_SESSION['success'] = "Product status updated!";
    header('Location: manage_products.php');
    exit();
}

// Get all products with related data
$products = mysqli_query($conn, "SELECT p.*, 
                                 fs.name as species_name, 
                                 pt.name as processing_name,
                                 pkg.name as packaging_name,
                                 (SELECT COUNT(*) FROM inventory_batches WHERE product_id = p.id) as batch_count,
                                 (SELECT SUM(current_quantity_kg) FROM inventory_batches WHERE product_id = p.id) as total_stock
                                 FROM products p 
                                 LEFT JOIN fish_species fs ON p.species_id = fs.id 
                                 LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
                                 LEFT JOIN packaging_types pkg ON p.packaging_type_id = pkg.id
                                 ORDER BY p.id DESC");

// Get dropdown data
$species = mysqli_query($conn, "SELECT * FROM fish_species WHERE status = 1 ORDER BY name");
$processing = mysqli_query($conn, "SELECT * FROM processing_types ORDER BY name");
$packaging = mysqli_query($conn, "SELECT * FROM packaging_types ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - SeaFood Export</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }
        
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 250px; padding: 30px; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-add {
            background: linear-gradient(135deg, #00d4ff, #0077be);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.4);
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #0a3147;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00d4ff;
            font-size: 1.5rem;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background: #2ecc71;
            color: white;
        }
        
        .status-inactive {
            background: #e74c3c;
            color: white;
        }
        
        .grade-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            background: #00d4ff;
            color: white;
        }
        
        .grade-premium { background: #f1c40f; color: #2c3e50; }
        .grade-a { background: #2ecc71; color: white; }
        .grade-b { background: #3498db; color: white; }
        .grade-c { background: #e67e22; color: white; }
        
        .stock-info {
            margin: 5px 0;
        }
        
        .stock-bar {
            width: 100px;
            height: 6px;
            background: #ecf0f1;
            border-radius: 3px;
        }
        
        .stock-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #0077be);
            border-radius: 3px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-edit, .btn-toggle, .btn-delete, .btn-view {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-edit { background: #3498db; color: white; }
        .btn-toggle { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-view { background: #2ecc71; color: white; }
        
        .btn-edit:hover, .btn-toggle:hover, .btn-delete:hover, .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00d4ff;
        }
        
        .modal-header h3 {
            color: #0a3147;
            font-size: 1.5rem;
        }
        
        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #e74c3c;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
            padding: 10px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #00d4ff;
            outline: none;
            box-shadow: 0 0 10px rgba(0,212,255,0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            accent-color: #00d4ff;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #00d4ff, #0077be);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.4);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-grid,
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-boxes"></i> Manage Products</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" id="searchInput" placeholder="Product name or code...">
            </div>
            <div class="filter-group">
                <label>Species</label>
                <select id="speciesFilter">
                    <option value="">All Species</option>
                    <?php mysqli_data_seek($species, 0); while($s = mysqli_fetch_assoc($species)): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Processing Type</label>
                <select id="processingFilter">
                    <option value="">All Types</option>
                    <?php mysqli_data_seek($processing, 0); while($p = mysqli_fetch_assoc($processing)): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select id="statusFilter">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button class="btn-filter" onclick="filterTable()">Apply Filters</button>
        </div>
        
        <!-- Products Table -->
        <div class="table-container">
            <table id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Species</th>
                        <th>Processing</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <tr data-species="<?php echo $product['species_id']; ?>" 
                            data-processing="<?php echo $product['processing_type_id']; ?>"
                            data-status="<?php echo $product['status']; ?>">
                            <td>#<?php echo $product['id']; ?></td>
                            <td>
                                <div class="product-image">
                                    <?php if ($product['image']): ?>
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>" 
                                             alt="<?php echo $product['name']; ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <i class="fas fa-fish"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo $product['name']; ?></strong><br>
                                <small style="color: #7f8c8d;">Code: <?php echo $product['product_code']; ?></small><br>
                                <span class="grade-badge grade-<?php echo strtolower($product['grade']); ?>">
                                    <?php echo $product['grade']; ?> Grade
                                </span>
                                <?php if ($product['size_range']): ?>
                                    <br><small><?php echo $product['size_range']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['species_name'] ?? '-'; ?></td>
                            <td><?php echo $product['processing_name'] ?? '-'; ?></td>
                            <td>
                                <strong>₹<?php echo number_format($product['price_per_kg'], 2); ?>/kg</strong><br>
                                <small>Min: <?php echo $product['minimum_order_kg']; ?> kg</small>
                            </td>
                            <td>
                                <div class="stock-info">
                                    <strong><?php echo number_format($product['total_stock'] ?? $product['stock_kg'], 2); ?> kg</strong>
                                </div>
                                <div class="stock-bar">
                                    <?php 
                                    $max_stock = 1000; // Example max
                                    $percentage = min(($product['total_stock'] ?? $product['stock_kg']) / $max_stock * 100, 100);
                                    ?>
                                    <div class="stock-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <small><?php echo $product['batch_count']; ?> batches</small>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $product['status'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $product['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?toggle=<?php echo $product['id']; ?>" class="btn-toggle" onclick="return confirm('Toggle product status?')">
                                        <i class="fas <?php echo $product['status'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                    </a>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Delete this product?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="../user/product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fish Species *</label>
                        <select name="species_id" required>
                            <option value="">Select Species</option>
                            <?php mysqli_data_seek($species, 0); while($s = mysqli_fetch_assoc($species)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Processing Type *</label>
                        <select name="processing_type_id" required>
                            <option value="">Select Processing</option>
                            <?php mysqli_data_seek($processing, 0); while($p = mysqli_fetch_assoc($processing)): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Packaging Type *</label>
                        <select name="packaging_type_id" required>
                            <option value="">Select Packaging</option>
                            <?php mysqli_data_seek($packaging, 0); while($pk = mysqli_fetch_assoc($packaging)): ?>
                                <option value="<?php echo $pk['id']; ?>"><?php echo $pk['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Code *</label>
                        <input type="text" name="product_code" required placeholder="e.g., PRAWN-001">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Product Name *</label>
                        <input type="text" name="name" required placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Product description..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Grade *</label>
                        <select name="grade" required>
                            <option value="Premium">Premium</option>
                            <option value="A">A Grade</option>
                            <option value="B">B Grade</option>
                            <option value="C">C Grade</option>
                            <option value="Standard">Standard</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Size Range</label>
                        <input type="text" name="size_range" placeholder="e.g., 20-30 pcs/kg">
                    </div>
                    
                    <div class="form-group">
                        <label>Catch Area</label>
                        <input type="text" name="catch_area" placeholder="e.g., Bay of Bengal">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₹/kg) *</label>
                            <input type="number" name="price_per_kg" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Stock (kg) *</label>
                            <input type="number" name="stock_kg" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Min Order (kg) *</label>
                            <input type="number" name="minimum_order_kg" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Moisture (%)</label>
                            <input type="number" name="moisture_content" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label>Fat Content (%)</label>
                            <input type="number" name="fat_content" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label>Protein (%)</label>
                            <input type="number" name="protein_content" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Preservation Method</label>
                        <input type="text" name="preservation_method" placeholder="e.g., IQF, Ice packed">
                    </div>
                    
                    <div class="form-group">
                        <label>Certifications</label>
                        <input type="text" name="certification" placeholder="e.g., HACCP, BRC, MSC">
                    </div>
                    
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="featured" id="featured">
                        <label for="featured">Featured Product</label>
                    </div>
                </div>
                
                <button type="submit" name="add_product" class="btn-save">
                    <i class="fas fa-save"></i> Save Product
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Product</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fish Species *</label>
                        <select name="species_id" id="edit_species_id" required>
                            <option value="">Select Species</option>
                            <?php mysqli_data_seek($species, 0); while($s = mysqli_fetch_assoc($species)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Processing Type *</label>
                        <select name="processing_type_id" id="edit_processing_id" required>
                            <option value="">Select Processing</option>
                            <?php mysqli_data_seek($processing, 0); while($p = mysqli_fetch_assoc($processing)): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Packaging Type *</label>
                        <select name="packaging_type_id" id="edit_packaging_id" required>
                            <option value="">Select Packaging</option>
                            <?php mysqli_data_seek($packaging, 0); while($pk = mysqli_fetch_assoc($packaging)): ?>
                                <option value="<?php echo $pk['id']; ?>"><?php echo $pk['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Code *</label>
                        <input type="text" name="product_code" id="edit_product_code" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Product Name *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Grade *</label>
                        <select name="grade" id="edit_grade" required>
                            <option value="Premium">Premium</option>
                            <option value="A">A Grade</option>
                            <option value="B">B Grade</option>
                            <option value="C">C Grade</option>
                            <option value="Standard">Standard</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Size Range</label>
                        <input type="text" name="size_range" id="edit_size_range">
                    </div>
                    
                    <div class="form-group">
                        <label>Catch Area</label>
                        <input type="text" name="catch_area" id="edit_catch_area">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₹/kg) *</label>
                            <input type="number" name="price_per_kg" id="edit_price" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Min Order (kg) *</label>
                            <input type="number" name="minimum_order_kg" id="edit_min_order" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Moisture (%)</label>
                            <input type="number" name="moisture_content" id="edit_moisture" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label>Fat Content (%)</label>
                            <input type="number" name="fat_content" id="edit_fat" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label>Protein (%)</label>
                            <input type="number" name="protein_content" id="edit_protein" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Preservation Method</label>
                        <input type="text" name="preservation_method" id="edit_preservation">
                    </div>
                    
                    <div class="form-group">
                        <label>Certifications</label>
                        <input type="text" name="certification" id="edit_certification">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="featured" id="edit_featured">
                        <label for="edit_featured">Featured Product</label>
                    </div>
                </div>
                
                <button type="submit" name="edit_product" class="btn-save">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_species_id').value = product.species_id;
            document.getElementById('edit_processing_id').value = product.processing_type_id;
            document.getElementById('edit_packaging_id').value = product.packaging_type_id;
            document.getElementById('edit_product_code').value = product.product_code;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_grade').value = product.grade;
            document.getElementById('edit_size_range').value = product.size_range || '';
            document.getElementById('edit_catch_area').value = product.catch_area || '';
            document.getElementById('edit_price').value = product.price_per_kg;
            document.getElementById('edit_min_order').value = product.minimum_order_kg;
            document.getElementById('edit_moisture').value = product.moisture_content || '';
            document.getElementById('edit_fat').value = product.fat_content || '';
            document.getElementById('edit_protein').value = product.protein_content || '';
            document.getElementById('edit_preservation').value = product.preservation_method || '';
            document.getElementById('edit_certification').value = product.certification || '';
            document.getElementById('edit_featured').checked = product.featured == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const speciesFilter = document.getElementById('speciesFilter').value;
            const processingFilter = document.getElementById('processingFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                const productName = row.cells[2].textContent.toLowerCase();
                const productCode = row.cells[2].innerHTML.toLowerCase();
                const species = row.dataset.species;
                const processing = row.dataset.processing;
                const status = row.dataset.status;
                
                const matchesSearch = searchInput === '' || 
                    productName.includes(searchInput) || 
                    productCode.includes(searchInput);
                const matchesSpecies = speciesFilter === '' || species == speciesFilter;
                const matchesProcessing = processingFilter === '' || processing == processingFilter;
                const matchesStatus = statusFilter === '' || status == statusFilter;
                
                row.style.display = matchesSearch && matchesSpecies && matchesProcessing && matchesStatus ? '' : 'none';
            });
        }
        
        // Real-time search
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('speciesFilter').addEventListener('change', filterTable);
        document.getElementById('processingFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>