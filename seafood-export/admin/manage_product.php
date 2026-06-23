<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Product
if (isset($_POST['add_product'])) {
    $category_id = (int)$_POST['category_id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $disease_tags = sanitize($_POST['disease_tags']);
    
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
    
    $query = "INSERT INTO products (category_id, name, description, price, stock, disease_tags, image) 
              VALUES ('$category_id', '$name', '$description', '$price', '$stock', '$disease_tags', '$image')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Product added successfully!";
    } else {
        $_SESSION['error'] = "Error adding product: " . mysqli_error($conn);
    }
    header('Location: manage_product.php');
    exit();
}

// Handle Edit Product
if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $category_id = (int)$_POST['category_id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $disease_tags = sanitize($_POST['disease_tags']);
    
    $query = "UPDATE products SET 
              category_id='$category_id', 
              name='$name', 
              description='$description', 
              price='$price', 
              stock='$stock', 
              disease_tags='$disease_tags' 
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Product updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating product: " . mysqli_error($conn);
    }
    header('Location: manage_product.php');
    exit();
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if product is used in orders
    $check_query = "SELECT id FROM order_items WHERE product_id = '$id' LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Cannot delete product as it is referenced in orders.";
    } else {
        $query = "DELETE FROM products WHERE id='$id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting product: " . mysqli_error($conn);
        }
    }
    header('Location: manage_product.php');
    exit();
}

// Get all products with category names
$products = mysqli_query($conn, "SELECT p.*, c.name as category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.id 
                                 ORDER BY p.id DESC");

// Get categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - PMBJK Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header i {
            font-size: 3rem;
            color: #2ecc71;
            margin-bottom: 10px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        .sidebar-menu li {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .sidebar-menu li:hover, .sidebar-menu li.active {
            background: rgba(46, 204, 113, 0.2);
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: #f5f5f5;
        }
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
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
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
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideInUp 0.5s ease;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2ecc71;
        }
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: all 0.3s ease;
        }
        .close:hover {
            color: #e74c3c;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }
        .product-table th {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 12px;
            text-align: left;
        }
        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        .product-table tr:hover {
            background: #f8f9fa;
        }
        .stock-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 2px;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .product-image-preview {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2ecc71;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-clinic-medical"></i>
                <h3>PMBJK Pharmacy</h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li>
                    <a href="manage_category.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="active">
                    <a href="manage_product.php"><i class="fas fa-pills"></i> Products</a>
                </li>
                <li>
                    <a href="view_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                </li>
                <li>
                    <a href="report.php"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h2><i class="fas fa-pills"></i> Manage Products</h2>
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
            
            <!-- Products Table -->
            <div class="table-container">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Disease Tags</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products)): 
                            // Determine stock status
                            $stock_class = 'stock-high';
                            if ($product['stock'] <= 10) {
                                $stock_class = 'stock-low';
                            } elseif ($product['stock'] <= 50) {
                                $stock_class = 'stock-medium';
                            }
                        ?>
                            <tr>
                                <td>#<?php echo $product['id']; ?></td>
                                <td>
                                    <div class="product-image-preview">
                                        <?php if ($product['image']): ?>
                                            <img src="../assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-pills"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><strong><?php echo $product['name']; ?></strong></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $product['stock']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($product['disease_tags']) {
                                        $tags = explode(',', $product['disease_tags']);
                                        $display_tags = array_slice($tags, 0, 2);
                                        echo implode(', ', $display_tags);
                                        if (count($tags) > 2) {
                                            echo '...';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $product['id']; ?>, <?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        mysqli_data_seek($categories, 0);
                        while ($cat = mysqli_fetch_assoc($categories)): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required placeholder="Enter product name">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" required placeholder="Enter product description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" step="0.01" min="0" required placeholder="Enter price">
                </div>
                
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" min="0" required placeholder="Enter stock quantity">
                </div>
                
                <div class="form-group">
                    <label>Disease Tags (comma separated)</label>
                    <input type="text" name="disease_tags" placeholder="e.g., fever, headache, cold">
                    <small style="color: #7f8c8d;">Enter diseases this medicine is used for, separated by commas</small>
                </div>
                
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                
                <button type="submit" name="add_product" class="btn btn-primary" style="width: 100%;">
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
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="edit_category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        mysqli_data_seek($categories, 0);
                        while ($cat = mysqli_fetch_assoc($categories)): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" required placeholder="Enter product name">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="4" required placeholder="Enter product description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0" required placeholder="Enter price">
                </div>
                
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" id="edit_stock" min="0" required placeholder="Enter stock quantity">
                </div>
                
                <div class="form-group">
                    <label>Disease Tags (comma separated)</label>
                    <input type="text" name="disease_tags" id="edit_disease_tags" placeholder="e.g., fever, headache, cold">
                </div>
                
                <button type="submit" name="edit_product" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(id, product) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_disease_tags').value = product.disease_tags || '';
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>