<?php
require_once __DIR__ . '/../../config/paths.php';

// Start session at the very beginning only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Force redirect to login page
    header("Location: " . BASE_URL . "signin.php");
    exit(); // Stop execution immediately
}

// Only Admin and Moderator can add products
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: " . BASE_URL . "modules/products/product_list.php");
    exit();
}

// Include the database connection file
require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php'; // Include helper functions

// Variable to track product addition status
$product_added = false;
$error_message = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $lkr_price = floatval($_POST['lkr_price']);
    
    // Insert the new product into the database
    $sql = "INSERT INTO products (name, description, lkr_price, created_at, status) 
            VALUES (?, ?, ?, NOW(), 'active')";
    
    // Use prepared statement to avoid SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $name, $description, $lkr_price);
    
    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['success_message'] = "Product Added Successfully!";
        header("Location: " . BASE_URL . "modules/products/add_product.php");
        exit();
    } else {
        // Store error message
        $error_message = 'Error: ' . $stmt->error;
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Add New Product</title>
    <link href="<?= BASE_URL ?>css/forms.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8fafc;
        }

    </style>
</head>

<body class="sb-nav-fixed">
    <?php require_once BASE_PATH . 'includes/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require_once BASE_PATH . 'includes/sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5>Create New Product</h5>
                        <p class="text-muted">Add a new product to the inventory</p>
                    </div>
                </div>

                    <?php
                    // Display error messages if any
                    if (isset($error_message) && $error_message) {
                        echo '<script>document.addEventListener("DOMContentLoaded", function() { showToast("error", "' . addslashes($error_message) . '"); });</script>';
                    }
                    if (isset($_SESSION['success_message'])) {
                        echo '<script>document.addEventListener("DOMContentLoaded", function() { showToast("success", "' . addslashes($_SESSION['success_message']) . '"); });</script>';
                        unset($_SESSION['success_message']);
                    }
                    ?>

                    <div class="card" style="margin: 0 32px; border: 1px solid #eaecf0; border-radius: 12px; box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04); background: #fff;">
                        <div class="card-body" style="padding: 28px 32px;">
                            <form method="POST" action="<?= BASE_URL ?>modules/products/add_product.php" id="addProductForm">
                                <div class="row">
                                    <!-- Product Details Section -->
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-tag"></i> Product Details
                                        </div>

                                        <!-- Name Field -->
                                        <div class="mb-3">
                                            <label for="name" class="form-label">
                                                <i class="fas fa-tag"></i> Product Name
                                            </label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Enter Product Name" required
                                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                        </div>

                                        <!-- Description Field -->
                                        <div class="mb-3">
                                            <label for="description" class="form-label">
                                                <i class="fas fa-info-circle"></i> Product Description
                                            </label>
                                            <textarea class="form-control" id="description" name="description"
                                                placeholder="Enter Product Description" rows="3"><?php 
                                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                                                ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Pricing Details Section -->
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-dollar-sign"></i> Pricing Details
                                        </div>
                                        
                                        <!-- LKR Price Field -->
                                        <div class="mb-3">
                                            <label for="lkr_price" class="form-label">
                                                <i class="fas fa-money-bill-wave"></i> LKR Price
                                            </label>
                                            <input type="number" step="0.01" class="form-control" id="lkr_price" 
                                                 name="lkr_price" placeholder="Enter LKR Price" required
                                                value="<?php echo isset($_POST['lkr_price']) ? htmlspecialchars($_POST['lkr_price']) : ''; ?>">
                                        </div>

                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-12 d-flex justify-content-end gap-3">
                                        <a href="<?= BASE_URL ?>modules/products/product_list.php" class="back-btn text-decoration-none d-flex align-items-center">
                                            <i class="fas fa-arrow-left me-2"></i> Cancel
                                        </a>
                                        <button type="submit" class="save-btn">
                                            <i class="fas fa-plus-circle"></i> Add Product
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
            </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="<?= BASE_URL ?>js/scripts.js"></script>
    <script>
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(event) {
            const lkrPrice = document.getElementById('lkr_price').value.trim();
            
            // Check if LKR price field is filled
            if (lkrPrice === '') {
                event.preventDefault();
                showToast('warning', 'LKR price is required.');
                return false;
            }
            
            // Validate LKR price
            if (parseFloat(lkrPrice) < 0 || parseFloat(lkrPrice) > 1000000) {
                event.preventDefault();
                showToast('warning', 'Please enter a valid LKR price.');
                return false;
            }
        });

        // Product Name Validation
        document.getElementById('name').addEventListener('blur', function () {
            const nameRegex = /^[a-zA-Z0-9\s\-_.,()]{3,100}$/;
            if (!nameRegex.test(this.value)) {
                this.setCustomValidity('Product name must be 3-100 characters long and can contain letters, numbers, spaces, and some special characters');
            } else {
                this.setCustomValidity('');
            }
        });


    </script>

    <?php
    // Close the connection at the end of the script
    $conn->close();
    ?>
</body>
</html>