<div class="admin-sidebar" style="width: 250px; background: linear-gradient(135deg, #0a3147, #1b4b6c); color: white; position: fixed; height: 100vh; overflow-y: auto;">
    <div style="padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <i class="fas fa-fish" style="font-size: 3rem; color: #00d4ff; margin-bottom: 10px;"></i>
        <h3 style="color: white;">SeaFood Export</h3>
        <p style="color: #b0c4de; font-size: 0.9rem;">Admin Panel</p>
    </div>
    
    <ul style="list-style: none; padding: 20px 0;">
        <li style="margin-bottom: 5px;">
            <a href="dashboard.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="manage_species.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'manage_species.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-fish"></i> Fish Species
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="manage_processing.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'manage_processing.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-industry"></i> Processing Types
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="manage_products.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-boxes"></i> Products
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="manage_batches.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'manage_batches.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-layer-group"></i> Batches
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="manage_destinations.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'manage_destinations.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-globe"></i> Export Destinations
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="quality_checks.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'quality_checks.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-clipboard-check"></i> Quality Checks
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="view_orders.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'view_orders.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-ship"></i> Export Orders
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="report.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px; <?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'background: rgba(0,212,255,0.2);' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </li>
        <li style="margin-bottom: 5px; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <a href="logout.php" style="color: white; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<style>
    .admin-sidebar a:hover {
        background: rgba(0,212,255,0.1);
        padding-left: 25px !important;
        transition: all 0.3s ease;
    }
</style>