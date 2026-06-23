<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Species
if (isset($_POST['add_species'])) {
    $name = sanitize($_POST['name']);
    $scientific_name = sanitize($_POST['scientific_name']);
    $local_name = sanitize($_POST['local_name']);
    $description = sanitize($_POST['description']);
    $habitat = sanitize($_POST['habitat']);
    $season = sanitize($_POST['season']);
    
    $query = "INSERT INTO fish_species (name, scientific_name, local_name, description, habitat, season) 
              VALUES ('$name', '$scientific_name', '$local_name', '$description', '$habitat', '$season')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Fish species added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_species.php');
    exit();
}

// Handle Edit Species
if (isset($_POST['edit_species'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $scientific_name = sanitize($_POST['scientific_name']);
    $local_name = sanitize($_POST['local_name']);
    $description = sanitize($_POST['description']);
    $habitat = sanitize($_POST['habitat']);
    $season = sanitize($_POST['season']);
    
    $query = "UPDATE fish_species SET 
              name='$name', scientific_name='$scientific_name', local_name='$local_name',
              description='$description', habitat='$habitat', season='$season' 
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Species updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_species.php');
    exit();
}

// Handle Delete Species
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if species is used in products
    $check = mysqli_query($conn, "SELECT id FROM products WHERE species_id = '$id' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Cannot delete species as it has associated products.";
    } else {
        mysqli_query($conn, "DELETE FROM fish_species WHERE id='$id'");
        $_SESSION['success'] = "Species deleted successfully!";
    }
    header('Location: manage_species.php');
    exit();
}

// Get all species
$species = mysqli_query($conn, "SELECT * FROM fish_species ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fish Species - SeaFood Export</title>
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
            box-shadow: 0 5px 15px rgba(0,212,255,0.4);
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
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            animation: slideInUp 0.5s ease;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d4ff;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
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
        
        .species-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .species-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .species-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .species-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d4ff;
        }
        
        .species-header h3 {
            color: #0a3147;
            margin: 0;
        }
        
        .product-count {
            background: #00d4ff;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .species-details {
            margin-bottom: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
            color: #7f8c8d;
        }
        
        .detail-item strong {
            color: #0a3147;
        }
        
        .species-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-edit:hover, .btn-delete:hover {
            transform: translateY(-2px);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #00d4ff;
            outline: none;
        }
        
        @keyframes slideInUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-fish"></i> Manage Fish Species</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Species
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
        
        <div class="species-grid">
            <?php while ($row = mysqli_fetch_assoc($species)): 
                $product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE species_id = {$row['id']}"))['count'];
            ?>
                <div class="species-card">
                    <div class="species-header">
                        <h3><?php echo $row['name']; ?></h3>
                        <span class="product-count"><?php echo $product_count; ?> products</span>
                    </div>
                    
                    <div class="species-details">
                        <?php if ($row['scientific_name']): ?>
                            <div class="detail-item">
                                <strong>Scientific:</strong> <?php echo $row['scientific_name']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['local_name']): ?>
                            <div class="detail-item">
                                <strong>Local Name:</strong> <?php echo $row['local_name']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['habitat']): ?>
                            <div class="detail-item">
                                <strong>Habitat:</strong> <?php echo $row['habitat']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['season']): ?>
                            <div class="detail-item">
                                <strong>Season:</strong> <?php echo $row['season']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['description']): ?>
                            <div class="detail-item">
                                <strong>Description:</strong><br>
                                <?php echo substr($row['description'], 0, 100); ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="species-actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this species?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add Fish Species</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Common Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Scientific Name</label>
                    <input type="text" name="scientific_name">
                </div>
                <div class="form-group">
                    <label>Local Name</label>
                    <input type="text" name="local_name">
                </div>
                <div class="form-group">
                    <label>Habitat</label>
                    <input type="text" name="habitat" placeholder="e.g., Coastal waters, Deep sea">
                </div>
                <div class="form-group">
                    <label>Season</label>
                    <input type="text" name="season" placeholder="e.g., August-March">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" name="add_species" class="btn-add" style="width: 100%;">Save Species</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Fish Species</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Common Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Scientific Name</label>
                    <input type="text" name="scientific_name" id="edit_scientific">
                </div>
                <div class="form-group">
                    <label>Local Name</label>
                    <input type="text" name="local_name" id="edit_local">
                </div>
                <div class="form-group">
                    <label>Habitat</label>
                    <input type="text" name="habitat" id="edit_habitat">
                </div>
                <div class="form-group">
                    <label>Season</label>
                    <input type="text" name="season" id="edit_season">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_species" class="btn-add" style="width: 100%;">Update Species</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(species) {
            document.getElementById('edit_id').value = species.id;
            document.getElementById('edit_name').value = species.name;
            document.getElementById('edit_scientific').value = species.scientific_name || '';
            document.getElementById('edit_local').value = species.local_name || '';
            document.getElementById('edit_habitat').value = species.habitat || '';
            document.getElementById('edit_season').value = species.season || '';
            document.getElementById('edit_description').value = species.description || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>