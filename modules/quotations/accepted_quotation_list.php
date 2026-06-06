<?php
require_once __DIR__ . '/../../config/paths.php';

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) { ob_end_clean(); }
    header("Location: " . BASE_URL . "signin.php");
    exit();
}

require_once BASE_PATH . 'includes/db_connection.php';
require_once BASE_PATH . 'includes/functions.php';

$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$status_filter = "Accepted";

$countSql = "SELECT COUNT(*) as total FROM quotations q 
             LEFT JOIN customers c ON q.customer_id = c.customer_id
             LEFT JOIN users u ON q.created_by = u.id
             WHERE q.status = '$status_filter'";

$sql = "SELECT q.*, c.name as customer_name, c.business_name as customer_business_name, 
               u.name as creator_name
        FROM quotations q 
        LEFT JOIN customers c ON q.customer_id = c.customer_id
        LEFT JOIN users u ON q.created_by = u.id
        WHERE q.status = '$status_filter'";

if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        q.quotation_id LIKE '%$searchTerm%' OR
                        q.ref_no LIKE '%$searchTerm%' OR
                        c.name LIKE '%$searchTerm%' OR
                        c.business_name LIKE '%$searchTerm%' OR
                        q.quotation_date LIKE '%$searchTerm%' OR
                        q.total_amount LIKE '%$searchTerm%' OR
                        u.name LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

$sql .= " ORDER BY q.quotation_id DESC LIMIT $limit OFFSET $offset";

$countResult = $conn->query($countSql);
$totalRows = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . 'includes/header.php'; ?>
    <title>Accepted Quotations</title>
    <link href="<?= BASE_URL ?>css/quotation-list.css" rel="stylesheet" />
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
                        <h5>Accepted Quotations</h5>
                        <p class="text-muted">View accepted and confirmed quotations</p>
                    </div>
                </div>
                    
                    <div class="card quotation-card">
                        <div class="card-body">
                            <div class="quotation-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4" placeholder="Search accepted quotations..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter"><i class="fas fa-search me-1"></i> Search</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/quotations/accepted_quotation_list.php" class="btn btn-outline-secondary btn-clear"><i class="fas fa-times me-1"></i> Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-quotation">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Quotation ID</th>
                                            <th>Ref No</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Expiry</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['quotation_id']); ?></td>
                                                    <td>
                                                        <?php
                                                        $refNo = !empty($row['ref_no'])
                                                            ? htmlspecialchars($row['ref_no'])
                                                            : htmlspecialchars(generateRefNo($conn, $row['quotation_id'], $row['quotation_date'], 'QT'));
                                                        echo $refNo;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($row['customer_business_name'] ?: $row['customer_name']); ?></div>
                                                        <div class="text-muted small"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($row['quotation_date'])); ?></td>
                                                    <td><?php echo $row['expiry_date'] ? date('d/m/Y', strtotime($row['expiry_date'])) : 'N/A'; ?></td>
                                                    <td>
                                                        <div class="amount-text"><?php echo number_format($row['total_amount'], 2); ?> <span class="currency-symbol">(<?php echo $row['currency'] == 'usd' ? '$' : 'Rs'; ?>)</span></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge-soft badge-soft-accepted">
                                                            Accepted
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['creator_name']); ?></td>
<td>
                            <div class="action-btn-group d-flex gap-1">
                                <a href="#" class="btn btn-view view-quotation" title="View Quotation"
                                    data-id="<?php echo $row['quotation_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>modules/quotations/download_quotation.php?id=<?php echo $row['quotation_id']; ?>" class="btn btn-download" title="Download Quotation" target="_blank">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="9" class="text-center empty-state"><i class="fas fa-file-alt"></i><p>No Accepted quotations found</p></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                                <div class="entries-info">
                                    Showing <strong><?php echo ($offset + 1); ?></strong> to <strong><?php echo min($offset + $limit, $totalRows); ?></strong> of <strong><?php echo $totalRows; ?></strong> entries
                                </div>
                                <nav>
                                    <ul class="pagination mb-0">
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                                        </li>
                                    </ul>
                                </nav>
</div>
                         </div>
                     </div>
             </main>
         </div>
     </div>
     
     <!-- Modal for Viewing Quotation -->
     <div class="modal fade" id="viewQuotationModal" tabindex="-1" aria-labelledby="viewQuotationModalLabel"
         aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered modal-lg modal-system">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="viewQuotationModalLabel"><i class="fas fa-file-alt"></i>Quotation Details</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body" id="quotationDetails">
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
             $('.view-quotation').click(function (e) {
                 e.preventDefault();
                 var quotationId = $(this).data('id');
                 $('#quotationDetails').html('Loading...');
                 $.ajax({
                     url: 'download_quotation.php',
                     type: 'GET',
                     data: { 
                         id: quotationId,
                         format: 'html'
                     },
                     success: function (response) {
                         $('#quotationDetails').html(response);
                        $('#quotationDetails').find('button:contains("Print Quotation")').remove();
                         $('#quotationDetails').find('button:contains("Download PDF")').remove();
                         $('#viewQuotationModal').modal('show');
                     },
                     error: function () {
                         $('#quotationDetails').html('Failed to load quotation details.');
                     }
                 });
             });
         });
     </script>
 </body>
 </html>
 <?php $conn->close(); ?>
