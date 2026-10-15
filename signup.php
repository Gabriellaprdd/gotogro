<?php
session_start();

include 'php/config.php';

$message = "";
$errors = [];

$fname = '';
$lname = '';
$dob = '';
$email = '';
$address = '';
$phoneNumber = '';

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST['action'] ?? '') === 'signup'
) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $countryCode = '+61';
    $phoneNumber = trim($_POST['phone'] ?? '');
    $fullPhone = $countryCode . '-' . $phoneNumber;

    $plainPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $profilePictureData = null;

    if ($plainPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    $emailCheckStmt = $conn->prepare("SELECT staffID FROM staff WHERE email = ? LIMIT 1");
    if ($emailCheckStmt) {
        $emailCheckStmt->bind_param("s", $email);
        $emailCheckStmt->execute();
        if ($emailCheckStmt->get_result()->num_rows > 0) {
            $errors[] = "An account with this email address already exists.";
        }
        $emailCheckStmt->close();
    }

    $phoneCheckStmt = $conn->prepare("SELECT staffID FROM staff WHERE phone = ? LIMIT 1");
    if ($phoneCheckStmt) {
        $phoneCheckStmt->bind_param("s", $fullPhone);
        $phoneCheckStmt->execute();
        if ($phoneCheckStmt->get_result()->num_rows > 0) {
            $errors[] = "An account with this phone number already exists.";
        }
        $phoneCheckStmt->close();
    }

    $nameCheckStmt = $conn->prepare("SELECT staffID FROM staff WHERE fname = ? AND lname = ? LIMIT 1");
    if ($nameCheckStmt) {
        $nameCheckStmt->bind_param("ss", $fname, $lname);
        $nameCheckStmt->execute();
        if ($nameCheckStmt->get_result()->num_rows > 0) {
            $errors[] = "An account with this first and last name already exists.";
        }
        $nameCheckStmt->close();
    }

    if (
        !isset($_FILES['profile_picture']) ||
        $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $errors[] = "Please upload a profile picture to proceed.";
    } else {
        $file = $_FILES['profile_picture'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "The profile picture could not be uploaded.";
        } else {
            $maximumFileSize = 2 * 1024 * 1024;

            if ($file['size'] > $maximumFileSize) {
                $errors[] = "The profile picture must be smaller than 2 MB.";
            }

            $imageInformation = getimagesize($file['tmp_name']);
            if ($imageInformation === false) {
                $errors[] = "The selected file is not a valid image.";
            }

            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mimeType])) {
                $errors[] = "Only JPG, PNG, and WebP images are allowed.";
            } else {
                $imageData = file_get_contents($file['tmp_name']);
                if ($imageData !== false) {
                    $profilePictureData = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                } else {
                    $errors[] = "The profile picture could not be processed.";
                }
            }
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO staff (fname, lname, dob, email, phone, password_hash, address, profile_picture)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $errors[] = "The account could not be created. Database error.";
        } else {
            $stmt->bind_param("ssssssss", $fname, $lname, $dob, $email, $fullPhone, $passwordHash, $address, $profilePictureData);

            if ($stmt->execute()) {
                $message = "$fname $lname's account has been created successfully!";
                $fname = $lname = $dob = $email = $address = $phoneNumber = '';
            } else {
                $errors[] = "The account could not be created.";
            }
            $stmt->close();
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
        } else {
            echo json_encode(['success' => true, 'message' => $message]);
        }
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Staff Sign Up Page">
    <meta name="keywords" content="staff, signup">
    <meta name="author" content="Pookie">
    <link rel="preload" as="image" href="styles/images/background.jpg">
    <link rel="preload" as="image" href="styles/images/logo.png">
    <link href="styles/styleforsignup.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Sign Up | GotoGro</title>
</head>
<body>
    <div class="background-img" id="bgImg" style="background-image: url('styles/images/background.jpg');"></div>

    <?php if (!empty($message)): ?>
        <div class="notification success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="notification errors">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="error-messages" style="display:none;"></div>

    <form class="signup-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="signup">

        <header>
            <a href="login.php" class="logo-link">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo" loading="eager" decoding="async">
            </a>
            <h1>Create Your Account</h1>
            <p>Provide your details to sign up for staff access.</p>
        </header>

        <div class="profile-picture-container">
            <label for="profile-picture" class="user-avatar" title="Click to upload picture">
                <div class="upload-placeholder" id="upload-placeholder">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Upload</span>
                </div>
                <img id="profile-preview" src="" alt="" style="display: none;">
            </label>
            <input type="file" id="profile-picture" name="profile_picture" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG, WebP. Max size: 2 MB.</small>
        </div>

        <div class="form-container">
            <div class="form-section">
                <div class="input-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" placeholder="Enter first name" value="<?php echo htmlspecialchars($fname); ?>" oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" required>
                </div>

                <div class="input-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" placeholder="Enter last name" value="<?php echo htmlspecialchars($lname); ?>" oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" required>
                </div>

                <div class="input-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" value="<?php echo htmlspecialchars($email); ?>" oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')" required>
                </div>
            </div>

            <div class="form-section">
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <div class="phone-container">
                        <span class="country-prefix">+61</span>
                        <input type="text" id="phone" name="phone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($phoneNumber); ?>" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="address">Residential Address</label>
                    <input type="text" id="address" name="address" placeholder="Enter full address" value="<?php echo htmlspecialchars($address); ?>" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s,\.\-\/]/g, '')" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('password', this)"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Re-enter password" required>
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility('confirm-password', this)"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-container">
            <button type="submit" class="btn signup-button">Create Account</button>
            <button type="reset" class="btn clear-button">Clear</button>
        </div>

        <p class="login-link">Already have an account? <a href="login.php">Log in here</a>.</p>

        <footer class="footer">
            <p>&#169; 2024 GotoGro Members Record Management System</p>
        </footer>
    </form>

    <script src="javascript/signup.js"></script>
</body>
</html>