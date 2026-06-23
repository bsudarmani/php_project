<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle Add Quality Check
if (isset($_POST['add_quality_check'])) {
    $batch_id = (int)$_POST['batch_id'];
    $check_date = $_POST['check_date'];
    $checked_by = $_SESSION['admin_id'];
    $temperature = (float)$_POST['temperature'];
    $ph_level = (float)$_POST['ph_level'];
    $organoleptic_score = (int)$_POST['organoleptic_score'];
    $appearance = $_POST['appearance'];
    $odor = $_POST['odor'];
    $texture = $_POST['texture'];
    $microbiological_test = $_POST['microbiological_test'];
    $chemical_test = $_POST['chemical_test'];
    $heavy_metals_test = $_POST['heavy_metals_test'];
    $histamine_level = (float)$_POST['histamine_level'];
    $tvbn_value = (float)$_POST['tvbn_value'];
    $remarks = sanitize($_POST['remarks']);
    $next_check_date = !empty($_POST['next_check_date']) ? $_POST['next_check_date'] : null;
    
    $query = "INSERT INTO quality_checks 
              (batch_id, check_date, checked_by, temperature, ph_level, organoleptic_score,
               appearance, odor, texture, microbiological_test, chemical_test, heavy_metals_test,
               histamine_level, tvbn_value, remarks, next_check_date)
              VALUES ('$batch_id', '$check_date', '$checked_by', '$temperature', '$ph_level',
                      '$organoleptic_score', '$appearance', '$odor', '$texture',
                      '$microbiological_test', '$chemical_test', '$heavy_metals_test',
                      '$histamine_level', '$tvbn_value', '$remarks', " . 
                      ($next_check_date ? "'$next_check_date'" : "NULL") . ")";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Quality check recorded successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header('Location: quality_checks.php');
    exit();
}

// Get batch ID from query
$batch_filter = isset($_GET['batch']) ? (int)$_GET['batch'] : 0;

// Get all batches for dropdown
$batches = mysqli_query($conn, "SELECT ib.id, ib.batch_number, p.name as product_name 
                                FROM inventory_batches ib
                                JOIN products p ON ib.product_id = p.id
                                ORDER BY ib.created_at DESC");

// Get quality checks
$where = $batch_filter ? "WHERE qc.batch_id = '$batch_filter'" : "";
$checks = mysqli_query($conn, "SELECT qc.*, ib.batch_number, p.name as product_name,
                               CONCAT(a.full_name, ' (', a.username, ')') as checker_name
                               FROM quality_checks qc
                               JOIN inventory_batches ib ON qc.batch_id = ib.id
                               JOIN products p ON ib.product_id = p.id
                               LEFT JOIN admin a ON qc.checked_by = a.id
                               $where
                               ORDER BY qc.check_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Checks - SeaFood Export</title>
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
        }
        
        .filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }
        
        .checks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .check-card {
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
        
        .batch-info {
            color: #0a3147;
            font-weight: 600;
        }
        
        .check-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .checker {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        
        .test-item {
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .test-label {
            color: #7f8c8d;
            font-size: 0.8rem;
        }
        
        .test-value {
            color: #0a3147;
            font-weight: 600;
        }
        
        .test-result {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .result-pass {
            background: #2ecc71;
            color: white;
        }
        
        .result-fail {
            background: #e74c3c;
            color: white;
        }
        
        .result-pending {
            background: #f39c12;
            color: white;
        }
        
        .remarks {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-style: italic;
        }
        
        .next-check {
            color: #00d4ff;
            font-weight: 600;
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
            max-width: 800px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
        
        @media (max-width: 768px) {
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
            <h2><i class="fas fa-clipboard-check"></i> Quality Control</h2>
            <button class="btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> New Quality Check
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Filter by Batch</label>
                    <select name="batch" onchange="this.form.submit()">
                        <option value="">All Batches</option>
                        <?php while ($b = mysqli_fetch_assoc($batches)): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $batch_filter == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo $b['batch_number']; ?> - <?php echo $b['product_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if ($batch_filter): ?>
                    <a href="quality_checks.php" class="btn-add" style="text-decoration: none;">Clear Filter</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Quality Checks Grid -->
        <div class="checks-grid">
            <?php if (mysqli_num_rows($checks) > 0): ?>
                <?php while ($check = mysqli_fetch_assoc($checks)): ?>
                    <div class="check-card">
                        <div class="card-header">
                            <span class="batch-info"><?php echo $check['batch_number']; ?></span>
                            <span class="check-date"><?php echo date('d M Y', strtotime($check['check_date'])); ?></span>
                        </div>
                        
                        <div class="checker">
                            <i class="fas fa-user"></i> Checked by: <?php echo $check['checker_name'] ?? 'System'; ?>
                        </div>
                        
                        <div style="font-weight: 600; color: #0a3147; margin-bottom: 10px;">
                            <?php echo $check['product_name']; ?>
                        </div>
                        
                        <div class="test-grid">
                            <div class="test-item">
                                <div class="test-label">Temperature</div>
                                <div class="test-value"><?php echo $check['temperature']; ?>°C</div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">pH Level</div>
                                <div class="test-value"><?php echo $check['ph_level']; ?></div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">Organoleptic</div>
                                <div class="test-value"><?php echo $check['organoleptic_score']; ?>/10</div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">Appearance</div>
                                <div class="test-value"><?php echo $check['appearance']; ?></div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">Odor</div>
                                <div class="test-value"><?php echo $check['odor']; ?></div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">Texture</div>
                                <div class="test-value"><?php echo $check['texture']; ?></div>
                            </div>
                        </div>
                        
                        <div style="margin: 15px 0;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <span>
                                    <span class="test-label">Microbiological:</span>
                                    <span class="test-result <?php echo strtolower($check['microbiological_test']); ?>">
                                        <?php echo $check['microbiological_test']; ?>
                                    </span>
                                </span>
                                <span>
                                    <span class="test-label">Chemical:</span>
                                    <span class="test-result <?php echo strtolower($check['chemical_test']); ?>">
                                        <?php echo $check['chemical_test']; ?>
                                    </span>
                                </span>
                                <span>
                                    <span class="test-label">Heavy Metals:</span>
                                    <span class="test-result <?php echo strtolower($check['heavy_metals_test']); ?>">
                                        <?php echo $check['heavy_metals_test']; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="test-grid">
                            <div class="test-item">
                                <div class="test-label">Histamine (ppm)</div>
                                <div class="test-value"><?php echo $check['histamine_level']; ?></div>
                            </div>
                            <div class="test-item">
                                <div class="test-label">TVBN (mg/100g)</div>
                                <div class="test-value"><?php echo $check['tvbn_value']; ?></div>
                            </div>
                        </div>
                        
                        <?php if ($check['remarks']): ?>
                            <div class="remarks">
                                <i class="fas fa-quote-left" style="color: #00d4ff;"></i>
                                <?php echo $check['remarks']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($check['next_check_date']): ?>
                            <div class="next-check">
                                <i class="fas fa-calendar-alt"></i> Next check: <?php echo date('d M Y', strtotime($check['next_check_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 40px; background: white; border-radius: 10px;">
                    No quality checks found.
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Quality Check Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Quality Check</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Batch *</label>
                    <select name="batch_id" required>
                        <option value="">Select Batch</option>
                        <?php mysqli_data_seek($batches, 0); while($b = mysqli_fetch_assoc($batches)): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $batch_filter == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo $b['batch_number']; ?> - <?php echo $b['product_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Check Date *</label>
                        <input type="date" name="check_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Temperature (°C)</label>
                        <input type="number" name="temperature" step="0.1">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>pH Level</label>
                        <input type="number" name="ph_level" step="0.1" min="0" max="14">
                    </div>
                    <div class="form-group">
                        <label>Organoleptic Score (1-10)</label>
                        <input type="number" name="organoleptic_score" min="1" max="10">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Appearance</label>
                        <select name="appearance">
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Average">Average</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Odor</label>
                        <select name="odor">
                            <option value="">Select</option>
                            <option value="Fresh">Fresh</option>
                            <option value="Neutral">Neutral</option>
                            <option value="Slightly Off">Slightly Off</option>
                            <option value="Offensive">Offensive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Texture</label>
                        <select name="texture">
                            <option value="">Select</option>
                            <option value="Firm">Firm</option>
                            <option value="Slightly Soft">Slightly Soft</option>
                            <option value="Soft">Soft</option>
                            <option value="Mushy">Mushy</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Microbiological Test</label>
                        <select name="microbiological_test">
                            <option value="Pending">Pending</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Chemical Test</label>
                        <select name="chemical_test">
                            <option value="Pending">Pending</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Heavy Metals Test</label>
                        <select name="heavy_metals_test">
                            <option value="Pending">Pending</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Histamine Level (ppm)</label>
                        <input type="number" name="histamine_level" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>TVBN Value (mg/100g)</label>
                        <input type="number" name="tvbn_value" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Next Check Date</label>
                    <input type="date" name="next_check_date">
                </div>
                
                <button type="submit" name="add_quality_check" class="btn-add" style="width: 100%;">Save Quality Check</button>
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
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>