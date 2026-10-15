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
    <meta name="description" content="Staff Account Page">
    <meta name="keywords" content="grocery, account, staff">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforaccount.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Staff Account | GotoGro</title>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="notification <?php echo (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'updated') !== false) ? 'success' : 'error'; ?>" id="notification">
        <i class="fa-solid <?php echo (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'updated') !== false) ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php else: ?>
    <div class="notification" id="notification" style="display: none;"></div>
<?php endif; ?>

<div class="error-messages" id="errorMessages" style="display: none;"></div>

<header class="topbar">
    <button class="mobile-toggle-btn" id="mobileToggleBtn" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="profile-logout-container">
        <div class="profile-picture-top">
            <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img" loading="eager" decoding="async">
            <span class="greeting">Welcome, <?php echo htmlspecialchars($fname); ?>!</span>
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
            <a href="account.php" class="active"><img src="styles/images/account.png" alt=""><span>Account</span></a>
        </div>
    </nav>
</header>

<main>
    <section class="content">
        <form id="regform" class="reg-form" action="php/update_account.php" method="POST" enctype="multipart/form-data">

            <header class="form-header">
                <h1>My Account</h1>
                <p>View and update your staff profile information.</p>
            </header>

            <div class="profile-picture-container">
                <label for="profilePicture" class="user-avatar" title="Click to upload profile photo">
                    <img id="imagePreview" src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture">
                    <div class="upload-overlay">
                        <i class="fa-solid fa-camera"></i>
                        <span>Change</span>
                    </div>
                </label>
                <input type="file" name="profile_picture" id="profilePicture" accept="image/jpeg,image/png,image/webp" onchange="previewImage(event)">
                <small>JPG, PNG or WebP. Max size: 2 MB.</small>
            </div>

            <h3 class="user-name"><?php echo htmlspecialchars($fname . ' ' . $lname); ?></h3>

            <div class="form-container">
                <div class="form-row">
                    <div class="input-group">
                        <label for="staffid">Staff ID</label>
                        <input type="text" name="staffid" id="staffid" value="<?php echo htmlspecialchars('S' . $staffID); ?>" readonly>
                    </div>

                    <div class="input-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($dob); ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label for="fname">First Name</label>
                        <input type="text" name="fname" id="fname" value="<?php echo htmlspecialchars($fname); ?>" readonly>
                    </div>

                    <div class="input-group">
                        <label for="lname">Last Name</label>
                        <input type="text" name="lname" id="lname" value="<?php echo htmlspecialchars($lname); ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter email address" value="<?php echo htmlspecialchars($email); ?>" oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')" required>
                    </div>

                    <div class="input-group">
                        <label for="ph">Phone Number</label>
                        <div class="phone-container">
                            <span class="country-prefix">+61</span>
                            <input type="text" name="ph" id="ph" value="<?php echo htmlspecialchars(str_replace('+61-', '', $phone)); ?>" placeholder="Enter phone number" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label for="addy">Residential Address</label>
                    <input type="text" id="addy" name="addy" placeholder="Enter full address" value="<?php echo htmlspecialchars($address); ?>" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s,\.\-\/]/g, '')" required>
                </div>
            </div>

            <div class="password-update-section">
                <h4>Change Password (Optional)</h4>

                <div class="input-group">
                    <label for="password">Current Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter current password">
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('password', this)"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="new-password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new-password" name="new-password" placeholder="Enter new password">
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('new-password', this)"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter new password">
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('confirm-password', this)"></i>
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button type="submit" class="btn update-button">Update Profile</button>
                <button type="button" class="btn cancel-button" onclick="resetFormAndImage();">Cancel</button>
            </div>
        </form>
    </section>
</main>

<footer>
    <p>&#169; 2024 GotoGro Members Record Management System</p>
</footer>

<script src="javascript/account.js"></script>
</body>
</html>