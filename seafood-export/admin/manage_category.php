<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Category added successfully!";
    } else {
        $_SESSION['error'] = "Error adding category: " . mysqli_error($conn);
    }
    header('Location: manage_category.php');
    exit();
}

// Handle Edit Category
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $query = "UPDATE categories SET name='$name', description='$description' WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Category updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating category: " . mysqli_error($conn);
    }
    header('Location: manage_category.php');
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM categories WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting category: " . mysqli_error($conn);
    }
    header('Location: manage_category.php');
    exit();
}

// Get all categories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - PMBJK Pharmacy</title>
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
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
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
            animation: slideInRight 0.5s ease;
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
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .category-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2ecc71;
        }
        .category-header h3 {
            color: #2c3e50;
            margin: 0;
        }
        .category-actions {
            display: flex;
            gap: 5px;
        }
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
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
        .product-count {
            display: inline-block;
            padding: 3px 10px;
            background: #2ecc71;
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
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
                <li class="active">
                    <a href="manage_category.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li>
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
                <h2><i class="fas fa-tags"></i> Manage Categories</h2>
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Category
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
            
            <!-- Categories Grid -->
            <div class="category-grid">
                <?php while ($category = mysqli_fetch_assoc($categories)): 
                    $product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = {$category['id']}"))['count'];
                ?>
                    <div class="category-card">
                        <div class="category-header">
                            <h3><?php echo $category['name']; ?></h3>
                            <span class="product-count"><?php echo $product_count; ?> products</span>
                        </div>
                        <p class="category-description"><?php echo $category['description'] ?: 'No description'; ?></p>
                        <div class="category-footer">
                            <small class="text-muted">Created: <?php echo date('d M Y', strtotime($category['created_at'])); ?></small>
                        </div>
                        <div class="category-actions">
                            <button class="btn-edit" onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo $category['name']; ?>', '<?php echo $category['description']; ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?delete=<?php echo $category['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Category</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" required placeholder="Enter category name">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Enter category description"></textarea>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Save Category
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Category</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" id="edit_name" required placeholder="Enter category name">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="4" placeholder="Enter category description"></textarea>
                </div>
                <button type="submit" name="edit_category" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Category
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(id, name, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
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