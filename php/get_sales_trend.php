<?php
include 'config.php';

error_reporting(0);
header('Content-Type: application/json');

if (isset($_GET['productID'])) {
    $rawProductID = $_GET['productID'];
    $productID = preg_replace('/[^0-9]/', '', $rawProductID);

    $sql = "
        SELECT
            DATE_FORMAT(st.purchase_date, '%b %y') AS month_label,
            MIN(st.purchase_date) AS sort_date,
            SUM(ti.subtotal_price) AS sales
        FROM sales_transactions st
        JOIN transaction_item ti
            ON st.transactionID = ti.transactionID
        WHERE ti.productID = ?
        GROUP BY DATE_FORMAT(st.purchase_date, '%Y-%m'), DATE_FORMAT(st.purchase_date, '%b %y')
        ORDER BY sort_date
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "error" => "SQL Error: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    $salesTrend = [];
    while ($row = $result->fetch_assoc()) {
        $salesTrend[] = $row;
    }

    echo json_encode($salesTrend);
}
?>