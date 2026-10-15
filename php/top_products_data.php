<?php
include('config.php');

$filter = $_GET['filter'] ?? 'overall';
$date   = $_GET['date'] ?? $_GET['month'] ?? '';

$colors = [
    '#3f6844',
    '#14b8a6',
    '#3b82f6',
    '#8b5cf6',
    '#f97316'
];

$topProducts = [];

if ($filter === 'daily' && !empty($date)) {
    $startDate = $date . ' 00:00:00';
    $endDate   = $date . ' 23:59:59';

    $stmt = $conn->prepare("
        SELECT
            p.product_name,
            SUM(ti.quantity_sold) AS units_sold,
            p.category
        FROM sales_transactions st
        JOIN transaction_item ti
            ON st.transactionID = ti.transactionID
        JOIN product p
            ON ti.productID = p.productID
        WHERE st.purchase_date >= ? AND st.purchase_date <= ?
        GROUP BY
            p.productID,
            p.product_name,
            p.category
        ORDER BY units_sold DESC
        LIMIT 5
    ");

    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

} elseif ($filter === 'monthly' && !empty($date)) {
    $startDate = $date . '-01 00:00:00';
    $endDate   = date('Y-m-t 23:59:59', strtotime($startDate));

    $stmt = $conn->prepare("
        SELECT
            p.product_name,
            SUM(ti.quantity_sold) AS units_sold,
            p.category
        FROM sales_transactions st
        JOIN transaction_item ti
            ON st.transactionID = ti.transactionID
        JOIN product p
            ON ti.productID = p.productID
        WHERE st.purchase_date >= ? AND st.purchase_date <= ?
        GROUP BY
            p.productID,
            p.product_name,
            p.category
        ORDER BY units_sold DESC
        LIMIT 5
    ");

    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

} else {
    $query = "
        SELECT
            p.product_name,
            SUM(ti.quantity_sold) AS units_sold,
            p.category
        FROM sales_transactions st
        JOIN transaction_item ti
            ON st.transactionID = ti.transactionID
        JOIN product p
            ON ti.productID = p.productID
        GROUP BY
            p.productID,
            p.product_name,
            p.category
        ORDER BY units_sold DESC
        LIMIT 5
    ";

    $result = mysqli_query($conn, $query);
}

if ($result) {
    $colorIndex = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $topProducts[] = [
            'product_name' => $row['product_name'],
            'units_sold'   => (int) $row['units_sold'],
            'color'        => $colors[$colorIndex % count($colors)]
        ];

        $colorIndex++;
    }
}

header('Content-Type: application/json');
echo json_encode($topProducts);
$conn->close();
?>