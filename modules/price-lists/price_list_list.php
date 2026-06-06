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
    $searchCondition = " WHERE pl.id LIKE '%$searchTerm%'";
}

// Count total price lists
$countSql = "SELECT COUNT(*) as total FROM price_lists pl $searchCondition";
$countResult = $conn->query($countSql);
$totalRows = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);

// Fetch price lists with limit (JOIN with customers)
$sql = "SELECT pl.*, c.name as customer_name 
        FROM price_lists pl 
        LEFT JOIN customers c ON pl.customer_id = c.customer_id 
        $searchCondition 
        ORDER BY pl.id DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Price Lists</title>
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
        
        body.modal-open {
            padding-right: 0 !important;
            overflow: auto !important;
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
                        <h5>Price Lists</h5>
                        <p class="text-muted">Manage and review all price lists</p>
                    </div>
                    <a href="<?= BASE_URL ?>modules/price-lists/price_list_create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create New
                    </a>
                </div>

                    <div class="card invoice-card">
                        <div class="card-body">
                            <!-- Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4" placeholder="Search price lists by ID..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter"><i class="fas fa-search me-1"></i> Search</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/price-lists/price_list_list.php" class="btn btn-outline-secondary btn-clear"><i class="fas fa-times me-1"></i> Clear</a>
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
                                <div class="search-count"><?php echo $totalRows; ?> Price Lists<?= $totalRows !== 1 ? 's' : '' ?></div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-invoice align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Price List No.</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Currency</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-muted" style="font-weight: 500;">PL-<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                                    <td><?= date('d M, Y', strtotime($row['price_list_date'])) ?></td>
                                                    <td><?= htmlspecialchars($row['customer_name'] ?? '-') ?></td>
                                                    <td>
                                                        <span class="badge-soft badge-soft-info">
                                                            <?= strtoupper($row['currency']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3">
                                <div class="action-btn-group d-flex justify-content-end gap-1">
                                    <a href="<?= BASE_URL ?>modules/price-lists/price_list_edit.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="#" class="btn btn-view view-price-list" title="View Price List"
                                        data-id="<?php echo $row['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/price-lists/view_price_list.php?id=<?= $row['id'] ?>" class="btn btn-download" title="Download Price List" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">No price lists found.</td>
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
  
  <!-- Modal for Viewing Price List -->
  <div class="modal fade" id="viewPriceListModal" tabindex="-1" aria-labelledby="viewPriceListModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="viewPriceListModalLabel"><i class="fas fa-file-alt me-2"></i>Price List Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="priceListDetails">
                  Loading...
              </div>
          </div>
      </div>
  </div>
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>js/scripts.js"></script>

  <script>
      $(document).ready(function () {
          $('.view-price-list').click(function (e) {
              e.preventDefault();
              var priceListId = $(this).data('id');
              $('#priceListDetails').html('Loading...');
              $.ajax({
                  url: 'view_price_list.php',
                  type: 'GET',
                  data: { 
                      id: priceListId,
                      format: 'html'
                  },
                  success: function (response) {
                      $('#priceListDetails').html(response);
                      $('#priceListDetails').find('.control-panel').remove();
                      $('#viewPriceListModal').modal('show');
                  },
                  error: function () {
                      $('#priceListDetails').html('Failed to load price list details.');
                  }
              });
          });
      });
  </script>
</body>
</html>
<?php $conn->close(); ?>
