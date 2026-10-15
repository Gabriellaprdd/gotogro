<?php
session_start();

include 'php/config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

$sql = "SELECT fname, profile_picture FROM staff WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($fname, $profile_picture);
$stmt->fetch();
$stmt->close();

$defaultAvatar = 'styles/images/default.png';
$displayPicture = $defaultAvatar;

if (!empty($profile_picture)) {
    $trimmedPic = trim($profile_picture);
    if (strpos($trimmedPic, 'data:image/') === 0 || file_exists($trimmedPic)) {
        $displayPicture = $trimmedPic;
    }
}

$memberID = isset($_GET['memberID']) ? intval($_GET['memberID']) : 0;
$memberName = "Member";

if ($memberID > 0) {
    $memberSql = "SELECT fname, lname FROM member WHERE memberID = ?";
    $memberStmt = $conn->prepare($memberSql);
    $memberStmt->bind_param("i", $memberID);
    $memberStmt->execute();
    $memberResult = $memberStmt->get_result();

    if ($memberResult->num_rows > 0) {
        $memberRow = $memberResult->fetch_assoc();
        $memberName = htmlspecialchars($memberRow['fname'] . ' ' . $memberRow['lname']);
    }

    $memberStmt->close();
}

$sql = "SELECT st.transactionID, st.purchase_date, p.product_name, ti.quantity_sold, p.product_price, st.total_price, st.payment_method
        FROM sales_transactions st
        INNER JOIN transaction_item ti ON st.transactionID = ti.transactionID
        INNER JOIN product p ON ti.productID = p.productID
        WHERE st.memberID = ?
        ORDER BY st.transactionID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $memberID);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[$row['transactionID']][] = $row;
    }
}

$stmt->close();

$hasNotifications = false;
$lowStockCheck = $conn->query("SELECT COUNT(*) AS total FROM product WHERE inv_qty < 20");
if ($lowStockCheck && $row = $lowStockCheck->fetch_assoc()) {
    if ($row['total'] > 0) {
        $hasNotifications = true;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Purchase History Page">
    <meta name="keywords" content="grocery, purchase, history">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforpurchasehistory.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Purchase History | GotoGro</title>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="topbar">
        <button class="mobile-toggle-btn" id="mobileToggleBtn" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="profile-logout-container">
            <div class="profile-picture-top">
                <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img" loading="eager" decoding="async">
                <span class="greeting">Welcome, <?php echo htmlspecialchars($fname ?? 'Staff'); ?>!</span>
            </div>
            <div class="logout-button">
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <header class="sidebar" id="sidebar">
        <nav class="navbar">
            <div class="logo-container">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo" loading="eager" decoding="async">
                <a href="index.php" class="nav-title">GotoGro-MRMS</a>
            </div>
            <div class="nav-links">
                <a href="data.php"><img src="styles/images/analytics.png" alt=""><span>Dashboard</span></a>
                <a href="members.php" class="active"><img src="styles/images/members.png" alt=""><span>Members</span></a>
                <a href="inventory.php"><img src="styles/images/inventory.png" alt=""><span>Inventory</span></a>
                <a href="sales.php"><img src="styles/images/sales.png" alt=""><span>Sales</span></a>
                <a href="report.php"><img src="styles/images/report.png" alt=""><span>Report</span></a>
                <a href="notification.php">
                    <div class="nav-icon-container">
                        <img src="styles/images/notification.png" alt="">
                        <?php if ($hasNotifications): ?>
                            <span class="urgent-dot" id="sidebar-urgent-dot"></span>
                        <?php endif; ?>
                    </div>
                    <span>Notifications</span>
                </a>
                <a href="account.php"><img src="styles/images/account.png" alt=""><span>Account</span></a>
            </div>
        </nav>
    </header>

    <main>
        <section class="content">
            <div class="page-header">
                <a href="members.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Members
                </a>
                <h1>Purchase History</h1>
                <p>Showing past transaction logs for <strong><?= $memberName ?></strong> <span class="member-tag">M<?= $memberID ?></span></p>
            </div>

            <div class="table-card">
                <?php if (!empty($transactions)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Product Name</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transactionID => $items):
                                $rowCount = count($items);
                                $firstItem = $items[0];
                            ?>
                                <tr>
                                    <td rowspan="<?= $rowCount ?>"><span class="txn-tag">#<?= htmlspecialchars($transactionID) ?></span></td>
                                    <td rowspan="<?= $rowCount ?>"><?= htmlspecialchars($firstItem['purchase_date']) ?></td>
                                    <td class="font-medium"><?= htmlspecialchars($firstItem['product_name']) ?></td>
                                    <td><?= htmlspecialchars($firstItem['quantity_sold']) ?></td>
                                    <td>$<?= number_format((float)$firstItem['product_price'], 2) ?></td>
                                    <td rowspan="<?= $rowCount ?>" class="price-total">$<?= number_format((float)$firstItem['total_price'], 2) ?></td>
                                    <td rowspan="<?= $rowCount ?>">
                                        <span class="payment-badge"><?= ucfirst(htmlspecialchars($firstItem['payment_method'])) ?></span>
                                    </td>
                                </tr>
                                <?php for ($i = 1; $i < $rowCount; $i++):
                                    $item = $items[$i];
                                ?>
                                    <tr>
                                        <td class="font-medium"><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity_sold']) ?></td>
                                        <td>$<?= number_format((float)$item['product_price'], 2) ?></td>
                                    </tr>
                                <?php endfor; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fa-solid fa-receipt icon-empty"></i>
                        <p>No purchase records found for this member.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&#169; 2024 GotoGro Members Record Management System</p>
    </footer>

    <script src="javascript/purchasehistory.js"></script>
</body>
</html>