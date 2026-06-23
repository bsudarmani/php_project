<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'seafood_export';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Site URL
define('SITE_URL', 'http://localhost/seafood-export/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('USER_URL', SITE_URL . 'user/');
define('ASSETS_URL', SITE_URL . 'assets/');

// Function to generate order number
function generateOrderNumber() {
    return 'SEAF-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Function to generate batch number
function generateBatchNumber($product_code, $catch_date) {
    return $product_code . '-' . date('Ymd', strtotime($catch_date)) . '-B' . rand(1, 99);
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to get cart total kg
function getCartTotalKg($user_id) {
    global $conn;
    if (!$user_id) return 0;
    
    $query = "SELECT SUM(quantity_kg) as total FROM cart WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    return 0;
}

// Function to convert currency
function convertCurrency($amount_inr, $to_currency, $exchange_rate = null) {
    if ($exchange_rate) {
        return $amount_inr / $exchange_rate;
    }
    
    $query = "SELECT exchange_rate FROM export_destinations WHERE currency = '$to_currency' LIMIT 1";
    global $conn;
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $amount_inr / $row['exchange_rate'];
    }
    return $amount_inr;
}

// Function to check low stock
function checkLowStock($product_id, $threshold = 100) {
    global $conn;
    $query = "SELECT stock_kg FROM products WHERE id = '$product_id'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['stock_kg'] <= $threshold;
    }
    return false;
}

// Function to add notification
function addNotification($user_id, $type, $title, $message, $link = null) {
    global $conn;
    $user_id = $user_id ? "'$user_id'" : "NULL";
    $link = $link ? "'$link'" : "NULL";
    $query = "INSERT INTO notifications (user_id, type, title, message, link) 
              VALUES ($user_id, '$type', '$title', '$message', $link)";
    return mysqli_query($conn, $query);
}
?>