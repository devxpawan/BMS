<?php
require_once __DIR__ . '/../../config/paths.php';

// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: " . BASE_URL . "signin.php");
    exit();
}

// Admin-only access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Include the database connection file
require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php';

        // Handle user status update via AJAX if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
            $response = ['success' => false, 'message' => 'Unknown error'];

            // Only admin can update user status
            if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
                $response = ['success' => false, 'message' => 'Access denied. Admin privileges required.'];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }

            if (isset($_POST['user_id']) && isset($_POST['new_status'])) {
                $user_id = intval($_POST['user_id']);
                $new_status = $_POST['new_status'];
                $current_user_id = $_SESSION['user_id'];

                // Prevent self-deactivation: admin cannot deactivate their own account
                if ($user_id == $current_user_id && $new_status === 'inactive') {
                    $response = [
                        'success' => false, 
                        'message' => 'You cannot deactivate your own account.'
                    ];
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();
                }

                if (in_array($new_status, ['active', 'inactive'])) {
                    $conn->begin_transaction();

                    try {
                        $user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                        $user_stmt->bind_param("i", $user_id);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        $user_name = "";

                        if ($user_result && $user_result->num_rows > 0) {
                            $user_name = $user_result->fetch_assoc()['name'];
                        }
                        $user_stmt->close();

                        $update_stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $new_status, $user_id);
                        $update_success = $update_stmt->execute();
                        $affected = $update_stmt->affected_rows;
                        $update_stmt->close();

                if ($update_success) {
                    if ($affected > 0) {
                        $action_type = ($new_status === 'active') ? 'activate_user' : 'deactivate_user';
                        $details = "User ID #$user_id ($user_name) was " .
                                   ($new_status === 'active' ? 'activated' : 'deactivated') .
                                   " by user ID #$current_user_id";

                        $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, '0', ?)");
                        $log_stmt->bind_param("iss", $current_user_id, $action_type, $details);
                        $log_success = $log_stmt->execute();
                        $log_stmt->close();

                        if (!$log_success) {
                            $conn->rollback();
                            $response = [
                                'success' => false, 
                                'message' => "Error logging status change: " . $conn->error
                            ];
                            header('Content-Type: application/json');
                            echo json_encode($response);
                            exit();
                        }
                    }

                    $conn->commit();
                    
                    $response = [
                        'success' => true, 
                        'message' => "User status updated to $new_status successfully",
                        'new_status' => $new_status
                    ];
                } else {
                    // Rollback if update failed
                    $conn->rollback();
                    $response = [
                        'success' => false, 
                        'message' => "Error updating status: " . $conn->error
                    ];
                }
            } catch (Exception $e) {
                // Rollback on any exception
                $conn->rollback();
                $response = [
                    'success' => false, 
                    'message' => "Transaction failed: " . $e->getMessage()
                ];
            }
        } else {
            $response = [
                'success' => false, 
                'message' => "Invalid status value"
            ];
        }
    } else {
        $response = [
            'success' => false, 
            'message' => "Missing required parameters"
        ];
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;

// Check if user is admin (role_id = 1)
$is_admin = ($current_user_role == 1);

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build search condition
$searchCondition = "";
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (u.id LIKE '%$searchTerm%' OR 
                            u.name LIKE '%$searchTerm%' OR 
                            u.email LIKE '%$searchTerm%' OR
                            u.mobile LIKE '%$searchTerm%' OR
                            r.name LIKE '%$searchTerm%' OR
                            p.name LIKE '%$searchTerm%' OR
                            u.status LIKE '%$searchTerm%')";
}

    // Modify SQL based on user role and search
    if ($is_admin) {
        // Admin can see all users
        $sql = "SELECT u.*, r.name AS role_name, p.name AS position_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN positions p ON u.position_id = p.id 
                WHERE 1=1 $searchCondition
                ORDER BY u.id DESC 
                LIMIT $limit OFFSET $offset";
        
        $countQuery = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1 $searchCondition";
    } else {
        // Non-admin users can only see non-admin users
        $sql = "SELECT u.*, r.name AS role_name, p.name AS position_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN positions p ON u.position_id = p.id 
                WHERE u.role_id != 1 $searchCondition
                ORDER BY u.id DESC 
                LIMIT $limit OFFSET $offset";
                
        $countQuery = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id WHERE u.role_id != 1 $searchCondition";
    }

// Execute queries
$countResult = $conn->query($countQuery);
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
    <title>All Users</title>
    <link href="<?= BASE_URL ?>css/users-list.css" rel="stylesheet" />
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
                        <h5>Users List</h5>
                        <p class="text-muted">Manage and review all system users</p>
                    </div>
                </div>

                    <div class="card invoice-card mb-4">
                        <div class="card-body">
                            <!-- Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder=" Search by name, email, role..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/users/users.php" class="btn btn-outline-secondary btn-clear">
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
                                <div class="search-count"><?php echo $totalRows; ?> Users<?= $totalRows !== 1 ? 's' : '' ?></div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-users" id="users_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name / Role</th>
                                            <th>Email</th>
                                            <th>Mobile</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr id="user-row-<?= $row['id'] ?>">
                                                    <td>
                                                        <span class="fw-semibold"><?= htmlspecialchars($row['id']) ?></span>
                                                        <br>
                                                    </td>
                                                    <td>
                                                        <div class="user-name"><?= htmlspecialchars($row['name']) ?></div>
                                                        <div class="user-role"><?= htmlspecialchars($row['role_name']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                    <td><?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?></td>
                                                    <td>
                                                        <?php if (!empty($row['position_name'])): ?>
                                                            <span class="badge badge-soft badge-soft-info"><?= htmlspecialchars($row['position_name']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'active'): ?>
                                                            <span class="user-status-badge badge-soft badge-soft-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="user-status-badge badge-soft badge-soft-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-btn-group d-flex gap-1">
                                                            <a href="<?= BASE_URL ?>modules/users/edit_user.php?id=<?= htmlspecialchars($row['id']) ?>&name=<?= urlencode($row['name']) ?>&email=<?= urlencode($row['email']) ?>&status=<?= htmlspecialchars($row['status']) ?>&role=<?= urlencode($row['role_name']) ?>"
                                                                class="btn btn-edit"
                                                                title="Edit User">
                                                                <i class="fas fa-pen"></i>
                                                            </a>
                                                            <button class="btn btn-view view-user-btn"
                                                                title="View Details"
                                                                data-user-id="<?= $row['id'] ?>"
                                                                data-user-name="<?= htmlspecialchars($row['name']) ?>"
                                                                data-user-email="<?= htmlspecialchars($row['email']) ?>"
                                                                data-user-mobile="<?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?>"
                                                                data-user-nic="<?= isset($row['nic']) ? htmlspecialchars($row['nic']) : 'N/A' ?>"
                                                                data-user-status="<?= htmlspecialchars($row['status']) ?>"
                                                                data-user-role="<?= htmlspecialchars($row['role_name']) ?>"
                                                                data-user-role-id="<?= htmlspecialchars($row['role_id']) ?>"
                                                                data-user-position="<?= isset($row['position_name']) ? htmlspecialchars($row['position_name']) : 'N/A' ?>"
                                                                data-user-created="<?= htmlspecialchars($row['created_at']) ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn <?= $row['status'] == 'active' ? 'btn-deactivate' : 'btn-activate' ?> toggle-status-btn"
                                                                title="<?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>"
                                                                data-user-id="<?= $row['id'] ?>"
                                                                data-current-status="<?= $row['status'] ?>"
                                                                data-user-name="<?= htmlspecialchars($row['name']) ?>">
                                                                <i class="fas <?= $row['status'] == 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-users"></i>
                                                        <p>No users found</p>
                                                    </div>
                                                </td>
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

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-system">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel"><i class="fas fa-user-circle me-2"></i>User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewUserModalBody">
                    <!-- Dynamic content will be inserted here -->
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



        // View User Modal Handling
        const viewButtons = document.querySelectorAll('.view-user-btn');
        const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
        const viewUserModalBody = document.getElementById('viewUserModalBody');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const userData = {
                    name: this.getAttribute('data-user-name'),
                    email: this.getAttribute('data-user-email'),
                    mobile: this.getAttribute('data-user-mobile'),
                    nic: this.getAttribute('data-user-nic'),
                    status: this.getAttribute('data-user-status'),
                    role: this.getAttribute('data-user-role'),
                    roleId: this.getAttribute('data-user-role-id'),
                    position: this.getAttribute('data-user-position'),
                    created: this.getAttribute('data-user-created')
                };

                viewUserModalBody.innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-user"></i>Name</span>
                            <p class="detail-value">${userData.name}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-envelope"></i>Email</span>
                            <p class="detail-value">${userData.email}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-phone"></i>Mobile</span>
                            <p class="detail-value">${userData.mobile}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-id-card"></i>NIC</span>
                            <p class="detail-value">${userData.nic}</p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-flag"></i>Status</span>
                            <p class="detail-value">
                                ${userData.status === 'active' 
                                    ? '<span class="badge-soft badge-soft-success">Active</span>' 
                                    : '<span class="badge-soft badge-soft-secondary">Inactive</span>'}
                            </p>
                        </div>
                        <div class="detail-card">
                            <span class="detail-label"><i class="fas fa-briefcase"></i>Position</span>
                            <p class="detail-value">${userData.position}</p>
                        </div>
                        <div class="detail-card full-width">
                            <span class="detail-label"><i class="fas fa-calendar"></i>Created At</span>
                            <p class="detail-value">${userData.created}</p>
                        </div>
                    </div>
                `;

                viewUserModal.show();
            });
        });

        // Status Toggle Handling
        const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;

        toggleStatusButtons.forEach(btn => {
            const userId = parseInt(btn.getAttribute('data-user-id'));
            const currentStatus = btn.getAttribute('data-current-status');
            
            // Hide the toggle button for the current user (self) to prevent self-deactivation
            if (userId === currentUserId && currentStatus === 'active') {
                btn.style.display = 'none';
                return;
            }

            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const currentStatus = this.getAttribute('data-current-status');
                const userName = this.getAttribute('data-user-name');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
                const actionColor = currentStatus === 'active' ? '#d33' : '#28a745';
                
                // SweetAlert confirmation before status change
                Swal.fire({
                    title: `Are you sure?`,
                    html: `You are about to <strong>${actionText}</strong> user: <br><strong>${userName}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: actionColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} user!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            html: `Updating user status to ${newStatus}`,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // AJAX call to update status
                        fetch('<?= BASE_URL ?>modules/users/users.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update_status&user_id=${userId}&new_status=${newStatus}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update button and badge
                                const userRow = document.getElementById(`user-row-${userId}`);
                                const statusBadge = userRow.querySelector('.user-status-badge');
                                const toggleButton = userRow.querySelector('.toggle-status-btn');

                                if (newStatus === 'active') {
                                    statusBadge.classList.remove('badge-soft-secondary');
                                    statusBadge.classList.add('badge-soft-success');
                                    statusBadge.textContent = 'Active';
                                    toggleButton.classList.remove('btn-activate');
                                    toggleButton.classList.add('btn-deactivate');
                                    toggleButton.innerHTML = '<i class="fas fa-ban"></i>';
                                    toggleButton.setAttribute('title', 'Deactivate');
                                } else {
                                    statusBadge.classList.remove('badge-soft-success');
                                    statusBadge.classList.add('badge-soft-secondary');
                                    statusBadge.textContent = 'Inactive';
                                    toggleButton.classList.remove('btn-deactivate');
                                    toggleButton.classList.add('btn-activate');
                                    toggleButton.innerHTML = '<i class="fas fa-check"></i>';
                                    toggleButton.setAttribute('title', 'Activate');
                                }
                                toggleButton.setAttribute('data-current-status', newStatus);

                                // Show success message
                                showToast('success', `User ${userName} has been ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully.`);
                            } else {
                                // Show error message
                                showToast('error', data.message || 'Failed to update user status');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', 'An error occurred while updating user status');
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