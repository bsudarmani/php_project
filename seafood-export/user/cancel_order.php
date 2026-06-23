<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$check_query = "SELECT * FROM orders WHERE id = '$order_id' AND user_id = '$user_id' AND order_status = 'Pending'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 1) {
    $order = mysqli_fetch_assoc($check_result);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Restore stock
        $items_query = "SELECT * FROM order_items WHERE order_id = '$order_id'";
        $items_result = mysqli_query($conn, $items_query);
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity_kg'];
            mysqli_query($conn, "UPDATE products SET stock_kg = stock_kg + $quantity WHERE id = '$product_id'");
        }
        
        // Update order status
        mysqli_query($conn, "UPDATE orders SET order_status = 'Cancelled' WHERE id = '$order_id'");
        
        // Add notification
        mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message, link)
                            VALUES ('$user_id', 'order', 'Order Cancelled',
                            'Your order #{$order['order_number']} has been cancelled.',
                            'my_orders.php')");
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Order cancelled successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to cancel order.";
    }
} else {
    $_SESSION['error'] = "Order not found or cannot be cancelled.";
}

header('Location: my_orders.php');
exit();
?>