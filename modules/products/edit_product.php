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

// Only Admin and Moderator can edit products
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: " . BASE_URL . "modules/products/product_list.php");
    exit();
}

// Include the database connection file
require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php'; // Include helper functions

// Check if the ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to products page if no ID is provided
    header("Location: " . BASE_URL . "modules/products/product_list.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch the product data
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Product not found, redirect to products page
    header("Location: " . BASE_URL . "modules/products/product_list.php");
    exit();
}

$product = $result->fetch_assoc();
$original_product = $product; // Store original values for comparison

// Variable to track product update status
$product_updated = false;
$error_message = null;

// Check if form is submitted for update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    $lkr_price = floatval($_POST['lkr_price']);
    
    // Use a simple approach that works correctly with values
    $updateSql = "UPDATE products SET 
                 name = ?, 
                 description = ?, 
                 lkr_price = ? 
                 WHERE id = ?";
                 
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssdi", $name, $description, $lkr_price, $product_id);
    
    if ($stmt->execute()) {
        // Set flag to show success message
        $product_updated = true;
        
        // Refresh product data after update
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        // Log the changes to user_logs table
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'Unknown User';
        $action_type = "edit_product";
        
        // Create detailed changes log
        $changes = array();
        
        // Check each field for changes
        if ($original_product['name'] != $name) {
            $changes[] = "Name changed from '" . htmlspecialchars($original_product['name']) . "' to '" . htmlspecialchars($name) . "'";
        }
        
        if ($original_product['description'] != $description) {
            // For longer text, truncate if needed
            $old_desc = strlen($original_product['description']) > 30 ? 
                substr(htmlspecialchars($original_product['description']), 0, 30) . '...' : 
                htmlspecialchars($original_product['description']);
                
            $new_desc = strlen($description) > 30 ? 
                substr(htmlspecialchars($description), 0, 30) . '...' : 
                htmlspecialchars($description);
                
            $changes[] = "Description was updated from '$old_desc' to '$new_desc'";
        }
        
        // Handle price changes
        $original_lkr = $original_product['lkr_price'];
        
        // Check for price changes
        if ($original_lkr != $lkr_price) {
            $old_price = is_null($original_lkr) ? 'not set' : number_format($original_lkr, 2) . ' LKR';
            $new_price = number_format($lkr_price, 2) . ' LKR';
            $changes[] = "LKR Price changed from $old_price to $new_price";
        }
        
        // If no changes were detected
        if (empty($changes)) {
            $changes[] = "No changes were made to the product";
        }
        
        // Format the changes list with bullet points
        $changes_text = "Product ID #$product_id (" . htmlspecialchars($name) . ") was updated by user $user_name($user_id). Changes:\n* " . implode("\n* ", $changes);
        
        // Insert into user_logs table
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $inquiry_id = 0; // Not applicable for product editing
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $changes_text);
        $logStmt->execute();
        $logStmt->close();
        
        // Redirect using PRG pattern
        $_SESSION['success_message'] = "Product Updated Successfully!";
        header("Location: " . BASE_URL . "modules/products/edit_product.php?id=" . $product_id);
        exit();
        
    } else {
        // Store error message 
        $error_message = 'Error: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Edit Product</title>
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
                        <h5>Edit Product</h5>
                        <p class="text-muted">Update product information</p>
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
                                <form method="POST" action="<?= BASE_URL ?>modules/products/edit_product.php?id=<?= $product_id ?>" id="editProductForm">
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
                                                    value="<?= htmlspecialchars($product['name']) ?>" data-original="<?= htmlspecialchars($product['name']) ?>">
                                            </div>

                                            <!-- Description Field -->
                                            <div class="mb-3">
                                                <label for="description" class="form-label">
                                                    <i class="fas fa-info-circle"></i> Product Description
                                                </label>
                                                <textarea class="form-control" id="description" name="description"
                                                    placeholder="Enter Product Description" rows="3" data-original="<?= htmlspecialchars($product['description']) ?>"><?= 
                                                    htmlspecialchars($product['description']) 
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
                                                    value="<?= ($product['lkr_price'] !== NULL) ? htmlspecialchars($product['lkr_price']) : '' ?>" data-original="<?= ($product['lkr_price'] !== NULL) ? htmlspecialchars($product['lkr_price']) : '' ?>">
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="row mt-4 pt-3 border-top">
                                        <div class="col-12 d-flex justify-content-end gap-3">
                                            <a href="<?= BASE_URL ?>modules/products/product_list.php" class="back-btn text-decoration-none d-flex align-items-center">
                                                <i class="fas fa-arrow-left me-2"></i> Cancel
                                            </a>
                                            <button type="submit" class="save-btn" id="submitBtn" disabled>
                                                <i class="fas fa-save"></i> Update Product
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
            </main>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="<?= BASE_URL ?>js/scripts.js"></script>
    <script>
        // Form validation
        document.getElementById('editProductForm').addEventListener('submit', function(event) {
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
                showToast('warning', 'Please enter a valid LKR price between 0 and 1,000,000');
                return false;
            }
        });

// Enable/disable submit button based on whether any field changed
function checkForChanges() {
    const fields = ['name', 'description', 'lkr_price'];
    let hasChanged = false;
    for (const id of fields) {
        const el = document.getElementById(id);
        if (el && el.value !== el.getAttribute('data-original')) {
            hasChanged = true;
            break;
        }
    }
    document.getElementById('submitBtn').disabled = !hasChanged;
}

['name', 'description', 'lkr_price'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', checkForChanges);
        el.addEventListener('change', checkForChanges);
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