<?php

session_start();

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../sales.php");
    exit();
}

$rawMemberID = $_POST['memberID'] ?? '';
$memberID = preg_replace('/[^0-9]/', '', $rawMemberID);

$numProducts = isset($_POST['numProducts'])
    ? intval($_POST['numProducts'])
    : 0;

$paymentMethod = $_POST['paymentMethod'] ?? '';
$transactionDate = $_POST['transactionDate'] ?? date('Y-m-d');


if (empty($memberID)) {
    $_SESSION['message'] = "Invalid Member ID format.";

    header("Location: ../sales.php");
    exit();
}

if ($numProducts <= 0) {
    $_SESSION['message'] = "Please specify at least 1 product.";

    header("Location: ../sales.php");
    exit();
}

$conn->begin_transaction();

try {
    $stmtMember = $conn->prepare(
        "SELECT memberID FROM member WHERE memberID = ?"
    );

    if (!$stmtMember) {
        $stmtMember = $conn->prepare(
            "SELECT memberID FROM member WHERE memberID = ?"
        );
    }

    if (!$stmtMember) {
        throw new Exception(
            "Database schema mismatch for member lookup."
        );
    }

    $stmtMember->bind_param(
        "i",
        $memberID
    );

    $stmtMember->execute();

    $resultMember = $stmtMember->get_result();

    if ($resultMember->num_rows === 0) {
        throw new Exception(
            "Member ID 'M$memberID' does not exist."
        );
    }

    $stmtMember->close();

    $insertTransaction = "
        INSERT INTO sales_transactions (
            memberID,
            total_price,
            purchase_date,
            payment_method
        )
        VALUES (?, 0, ?, ?)
    ";

    $stmt = $conn->prepare($insertTransaction);

    if (!$stmt) {
        throw new Exception(
            "Failed to prepare sales transaction query."
        );
    }

    $stmt->bind_param(
        "iss",
        $memberID,
        $transactionDate,
        $paymentMethod
    );

    $stmt->execute();

    $transactionID = $conn->insert_id;

    $stmt->close();

    $totalPrice = 0.0;

    for ($i = 1; $i <= $numProducts; $i++) {
        $rawProductID = trim(
            $_POST["productID$i"] ?? ''
        );

        $quantity = isset($_POST["quantity$i"])
            ? intval($_POST["quantity$i"])
            : 0;


        if (empty($rawProductID) || $quantity <= 0) {
            throw new Exception(
                "Please specify a valid Product ID and Quantity for item #$i."
            );
        }

        $productID = null;
        $productPrice = 0.0;
        $inventoryQuantity = 0;


        if (preg_match('/^([A-Z]+)(\d+)$/i', $rawProductID, $matches)) {
            $prefix = strtoupper($matches[1]);
            $seq = intval($matches[2]);

            $categoryMap = [
                'D'  => ['Dairy'],
                'V'  => ['Vegetable', 'Vegetables'],
                'F'  => ['Fruit', 'Fruits'],
                'B'  => ['Beverage', 'Beverages'],
                'P'  => ['Pastry', 'Pastries'],
                'M'  => ['Meat', 'Meats'],
                'PC' => ['Personal Care'],
                'S'  => ['Snacks', 'Snack'],
                'G'  => ['Grains', 'Grain'],
                'HS' => ['Household Supplies']
            ];

            if (isset($categoryMap[$prefix])) {
                $categories = $categoryMap[$prefix];

                $placeholders = implode(
                    ',',
                    array_fill(0, count($categories), '?')
                );

                $offset = $seq - 1;

                $tableName = 'product';

                $checkTable = $conn->query(
                    "SHOW TABLES LIKE 'product'"
                );

                if ($checkTable && $checkTable->num_rows === 0) {
                    $tableName = 'products';
                }

                $query = "
                    SELECT
                        productID,
                        product_price,
                        inv_qty
                    FROM $tableName
                    WHERE category IN ($placeholders)
                    ORDER BY productID ASC
                    LIMIT 1 OFFSET ?
                ";

                $stmtSeq = $conn->prepare($query);

                if ($stmtSeq) {
                    $types = str_repeat(
                        's',
                        count($categories)
                    ) . 'i';

                    $params = array_merge(
                        $categories,
                        [$offset]
                    );

                    $stmtSeq->bind_param(
                        $types,
                        ...$params
                    );

                    $stmtSeq->execute();

                    $resSeq = $stmtSeq->get_result();

                    if ($resSeq && $rowSeq = $resSeq->fetch_assoc()) {
                        $productID = intval(
                            $rowSeq['productID']
                        );

                        $productPrice = floatval(
                            $rowSeq['product_price']
                        );

                        $inventoryQuantity = intval(
                            $rowSeq['inv_qty']
                        );
                    }

                    $stmtSeq->close();
                }
            }
        }

        if ($productID === null) {
            $cleanID = intval(
                preg_replace(
                    '/[^0-9]/',
                    '',
                    $rawProductID
                )
            );

            if ($cleanID > 0) {
                $tableName = 'product';

                $stmtProd = $conn->prepare(
                    "SELECT productID, product_price, inv_qty
                     FROM product
                     WHERE productID = ?"
                );

                if (!$stmtProd) {
                    $tableName = 'products';

                    $stmtProd = $conn->prepare(
                        "SELECT productID, product_price, inv_qty
                         FROM products
                         WHERE productID = ?"
                    );
                }

                if ($stmtProd) {
                    $stmtProd->bind_param(
                        "i",
                        $cleanID
                    );

                    $stmtProd->execute();

                    $resProd = $stmtProd->get_result();

                    if ($resProd && $rowProd = $resProd->fetch_assoc()) {
                        $productID = intval(
                            $rowProd['productID']
                        );

                        $productPrice = floatval(
                            $rowProd['product_price']
                        );

                        $inventoryQuantity = intval(
                            $rowProd['inv_qty']
                        );
                    }

                    $stmtProd->close();
                }
            }
        }

        if ($productID === null) {
            throw new Exception(
                "Product ID '$rawProductID' could not be found."
            );
        }

        if ($inventoryQuantity < $quantity) {
            throw new Exception(
                "Insufficient stock for Product ID '$rawProductID'. "
                . "Available: $inventoryQuantity, Requested: $quantity."
            );
        }

        $subtotal = $productPrice * $quantity;
        $totalPrice += $subtotal;

        $insertItem = "
            INSERT INTO transaction_item (
                transactionID,
                productID,
                quantity_sold,
                subtotal_price
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmtItem = $conn->prepare($insertItem);

        if (!$stmtItem) {
            throw new Exception(
                "Failed to prepare transaction item query."
            );
        }

        $stmtItem->bind_param(
            "iiid",
            $transactionID,
            $productID,
            $quantity,
            $subtotal
        );

        $stmtItem->execute();
        $stmtItem->close();

        $newQuantity = $inventoryQuantity - $quantity;

        $updateInventory = "
            UPDATE product
            SET inv_qty = ?
            WHERE productID = ?
        ";

        $stmtUpdate = $conn->prepare($updateInventory);

        if (!$stmtUpdate) {
            $updateInventory = "
                UPDATE products
                SET inv_qty = ?
                WHERE productID = ?
            ";

            $stmtUpdate = $conn->prepare($updateInventory);
        }

        if (!$stmtUpdate) {
            throw new Exception(
                "Failed to prepare inventory update query."
            );
        }

        $stmtUpdate->bind_param(
            "ii",
            $newQuantity,
            $productID
        );

        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    $updateTransaction = "
        UPDATE sales_transactions
        SET total_price = ?
        WHERE transactionID = ?
    ";

    $stmtFinal = $conn->prepare($updateTransaction);

    if (!$stmtFinal) {
        throw new Exception(
            "Failed to prepare total price update."
        );
    }

    $stmtFinal->bind_param(
        "di",
        $totalPrice,
        $transactionID
    );

    $stmtFinal->execute();
    $stmtFinal->close();

    $conn->commit();

    $_SESSION['message'] = "Transaction has been recorded successfully!";

    header("Location: ../sales.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['message'] = $e->getMessage();

    header("Location: ../sales.php");
    exit();
}

?>