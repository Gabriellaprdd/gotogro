<?php
include 'config.php';

$category = isset($_GET['category']) ? $_GET['category'] : '';

function getCategoryPrefix($category) {
    $prefixes = [
        'Dairy' => 'D',
        'Vegetable' => 'V',
        'Fruit' => 'F',
        'Beverage' => 'B',
        'Fruits' => 'F',
        'Pastry' => 'P',
        'Meat' => 'M',
        'Personal Care' => 'PC',
        'Snacks' => 'S',
        'Grains' => 'G',
        'Household Supplies' => 'HS',
    ];
    return $prefixes[$category] ?? strtoupper(substr($category, 0, 1));
}

$query = "SELECT productID, product_name, category FROM product WHERE category = ? ORDER BY productID ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $products = [];
    $seq = 1;
    $prefix = getCategoryPrefix($category);

    while ($row = $result->fetch_assoc()) {
        $row['displayID'] = $prefix . $seq;
        $products[] = $row;
        $seq++;
    }
    echo json_encode($products);
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>