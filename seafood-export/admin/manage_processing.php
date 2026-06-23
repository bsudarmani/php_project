<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Processing Type
if (isset($_POST['add_processing'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $shelf_life_days = (int)$_POST['shelf_life_days'];
    $storage_temperature = sanitize($_POST['storage_temperature']);
    
    $query = "INSERT INTO processing_types (name, description, shelf_life_days, storage_temperature) 
              VALUES ('$name', '$description', '$shelf_life_days', '$storage_temperature')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Processing type added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_processing.php');
    exit();
}

// Handle Edit Processing Type
if (isset($_POST['edit_processing'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $shelf_life_days = (int)$_POST['shelf_life_days'];
    $storage_temperature = sanitize($_POST['storage_temperature']);
    
    $query = "UPDATE processing_types SET 
              name='$name', description='$description', 
              shelf_life_days='$shelf_life_days', storage_temperature='$storage_temperature' 
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Processing type updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_processing.php');
    exit();
}

// Handle Delete Processing Type
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $check = mysqli_query($conn, "SELECT id FROM products WHERE processing_type_id = '$id' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Cannot delete as it has associated products.";
    } else {
        mysqli_query($conn, "DELETE FROM processing_types WHERE id='$id'");
        $_SESSION['success'] = "Processing type deleted successfully!";
    }
    header('Location: manage_processing.php');
    exit();
}

$processing = mysqli_query($conn, "SELECT * FROM processing_types ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Processing Types - SeaFood Export</title>
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
        }
        
        .processing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .processing-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .processing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d4ff;
        }
        
        .processing-header h3 {
            color: #0a3147;
            margin: 0;
        }
        
        .details {
            margin-bottom: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
            color: #7f8c8d;
        }
        
        .detail-item strong {
            color: #0a3147;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        
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
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #00d4ff;
            padding-bottom: 10px;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
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
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-industry"></i> Processing Types</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Processing Type
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="processing-grid">
            <?php while ($row = mysqli_fetch_assoc($processing)): ?>
                <div class="processing-card">
                    <div class="processing-header">
                        <h3><?php echo $row['name']; ?></h3>
                    </div>
                    
                    <div class="details">
                        <div class="detail-item">
                            <strong>Shelf Life:</strong> <?php echo $row['shelf_life_days']; ?> days
                        </div>
                        <div class="detail-item">
                            <strong>Storage Temp:</strong> <?php echo $row['storage_temperature']; ?>
                        </div>
                        <?php if ($row['description']): ?>
                            <div class="detail-item">
                                <strong>Description:</strong><br>
                                <?php echo $row['description']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this processing type?')">
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
                <h3>Add Processing Type</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Shelf Life (days) *</label>
                    <input type="number" name="shelf_life_days" required>
                </div>
                <div class="form-group">
                    <label>Storage Temperature *</label>
                    <input type="text" name="storage_temperature" required placeholder="e.g., -20°C">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" name="add_processing" class="btn-add" style="width: 100%;">Save</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Processing Type</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Shelf Life (days) *</label>
                    <input type="number" name="shelf_life_days" id="edit_shelf" required>
                </div>
                <div class="form-group">
                    <label>Storage Temperature *</label>
                    <input type="text" name="storage_temperature" id="edit_temp" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_desc" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_processing" class="btn-add" style="width: 100%;">Update</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_shelf').value = data.shelf_life_days;
            document.getElementById('edit_temp').value = data.storage_temperature;
            document.getElementById('edit_desc').value = data.description || '';
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