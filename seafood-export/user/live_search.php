<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (empty($search) || strlen($search) < 2) {
    echo json_encode([]);
    exit();
}


$query = "SELECT p.id, p.name, p.product_code, p.price_per_kg, p.stock_kg,
          fs.name as species_name, pt.name as processing_name
          FROM products p
          LEFT JOIN fish_species fs ON p.species_id = fs.id
          LEFT JOIN processing_types pt ON p.processing_type_id = pt.id
          WHERE p.status = 1 AND (p.name LIKE '%$search%' 
                OR p.product_code LIKE '%$search%'
                OR fs.name LIKE '%$search%')
          LIMIT 10";

$result = mysqli_query($conn, $query);
$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars($row['name']),
        'code' => $row['product_code'],
        'species' => htmlspecialchars($row['species_name']),
        'processing' => htmlspecialchars($row['processing_name']),
        'price' => $row['price_per_kg'],
        'stock' => $row['stock_kg'],
        'url' => 'product_detail.php?id=' . $row['id']
    ];
}

echo json_encode($products);
?>