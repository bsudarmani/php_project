<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity_kg = isset($input['quantity_kg']) ? (float)$input['quantity_kg'] : 1;

if ($product_id <= 0 || $quantity_kg <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check product availability
$product_query = "SELECT * FROM products WHERE id = '$product_id' AND status = 1";
$product_result = mysqli_query($conn, $product_query);

if (mysqli_num_rows($product_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = mysqli_fetch_assoc($product_result);

if ($product['stock_kg'] < $quantity_kg) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $product['stock_kg'] . 'kg available']);
    exit();
}

// Check if already in cart
$check_query = "SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    $cart_item = mysqli_fetch_assoc($check_result);
    $new_quantity = $cart_item['quantity_kg'] + $quantity_kg;
    
    if ($new_quantity > $product['stock_kg']) {
        echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
        exit();
    }
    
    $update_query = "UPDATE cart SET quantity_kg = '$new_quantity' WHERE id = '{$cart_item['id']}'";
    mysqli_query($conn, $update_query);
} else {
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity_kg) VALUES ('$user_id', '$product_id', '$quantity_kg')";
    mysqli_query($conn, $insert_query);
}

// Get updated cart count
$cart_count = getCartTotalKg($user_id);

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cartCount' => $cart_count
]);
?>