<?php

session_start();
include 'config.php';

$filter = $_GET['filter'] ?? 'overall';
$date   = $_GET['date'] ?? '';
$month  = $_GET['month'] ?? '';

$conditionSales = '';

if ($filter === 'daily' && $date) {
    $conditionSales = "WHERE DATE(purchase_date) = '$date'";
} elseif ($filter === 'monthly' && $month) {
    $conditionSales = "WHERE DATE_FORMAT(purchase_date, '%Y-%m') = '$month'";
}

$conditionMember = '';

if ($filter === 'daily' && $date) {
    $conditionMember = "WHERE DATE(registration_date) = '$date'";
} elseif ($filter === 'monthly' && $month) {
    $conditionMember = "WHERE DATE_FORMAT(registration_date, '%Y-%m') = '$month'";
}

$salesQuery = "
    SELECT SUM(total_price) AS total_sales
    FROM sales_transactions
    $conditionSales
";

$salesResult = $conn->query($salesQuery);
$salesData   = $salesResult->fetch_assoc();

$conditionUnits = str_replace(
    "purchase_date",
    "st.purchase_date",
    $conditionSales
);

$unitsQuery = "
    SELECT SUM(ti.quantity_sold) AS total_units
    FROM transaction_item ti
    JOIN sales_transactions st
        ON ti.transactionID = st.transactionID
    $conditionUnits
";

$unitsResult = $conn->query($unitsQuery);
$unitsData   = $unitsResult->fetch_assoc();

$membersQuery = "
    SELECT COUNT(*) AS new_members
    FROM member
    $conditionMember
";

$membersResult = $conn->query($membersQuery);
$membersData   = $membersResult->fetch_assoc();

$data = [
    'total_sales' => $salesData['total_sales'] ?? 0,
    'total_units' => $unitsData['total_units'] ?? 0,
    'new_members' => $membersData['new_members'] ?? 0
];


header('Content-Type: application/json');

echo json_encode($data);

$conn->close();

?>