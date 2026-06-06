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

$status_filter = "Draft";

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
    <title>Draft Quotations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>css/forms.css" rel="stylesheet" />
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4ff 100%);
            border-bottom: 1px solid #eaecf0;
            padding: 20px 32px;
            margin: 0 0 24px 0;
        }
        .page-header h5 { font-size: 22px; font-weight: 700; color: #101828; letter-spacing: -0.02em; margin-bottom: 2px; }
        .page-header .text-muted { font-size: 14px; color: #667085; margin: 0; }

        .card-draft {
            border-radius: 12px;
            border: 1px solid #eaecf0;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            background: #fff;
            overflow: hidden;
        }
        .card-draft .card-body { padding: 20px 24px; }

        .filter-bar {
            background: #f9fafb;
            border: 1px solid #eaecf0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .filter-bar .form-control {
            border-radius: 8px;
            border-color: #d0d5dd;
            font-size: 14px;
            color: #101828;
            padding: 8px 12px 8px 36px;
            height: 40px;
        }
        .filter-bar .form-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }
        .filter-bar .form-control:hover { border-color: #98a2b3; }
        .btn-filter {
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
            background: #3B82F6;
            border-color: #3B82F6;
            color: #fff;
        }
        .btn-filter:hover {
            background: #2563EB;
            border-color: #2563EB;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
            color: #fff;
        }
        .btn-clear {
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 500;
            color: #344054;
            border-color: #d0d5dd;
            background: #fff;
        }
        .btn-clear:hover {
            background: #f9fafb;
            border-color: #98a2b3;
            color: #101828;
        }

        .table-draft {
            border-collapse: collapse;
            width: 100%;
        }
        .table-draft thead th {
            background: #303361;
            color: #FFFFFF;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 10px 16px;
            white-space: nowrap;
        }
        .table-draft tbody td {
            padding: 12px 16px;
            font-size: 14px;
            color: #344054;
            border-bottom: 1px solid #f2f4f7;
            vertical-align: middle;
        }
        .table-draft tbody tr:last-child td { border-bottom: none; }
        .table-draft tbody tr:hover { background: #f9fafb; }

        .amount-text {
            font-weight: 700;
            color: #101828;
            font-size: 14px;
        }
        .currency-symbol {
            color: #667085;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-soft {
            display: inline-block;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            letter-spacing: 0.01em;
        }
        .badge-soft-draft {
            background: #f2f4f7;
            color: #344054;
            border: 1px solid #eaecf0;
        }

        .action-btn-group .btn {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.15s ease;
            border: 1px solid #d0d5dd;
            background: #fff;
        }
        .action-btn-group .btn-view { color: #3B82F6; }
        .action-btn-group .btn-view:hover {
            background: #f5f5ff;
            border-color: #3B82F6;
            color: #2563EB;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
        }
        .action-btn-group .btn-edit { color: #f59e0b; }
        .action-btn-group .btn-edit:hover {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
        }
        .action-btn-group .btn-download { color: #079455; }
        .action-btn-group .btn-download:hover {
            background: #f6fef9;
            border-color: #079455;
            color: #067647;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(7, 148, 85, 0.2);
        }
        .action-btn-group .btn-process { color: #3B82F6; }
        .action-btn-group .btn-process:hover {
            background: #f5f5ff;
            border-color: #3B82F6;
            color: #2563EB;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
        }
        .action-btn-group .btn-cancel { color: #f04438; }
        .action-btn-group .btn-cancel:hover {
            background: #fffbfa;
            border-color: #f04438;
            color: #d92d20;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(240, 68, 56, 0.2);
        }

        .pagination { gap: 4px; }
        .pagination .page-link {
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            padding: 8px 14px;
            color: #344054;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease;
            background: #fff;
        }
        .pagination .page-link:hover {
            background: #f9fafb;
            border-color: #98a2b3;
            color: #101828;
        }
        .pagination .page-item.active .page-link {
            background: #3B82F6;
            border-color: #3B82F6;
            color: #fff;
        }
        .pagination .page-item.active .page-link:hover {
            background: #2563EB;
            border-color: #2563EB;
        }
        .pagination .page-item.disabled .page-link {
            background: #f9fafb;
            color: #d0d5dd;
            border-color: #eaecf0;
        }

        .entries-info {
            font-size: 14px;
            color: #667085;
            font-weight: 500;
        }

        .empty-state { padding: 48px 24px; text-align: center; }
        .empty-state i { font-size: 3rem; color: #d0d5dd; margin-bottom: 12px; display: block; }
        .empty-state p { color: #98a2b3; font-size: 14px; font-weight: 500; margin: 0; }

        .modal-content {
            border-radius: 12px;
            border: 1px solid #eaecf0;
        }
        .modal-header {
            border-bottom: 1px solid #eaecf0;
            padding: 16px 20px;
        }

        @media (max-width: 768px) {
            .page-header { padding: 16px 20px; }
            .card-draft .card-body { padding: 16px; }
            .filter-bar { padding: 12px 16px; }
            .table-draft thead th,
            .table-draft tbody td { padding: 10px 12px; font-size: 13px; }
            .action-btn-group .btn { width: 30px; height: 30px; font-size: 12px; }
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
                        <h5>Draft Quotations</h5>
                        <p class="text-muted">Manage and review draft quotations</p>
                    </div>
                </div>
                    
                    <div class="card-draft">
                        <div class="card-body">
                            <div class="filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #98a2b3; font-size: 14px;"></i>
                                        <input type="text" name="search" class="form-control ps-4" placeholder="Search draft quotations..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-filter"><i class="fas fa-search me-1"></i> Search</button>
                                    <?php if (!empty($search)): ?>
                                        <a href="<?= BASE_URL ?>modules/quotations/draft_quotation_list.php" class="btn btn-clear"><i class="fas fa-times me-1"></i> Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-draft">
                                    <thead>
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
                                                        <span class="badge-soft badge-soft-draft">
                                                            Draft
                                                        </span>
                                                    </td>
<td><?php echo htmlspecialchars($row['creator_name']); ?></td>
                            <td>
                                <div class="action-btn-group d-flex gap-1">
                                    <a href="#" class="btn btn-view view-quotation" title="View Quotation"
                                        data-id="<?php echo $row['quotation_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/quotations/quotation_edit.php?id=<?php echo $row['quotation_id']; ?>" class="btn btn-edit" title="Edit Quotation">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/quotations/download_quotation.php?id=<?php echo $row['quotation_id']; ?>" class="btn btn-download" title="Download Quotation" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="convertAndOpen('convert_to_invoice.php?id=<?php echo $row['quotation_id']; ?>')" class="btn btn-process" title="Convert to Invoice">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/quotations/cancel_quotation.php?id=<?php echo $row['quotation_id']; ?>" class="btn btn-cancel cancel-quotation" title="Cancel Quotation">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </div>
                            </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="9" class="text-center empty-state"><i class="fas fa-file-alt"></i><p>No draft quotations found</p></td></tr>
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

        function convertAndOpen(url) {
            Swal.fire({
                title: 'Convert to Invoice?',
                text: 'Are you sure you want to convert this quotation to an invoice?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, convert it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(url, '_blank');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            });
        }

        $(document).on('click', '.cancel-quotation', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            Swal.fire({
                title: 'Cancel Quotation?',
                text: 'Are you sure you want to cancel this quotation?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    </script>
</body>
</html>
 <?php $conn->close(); ?>
