<?php
require_once __DIR__ . '/../../config/paths.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: " . BASE_URL . "signin.php");
    exit();
}

require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php';

// Pagination and Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchCondition = "";
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE name LIKE '%$searchTerm%'";
}

// Count total assets
$countSql = "SELECT COUNT(*) as total FROM assets $searchCondition";
$countResult = $conn->query($countSql);
$totalRows = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);

// Fetch assets with limit
$sql = "SELECT * FROM assets $searchCondition ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Manage Assets</title>
    <link href="<?= BASE_URL ?>css/invoice-list.css" rel="stylesheet" />
    <style>
        .btn-primary {
            background: #3B82F6;
            border-color: #3B82F6;
        }
        .btn-primary:hover {
            background: #2563EB;
            border-color: #2563EB;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }
        .btn-edit { color: #f59e0b; }
        .btn-edit:hover {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php require_once BASE_PATH . 'includes/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require_once BASE_PATH . 'includes/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Asset Management</h5>
                        <p class="text-muted">Manage assets used in price lists</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assetModal" onclick="prepareAdd()">
                        <i class="fas fa-plus me-1"></i> Add New Asset
                    </button>
                </div>

                    <div class="card invoice-card">
                        <div class="card-body">
                            <!-- Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4" placeholder="Search assets by name..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter"><i class="fas fa-search me-1"></i> Search</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/price-lists/manage_assets.php" class="btn btn-outline-secondary btn-clear"><i class="fas fa-times me-1"></i> Clear</a>
                                    <?php endif; ?>
                                </form>
                                <form method="get" class="d-flex align-items-center gap-2">
                                    <span class="entries-label">Show</span>
                                    <select name="limit" class="form-select" style="width: 80px;" onchange="this.form.submit()">
                                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                    </select>
                                    <span class="entries-label">entries</span>
                                    <?php if (!empty($search)): ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <?php endif; ?>
                                </form>
                            </div>

                            <?php if (!empty($search)): ?>
                                <div class="search-count"><?php echo $totalRows; ?> Assets<?= $totalRows !== 1 ? 's' : '' ?></div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-invoice align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Asset Name</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($result && $result->num_rows > 0): 
                                            while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-muted" style="font-weight: 500;"><?= $row['id'] ?></td>
                                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge-soft badge-soft-<?= $row['status'] == 'active' ? 'success' : 'danger' ?>">
                                                            <?= ucfirst($row['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <div class="action-btn-group d-flex justify-content-end gap-1">
                                                            <button class="btn btn-edit" onclick="prepareEdit(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', '<?= $row['status'] ?>')"
                                                                    title="Edit">
                                                                <i class="fas fa-pen"></i>
                                                            </button>
                                                            <button class="btn <?= $row['status'] == 'active' ? 'btn-cancel' : 'btn-download' ?>" 
                                                                    onclick="toggleStatus(<?= $row['id'] ?>, '<?= $row['status'] == 'active' ? 'inactive' : 'active' ?>')"
                                                                    title="<?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                                                <i class="fas <?= $row['status'] == 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; 
                                        else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">No assets found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                                <div class="entries-info">
                                    Showing <strong><?= ($offset + 1) ?></strong> to <strong><?= min($offset + $limit, $totalRows) ?></strong> of <strong><?= $totalRows ?></strong> entries
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
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

    <!-- Asset Modal -->
    <div class="modal fade" id="assetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assetForm">
                    <div class="modal-body py-4">
                        <input type="hidden" name="id" id="asset_id">
                        <input type="hidden" name="action" id="asset_action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Asset Name</label>
                            <input type="text" name="name" id="asset_name" class="form-control" placeholder="e.g. Laptop, Printer, Router" required>
                        </div>
                        <div class="mb-0" id="statusField" style="display:none;">
                            <label class="form-label">Status</label>
                            <select name="status" id="asset_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnSave">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function prepareAdd() {
            $('#modalTitle').text('Add New Asset');
            $('#asset_id').val('');
            $('#asset_name').val('');
            $('#asset_action').val('add');
            $('#statusField').hide();
            $('#btnSave').text('Add Asset');
        }

        function prepareEdit(id, name, status) {
            $('#modalTitle').text('Edit Asset');
            $('#asset_id').val(id);
            $('#asset_name').val(name);
            $('#asset_status').val(status);
            $('#asset_action').val('edit');
            $('#statusField').show();
            $('#btnSave').text('Save Changes');
            $('#assetModal').modal('show');
        }

        $('#assetForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'ajax_asset_handler.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#assetModal').modal('hide');
                        showToast('success', response.message);
                        setTimeout(() => { location.reload(); }, 1500);
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function() {
                    showToast('error', 'Something went wrong. Please try again.');
                }
            });
        });

        function toggleStatus(id, newStatus) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You want to mark this asset as ${newStatus}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'ajax_asset_handler.php',
                        type: 'POST',
                        data: { action: 'toggle_status', id: id, status: newStatus },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showToast('success', response.message);
                                setTimeout(() => { location.reload(); }, 1000);
                            } else {
                                showToast('error', response.message);
                            }
                        },
                        error: function() {
                            showToast('error', 'Something went wrong. Please try again.');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
