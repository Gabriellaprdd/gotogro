<?php

session_start();

include 'config.php';

$columnMaps = [
    'Members' => [
        "First Name"        => "fname",
        "Last Name"         => "lname",
        "Date of Birth"     => "dob",
        "Gender"            => "gender",
        "Email"             => "email",
        "Phone Number"      => "phno",
        "Address"           => "address",
        "Registration Date" => "registration_date"
    ],

    'Products' => [
        "Product Name"        => "product_name",
        "Product Price"       => "product_price",
        "Category"            => "category",
        "Inventory Quantity"  => "inv_qty",
        "Last Restock Date"   => "last_restock_date"
    ],

    'Sales' => [
        "Transaction ID"     => "transactionID",
        "Member ID"          => "memberID",
        "Total Price"        => "total_price",
        "Payment Method"     => "payment_method",
        "Date of Transaction" => "purchase_date"
    ],
];

header('Content-Type: application/json');

$table = $_POST['table'] ?? null;

if (!isset($columnMaps[$table])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid table selected.'
    ]);

    exit;
}

$columnMap = $columnMaps[$table];
$selectedColumns = $_POST['columns'] ?? [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($selectedColumns)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No columns selected.'
        ]);

        exit;
    }

    $mappedColumns = array_map(
        function ($column) use ($columnMap) {
            return "`" . $columnMap[$column] . "`";
        },
        $selectedColumns
    );

    $startDate = mysqli_real_escape_string(
        $conn,
        $_POST['startDate']
    );

    $endDate = mysqli_real_escape_string(
        $conn,
        $_POST['endDate']
    );

    $query = "";

    if ($table === 'Products') {
        $query = "
            SELECT " . implode(", ", $mappedColumns) . "
            FROM `product`
            WHERE DATE(last_restock_date)
            BETWEEN '$startDate' AND '$endDate'
        ";
    } elseif ($table === 'Members') {
        $query = "
            SELECT " . implode(", ", $mappedColumns) . "
            FROM `member`
            WHERE DATE(registration_date)
            BETWEEN '$startDate' AND '$endDate'
        ";
    } elseif ($table === 'Sales') {
        $query = "
            SELECT " . implode(", ", $mappedColumns) . "
            FROM `sales_transactions`
            WHERE DATE(purchase_date)
            BETWEEN '$startDate' AND '$endDate'
        ";
    }

    $result = $conn->query($query);

    if (!$result) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query failed: ' . $conn->error
        ]);

        exit;
    }

    if ($result->num_rows > 0) {

        header('Content-Type: text/csv; charset=utf-8');

        header(
            "Content-Disposition: attachment; filename={$table}_Report_"
            . date('Y-m-d')
            . ".csv"
        );

        $output = fopen('php://output', 'w');

        fputs($output, "\xEF\xBB\xBF");

        fputcsv(
            $output,
            $selectedColumns,
            ',',
            '"',
            ''
        );

        while ($row = $result->fetch_assoc()) {

            $csvRow = array_map(
                function ($col) use ($row, $columnMap) {

                    $dbCol = $columnMap[$col];
                    $val = $row[$dbCol] ?? '';

                    if ($val !== null && $val !== '') {

                        if ($dbCol === 'transactionID') {
                            return (
                                strpos($val, 'T') === 0
                                || strpos($val, 't') === 0
                            )
                                ? $val
                                : 'T' . $val;
                        }

                        if ($dbCol === 'memberID') {
                            return (
                                strpos($val, 'M') === 0
                                || strpos($val, 'm') === 0
                            )
                                ? $val
                                : 'M' . $val;
                        }

                        if ($dbCol === 'phno') {
                            return '="' . $val . '"';
                        }

                        if (
                            in_array(
                                $dbCol,
                                ['product_price', 'total_price']
                            )
                        ) {
                            return '$' . number_format(
                                (float) $val,
                                2,
                                '.',
                                ''
                            );
                        }

                        if (
                            in_array(
                                $dbCol,
                                [
                                    'dob',
                                    'registration_date',
                                    'last_restock_date',
                                    'purchase_date'
                                ]
                            )
                        ) {
                            return date(
                                'Y-m-d',
                                strtotime($val)
                            );
                        }
                    }

                    return $val;
                },
                $selectedColumns
            );

            fputcsv(
                $output,
                $csvRow,
                ',',
                '"',
                ''
            );
        }

        fclose($output);

        exit;

    } else {

        echo json_encode([
            'status'  => 'info',
            'message' => 'No records found for the selected date range and columns.'
        ]);

        exit;
    }
}

?>