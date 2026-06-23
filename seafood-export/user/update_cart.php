<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    // Verify cart item belongs to user
    $check_query = "SELECT c.*, p.stock FROM cart c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.id = '$cart_id' AND c.user_id = '$user_id'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 1) {
        $cart_item = mysqli_fetch_assoc($check_result);
        
        if ($quantity <= $cart_item['stock']) {
            if ($quantity > 0) {
                $update_query = "UPDATE cart SET quantity = '$quantity' WHERE id = '$cart_id'";
                mysqli_query($conn, $update_query);
                $_SESSION['success'] = "Cart updated successfully!";
            } else {
                // Remove item if quantity is 0
                $delete_query = "DELETE FROM cart WHERE id = '$cart_id'";
                mysqli_query($conn, $delete_query);
                $_SESSION['success'] = "Item removed from cart!";
            }
        } else {
            $_SESSION['error'] = "Quantity exceeds available stock!";
        }
    }
}

header('Location: cart.php');
exit();
?>