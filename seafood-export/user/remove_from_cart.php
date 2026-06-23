<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if (isset($_GET['id'])) {
    $cart_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify cart item belongs to user
    $delete_query = "DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success'] = "Item removed from cart!";
    } else {
        $_SESSION['error'] = "Failed to remove item!";
    }
}

header('Location: cart.php');
exit();
?>