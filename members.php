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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $fname = trim($_POST['fname']);
            $lname = trim($_POST['lname']);
            $dob = trim($_POST['dob']);
            $gender = trim($_POST['gender']);
            $address = trim($_POST['addy']);
            $email = trim($_POST['email']);
            $phRaw = trim($_POST['ph']);
            $phno = '+61-' . $phRaw;
            $registration_date = date("Y-m-d");

            $_SESSION['message'] = "";

            if (!preg_match("/^[A-Za-z]{1,30}$/", $fname) || !preg_match("/^[A-Za-z]{1,30}$/", $lname)) {
                $_SESSION['message'] .= "First and last name must contain only letters (max 30 characters). ";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['message'] .= "Please enter a valid email address. ";
            }

            if (!preg_match("/^\d{8}$/", $phRaw)) {
                $_SESSION['message'] .= "Phone number must be exactly 8 digits after +61. ";
            }

            if (!preg_match("/^[A-Za-z0-9\s,.\-\/]*$/", $address)) {
                $_SESSION['message'] .= "Address contains invalid characters. ";
            }

            if (empty($_SESSION['message'])) {
                $nameCheckStmt = $conn->prepare("SELECT * FROM member WHERE fname = ? AND lname = ?");
                $nameCheckStmt->bind_param("ss", $fname, $lname);
                $nameCheckStmt->execute();
                $nameResult = $nameCheckStmt->get_result();

                if ($nameResult->num_rows > 0) {
                    $_SESSION['message'] .= "A member with the same first and last name already exists. ";
                }

                $nameCheckStmt->close();
            }

            if (empty($_SESSION['message'])) {
                $emailCheckStmt = $conn->prepare("SELECT * FROM member WHERE email = ?");
                $emailCheckStmt->bind_param("s", $email);
                $emailCheckStmt->execute();
                $emailResult = $emailCheckStmt->get_result();

                if ($emailResult->num_rows > 0) {
                    $_SESSION['message'] .= "A member with the same email already exists. ";
                }

                $emailCheckStmt->close();
            }

            if (empty($_SESSION['message'])) {
                $phoneCheckStmt = $conn->prepare("SELECT * FROM member WHERE phno = ?");
                $phoneCheckStmt->bind_param("s", $phno);
                $phoneCheckStmt->execute();
                $phoneResult = $phoneCheckStmt->get_result();

                if ($phoneResult->num_rows > 0) {
                    $_SESSION['message'] .= "A member with the same phone number already exists. ";
                }

                $phoneCheckStmt->close();
            }

            if (empty($_SESSION['message'])) {
                $stmt = $conn->prepare(
                    "INSERT INTO member (fname, lname, dob, gender, address, email, phno, registration_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssssss",
                    $fname,
                    $lname,
                    $dob,
                    $gender,
                    $address,
                    $email,
                    $phno,
                    $registration_date
                );

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Member has been registered successfully.";
                } else {
                    $_SESSION['message'] = "Failed to add member. Error: " . $stmt->error;
                }

                $stmt->close();
            }
        } elseif ($_POST['action'] == 'update') {
            $memberID = intval($_POST['memberID']);
            $email = trim($_POST['email']);
            $phRaw = trim($_POST['ph']);
            $phno = '+61-' . $phRaw;
            $address = trim($_POST['addy']);

            $_SESSION['message'] = "";

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['message'] .= "Please enter a valid email address. ";
            }

            if (!preg_match("/^\d{8}$/", $phRaw)) {
                $_SESSION['message'] .= "Phone number must be exactly 8 digits after +61. ";
            }

            if (!preg_match("/^[A-Za-z0-9\s,.\-\/]*$/", $address)) {
                $_SESSION['message'] .= "Address contains invalid characters. ";
            }

            if (empty($_SESSION['message'])) {
                $stmt = $conn->prepare("UPDATE member SET email=?, phno=?, address=? WHERE memberID=?");
                $stmt->bind_param("sssi", $email, $phno, $address, $memberID);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "M$memberID's details updated successfully.";
                } else {
                    $_SESSION['message'] = "Failed to update member. Error: " . $conn->error;
                }

                $stmt->close();
            }
        } elseif ($_POST['action'] == 'delete') {
            $memberID = intval($_POST['memberID']);

            $deleteTransactionItemsSql = "DELETE FROM transaction_item WHERE transactionID IN (SELECT transactionID FROM sales_transactions WHERE memberID = ?)";
            $stmt = $conn->prepare($deleteTransactionItemsSql);
            $stmt->bind_param("i", $memberID);
            $stmt->execute();
            $stmt->close();

            $deleteSalesTransactionsSql = "DELETE FROM sales_transactions WHERE memberID = ?";
            $stmt = $conn->prepare($deleteSalesTransactionsSql);
            $stmt->bind_param("i", $memberID);
            $stmt->execute();
            $stmt->close();

            $deleteMemberSql = "DELETE FROM member WHERE memberID = ?";
            $stmt = $conn->prepare($deleteMemberSql);
            $stmt->bind_param("i", $memberID);

            if ($stmt->execute()) {
                $_SESSION['message'] = "M$memberID's records deleted successfully.";
            } else {
                $_SESSION['message'] = "Error deleting member: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$sql = "SELECT * FROM member";
$result = $conn->query($sql);

$hasNotifications = false;
$lowStockCheck = $conn->query("SELECT COUNT(*) AS total FROM product WHERE inv_qty < 20");

if ($lowStockCheck && $row = $lowStockCheck->fetch_assoc()) {
    if ($row['total'] > 0) {
        $hasNotifications = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="description" content="Member Profiles Page">
    <meta name="keywords" content="grocery, members, profile">
    <meta name="author" content="Pookie">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="styles/styleformembers.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Member Profiles | GotoGro</title>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php if (isset($_SESSION['message']) && !empty($_SESSION['message'])): ?>
        <div class="notification <?php echo (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'registered') !== false) ? 'success' : 'error'; ?>" id="notification">
            <i class="fa-solid <?php echo (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'registered') !== false) ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <span><?php echo $_SESSION['message']; ?></span>
        </div>

        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <header class="topbar">
        <button class="mobile-toggle-btn" id="mobileToggleBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="profile-logout-container">
            <div class="profile-picture-top">
                <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img">

                <span class="greeting">
                    Welcome,
                    <?php echo htmlspecialchars($fname ?? 'Staff'); ?>!
                </span>
            </div>

            <div class="logout-button">
                <a href="logout.php" class="btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <header class="sidebar" id="sidebar">
        <nav class="navbar">
            <div class="logo-container">
                <img src="styles/images/logo.png" alt="GotoGro Logo" class="logo">
                <a href="index.php" class="nav-title">GotoGro-MRMS</a>
            </div>

            <div class="nav-links">
                <a href="data.php">
                    <img src="styles/images/analytics.png" alt="">
                    <span>Dashboard</span>
                </a>

                <a href="members.php" class="active">
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

                <a href="notification.php">
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
        <section class="content">
            <div class="page-header">
                <h1>Member Profiles</h1>
                <p>View registered store members, add new profiles, or update details.</p>
            </div>

            <div class="search-container">
                <div class="search-wrapper">
                    <select id="searchType" class="search-select">
                        <option value="" disabled selected>By</option>
                        <option value="id">ID</option>
                        <option value="name">Name</option>
                    </select>

                    <div class="search-input-box">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="searchInput" placeholder="Search members...">
                    </div>

                    <button class="btn search-button" type="button">Search</button>
                </div>

                <button class="btn add-member" id="addMember" type="button">
                    <i class="fa-solid fa-user-plus"></i>
                    Add Member
                </button>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Member Since</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $memberID = htmlspecialchars($row['memberID']);
                                $name = htmlspecialchars($row['fname'] . " " . $row['lname']);
                                $dob = htmlspecialchars($row['dob']);
                                $gender = htmlspecialchars($row['gender']);
                                $address = htmlspecialchars($row['address']);
                                $email = htmlspecialchars($row['email']);
                                $phone = htmlspecialchars($row['phno']);
                                $regDate = htmlspecialchars($row['registration_date']);
                            ?>
                                <tr>
                                    <td>
                                        <span class="member-id-tag">M<?= $memberID ?></span>
                                    </td>

                                    <td class="font-medium"><?= $name ?></td>
                                    <td><?= $dob ?></td>

                                    <td>
                                        <span class="gender-badge"><?= ucfirst($gender) ?></span>
                                    </td>

                                    <td><?= $address ?></td>
                                    <td><?= $email ?></td>
                                    <td><?= $phone ?></td>
                                    <td><?= $regDate ?></td>

                                    <td>
                                        <div class="action-buttons-cell">
                                            <button class="btn action-icon-btn view-button" title="View Purchase History" onclick="location.href='purchasehistory.php?memberID=<?= $memberID ?>'">
                                                <i class="fa-solid fa-receipt"></i>
                                            </button>

                                            <button class="btn action-icon-btn edit-button" title="Edit Member" onclick="openUpdateForm(
                                                '<?= $memberID ?>',
                                                '<?= addslashes($row['fname']) ?>',
                                                '<?= addslashes($row['lname']) ?>',
                                                '<?= $dob ?>',
                                                '<?= $gender ?>',
                                                '<?= addslashes($address) ?>',
                                                '<?= $email ?>',
                                                '<?= $phone ?>',
                                                '<?= $regDate ?>'
                                            )">
                                                <i class="fa-solid fa-user-pen"></i>
                                            </button>

                                            <button class="btn action-icon-btn delete-button" title="Delete Member" onclick="openDeleteMemberModal('<?= $memberID ?>', '<?= addslashes($name) ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">No members found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php $conn->close(); ?>

            <div class="form" id="regForm">
                <div class="modal-box">
                    <span class="close-button" id="closeRegForm">&times;</span>
                    <h2>New Member Registration</h2>

                    <form method="POST" action="members.php" class="modal-form">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="input-group">
                                <label for="fname">First Name</label>
                                <input type="text" name="fname" id="fname" maxlength="30" placeholder="Enter first name" oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" required>
                            </div>

                            <div class="input-group">
                                <label for="lname">Last Name</label>
                                <input type="text" name="lname" id="lname" maxlength="30" placeholder="Enter last name" oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" name="dob" id="dob" required>
                        </div>

                        <fieldset class="gender-fieldset">
                            <legend>Gender</legend>

                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" id="male" name="gender" value="male" required>
                                    <span class="radio-custom"></span>
                                    Male
                                </label>

                                <label class="radio-label">
                                    <input type="radio" id="female" name="gender" value="female">
                                    <span class="radio-custom"></span>
                                    Female
                                </label>

                                <label class="radio-label">
                                    <input type="radio" id="non-binary" name="gender" value="non-binary">
                                    <span class="radio-custom"></span>
                                    Prefer Not to Say
                                </label>
                            </div>
                        </fieldset>

                        <div class="input-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter email address" oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')" required>
                        </div>

                        <div class="input-group">
                            <label for="ph">Phone Number</label>

                            <div class="phone-container">
                                <span class="country-prefix">+61</span>
                                <input type="text" name="ph" id="ph" placeholder="Enter phone number" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="addy">Residential Address</label>
                            <input type="text" name="addy" id="addy" maxlength="200" placeholder="Enter full address" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s,\.\-\/]/g, '')" required>
                        </div>

                        <div class="input-group">
                            <label for="membership_start">Date of Membership</label>
                            <input type="date" name="membership_start" id="membership_start" readonly>
                        </div>

                        <div class="button-container">
                            <button type="submit" class="btn save-btn">Register Member</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="form" id="updateForm">
                <div class="modal-box">
                    <span class="close-button" id="closeUpdateForm">&times;</span>
                    <h2 id="updateFormTitle">Update Member Details</h2>

                    <form id="updForm" method="POST" action="members.php" class="modal-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="update_member_id" name="memberID">

                        <div class="form-row">
                            <div class="input-group">
                                <label for="update_fname">First Name</label>
                                <input type="text" id="update_fname" name="fname" readonly>
                            </div>

                            <div class="input-group">
                                <label for="update_lname">Last Name</label>
                                <input type="text" id="update_lname" name="lname" readonly>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="update_dob">Date of Birth</label>
                            <input type="date" id="update_dob" name="dob" readonly>
                        </div>

                        <fieldset class="gender-fieldset">
                            <legend>Gender</legend>

                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" id="update_male" name="gender" value="male" disabled>
                                    <span class="radio-custom"></span>
                                    Male
                                </label>

                                <label class="radio-label">
                                    <input type="radio" id="update_female" name="gender" value="female" disabled>
                                    <span class="radio-custom"></span>
                                    Female
                                </label>

                                <label class="radio-label">
                                    <input type="radio" id="update_nonbinary" name="gender" value="non-binary" disabled>
                                    <span class="radio-custom"></span>
                                    Prefer Not to Say
                                </label>
                            </div>
                        </fieldset>

                        <div class="input-group">
                            <label for="update_email">Email Address</label>
                            <input type="email" id="update_email" name="email" placeholder="Enter email address" oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')" required>
                        </div>

                        <div class="input-group">
                            <label for="update_ph">Phone Number</label>

                            <div class="phone-container">
                                <span class="country-prefix">+61</span>
                                <input type="text" id="update_ph" name="ph" placeholder="Enter phone number" maxlength="8" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="update_addy">Residential Address</label>
                            <input type="text" id="update_addy" name="addy" placeholder="Enter full address" maxlength="200" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s,\.\-\/]/g, '')" required>
                        </div>

                        <div class="input-group">
                            <label for="update_membership_start">Date of Membership</label>
                            <input type="date" id="update_membership_start" name="membership_start" readonly>
                        </div>

                        <div class="button-container">
                            <button type="submit" class="btn save-btn">Update Details</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="form" id="deleteForm">
                <div class="modal-box delete-modal">
                    <div class="delete-icon-wrapper">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h2>Delete Member</h2>
                    <p id="deleteMessage">Are you sure you want to delete this member? This action cannot be undone.</p>

                    <form method="POST" action="members.php" class="modal-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" id="delete_member_id" name="memberID">

                        <div class="button-container">
                            <button type="submit" class="btn action-btn delete-btn-confirm">Yes, Delete</button>
                            <button type="button" class="btn action-btn cancel-btn" id="cancelDeleteButton">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&#169; 2024 GotoGro Members Record Management System</p>
    </footer>

    <script src="javascript/members.js"></script>
</body>
</html>