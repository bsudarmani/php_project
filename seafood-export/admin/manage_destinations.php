<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Destination
if (isset($_POST['add_destination'])) {
    $country = sanitize($_POST['country']);
    $country_code = sanitize($_POST['country_code']);
    $region = sanitize($_POST['region']);
    $currency = sanitize($_POST['currency']);
    $currency_symbol = sanitize($_POST['currency_symbol']);
    $exchange_rate = (float)$_POST['exchange_rate'];
    $tax_rate = (float)$_POST['tax_rate'];
    $duty_percentage = (float)$_POST['duty_percentage'];
    $shipping_multiplier = (float)$_POST['shipping_multiplier'];
    $documentation_requirements = sanitize($_POST['documentation_requirements']);
    $restrictions = sanitize($_POST['restrictions']);
    
    $query = "INSERT INTO export_destinations 
              (country, country_code, region, currency, currency_symbol, exchange_rate, 
               tax_rate, duty_percentage, shipping_multiplier, documentation_requirements, restrictions) 
              VALUES ('$country', '$country_code', '$region', '$currency', '$currency_symbol', 
                      '$exchange_rate', '$tax_rate', '$duty_percentage', '$shipping_multiplier', 
                      '$documentation_requirements', '$restrictions')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Destination added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_destinations.php');
    exit();
}

// Handle Edit Destination
if (isset($_POST['edit_destination'])) {
    $id = (int)$_POST['id'];
    $country = sanitize($_POST['country']);
    $country_code = sanitize($_POST['country_code']);
    $region = sanitize($_POST['region']);
    $currency = sanitize($_POST['currency']);
    $currency_symbol = sanitize($_POST['currency_symbol']);
    $exchange_rate = (float)$_POST['exchange_rate'];
    $tax_rate = (float)$_POST['tax_rate'];
    $duty_percentage = (float)$_POST['duty_percentage'];
    $shipping_multiplier = (float)$_POST['shipping_multiplier'];
    $documentation_requirements = sanitize($_POST['documentation_requirements']);
    $restrictions = sanitize($_POST['restrictions']);
    
    $query = "UPDATE export_destinations SET 
              country='$country', country_code='$country_code', region='$region',
              currency='$currency', currency_symbol='$currency_symbol', exchange_rate='$exchange_rate',
              tax_rate='$tax_rate', duty_percentage='$duty_percentage', 
              shipping_multiplier='$shipping_multiplier',
              documentation_requirements='$documentation_requirements', restrictions='$restrictions'
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Destination updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: manage_destinations.php');
    exit();
}

// Handle Delete Destination
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $check = mysqli_query($conn, "SELECT id FROM orders WHERE export_destination_id = '$id' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Cannot delete as it has associated orders.";
    } else {
        mysqli_query($conn, "DELETE FROM export_destinations WHERE id='$id'");
        $_SESSION['success'] = "Destination deleted successfully!";
    }
    header('Location: manage_destinations.php');
    exit();
}

$destinations = mysqli_query($conn, "SELECT * FROM export_destinations ORDER BY country");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Export Destinations - SeaFood Export</title>
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
        
        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .destination-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00d4ff;
        }
        
        .card-header h3 {
            color: #0a3147;
            margin: 0;
        }
        
        .country-code {
            background: #00d4ff;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .detail-item .label {
            color: #7f8c8d;
            font-size: 0.8rem;
            margin-bottom: 3px;
        }
        
        .detail-item .value {
            color: #0a3147;
            font-weight: 600;
        }
        
        .requirements {
            margin: 15px 0;
            padding: 10px;
            background: #fff3cd;
            border-radius: 5px;
            font-size: 0.9rem;
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
        .form-group textarea {
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
            <h2><i class="fas fa-globe"></i> Export Destinations</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Destination
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="destinations-grid">
            <?php while ($row = mysqli_fetch_assoc($destinations)): ?>
                <div class="destination-card">
                    <div class="card-header">
                        <h3><?php echo $row['country']; ?></h3>
                        <span class="country-code"><?php echo $row['country_code']; ?></span>
                    </div>
                    
                    <div class="details">
                        <div class="detail-item">
                            <div class="label">Currency</div>
                            <div class="value"><?php echo $row['currency']; ?> (<?php echo $row['currency_symbol']; ?>)</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Exchange Rate</div>
                            <div class="value">1 INR = <?php echo number_format(1/$row['exchange_rate'], 4); ?> <?php echo $row['currency']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Tax Rate</div>
                            <div class="value"><?php echo $row['tax_rate']; ?>%</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Duty</div>
                            <div class="value"><?php echo $row['duty_percentage']; ?>%</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Shipping Multiplier</div>
                            <div class="value"><?php echo $row['shipping_multiplier']; ?>x</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Region</div>
                            <div class="value"><?php echo $row['region'] ?? 'N/A'; ?></div>
                        </div>
                    </div>
                    
                    <?php if ($row['documentation_requirements']): ?>
                        <div class="requirements">
                            <strong>Docs Required:</strong><br>
                            <?php echo $row['documentation_requirements']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this destination?')">
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
                <h3>Add Export Destination</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" required>
                    </div>
                    <div class="form-group">
                        <label>Country Code *</label>
                        <input type="text" name="country_code" required maxlength="3">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Region</label>
                        <input type="text" name="region">
                    </div>
                    <div class="form-group">
                        <label>Currency *</label>
                        <input type="text" name="currency" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Currency Symbol *</label>
                        <input type="text" name="currency_symbol" required>
                    </div>
                    <div class="form-group">
                        <label>Exchange Rate *</label>
                        <input type="number" name="exchange_rate" step="0.0001" required value="1.0000">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" name="tax_rate" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Duty Percentage (%)</label>
                        <input type="number" name="duty_percentage" step="0.01" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Shipping Multiplier</label>
                    <input type="number" name="shipping_multiplier" step="0.01" value="1.00">
                </div>
                
                <div class="form-group">
                    <label>Documentation Requirements</label>
                    <textarea name="documentation_requirements" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Restrictions</label>
                    <textarea name="restrictions" rows="2"></textarea>
                </div>
                
                <button type="submit" name="add_destination" class="btn-add" style="width: 100%;">Save Destination</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Export Destination</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Country *</label>
                        <input type="text" name="country" id="edit_country" required>
                    </div>
                    <div class="form-group">
                        <label>Country Code *</label>
                        <input type="text" name="country_code" id="edit_code" required maxlength="3">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Region</label>
                        <input type="text" name="region" id="edit_region">
                    </div>
                    <div class="form-group">
                        <label>Currency *</label>
                        <input type="text" name="currency" id="edit_currency" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Currency Symbol *</label>
                        <input type="text" name="currency_symbol" id="edit_symbol" required>
                    </div>
                    <div class="form-group">
                        <label>Exchange Rate *</label>
                        <input type="number" name="exchange_rate" id="edit_rate" step="0.0001" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="edit_tax" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Duty Percentage (%)</label>
                        <input type="number" name="duty_percentage" id="edit_duty" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Shipping Multiplier</label>
                    <input type="number" name="shipping_multiplier" id="edit_shipping" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Documentation Requirements</label>
                    <textarea name="documentation_requirements" id="edit_docs" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Restrictions</label>
                    <textarea name="restrictions" id="edit_restrictions" rows="2"></textarea>
                </div>
                
                <button type="submit" name="edit_destination" class="btn-add" style="width: 100%;">Update Destination</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_country').value = data.country;
            document.getElementById('edit_code').value = data.country_code;
            document.getElementById('edit_region').value = data.region || '';
            document.getElementById('edit_currency').value = data.currency;
            document.getElementById('edit_symbol').value = data.currency_symbol;
            document.getElementById('edit_rate').value = data.exchange_rate;
            document.getElementById('edit_tax').value = data.tax_rate;
            document.getElementById('edit_duty').value = data.duty_percentage;
            document.getElementById('edit_shipping').value = data.shipping_multiplier;
            document.getElementById('edit_docs').value = data.documentation_requirements || '';
            document.getElementById('edit_restrictions').value = data.restrictions || '';
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