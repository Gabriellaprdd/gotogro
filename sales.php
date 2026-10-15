<?php
session_start();
include 'php/config.php';

$staffID = '';
$fname = '';
$lname = '';
$dob = '';
$email = '';
$phone = '';
$address = '';
$profile_picture = '';

if (isset($_SESSION['staffID']) || isset($_SESSION['email'])) {

    if (!empty($_SESSION['staffID'])) {
        $sql = "SELECT staffID, fname, lname, dob, email, phone, address, profile_picture FROM staff WHERE staffID = ?";
        $param = $_SESSION['staffID'];
        $paramType = "s";
    } else {
        $sql = "SELECT staffID, fname, lname, dob, email, phone, address, profile_picture FROM staff WHERE email = ?";
        $param = $_SESSION['email'];
        $paramType = "s";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramType, $param);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $staffID = $row['staffID'] ?? '';
        $fname = $row['fname'] ?? '';
        $lname = $row['lname'] ?? '';
        $dob = $row['dob'] ?? '';
        $email = $row['email'] ?? '';
        $phone = $row['phone'] ?? '';
        $address = $row['address'] ?? '';
        $profile_picture = $row['profile_picture'] ?? '';

        $_SESSION['staffID'] = $staffID;
        $_SESSION['email'] = $email;
    } else {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}

$defaultAvatar = 'styles/images/default.png';
$displayPicture = $defaultAvatar;

if (!empty($profile_picture)) {
    $trimmedPic = trim($profile_picture);
    if (strpos($trimmedPic, 'data:image/') === 0 || file_exists($trimmedPic)) {
        $displayPicture = $trimmedPic;
    }
}

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
    <meta name="description" content="Sales Transaction Page">
    <meta name="keywords" content="grocery, sales, transactions">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforsales.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Sales Transaction | GotoGro</title>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="notification <?php echo (strpos($_SESSION['message'], 'successfully') !== false) ? 'success' : 'error'; ?>" id="notification">
            <i class="fa-solid <?php echo (strpos($_SESSION['message'], 'successfully') !== false) ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php else: ?>
        <div class="notification" id="notification" style="display: none;"></div>
    <?php endif; ?>

    <header class="topbar">
        <button class="mobile-toggle-btn" onclick="toggleMobileMenu()" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="profile-logout-container">
            <div class="profile-picture-top">
                <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img" loading="eager" decoding="async">
                <span class="greeting">Welcome, <?php echo htmlspecialchars($fname ?? 'Staff'); ?>!</span>
            </div>
            <div class="logout-button">
                <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
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
                <a href="members.php"><img src="styles/images/members.png" alt=""><span>Members</span></a>
                <a href="inventory.php"><img src="styles/images/inventory.png" alt=""><span>Inventory</span></a>
                <a href="sales.php" class="active"><img src="styles/images/sales.png" alt=""><span>Sales</span></a>
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
            <form id="salesForm" class="sales-form" method="POST" action="php/transaction.php" onsubmit="prepareMemberID()">
                <header class="form-header">
                    <h1>Sales Transaction</h1>
                    <p>Enter member's transaction details and record new sales items.</p>
                </header>

                <div class="form-container">
                    <div class="input-group">
                        <label for="memberID">Member ID</label>
                        <input type="text" name="memberID" id="memberID" value="M" required placeholder="e.g. M1" maxlength="10">
                    </div>

                    <div class="input-group">
                        <label for="numProducts">Number of Products</label>
                        <div class="input-with-button">
                            <input type="number" name="numProducts" id="numProducts" placeholder="Enter number of items" required min="1" max="50" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <button type="button" class="btn ok-button" onclick="generateProductFields()">
                                <i class="fa-solid fa-plus"></i> Add Products
                            </button>
                        </div>
                    </div>

                    <div id="productFields" class="product-fields-wrapper"></div>

                    <div class="input-group">
                        <label for="paymentMethod">Payment Method</label>
                        <div class="select-wrapper">
                            <select name="paymentMethod" id="paymentMethod" required>
                                <option value="">Select payment method</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="transactionDate">Date of Transaction</label>
                        <input type="date" name="transactionDate" id="transactionDate" readonly>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" class="btn record-button">
                        <i class="fa-solid fa-receipt"></i> Record Sale
                    </button>
                    <button type="reset" class="btn clear-button" onclick="resetProductFields()">
                        Clear Form
                    </button>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&#169; 2024 GotoGro Members Record Management System</p>
    </footer>

    <script src="javascript/sales.js"></script>
</body>
</html>