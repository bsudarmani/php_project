<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$query = "SELECT o.*, ed.country as destination 
          FROM orders o 
          LEFT JOIN export_destinations ed ON o.export_destination_id = ed.id 
          WHERE o.id = '$order_id' AND o.user_id = '$user_id' AND o.order_status = 'Shipped'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: my_orders.php');
    exit();
}

$order = mysqli_fetch_assoc($result);
?>

<?php include '../includes/header.php'; ?>

<style>
    .tracking-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .tracking-header {
        background: linear-gradient(135deg, #0a3147, #1b4b6c);
        color: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 40px;
    }
    
    .tracking-header h1 {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .tracking-number {
        font-size: 1.5rem;
        color: #00d4ff;
        margin: 15px 0;
    }
    
    .tracking-progress {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    
    .progress-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        width: 100%;
        height: 4px;
        background: #ecf0f1;
        z-index: 1;
    }
    
    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    
    .step-icon {
        width: 40px;
        height: 40px;
        background: #ecf0f1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        color: #7f8c8d;
        transition: all 0.3s ease;
    }
    
    .step.completed .step-icon {
        background: #2ecc71;
        color: white;
    }
    
    .step.active .step-icon {
        background: #00d4ff;
        color: white;
        box-shadow: 0 0 20px rgba(0,212,255,0.5);
    }
    
    .step-label {
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    
    .step.active .step-label {
        color: #00d4ff;
        font-weight: 600;
    }
    
    .shipment-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin: 30px 0;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .detail-item {
        padding: 15px;
        background: white;
        border-radius: 8px;
    }
    
    .detail-item label {
        display: block;
        color: #7f8c8d;
        font-size: 0.85rem;
        margin-bottom: 5px;
    }
    
    .detail-item .value {
        color: #0a3147;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .map-container {
        height: 300px;
        background: #ecf0f1;
        border-radius: 10px;
        margin: 30px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #7f8c8d;
    }
    
    .tracking-updates {
        margin-top: 30px;
    }
    
    .update-item {
        display: flex;
        gap: 20px;
        padding: 15px;
        border-left: 3px solid #00d4ff;
        margin-bottom: 15px;
        background: white;
        border-radius: 0 8px 8px 0;
    }
    
    .update-date {
        min-width: 120px;
        color: #00d4ff;
        font-weight: 600;
    }
    
    .update-status {
        color: #0a3147;
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .update-location {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    .btn-track {
        background: linear-gradient(135deg, #00d4ff, #0077be);
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .btn-track:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0,212,255,0.4);
    }
</style>

<div class="tracking-container">
    <div class="tracking-header">
        <h1>Track Your Shipment</h1>
        <div class="tracking-number">
            <i class="fas fa-box"></i> <?php echo $order['order_number']; ?>
        </div>
        <p>Destination: <?php echo $order['destination']; ?></p>
    </div>
    
    <div class="tracking-progress">
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-icon"><i class="fas fa-check"></i></div>
                <div class="step-label">Order Confirmed</div>
            </div>
            <div class="step completed">
                <div class="step-icon"><i class="fas fa-box"></i></div>
                <div class="step-label">Processing</div>
            </div>
            <div class="step completed">
                <div class="step-icon"><i class="fas fa-boxes"></i></div>
                <div class="step-label">Packed</div>
            </div>
            <div class="step active">
                <div class="step-icon"><i class="fas fa-ship"></i></div>
                <div class="step-label">In Transit</div>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-flag-checkered"></i></div>
                <div class="step-label">Delivered</div>
            </div>
        </div>
        
        <div class="shipment-details">
            <h3 style="color: #0a3147; margin-bottom: 20px;">Shipment Details</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Carrier</label>
                    <div class="value"><?php echo $order['shipping_line'] ?? 'Maersk Line'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Container Number</label>
                    <div class="value"><?php echo $order['container_number'] ?? 'MAEU1234567'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Tracking Number</label>
                    <div class="value"><?php echo $order['tracking_number'] ?? 'TRK' . str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="detail-item">
                    <label>Estimated Delivery</label>
                    <div class="value"><?php echo date('d M Y', strtotime($order['estimated_delivery'] ?? '+15 days')); ?></div>
                </div>
                <div class="detail-item">
                    <label>Port of Loading</label>
                    <div class="value"><?php echo $order['port_of_loading'] ?? 'Mumbai'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Port of Discharge</label>
                    <div class="value"><?php echo $order['port_of_discharge'] ?? 'Los Angeles'; ?></div>
                </div>
            </div>
        </div>
        
        <div class="map-container">
            <i class="fas fa-map-marked-alt" style="font-size: 3rem; opacity: 0.5;"></i>
            <p style="margin-left: 10px;">Live tracking map would be displayed here</p>
        </div>
        
        <div class="tracking-updates">
            <h3 style="color: #0a3147; margin-bottom: 20px;">Tracking Updates</h3>
            
            <div class="update-item">
                <div class="update-date"><?php echo date('d M Y, h:i A'); ?></div>
                <div>
                    <div class="update-status">Shipment departed from Mumbai Port</div>
                    <div class="update-location">Mumbai, India</div>
                </div>
            </div>
            
            <div class="update-item">
                <div class="update-date"><?php echo date('d M Y, h:i A', strtotime('-2 days')); ?></div>
                <div>
                    <div class="update-status">Container loaded on vessel</div>
                    <div class="update-location">Mumbai Port</div>
                </div>
            </div>
            
            <div class="update-item">
                <div class="update-date"><?php echo date('d M Y, h:i A', strtotime('-5 days')); ?></div>
                <div>
                    <div class="update-status">Shipment arrived at Mumbai Port</div>
                    <div class="update-location">Mumbai, India</div>
                </div>
            </div>
            
            <div class="update-item">
                <div class="update-date"><?php echo date('d M Y, h:i A', strtotime('-7 days')); ?></div>
                <div>
                    <div class="update-status">Shipment packed and ready for export</div>
                    <div class="update-location">Processing Facility</div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center;">
            <button class="btn-track" onclick="refreshTracking()">
                <i class="fas fa-sync-alt"></i> Refresh Tracking
            </button>
        </div>
    </div>
</div>

<script>
function refreshTracking() {
    showNotification('Tracking information updated', 'success');
    location.reload();
}
</script>

<?php include '../includes/footer.php'; ?>