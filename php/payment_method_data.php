<?php
session_start();
include 'config.php';

$filter = $_GET['filter'] ?? 'overall';
$date   = $_GET['date'] ?? '';
$month  = $_GET['month'] ?? '';

$data = [];

switch ($filter) {
    case 'daily':
        if ($date) {
            $startDate = $date . ' 00:00:00';
            $endDate   = $date . ' 23:59:59';
            $stmt = $conn->prepare("
                SELECT
                    payment_method,
                    COUNT(*) AS count
                FROM sales_transactions
                WHERE purchase_date >= ? AND purchase_date <= ?
                GROUP BY payment_method
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        break;

    case 'monthly':
        if ($month) {
            $startDate = $month . '-01 00:00:00';
            $endDate   = date('Y-m-t 23:59:59', strtotime($startDate));
            $stmt = $conn->prepare("
                SELECT
                    payment_method,
                    COUNT(*) AS count
                FROM sales_transactions
                WHERE purchase_date >= ? AND purchase_date <= ?
                GROUP BY payment_method
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        break;

    default:
        $sql = "
            SELECT
                payment_method,
                COUNT(*) AS count
            FROM sales_transactions
            GROUP BY payment_method
        ";
        $result = $conn->query($sql);
        break;
}

if (isset($result) && $result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $row['payment_method'],
            (int) $row['count']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
$conn->close();
?>