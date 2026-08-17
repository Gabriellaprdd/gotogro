<?php
session_start();
include 'php/config.php';

$fname = 'Staff';
$profile_picture = '';

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    $sql = "SELECT fname, profile_picture FROM staff WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($fname, $profile_picture);
    $stmt->fetch();
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}

$defaultAvatar = 'styles/images/default.png';
$displayPicture = $defaultAvatar;

if (!empty($profile_picture)) {
    $trimmedPic = trim($profile_picture);

    if (
        strpos($trimmedPic, 'data:image/') === 0 ||
        file_exists($trimmedPic)
    ) {
        $displayPicture = $trimmedPic;
    }
}

$threshold = 20;

$lowStockQuery = "SELECT product_seq.* FROM (
    SELECT p.*,
      (SELECT COUNT(*)
       FROM product p2
       WHERE p2.category = p.category AND p2.productID <= p.productID) AS category_seq
    FROM product p
) AS product_seq WHERE inv_qty < $threshold ORDER BY inv_qty ASC";

if (isset($_POST['refresh_notifications'])) {
    $lowStockProducts = array();
    $result = $conn->query($lowStockQuery);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $lowStockProducts[] = $row;
        }
    }

    $_SESSION['lowStockProducts'] = $lowStockProducts;

    header('Content-Type: application/json');

    echo json_encode([
        'html' => generateNotificationHTML($lowStockProducts),
        'hasNotifications' => !empty($lowStockProducts)
    ]);

    exit;
} elseif (isset($_POST['clear_notifications'])) {
    $_SESSION['lowStockProducts'] = [];

    header('Content-Type: application/json');

    echo json_encode([
        'html' => '<div class="no-notifications"><i class="fa-solid fa-circle-check"></i><p>No products low on stock.</p></div>',
        'hasNotifications' => false
    ]);

    exit;
}

$lowStockProducts = array();
$result = $conn->query($lowStockQuery);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lowStockProducts[] = $row;
    }
}

$_SESSION['lowStockProducts'] = $lowStockProducts;
$hasNotifications = !empty($lowStockProducts);

function getCategoryPrefix($category)
{
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

    return $prefixes[$category] ?? '';
}

function generateNotificationHTML($products)
{
    if (empty($products)) {
        return '<div class="no-notifications"><i class="fa-solid fa-circle-check"></i><p>No products low on stock.</p></div>';
    }

    $html = '';

    foreach ($products as $product) {
        $displayID =
            getCategoryPrefix($product['category']) .
            $product['category_seq'];

        $html .= '
        <div class="notification" data-product-id="' . htmlspecialchars($product['productID']) . '" id="notif-product-' . htmlspecialchars($product['productID']) . '" onclick="redirectAndScrollToProduct(' . htmlspecialchars($product['productID']) . ')">
            <div class="notification-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="notification-content">
                <strong>Low stock alert!</strong>
                <p><strong>Product ID:</strong> ' . htmlspecialchars($displayID) . '</p>
                <p><strong>Product Name:</strong> ' . htmlspecialchars($product['product_name']) . '</p>
                <p><strong>Quantity Left:</strong> <span class="qty-badge">' . htmlspecialchars($product['inv_qty']) . '</span></p>
            </div>
        </div>';
    }

    return $html;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Notification Page">
    <meta name="keywords" content="grocery, notification">
    <meta name="author" content="Pookie">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="styles/stylefornotification.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Notifications | GotoGro</title>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>
    <header class="topbar">
        <button class="mobile-toggle-btn" onclick="toggleMobileMenu()" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="profile-logout-container">
            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img">

                <span class="greeting">
                    Welcome,
                    <?php echo htmlspecialchars(!empty($fname) ? $fname : 'Staff'); ?>!
                </span>
            </div>

            <div class="logout-button">
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <header class="sidebar" id="sidebar">
        <nav class="navbar">
            <div class="logo-container">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo">
                <a href="index.php" class="nav-title">
                    GotoGro-MRMS
                </a>
            </div>

            <div class="nav-links">
                <a href="data.php">
                    <img src="styles/images/analytics.png" alt="">
                    <span>Dashboard</span>
                </a>

                <a href="members.php">
                    <img src="styles/images/members.png" alt="">
                    <span>Members</span>
                </a>

                <a href="inventory.php">
                    <img src="styles/images/inventory.png" alt="">
                    <span>Inventory</span>
                </a>

                <a href="sales.php">
                    <img src="styles/images/sales.png" alt="">
                    <span>Sales</span>
                </a>

                <a href="report.php">
                    <img src="styles/images/report.png" alt="">
                    <span>Report</span>
                </a>

                <a href="notification.php" class="active">
                    <div class="nav-icon-container">
                        <img src="styles/images/notification.png" alt="">

                        <?php if ($hasNotifications): ?>
                            <span class="urgent-dot" id="sidebar-urgent-dot"></span>
                        <?php endif; ?>
                    </div>

                    <span>Notifications</span>
                </a>

                <a href="account.php">
                    <img src="styles/images/account.png" alt="">
                    <span>Account</span>
                </a>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header-container">
            <h1>Notifications</h1>

            <button class="refresh-button">
                <img src="styles/images/refresh.png" alt="">
                Refresh
            </button>
        </div>

        <div id="notification-container">
            <?php echo generateNotificationHTML($lowStockProducts); ?>
        </div>
    </main>

    <footer>
        <p>
            &#169; 2024 GotoGro Members Record Management System
        </p>
    </footer>

    <script src="javascript/notification.js"></script>
</body>
</html>