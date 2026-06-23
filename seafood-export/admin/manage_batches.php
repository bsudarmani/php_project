<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Batch
if (isset($_POST['add_batch'])) {
    $product_id = (int)$_POST['product_id'];
    $batch_number = sanitize($_POST['batch_number']);
    $catch_date = $_POST['catch_date'];
    $processing_date = $_POST['processing_date'];
    $expiry_date = $_POST['expiry_date'];
    $initial_quantity_kg = (float)$_POST['initial_quantity_kg'];
    $storage_location = sanitize($_POST['storage_location']);
    $certificate_number = sanitize($_POST['certificate_number']);
    
    $query = "INSERT INTO inventory_batches 
              (product_id, batch_number, catch_date, processing_date, expiry_date, 
               initial_quantity_kg, current_quantity_kg, storage_location, certificate_number) 
              VALUES ('$product_id', '$batch_number', '$catch_date', '$processing_date', 
                      '$expiry_date', '$initial_quantity_kg', '$initial_quantity_kg', 
                      '$storage_location', '$certificate_number')";
    
    if (mysqli_query($conn, $query)) {
        // Update product stock
        mysqli_query($conn, "UPDATE products SET stock_kg = stock_kg + $initial_quantity_kg WHERE id = '$product_id'");
        $_SESSION['success'] = "Batch added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_batches.php');
    exit();
}

// Handle Update Batch Quality
if (isset($_POST['update_quality'])) {
    $batch_id = (int)$_POST['batch_id'];
    $quality_status = $_POST['quality_status'];
    $checked_by = $_SESSION['admin_id'];
    
    $query = "UPDATE inventory_batches SET 
              quality_check_status = '$quality_status', checked_by = '$checked_by', check_date = CURDATE()
              WHERE id = '$batch_id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Batch quality updated!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_batches.php');
    exit();
}

// Get products for dropdown
$products = mysqli_query($conn, "SELECT p.*, fs.name as species_name 
                                 FROM products p 
                                 LEFT JOIN fish_species fs ON p.species_id = fs.id 
                                 WHERE p.status = 1 ORDER BY p.name");

// Get all batches
$batches = mysqli_query($conn, "SELECT ib.*, p.name as product_name, p.product_code,
                                 CONCAT(a.full_name, ' (', a.username, ')') as checked_by_name
                                 FROM inventory_batches ib
                                 JOIN products p ON ib.product_id = p.id
                                 LEFT JOIN admin a ON ib.checked_by = a.id
                                 ORDER BY ib.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Batches - SeaFood Export</title>
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
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
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
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-pending { background: #f39c12; color: white; }
        .badge-passed { background: #2ecc71; color: white; }
        .badge-failed { background: #e74c3c; color: white; }
        .badge-quarantine { background: #e67e22; color: white; }
        
        .status-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-status {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-pass { background: #2ecc71; color: white; }
        .btn-fail { background: #e74c3c; color: white; }
        .btn-quarantine { background: #e67e22; color: white; }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #0077be);
            border-radius: 4px;
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
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            <h2><i class="fas fa-layer-group"></i> Inventory Batches</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Batch
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Filter by Product</label>
                <select id="productFilter" onchange="filterTable()">
                    <option value="">All Products</option>
                    <?php mysqli_data_seek($products, 0); while($p = mysqli_fetch_assoc($products)): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Filter by Status</label>
                <select id="statusFilter" onchange="filterTable()">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                    <option value="Quarantine">Quarantine</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Search</label>
                <input type="text" id="searchInput" placeholder="Batch number..." onkeyup="filterTable()">
            </div>
        </div>
        
        <!-- Batches Table -->
        <div class="table-container">
            <table id="batchesTable">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Product</th>
                        <th>Catch Date</th>
                        <th>Expiry</th>
                        <th>Quantity</th>
                        <th>Used %</th>
                        <th>Location</th>
                        <th>Quality Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($batch = mysqli_fetch_assoc($batches)): 
                        $used_percentage = (($batch['initial_quantity_kg'] - $batch['current_quantity_kg']) / $batch['initial_quantity_kg']) * 100;
                    ?>
                        <tr data-product="<?php echo $batch['product_id']; ?>" data-status="<?php echo $batch['quality_check_status']; ?>">
                            <td><strong><?php echo $batch['batch_number']; ?></strong></td>
                            <td>
                                <?php echo $batch['product_name']; ?><br>
                                <small style="color: #7f8c8d;"><?php echo $batch['product_code']; ?></small>
                            </td>
                            <td><?php echo date('d M Y', strtotime($batch['catch_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($batch['expiry_date'])); ?></td>
                            <td>
                                <strong><?php echo number_format($batch['current_quantity_kg'], 2); ?> kg</strong><br>
                                <small style="color: #7f8c8d;">of <?php echo number_format($batch['initial_quantity_kg'], 2); ?> kg</small>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $used_percentage; ?>%;"></div>
                                </div>
                                <small><?php echo number_format($used_percentage, 1); ?>% used</small>
                            </td>
                            <td><?php echo $batch['storage_location']; ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch($batch['quality_check_status']) {
                                    case 'Pending': $status_class = 'badge-pending'; break;
                                    case 'Passed': $status_class = 'badge-passed'; break;
                                    case 'Failed': $status_class = 'badge-failed'; break;
                                    case 'Quarantine': $status_class = 'badge-quarantine'; break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo $batch['quality_check_status']; ?>
                                </span>
                                <?php if ($batch['checked_by_name']): ?>
                                    <br><small>by <?php echo $batch['checked_by_name']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($batch['quality_check_status'] == 'Pending'): ?>
                                    <div class="status-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                            <input type="hidden" name="quality_status" value="Passed">
                                            <button type="submit" name="update_quality" class="btn-status btn-pass" onclick="return confirm('Mark as Passed?')">
                                                <i class="fas fa-check"></i> Pass
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                            <input type="hidden" name="quality_status" value="Failed">
                                            <button type="submit" name="update_quality" class="btn-status btn-fail" onclick="return confirm('Mark as Failed?')">
                                                <i class="fas fa-times"></i> Fail
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                            <input type="hidden" name="quality_status" value="Quarantine">
                                            <button type="submit" name="update_quality" class="btn-status btn-quarantine" onclick="return confirm('Move to Quarantine?')">
                                                <i class="fas fa-exclamation-triangle"></i> Quarantine
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <a href="quality_checks.php?batch=<?php echo $batch['id']; ?>" class="btn-status" style="background: #3498db; color: white; text-decoration: none;">
                                        <i class="fas fa-clipboard-list"></i> View QC
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Batch Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Batch</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Product *</label>
                    <select name="product_id" required>
                        <option value="">Select Product</option>
                        <?php mysqli_data_seek($products, 0); while($p = mysqli_fetch_assoc($products)): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?> (<?php echo $p['product_code']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Batch Number *</label>
                    <input type="text" name="batch_number" required placeholder="e.g., PRAWN-20240301-B1">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Catch Date *</label>
                        <input type="date" name="catch_date" required>
                    </div>
                    <div class="form-group">
                        <label>Processing Date *</label>
                        <input type="date" name="processing_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date *</label>
                        <input type="date" name="expiry_date" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity (kg) *</label>
                        <input type="number" name="initial_quantity_kg" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Storage Location</label>
                    <input type="text" name="storage_location" placeholder="e.g., Freezer A-12">
                </div>
                
                <div class="form-group">
                    <label>Certificate Number</label>
                    <input type="text" name="certificate_number" placeholder="QC certificate #">
                </div>
                
                <button type="submit" name="add_batch" class="btn-add" style="width: 100%;">Add Batch</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function filterTable() {
            const productFilter = document.getElementById('productFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#batchesTable tbody tr');
            
            rows.forEach(row => {
                const product = row.dataset.product;
                const status = row.dataset.status;
                const batchText = row.cells[0].textContent.toLowerCase();
                
                const productMatch = !productFilter || product == productFilter;
                const statusMatch = !statusFilter || status == statusFilter;
                const searchMatch = !searchInput || batchText.includes(searchInput);
                
                row.style.display = productMatch && statusMatch && searchMatch ? '' : 'none';
            });
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>