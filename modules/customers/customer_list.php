<?php
require_once __DIR__ . '/../../config/paths.php';

// Start session at the very beginning
session_start();

// Include the database connection file
require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php';

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: " . BASE_URL . "signin.php");
    exit();
}

// Check for success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear the messages from the session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query for counting total rows
$countSql = "SELECT COUNT(*) as total FROM customers";

// Build basic SQL query for fetching customers
$sql = "SELECT * FROM customers";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE (
                        customer_id LIKE '%$searchTerm%' OR 
                        business_name LIKE '%$searchTerm%' OR 
                        name LIKE '%$searchTerm%' OR 
                        email LIKE '%$searchTerm%' OR 
                        phone LIKE '%$searchTerm%' OR 
                        address LIKE '%$searchTerm%' OR
                        status LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY customer_id DESC LIMIT $limit OFFSET $offset";

// Execute the count query
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);

// Execute the main fetch query
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Customer List</title>
    <link href="<?= BASE_URL ?>css/customer-list.css" rel="stylesheet" />
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
                        <h5>Customer List</h5>
                        <p class="text-muted">Manage and review all customers</p>
                    </div>
                </div>
                    
                    <?php if ($success_message): ?>
                        <script>document.addEventListener('DOMContentLoaded', function() { showToast('success', '<?php echo addslashes(htmlspecialchars($success_message)); ?>'); });</script>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <script>document.addEventListener('DOMContentLoaded', function() { showToast('error', '<?php echo addslashes(htmlspecialchars($error_message)); ?>'); });</script>
                    <?php endif; ?>
                    
                    <div class="card invoice-card mb-4">
                        <div class="card-body">
                            <!-- Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder=" Search by name, email, phone..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/customers/customer_list.php" class="btn btn-outline-secondary btn-clear">
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
                                    <select name="limit" class="form-select" style="width: 80px;" onchange="document.getElementById('limitForm').submit()">
                                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                    </select>
                                    <span class="entries-label">entries</span>
                                </form>
                            </div>
                            <?php if (!empty($search)): ?>
                                <div class="search-count"><?php echo $totalRows; ?> Customer<?= $totalRows !== 1 ? 's' : '' ?></div>
                            <?php endif; ?>

                    <div class="table-responsive">
                             <table class="table table-invoice" id="customer_table">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Customer ID</th>
                                         <th>Business Name</th>
                                         <th>Customer Name</th>
                                         <th>Phone</th>
                                         <th>Address</th>
                                         <th>Status</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php while ($row = $result->fetch_assoc()): ?>
                                         <tr id="customer-row-<?= $row['customer_id'] ?>">
                                             <td>
                                                 <span class="fw-semibold"><?= htmlspecialchars($row['customer_id']) ?></span>
                                             </td>
                                             <td><?= htmlspecialchars($row['business_name']) ?></td>
                                             <td>
                                                 <div class="fw-semibold"><?= htmlspecialchars($row['name']) ?></div>
                                                 <div class="text-muted" style="font-size: 0.82rem;"><?= htmlspecialchars($row['email']) ?></div>
                                             </td>
                                             <td><?= htmlspecialchars($row['phone']) ?></td>
                                             <td><?= htmlspecialchars($row['address']) ?></td>
                                             <td>
                                                 <?php if ($row['status'] == 'Active'): ?>
                                                     <span class="badge-soft badge-soft-success">Active</span>
                                                 <?php else: ?>
                                                      <span class="badge-soft badge-soft-danger">Inactive</span>
                                                 <?php endif; ?>
                                             </td>
                                             <td>
                                                  <div class="action-btn-group d-flex gap-1">
                                                      <?php if ($canEditRecords): ?>
                                                      <a href="<?= BASE_URL ?>modules/customers/edit_customer.php?id=<?= htmlspecialchars($row['customer_id']) ?>"
                                                          class="btn btn-edit"
                                                          title="Edit Customer">
                                                          <i class="fas fa-pen"></i>
                                                      </a>
                                                      <?php endif; ?>
                                                       <button class="btn btn-view view-customer-btn"
                                                           title="View Details"
                                                           data-customer-id="<?= $row['customer_id'] ?>"
                                                           data-customer-business="<?= htmlspecialchars($row['business_name']) ?>"
                                                           data-customer-name="<?= htmlspecialchars($row['name']) ?>"
                                                           data-customer-email="<?= htmlspecialchars($row['email']) ?>"
                                                           data-customer-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                           data-customer-address="<?= htmlspecialchars($row['address']) ?>"
                                                            data-customer-status="<?= htmlspecialchars($row['status']) ?>"
                                                            data-customer-created="<?= htmlspecialchars($row['created_at']) ?>">
                                                          <i class="fas fa-eye"></i>
                                                      </button>
                                                      <?php if ($canEditRecords): ?>
                                                      <button class="btn <?= $row['status'] == 'Active' ? 'btn-cancel' : 'btn-view' ?> toggle-status-btn"
                                                          title="<?= $row['status'] == 'Active' ? 'Deactivate' : 'Activate' ?>"
                                                          data-customer-id="<?= $row['customer_id'] ?>"
                                                          data-current-status="<?= $row['status'] ?>"
                                                          data-customer-name="<?= htmlspecialchars($row['name']) ?>">
                                                          <i class="fas <?= $row['status'] == 'Active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                      </button>
                                                      <?php endif; ?>
                                                  </div>
                                              </td>
                                         </tr>
                                     <?php endwhile; ?>
                                 </tbody>
                             </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                            <div class="entries-info">
                                Showing <strong><?php echo ($totalRows > 0) ? ($offset + 1) : 0; ?></strong> to
                                <strong><?php echo min($offset + $limit, $totalRows); ?></strong> of <strong><?php echo $totalRows; ?></strong>
                                entries
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
                                    $maxPagesToShow = 5;
                                    $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                    $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                    if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
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
                    </div> <!-- card-body close -->
                </div> <!-- card close -->
            </div> <!-- container-fluid close -->
        </main>
        </div>
    </div>

    <!-- View Customer Modal -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-system">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCustomerModalLabel"><i class="fas fa-user me-2"></i>Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewCustomerModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script src="<?= BASE_URL ?>js/scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {




        // View Customer Modal Handling
        const viewButtons = document.querySelectorAll('.view-customer-btn');
        const viewCustomerModal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
        const viewCustomerModalBody = document.getElementById('viewCustomerModalBody');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerData = {
                    id: this.getAttribute('data-customer-id'),
                    business: this.getAttribute('data-customer-business'),
                    name: this.getAttribute('data-customer-name'),
                    email: this.getAttribute('data-customer-email'),
                    phone: this.getAttribute('data-customer-phone'),
                    address: this.getAttribute('data-customer-address'),
                    status: this.getAttribute('data-customer-status'),
                    created: this.getAttribute('data-customer-created')
                };

                viewCustomerModalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-hashtag"></i>Customer ID</span>
                            <p class="detail-value">${customerData.id}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-calendar"></i>Created At</span>
                            <p class="detail-value">${customerData.created}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-building"></i>Business Name</span>
                            <p class="detail-value">${customerData.business || '<em class="text-muted">N/A</em>'}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-user"></i>Name</span>
                            <p class="detail-value">${customerData.name}</p>
                        </div>
                        <div class="detail-card full-width">
                            <span class="detail-label"><i class="fas fa-envelope"></i>Email</span>
                            <p class="detail-value">${customerData.email || '<em class="text-muted">N/A</em>'}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-phone"></i>Phone</span>
                            <p class="detail-value">${customerData.phone}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-flag"></i>Status</span>
                            <p class="detail-value">
                                ${customerData.status === 'Active'
                                    ? '<span class="badge-soft badge-soft-success">Active</span>'
                                    : '<span class="badge-soft badge-soft-secondary">Inactive</span>'}
                            </p>
                        </div>
                        <div class="detail-card full-width">
                            <span class="detail-label"><i class="fas fa-location-dot"></i>Address</span>
                            <p class="detail-value">${customerData.address}</p>
                        </div>
                    </div>
                `;

                viewCustomerModal.show();
            });
        });

        // Status Toggle Button Handling with SweetAlert
        const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');

        toggleStatusButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                const currentStatus = this.getAttribute('data-current-status');
                const customerName = this.getAttribute('data-customer-name');
                const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                const actionText = currentStatus === 'Active' ? 'deactivate' : 'activate';
                const actionColor = currentStatus === 'Active' ? '#d33' : '#28a745';
                
                // SweetAlert confirmation before status change
                Swal.fire({
                    title: `Are you sure?`,
                    html: `You are about to <strong>${actionText}</strong> customer: <br><strong>${customerName}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: actionColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} customer!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            html: `Updating customer status to ${newStatus}`,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // AJAX call to update status
                        fetch('<?= BASE_URL ?>modules/customers/toggle_customer_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `customer_id=${customerId}&action=${actionText}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update button and badge
                                const customerRow = document.getElementById(`customer-row-${customerId}`);
                                const statusBadge = customerRow.querySelector('.customer-status-badge');
                                const toggleButton = customerRow.querySelector('.toggle-status-btn');

                                if (data.new_status === 'Active') {
                                    statusBadge.classList.remove('badge-soft-danger');
                                    statusBadge.classList.add('badge-soft-success');
                                    toggleButton.classList.remove('btn-activate');
                                    toggleButton.classList.add('btn-deactivate');
                                    toggleButton.innerHTML = '<i class="fas fa-ban"></i>';
                                    toggleButton.setAttribute('title', 'Deactivate');
                                } else {
                                    statusBadge.classList.remove('badge-soft-success');
                                    statusBadge.classList.add('badge-soft-danger');
                                    toggleButton.classList.remove('btn-deactivate');
                                    toggleButton.classList.add('btn-activate');
                                    toggleButton.innerHTML = '<i class="fas fa-check"></i>';
                                    toggleButton.setAttribute('title', 'Activate');
                                }

                                statusBadge.textContent = data.new_status;
                                toggleButton.setAttribute('data-current-status', data.new_status);

                                // Show success message
                                showToast('success', `Customer ${customerName} has been ${data.new_status === 'Active' ? 'activated' : 'deactivated'} successfully.`);
                            } else {
                                // Show error message
                                showToast('error', data.message || 'Failed to update customer status');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', 'An error occurred while updating customer status');
                        });
                    }
                });
            });
        });


    });
    </script>
</body>
</html>

<?php
$conn->close();
?>