<?php
require_once __DIR__ . '/../../config/paths.php';

// Start session at the very beginning
session_start();

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

// Include the database connection file
require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php'; // Include helper functions

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);



// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query with JOIN to customers table and payment information
$countSql = "SELECT COUNT(*) as total FROM invoices i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             LEFT JOIN users u2 ON i.created_by = u2.id"; // Added JOIN with users table for u2

$sql = "SELECT i.*, c.name as customer_name, c.business_name as customer_business_name, 
               p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
               u1.name as paid_by_name,
               u2.name as creator_name
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN payments p ON i.invoice_id = p.invoice_id
        LEFT JOIN users u1 ON p.pay_by = u1.id
        LEFT JOIN users u2 ON i.created_by = u2.id";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE (
                        i.invoice_id LIKE '%$searchTerm%' OR
                        i.quotation_ref_no LIKE '%$searchTerm%' OR
                        c.name LIKE '%$searchTerm%' OR
                        c.business_name LIKE '%$searchTerm%' OR
                        i.issue_date LIKE '%$searchTerm%' OR
                        i.due_date LIKE '%$searchTerm%' OR
                        i.total_amount LIKE '%$searchTerm%' OR
                        i.status LIKE '%$searchTerm%' OR
                        i.pay_status LIKE '%$searchTerm%' OR
                        u2.name LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY i.invoice_id DESC LIMIT $limit OFFSET $offset";

// Execute the queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Invoice List</title>
    <link href="<?= BASE_URL ?>css/invoice-list.css" rel="stylesheet" />
    <link href="<?= BASE_URL ?>css/forms.css" rel="stylesheet" />
</head>

<body class="sb-nav-fixed">
    <?php require_once BASE_PATH . 'includes/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require_once BASE_PATH . 'includes/sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Invoice List</h5>
                        <p class="text-muted">Manage and review all invoices</p>
                    </div>
                </div>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <script>document.addEventListener('DOMContentLoaded', function() { showToast('<?php echo $_SESSION["message_type"] === "danger" ? "error" : $_SESSION["message_type"]; ?>', '<?php echo addslashes($_SESSION["message"]); ?>'); });</script>
                        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <div class="card invoice-card">
                        <div class="card-body">
                            <!-- Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder=" Search by invoice ID, customer, date..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/invoices/invoice_list.php" class="btn btn-outline-secondary btn-clear">
                                            <i class="fas fa-times me-1"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                    <input type="hidden" name="page" value="1">
                                </form>
                                <form method="get" id="limitForm" class="d-flex align-items-center gap-2">
                                    <?php if (!empty($search)): ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <?php endif; ?>
                                    <input type="hidden" name="page" value="1">
                                    <span class="entries-label">Show</span>
                                    <select name="limit" class="form-select"
                                        style="width: 80px;"
                                        onchange="document.getElementById('limitForm').submit()">
                                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                    </select>
                                    <span class="entries-label">entries</span>
                                </form>
                            </div>
                            <?php if (!empty($search)): ?>
                                <div class="search-count"><?php echo $totalRows; ?> Invoice<?= $totalRows !== 1 ? 's' : '' ?></div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-invoice" id="invoice_table">
                                    <thead class="table-light">
                                            <tr>
                                                <th>Invoice ID</th>
                                                <th>Ref No</th>
                                                <th>Customer</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Processed By</th>
                                                <th>Created By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo isset($row['invoice_id']) ? htmlspecialchars($row['invoice_id']) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $refNo = htmlspecialchars($row['invoice_ref_no'] ?? generateRefNo($conn, $row['invoice_id'], $row['issue_date'], 'INV'));
                                                        echo $refNo;
                                                        if (isset($row['quotation_ref_no']) && !empty($row['quotation_ref_no'])): ?>
                                                            <br><small class="text-muted" style="font-size: 0.75rem;">From: <?php echo htmlspecialchars($row['quotation_ref_no']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $businessName = isset($row['customer_business_name']) ? htmlspecialchars($row['customer_business_name']) : '';
                                                        $customerName = isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A';
                                                        $customerId = isset($row['customer_id']) ? htmlspecialchars($row['customer_id']) : '';

                                                        if ($businessName) {
                                                            echo '<div class="fw-semibold">' . $businessName . '</div>';
                                                            echo '<div class="text-muted" style="font-size: 0.82rem;">' . $customerName;
                                                            if ($customerId) echo ' <span class="customer-id">(' . $customerId . ')</span>';
                                                            echo '</div>';
                                                        } else {
                                                            echo '<div class="fw-semibold">' . $customerName . '</div>';
                                                            if ($customerId) echo '<div class="customer-id">(' . $customerId . ')</div>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo isset($row['issue_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['issue_date']))) : ''; ?>
                                                    </td>
                                                    <td><?php echo isset($row['due_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $amount = isset($row['total_amount']) ? htmlspecialchars(number_format((float) $row['total_amount'], 2)) : '0.00';
                                                        $payStatus = isset($row['pay_status']) ? $row['pay_status'] : 'unpaid';

                                                        echo '<div class="amount-text">' . $amount . ' <span class="currency-symbol">(Rs)</span></div>';

                                                        if ($payStatus == 'paid'): ?>
                                                            <span class="badge-soft badge-soft-success mt-1">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge-soft badge-soft-danger mt-1">Unpaid</span>
                                                        <?php endif;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status = isset($row['status']) ? $row['status'] : 'pending';
                                                        if ($status == 'done'): ?>
                                                            <span class="badge-soft badge-soft-success">Complete</span>
                                                        <?php elseif ($status == 'cancel'): ?>
                                                            <span class="badge-soft badge-soft-danger">Canceled</span>
                                                        <?php else: ?>
                                                            <span class="badge-soft badge-soft-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (isset($row['pay_by']) && isset($row['paid_by_name'])) {
                                                            echo htmlspecialchars($row['paid_by_name']);
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (isset($row['created_by']) && isset($row['creator_name'])) {
                                                            echo htmlspecialchars($row['creator_name']);
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-btn-group d-flex gap-1">
                                                            <a href="#" class="btn btn-view view-invoice"
                                                                title="View Invoice"
                                                                data-id="<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                data-paystatus="<?php echo $payStatus; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="<?= BASE_URL ?>modules/invoices/download_invoice.php?id=<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                class="btn btn-download"
                                                                title="Download Invoice"
                                                                target="_blank">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            

                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No invoices found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                                <div class="entries-info">
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        Showing <strong><?php echo ($offset + 1); ?></strong> to
                                        <strong><?php echo min($offset + $limit, $totalRows); ?></strong> of <strong><?php echo $totalRows; ?></strong>
                                        entries
                                    <?php else: ?>
                                        Showing <strong>0</strong> to <strong>0</strong> of <strong>0</strong> entries
                                    <?php endif; ?>
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>

                                        <?php
                                        // Display a limited number of page links
                                        $maxPagesToShow = 5;
                                        $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                        $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                        // Show "..." before the first page link if needed
                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php 
                                        // Show "..." after the last page link if needed
                                        if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $totalPages; ?></a>
                                            </li>
                                        <?php endif; ?>

                                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
            </main>
        </div>
    </div>

    <!-- Modal for Viewing Invoice -->
    <div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-labelledby="viewInvoiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-system">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewInvoiceModalLabel"><i class="fas fa-file-invoice"></i>Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceDetails">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Marking Invoice as Paid -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="markPaidModalLabel"><i class="fas fa-money-bill-wave me-2"></i>Payment Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="markPaidForm" enctype="multipart/form-data">
                        <input type="hidden" name="invoice_id" id="invoice_id">

                        <div class="mb-3">
                            <label for="payment_slip" class="form-label fw-semibold">Please Select Your Payment Slip</label>
                            <input type="file" class="form-control" id="payment_slip" name="payment_slip"
                                accept=".jpg,.png,.pdf">
                            <small class="form-text text-muted">Supported File formats: .JPG / .PNG / .PDF (Optional)</small>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-check-circle me-2"></i>Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/scripts.js"></script>
   
    <script>
        $(document).ready(function () {


            // Handle "View" button click
            $('.view-invoice').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID
                var payStatus = $(this).data('paystatus'); // Get the payment status

                // Show loading message in the modal
                $('#invoiceDetails').html('Loading...');

                // Fetch invoice details via AJAX - CHANGED FROM view_invoice.php to download_invoice.php
                $.ajax({
                    url: 'download_invoice.php',
                    type: 'GET',
                    data: { 
                        id: invoiceId,
                        format: 'html' // Request HTML format instead of PDF download
                    },
                    success: function (response) {
                        // Populate the modal with the fetched data
                        $('#invoiceDetails').html(response);

                        // IMPORTANT: Remove any download buttons or unnecessary elements if they exist
                        $('#invoiceDetails').find('button:contains("Print Invoice")').remove();
                        $('#invoiceDetails').find('button:contains("Open in New Tab")').remove();
                        $('#invoiceDetails').find('button:contains("Download")').remove();

                        // Show the modal
                        $('#viewInvoiceModal').modal('show');
                    },
                    error: function () {
                        $('#invoiceDetails').html('Failed to load invoice details.');
                    }
                });
            });
            
            // Handle "Paid" button click
            $('.mark-paid').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID

                // Directly set the invoice ID in the form without fetching other details
                $('#invoice_id').val(invoiceId);

                // Show the modal
                $('#markPaidModal').modal('show');
            });

            // Handle form submission
            $('#markPaidForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                var formData = new FormData(this);

                $.ajax({
                    url: 'mark_paid.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        showToast('success', 'Invoice marked as paid successfully.');
                        $('#markPaidModal').modal('hide');
                        location.reload(); // Reload the page to reflect changes
                    },
                    error: function () {
                        showToast('error', 'Failed to mark invoice as paid.');
                    }
                });
            });
            

        });
    </script>
</body>

</html>