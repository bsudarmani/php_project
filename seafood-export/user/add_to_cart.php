<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    // Check if product exists and has sufficient stock
    $product_query = "SELECT * FROM products WHERE id = '$product_id'";
    $product_result = mysqli_query($conn, $product_query);
    
    if (mysqli_num_rows($product_result) > 0) {
        $product = mysqli_fetch_assoc($product_result);
        
        if ($product['stock'] >= $quantity) {
            // Check if product already in cart
            $check_query = "SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update quantity
                $cart_item = mysqli_fetch_assoc($check_result);
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                if ($new_quantity <= $product['stock']) {
                    $update_query = "UPDATE cart SET quantity = '$new_quantity' WHERE id = '{$cart_item['id']}'";
                    mysqli_query($conn, $update_query);
                    $_SESSION['success'] = "Cart updated successfully!";
                } else {
                    $_SESSION['error'] = "Cannot add more than available stock!";
                }
            } else {
                // Insert new cart item
                $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', '$quantity')";
                mysqli_query($conn, $insert_query);
                $_SESSION['success'] = "Product added to cart!";
            }
        } else {
            $_SESSION['error'] = "Insufficient stock!";
        }
    } else {
        $_SESSION['error'] = "Product not found!";
    }
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? 'products.php';
header("Location: $referer");
exit();
?>