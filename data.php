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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Data Analytics Page">
    <meta name="keywords" content="grocery, data, analytics">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link rel="preload" as="script" href="https://www.gstatic.com/charts/loader.js">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="styles/stylefordata.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Data Analytics | GotoGro</title>
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
                <a href="data.php" class="active"><img src="styles/images/analytics.png" alt=""><span>Dashboard</span></a>
                <a href="members.php"><img src="styles/images/members.png" alt=""><span>Members</span></a>
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
                <h1>Analytics Dashboard</h1>
                <p>Monitor sales performance, payment preferences, and product trends.</p>
            </div>

            <div class="filter-toolbar">
                <div class="filter-group">
                    <label for="filterType"><i class="fa-solid fa-filter"></i> View Mode:</label>
                    <div class="select-wrapper">
                        <select id="filterType" onchange="updateFilterInputs()" class="filter-select">
                            <option value="overall" selected>Overall</option>
                            <option value="daily">Daily</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <label id="dailyDateLabel" for="dailyDate" style="display: none;"><i class="fa-solid fa-calendar-day"></i> Date:</label>
                    <input type="date" id="dailyDate" class="filter-input" style="display: none;" onchange="fetchSummaryData(); fetchChartData(); fetchColumnChartData();">

                    <label id="monthlyDateLabel" for="monthlyDate" style="display: none;"><i class="fa-solid fa-calendar-days"></i> Month:</label>
                    <input type="month" id="monthlyDate" class="filter-input" style="display: none;" onchange="fetchSummaryData(); fetchChartData(); fetchColumnChartData();">
                </div>
            </div>

            <div class="kpi-container">
                <div class="kpi-card" id="totalSales">
                    <div class="kpi-icon icon-green">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Total Sales</h3>
                        <p>$<span id="totalSalesValue">0.00</span></p>
                    </div>
                </div>

                <div class="kpi-card" id="totalUnits">
                    <div class="kpi-icon icon-blue">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Units Sold</h3>
                        <p><span id="totalUnitsValue">0</span> <small>units</small></p>
                    </div>
                </div>

                <div class="kpi-card" id="newMembers">
                    <div class="kpi-icon icon-orange">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>New Members</h3>
                        <p><span id="newMembersValue">0</span></p>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h2>Payment Methods</h2>
                    </div>
                    <div id="piechart" class="chart-body"></div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h2>Top Products by Sales</h2>
                    </div>
                    <div id="columnchart_values" class="chart-body"></div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h2>Product Performance Trend</h2>
                    <p>Select a product to view monthly sales velocity over time.</p>
                </div>

                <div class="filter-toolbar inner-toolbar">
                    <div class="filter-group">
                        <div class="select-wrapper">
                            <select id="categoryFilter" class="filter-select" onchange="updateProductFilter()">
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <select id="productFilter" class="filter-select">
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="salesTrendvalues" class="trend-chart-body">
                    <div class="empty-chart-msg">
                        <i class="fa-solid fa-chart-line"></i>
                        <p>Select a category and product above to view sales trends.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&#169; 2024 GotoGro Members Record Management System</p>
    </footer>

    <script src="javascript/data.js"></script>
</body>
</html>