<?php
session_start();
include 'php/config.php';

$fname = '';
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sql = "SELECT fname FROM staff WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($fname);
    $stmt->fetch();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Home Page">
    <meta name="keywords" content="grocery, home">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/background.jpg">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforhome.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Home | GotoGro</title>
</head>

<body>
    <div class="background-img" id="bgImg" style="background-image: url('styles/images/background.jpg');"></div>

    <div class="home-container">
        <header class="home-header">
            <div class="logo-badge">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo" loading="eager" decoding="async">
            </div>
            <h1>GotoGro-MRMS</h1>
            <p>
                <?php if (!empty($fname)): ?>
                    Welcome back, <strong><?php echo htmlspecialchars($fname); ?></strong>! Manage your store with ease.
                <?php else: ?>
                    Your all-in-one platform for efficiently managing grocery store memberships and sales records.
                <?php endif; ?>
            </p>
        </header>

        <div class="button-container">
            <?php if (!empty($fname)): ?>
                <a href="data.php" class="btn primary-btn"><i class="fa-solid fa-chart-line"></i> Go to Dashboard</a>
                <a href="logout.php" class="btn secondary-btn">Log Out</a>
            <?php else: ?>
                <a href="login.php" class="btn primary-btn"><i class="fa-solid fa-right-to-bracket"></i> Log In</a>
                <a href="signup.php" class="btn secondary-btn"><i class="fa-solid fa-user-plus"></i> Sign Up</a>
            <?php endif; ?>
        </div>

        <div class="features-toggle-wrapper">
            <button type="button" id="start-now" class="explore-pill-btn">
                <span>Discover Key Features</span>
                <div class="icon-circle">
                    <i class="fa-solid fa-chevron-down" id="chevron-icon"></i>
                </div>
            </button>
        </div>

        <div class="features-collapse-wrapper" id="featuresWrapper">
            <div class="features-inner">
                <div class="feature-container">
                    <div class="feature-box">
                        <div class="feature-icon-bg">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <h3>Member Management</h3>
                        <p>Effortlessly add, update, and manage member profiles and details.</p>
                    </div>

                    <div class="feature-box">
                        <div class="feature-icon-bg">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <h3>Inventory Control</h3>
                        <p>Keep track of stock levels and manage product availability seamlessly.</p>
                    </div>

                    <div class="feature-box">
                        <div class="feature-icon-bg">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <h3>Sales Tracking</h3>
                        <p>Monitor and analyze sales records to enhance performance.</p>
                    </div>

                    <div class="feature-box">
                        <div class="feature-icon-bg">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <h3>Reports & Analytics</h3>
                        <p>Generate reports to gain insights into performance.</p>
                    </div>
                </div>
            </div>
        </div>

        <footer class="home-footer">
            <p>&#169; 2024 GotoGro Members Record Management System</p>
        </footer>
    </div>

    <script src="javascript/home.js"></script>
</body>
</html>