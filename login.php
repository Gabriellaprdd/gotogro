<?php
session_start();

include 'php/config.php';

$message = "";
$password = "";
$hashed_password = "";
$is_locked_out = false;
$remaining_seconds = 0;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = null;
}

if (isset($_SESSION['lockout_time']) && $_SESSION['lockout_time'] && time() < $_SESSION['lockout_time']) {
    $is_locked_out = true;
    $remaining_seconds = $_SESSION['lockout_time'] - time();
} else {
    $_SESSION['lockout_time'] = null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        if ($is_locked_out) {
            $minutes = floor($remaining_seconds / 60);
            $seconds = $remaining_seconds % 60;
            $message = "Please wait {$minutes} minute(s) and {$seconds} second(s) before trying again.";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $stmt = $conn->prepare("SELECT password_hash FROM staff WHERE email = ?");

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($hashed_password);
                    $stmt->fetch();

                    if (password_verify($password, $hashed_password)) {
                        $_SESSION['email'] = $email;
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['lockout_time'] = null;
                        $_SESSION['success_message'] = "You have logged in successfully!";
                        header("Location: account.php");
                        exit();
                    } else {
                        $message = "Incorrect password.";
                        $_SESSION['login_attempts']++;
                    }
                } else {
                    $message = "Email is not registered.";
                    $_SESSION['login_attempts']++;
                }

                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['lockout_time'] = time() + 60;
                    $is_locked_out = true;
                    $remaining_seconds = 60;
                    $message = "Too many failed attempts. Please wait 1 minute before trying again.";
                }

                $stmt->close();
            } else {
                $message = "Database query failed.";
            }
        }
    }
} else {
    if ($is_locked_out) {
        $minutes = floor($remaining_seconds / 60);
        $seconds = $remaining_seconds % 60;
        $message = "Too many failed attempts. Please wait {$minutes} minute(s) and {$seconds} second(s) before trying again.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Staff Log In Page">
    <meta name="keywords" content="grocery, staff, login">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/background.jpg">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforlogin.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Log In | GotoGro</title>
</head>

<body>
    <div class="background-img" id="bgImg" style="background-image: url('styles/images/background.jpg');"></div>

    <?php if (!empty($message)): ?>
        <div class="notification errors" id="loginNotification">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="notificationText"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <form class="login-form" method="POST" id="loginForm" data-lockout-time="<?php echo $is_locked_out ? $remaining_seconds : 0; ?>">
        <input type="hidden" name="action" value="login">

        <header>
            <a href="login.php" class="logo-link">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo" loading="eager" decoding="async">
            </a>
            <h1>Login to Your Account</h1>
            <p>Please provide your login details to access the system.</p>
        </header>

        <div class="form-container">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter email address" oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')" required <?php echo $is_locked_out ? 'disabled' : ''; ?>>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Enter password" required <?php echo $is_locked_out ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('password', this)"></i>
                </div>
            </div>
        </div>

        <div class="button-container">
            <button type="submit" id="loginBtn" class="btn login-button" <?php echo $is_locked_out ? 'disabled' : ''; ?>>Log In</button>
        </div>

        <p class="signup-link">Don't have an account? <a href="signup.php">Sign up here</a>.</p>

        <footer class="footer">
            <p>&#169; 2024 GotoGro Members Record Management System</p>
        </footer>
    </form>

    <script src="javascript/login.js"></script>
</body>
</html>