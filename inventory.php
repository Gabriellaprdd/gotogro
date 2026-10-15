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

$categoryOptions = [];
$resultCat = $conn->query("SHOW COLUMNS FROM product LIKE 'category'");

if ($resultCat) {
    $rowCat = $resultCat->fetch_assoc();
    preg_match("/^enum\((.*)\)$/", $rowCat['Type'], $matches);
    $categoryOptions = str_getcsv($matches[1], ',', "'", '\\');
}

$message = "";
$errormessage = "";

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $productName = trim($_POST['product_name']);
        $productPrice = floatval($_POST['product_price']);
        $category = $_POST['category'];
        $inventoryQuantity = intval($_POST['inv_qty']);
        $restockDate = $_POST['last_restock_date'];

        $stmtCheck = $conn->prepare("SELECT * FROM product WHERE product_name = ?");
        $stmtCheck->bind_param("s", $productName);
        $stmtCheck->execute();
        $checkProductResult = $stmtCheck->get_result();

        if ($checkProductResult->num_rows > 0) {
            $errormessage = "The product '$productName' already exists.";
        } else {
            $stmtInsert = $conn->prepare("INSERT INTO product (product_name, product_price, category, inv_qty, last_restock_date) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("sdsis", $productName, $productPrice, $category, $inventoryQuantity, $restockDate);

            if ($stmtInsert->execute()) {
                $message = "'$productName' has been added successfully!";
            } else {
                $errormessage = "Error adding product: " . $conn->error;
            }

            $stmtInsert->close();
        }

        $stmtCheck->close();
    } elseif (isset($_POST['action']) && $_POST['action'] == 'update') {
        $productID = intval($_POST['product_id']);
        $newQuantity = intval($_POST['inv_qty']);
        $restockDate = $_POST['last_restock_date'];

        $stmtProd = $conn->prepare("SELECT inv_qty, product_name FROM product WHERE productID = ?");
        $stmtProd->bind_param("i", $productID);
        $stmtProd->execute();
        $productResult = $stmtProd->get_result();

        if ($productResult && $productResult->num_rows > 0) {
            $productRow = $productResult->fetch_assoc();
            $currentQuantity = $productRow['inv_qty'];
            $product_name = $productRow['product_name'];

            $updatedQuantity = $currentQuantity + $newQuantity;

            $stmtUpd = $conn->prepare("UPDATE product SET inv_qty = ?, last_restock_date = ? WHERE productID = ?");
            $stmtUpd->bind_param("isi", $updatedQuantity, $restockDate, $productID);

            if ($stmtUpd->execute()) {
                $message = "'$product_name' has been updated successfully!";
            } else {
                $errormessage = "Error updating product: " . $conn->error;
            }

            $stmtUpd->close();
        }

        $stmtProd->close();
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $productID = intval($_POST['product_id']);

        $stmtDelCheck = $conn->prepare("SELECT product_name FROM product WHERE productID = ?");
        $stmtDelCheck->bind_param("i", $productID);
        $stmtDelCheck->execute();
        $productResult = $stmtDelCheck->get_result();

        if ($productResult && $productResult->num_rows > 0) {
            $productRow = $productResult->fetch_assoc();
            $productName = $productRow['product_name'];

            $stmtTi = $conn->prepare("DELETE FROM transaction_item WHERE productID = ?");
            $stmtTi->bind_param("i", $productID);
            $stmtTi->execute();
            $stmtTi->close();

            $stmtDel = $conn->prepare("DELETE FROM product WHERE productID = ?");
            $stmtDel->bind_param("i", $productID);

            if ($stmtDel->execute()) {
                $message = "'$productName' has been deleted successfully!";
            } else {
                $errormessage = "Error deleting product: " . $conn->error;
            }

            $stmtDel->close();
        }

        $stmtDelCheck->close();
    }
}

$hasNotifications = false;
$lowStockCheck = $conn->query("SELECT COUNT(*) AS total FROM product WHERE inv_qty < 20");

if ($lowStockCheck && $row = $lowStockCheck->fetch_assoc()) {
    if ($row['total'] > 0) {
        $hasNotifications = true;
    }
}

$filterConditions = [];

if (!empty($_POST['stock_status'])) {
    $stockConditions = [];

    foreach ($_POST['stock_status'] as $status) {
        if ($status == 'low-stock') {
            $stockConditions[] = "inv_qty < 20";
        } elseif ($status == 'out-of-stock') {
            $stockConditions[] = "inv_qty = 0";
        }
    }

    if (!empty($stockConditions)) {
        $filterConditions[] = '(' . implode(' OR ', $stockConditions) . ')';
    }
}

if (!empty($_POST['category']) && is_array($_POST['category'])) {
    $categoryConditions = array_map(
        fn($cat) => "'" . $conn->real_escape_string($cat) . "'",
        $_POST['category']
    );

    $filterConditions[] = "category IN (" . implode(',', $categoryConditions) . ")";
}

$sql = "SELECT * FROM (
    SELECT p.*,
      (SELECT COUNT(*)
       FROM product p2
       WHERE p2.category = p.category AND p2.productID <= p.productID) AS category_seq
    FROM product p
) AS product_seq";

if (!empty($filterConditions)) {
    $sql .= " WHERE " . implode(" AND ", $filterConditions);
}

$result = $conn->query($sql);

if (!$result) {
    die("Error: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inventory Management Page">
    <meta name="keywords" content="grocery, inventory">

    <link href="styles/styleforinventory.css" rel="stylesheet">
    <link rel="icon" href="styles/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>Inventory | GotoGro</title>
</head>

<body>
    <?php if (!empty($message)): ?>
        <div class="notification success" id="notification">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errormessage)): ?>
        <div class="notification error" id="notification">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($errormessage); ?></span>
        </div>
    <?php endif; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="topbar">
        <button class="mobile-toggle-btn" id="mobileToggleBtn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="profile-logout-container">
            <div class="profile-picture-top">
                <img src="<?php echo htmlspecialchars($displayPicture); ?>" alt="Profile Picture" class="profile-img">
                <span class="greeting">Welcome, <?php echo htmlspecialchars($fname ?? 'Staff'); ?>!</span>
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
                    Dashboard
                </a>

                <a href="members.php">
                    <img src="styles/images/members.png" alt="">
                    Members
                </a>

                <a href="inventory.php" class="active">
                    <img src="styles/images/inventory.png" alt="">
                    Inventory
                </a>

                <a href="sales.php">
                    <img src="styles/images/sales.png" alt="">
                    Sales
                </a>

                <a href="report.php">
                    <img src="styles/images/report.png" alt="">
                    Report
                </a>

                <a href="notification.php">
                    <div class="nav-icon-container">
                        <img src="styles/images/notification.png" alt="">

                        <?php if ($hasNotifications): ?>
                            <span class="urgent-dot" id="sidebar-urgent-dot"></span>
                        <?php endif; ?>
                    </div>

                    Notifications
                </a>

                <a href="account.php">
                    <img src="styles/images/account.png" alt="">
                    Account
                </a>
            </div>
        </nav>
    </header>

    <main>
        <section class="content">
            <div class="page-header">
                <h1>Inventory Management</h1>
                <p>Manage product items, track stock levels, and perform stock updates.</p>
            </div>

            <div class="filter-container">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" class="search-bar" id="searchBar" placeholder="Search by Product ID or Name" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s\-\.]/g, '')">
                </div>

                <div class="action-buttons">
                    <button class="btn filter-button" id="filterButton">
                        <i class="fa-solid fa-filter"></i>
                        Filter
                    </button>

                    <button class="btn add-button" id="AddButton">
                        <i class="fa-solid fa-plus"></i>
                        Add Product
                    </button>
                </div>
            </div>

            <div class="filter-sidebar" id="filterSidebar">
                <div class="sidebar-header">
                    <h3>Filter & Sort</h3>
                    <span class="close-button" id="closeSidebar">&times;</span>
                </div>

                <form method="POST" action="inventory.php">
                    <div class="filter-options" id="stockOptions">
                        <h4>Stock Status</h4>

                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="stock_status[]" value="low-stock">
                                Low Stock (&lt; 20)
                            </label>
                        </div>

                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" name="stock_status[]" value="out-of-stock">
                                Out of Stock (0)
                            </label>
                        </div>
                    </div>

                    <div class="filter-options">
                        <h4>Categories</h4>

                        <div class="checkbox-item">
                            <label>
                                <input type="checkbox" id="selectAllCategories" onclick="toggleCategoryCheckboxes()">
                                All Categories
                            </label>
                        </div>

                        <?php foreach ($categoryOptions as $option): ?>
                            <div class="checkbox-item">
                                <label>
                                    <input type="checkbox" name="category[]" value="<?= htmlspecialchars($option) ?>">
                                    <?= htmlspecialchars($option) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn apply-button" id="applyButton">Apply Filter</button>
                        <button type="button" class="btn clear-filter" onclick="clearFilters()">Clear</button>
                    </div>
                </form>
            </div>

            <div class="product-cards" id="productCards">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $productID = htmlspecialchars($row['productID']);
                        $productName = htmlspecialchars($row['product_name']);
                        $productPrice = htmlspecialchars($row['product_price']);
                        $category = htmlspecialchars($row['category']);
                        $inventoryQty = htmlspecialchars($row['inv_qty']);
                        $lastRestockDate = htmlspecialchars($row['last_restock_date']);

                        $categoryPrefix = getCategoryPrefix($category);
                        $displayID = $categoryPrefix . htmlspecialchars($row['category_seq']);
                    ?>
                        <div class="product-card" id="product-<?= $productID ?>">
                            <div class="card-header">
                                <h3><?= $productName ?></h3>
                                <span class="category-badge"><?= $category ?></span>
                            </div>

                            <div class="card-body">
                                <p>
                                    <strong>Product ID:</strong>
                                    <span><?= $displayID ?></span>
                                </p>

                                <p>
                                    <strong>Price:</strong>
                                    <span>$<?= number_format((float)$productPrice, 2) ?></span>
                                </p>

                                <p>
                                    <strong>Stock Qty:</strong>

                                    <span class="stock-count <?= ($inventoryQty == 0) ? 'out-stock' : (($inventoryQty < 20) ? 'low-stock' : 'in-stock') ?>">
                                        <?= $inventoryQty ?>
                                    </span>
                                </p>
                            </div>

                            <div class="card-button">
                                <button class="btn card-btn update-button" title="Edit Product" onclick="openUpdateForm(<?= $productID ?>, '<?= addslashes($productName) ?>', <?= $productPrice ?>, '<?= $category ?>', <?= $inventoryQty ?>, '<?= $lastRestockDate ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <button class="btn card-btn view-button" title="View Details" onclick="openViewForm('<?= addslashes($productName) ?>', <?= $inventoryQty ?>, '<?= $lastRestockDate ?>')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                <button class="btn card-btn delete-button" title="Delete Product" onclick="openDeleteForm(<?= $productID ?>, '<?= addslashes($productName) ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fa-solid fa-box-open"></i>
                        <p>No products found matching your request.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form" id="addForm">
                <div class="modal-box">
                    <span class="close-button" id="closeAddForm">&times;</span>
                    <h2>Add New Product</h2>

                    <form method="POST" action="inventory.php" class="modal-form">
                        <input type="hidden" name="action" value="add">

                        <div class="input-group">
                            <label for="category">Category</label>

                            <div class="select-wrapper">
                                <select id="category" name="category" required class="filter-select">
                                    <option value="">Select Category</option>

                                    <?php foreach ($categoryOptions as $option): ?>
                                        <option value="<?= htmlspecialchars($option) ?>">
                                            <?= htmlspecialchars($option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" required disabled placeholder="Enter product name" maxlength="50" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s\-\.\,\&\'\(\)]/g, '')">
                        </div>

                        <div class="input-group">
                            <label for="product_price">Price ($)</label>
                            <input type="number" id="product_price" name="product_price" step="0.01" required min="0.00" disabled placeholder="0.00">
                        </div>

                        <div class="input-group">
                            <label for="inv_qty">Initial Quantity</label>
                            <input type="number" id="inv_qty" name="inv_qty" required min="1" disabled placeholder="Enter stock quantity">
                        </div>

                        <div class="input-group">
                            <label for="last_restock_date">Restock Date</label>
                            <input type="date" id="last_restock_date" name="last_restock_date" readonly>
                        </div>

                        <div class="button-container">
                            <button type="submit" class="btn action-btn save-btn">Add Product</button>
                            <button type="reset" class="btn action-btn cancel-btn">Clear</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="form" id="updateForm">
                <div class="modal-box">
                    <span class="close-button" id="closeUpdateForm">&times;</span>
                    <h2 id="updateFormTitle">Update Restock Quantity</h2>

                    <form method="POST" action="inventory.php" class="modal-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="update_product_id" name="product_id">

                        <div class="input-group">
                            <label for="update_product_name">Product Name</label>
                            <input type="text" id="update_product_name" name="product_name" readonly>
                        </div>

                        <div class="input-group">
                            <label for="update_inv_qty">Add Stock Quantity</label>
                            <input type="number" id="update_inv_qty" name="inv_qty" required min="1" placeholder="Quantity to add">
                        </div>

                        <div class="input-group">
                            <label for="update_restock_date">Restock Date</label>
                            <input type="date" id="update_restock_date" name="last_restock_date" required>
                        </div>

                        <div class="button-container">
                            <button type="submit" class="btn action-btn save-btn">Update Stock</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="form" id="viewForm">
                <div class="modal-box">
                    <span class="close-button" id="closeViewForm">&times;</span>
                    <h2 id="viewFormTitle">Product Stock Info</h2>

                    <table id="viewFormTable" class="view-table">
                        <thead>
                            <tr>
                                <th>Last Restock Date</th>
                                <th>Current Stock</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td id="restockDateValue"></td>
                                <td id="stockValue"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form" id="deleteForm">
                <div class="modal-box delete-modal">
                    <div class="delete-icon-wrapper">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h2 id="deleteFormTitle">Delete Product</h2>
                    <p>Are you sure you want to delete this product? This action cannot be undone.</p>

                    <form method="POST" action="inventory.php" class="modal-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" id="delete_product_id" name="product_id">

                        <div class="button-container">
                            <button type="submit" class="btn action-btn delete-btn-confirm">Yes, Delete</button>
                            <button type="button" class="btn action-btn cancel-btn" id="cancelButton">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&#169; 2024 GotoGro Members Record Management System</p>
    </footer>

    <script src="javascript/inventory.js"></script>
    <script src="javascript/notification.js"></script>
</body>
</html>