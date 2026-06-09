<!-- Sidebar Styles -->
<style>
/* ═══════════════════════════════════════════════════════════════
   SIDEBAR — Full-height Collapsible Navigation Panel
   ═══════════════════════════════════════════════════════════════ */

/* ── Layout container ─────────────────────────────────────── */
#layoutSidenav #layoutSidenav_nav {
    width: 260px;
    flex-shrink: 0;
    position: fixed;
    top: 0;
    left: 0;
    right: auto;
    height: 100vh;
    z-index: 1050;
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Sidenav core ─────────────────────────────────────────── */
#sidenavAccordion {
    background: #3F4D67;
    height: 100%;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ── Custom scrollbar ─────────────────────────────────────── */
#sidenavAccordion::-webkit-scrollbar { width: 3px; }
#sidenavAccordion::-webkit-scrollbar-track { background: transparent; }
#sidenavAccordion::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
}
#sidenavAccordion::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.15);
}

/* ═══════════════════════════════════════════════════════════════
   BRANDED LOGO SECTION
   ═══════════════════════════════════════════════════════════════ */

.sidebar-brand {
    height: 72px;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-brand-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.sidebar-brand-logo img {
    height: 68px;
    width: auto;
    display: block;
    object-fit: contain;
    filter: brightness(1.1);
}

.sidebar-brand-text {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    line-height: 1.3;
    max-height: 68px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.sidebar-brand-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(99, 102, 241, 0.12);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(99, 102, 241, 0.18);
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: rgba(165, 180, 252, 0.9);
    transition: all 0.3s ease;
}

.sidebar-brand-badge:hover {
    background: rgba(99, 102, 241, 0.18);
    border-color: rgba(99, 102, 241, 0.3);
    color: #a5b4fc;
}

/* ═══════════════════════════════════════════════════════════════
   SCROLLABLE NAVIGATION AREA
   ═══════════════════════════════════════════════════════════════ */

.sb-sidenav-menu {
    flex: 1;
    padding: 12px 0 16px;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.06) transparent;
}

.sb-sidenav-menu::-webkit-scrollbar { width: 3px; }
.sb-sidenav-menu::-webkit-scrollbar-track { background: transparent; }
.sb-sidenav-menu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
}

.sb-sidenav-menu > .nav {
    flex-direction: column;
}

/* ── Section headings (small uppercase captions) ──────────── */
.sb-sidenav-menu-heading {
    color: rgba(148, 163, 184, 0.45);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    padding: 20px 20px 8px;
    line-height: 1;
}

/* ═══════════════════════════════════════════════════════════════
   NAV LINKS — Icons, Labels, Badges, Indicators
   ═══════════════════════════════════════════════════════════════ */

.sb-sidenav-menu .nav-link {
    color: rgba(255, 255, 255, 0.55);
    padding: 9px 14px;
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.15px;
    border-radius: 10px;
    margin: 1px 10px;
    text-decoration: none;
    position: relative;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

/* Animated hover overlay */
.sb-sidenav-menu .nav-link::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    opacity: 0;
    transition: opacity 0.25s ease;
}

.sb-sidenav-menu .nav-link:hover::after {
    opacity: 1;
}

/* ── Icon ─────────────────────────────────────────────────── */
.sb-nav-link-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    min-width: 22px;
    font-size: 14px;
}

.sb-sidenav-menu .nav-link .sb-nav-link-icon {
    color: rgba(148, 163, 184, 0.45);
    margin-right: 12px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Collapse arrow ───────────────────────────────────────── */
.sb-sidenav-collapse-arrow {
    margin-left: auto;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.sb-sidenav-collapse-arrow i {
    color: rgba(148, 163, 184, 0.3);
    font-size: 11px;
    transition: color 0.2s ease;
}

.nav-link.collapsed .sb-sidenav-collapse-arrow {
    transform: rotate(0deg);
}

.nav-link:not(.collapsed) .sb-sidenav-collapse-arrow {
    transform: rotate(180deg);
}

/* ── Hover state ──────────────────────────────────────────── */
.sb-sidenav-menu .nav-link:hover {
    color: rgba(255, 255, 255, 0.9);
}

.sb-sidenav-menu .nav-link:hover .sb-nav-link-icon {
    color: rgba(165, 180, 252, 0.8);
    transform: scale(1.08);
}

.sb-sidenav-menu .nav-link:hover .sb-sidenav-collapse-arrow i {
    color: rgba(148, 163, 184, 0.6);
}

/* ═══════════════════════════════════════════════════════════════
   ACTIVE STATE — Distinct highlights and accent borders
   ═══════════════════════════════════════════════════════════════ */

.sb-sidenav-menu .nav-link.active {
    background: rgba(99, 102, 241, 0.14);
    color: #c7d2fe;
}

.sb-sidenav-menu .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 7px;
    bottom: 7px;
    width: 3px;
    background: linear-gradient(to bottom, #818cf8, #6366f1);
    border-radius: 0 3px 3px 0;
    z-index: 1;
}

.sb-sidenav-menu .nav-link.active::after {
    opacity: 0;
}

.sb-sidenav-menu .nav-link.active .sb-nav-link-icon {
    color: #818cf8;
}

/* ── Parent active (expanded dropdown) ───────────────────── */
.sb-sidenav-menu .nav-link.parent-active {
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.85);
}

.sb-sidenav-menu .nav-link.parent-active .sb-nav-link-icon {
    color: rgba(165, 180, 252, 0.7);
}

/* ═══════════════════════════════════════════════════════════════
   SUBMENUS — Progressive indentation with dot indicators
   ═══════════════════════════════════════════════════════════════ */

.sb-sidenav-menu-nested.nav {
    flex-direction: column;
    padding: 4px 0 6px 0;
    margin: 2px 10px 2px 10px;
    background: transparent;
    border-left: 2px solid rgba(99, 102, 241, 0.2);
    border-radius: 0;
    position: relative;
}

.sb-sidenav-menu-nested.nav .nav-link {
    padding: 7px 14px 7px 22px;
    font-size: 14px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.45);
    border-radius: 8px;
    margin: 1px 4px;
    position: relative;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Animated dot indicator */
.sb-sidenav-menu-nested .nav-link::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%) scale(0);
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: rgba(165, 180, 252, 0.6);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.sb-sidenav-menu-nested .nav-link:hover::before {
    transform: translateY(-50%) scale(1);
}

.sb-sidenav-menu-nested .nav-link::after {
    display: none;
}

.sb-sidenav-menu-nested .nav-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.88);
    padding-left: 26px;
}

/* Submenu active state */
.sb-sidenav-menu-nested .nav-link.active {
    background: rgba(99, 102, 241, 0.14);
    color: #c7d2fe;
    padding-left: 26px;
}

.sb-sidenav-menu-nested .nav-link.active::before {
    transform: translateY(-50%) scale(1);
    background: #818cf8;
    left: 8px;
    top: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
}

/* ── Deeper nested levels — progressive indentation ──────── */
.sb-sidenav-menu-nested .sb-sidenav-menu-nested {
    margin-left: 12px;
    border-left-color: rgba(99, 102, 241, 0.12);
}

.sb-sidenav-menu-nested .sb-sidenav-menu-nested .nav-link {
    padding-left: 30px;
    font-size: 12.5px;
}

.sb-sidenav-menu-nested .sb-sidenav-menu-nested .nav-link:hover {
    padding-left: 34px;
}

/* ── Badge support ────────────────────────────────────────── */
.nav-link .nav-badge {
    margin-left: auto;
    padding: 2px 7px;
    font-size: 10px;
    font-weight: 600;
    border-radius: 6px;
    background: rgba(99, 102, 241, 0.2);
    color: #a5b4fc;
    line-height: 1.4;
    position: relative;
    z-index: 1;
}

.nav-link .nav-badge-warning {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.nav-link .nav-badge-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* ═══════════════════════════════════════════════════════════════
   COLLAPSE ANIMATION
   ═══════════════════════════════════════════════════════════════ */

.collapse {
    transition: none;
}

.collapsing {
    transition: height 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ═══════════════════════════════════════════════════════════════
   SIDEBAR FOOTER
   ═══════════════════════════════════════════════════════════════ */

.sb-sidenav-footer {
    padding: 14px 16px;
    font-size: 11px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.04);
    color: rgba(148, 163, 184, 0.4);
    line-height: 1.5;
    flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════════════════
   MOBILE — Off-canvas drawer with blurred overlay
   ═══════════════════════════════════════════════════════════════ */

@media (max-width: 991.98px) {
    #layoutSidenav_nav {
        transform: translateX(-100%);
        width: 280px;
        box-shadow: none;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.35s ease;
    }

    /* Mobile drawer active */
    body.sb-sidenav-toggled #layoutSidenav_nav {
        transform: translateX(0);
        box-shadow: 
            4px 0 24px rgba(0, 0, 0, 0.3),
            20px 0 60px rgba(0, 0, 0, 0.15);
    }

    /* Fullscreen blurred overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 1045;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.35s ease, visibility 0.35s ease;
        cursor: pointer;
    }

    body.sb-sidenav-toggled .sidebar-overlay {
        opacity: 1;
        visibility: visible;
    }

    /* Lock body scroll when mobile sidebar is open */
    body.sb-sidenav-toggled {
        overflow: hidden;
    }
}

@media (min-width: 992px) {
    /* Desktop: visible by default, collapsible */
    #layoutSidenav_nav {
        transform: translateX(0);
    }

    /* Desktop collapsed state */
    body.sb-sidenav-toggled #layoutSidenav_nav {
        transform: translateX(-100%);
    }

    .sidebar-overlay {
        display: none !important;
    }
}
</style>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar HTML -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">

        <!-- Branded Logo Section -->
        <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>index.php" class="sidebar-brand-logo">
                <?php
                $_sb_logo = $_company_logo ?? '';
                $_sb_company_name = '';
                if (empty($_sb_logo) && isset($conn) && $conn instanceof mysqli) {
                    $_sb_info = getCompanyInfo($conn);
                    if (!empty($_sb_info['logo_path'])) {
                        $_sb_logo = BASE_URL . $_sb_info['logo_path'];
                    }
                    $_sb_company_name = $_sb_info['company_name'] ?? '';
                } else {
                    $_sb_company_name = $_company_name ?? '';
                }
                ?>
                <?php if (!empty($_sb_logo)): ?>
                    <img src="<?= htmlspecialchars($_sb_logo) ?>" alt="Logo">
                <?php elseif (!empty($_sb_company_name)): ?>
                    <span class="sidebar-brand-text"><?= htmlspecialchars($_sb_company_name) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="sb-sidenav-menu">
            <div class="nav">
                <?php
require_once __DIR__ . '/../config/paths.php';

                $userRoleId = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
                $isAdmin = ($userRoleId === 1);
                $isModerator = ($userRoleId === 3);
                $canApproveReject = $isAdmin || $isModerator;
                $canManageUsers = $isAdmin;
                $canEditRecords = $isAdmin || $isModerator;
                ?>

                <div class="sb-sidenav-menu-heading">Main</div>
                <a class="nav-link" href="<?= BASE_URL ?>index.php" id="dashboard-link">
                    <div class="sb-nav-link-icon"><i class="fas fa-grip"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Business</div>

                <!-- Invoices -->
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseInvoices"
                    aria-expanded="false" aria-controls="collapseInvoices" id="invoices-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>
                    Invoices
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseInvoices" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/invoices/invoice_create.php" id="create-invoice-link">Create Invoice</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/invoices/invoice_list.php" id="all-invoices-link">All Invoices</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/invoices/pending_invoice_list.php" id="pending-invoices-link">Pending Invoices</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/invoices/complete_invoice_list.php" id="complete-invoices-link">Complete Invoices</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/invoices/cancel_invoice_list.php" id="cancel-invoices-link">Cancel Invoices</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Quotations -->
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseQuotations"
                    aria-expanded="false" aria-controls="collapseQuotations" id="quotations-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                    Quotations
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseQuotations" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/quotations/quotation_create.php" id="create-quotation-link">Create Quotation</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/quotations/quotation_list.php" id="all-quotations-link">All Quotations</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/quotations/draft_quotation_list.php" id="draft-quotations-link">Draft Quotations</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/quotations/accepted_quotation_list.php" id="accepted-quotations-link">Accepted Quotations</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/quotations/cancelled_quotation_list.php" id="cancelled-quotations-link">Cancelled Quotations</a>
                    </nav>
                </div>

                <!-- Price Lists -->
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapsePriceLists"
                    aria-expanded="false" aria-controls="collapsePriceLists" id="price-lists-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                    Price Lists
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePriceLists" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/price-lists/price_list_create.php" id="create-price-list-link">Create Price List</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/price-lists/price_list.php" id="all-price-lists-link">All Price Lists</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/price-lists/manage_assets.php" id="manage-assets-link">Manage Assets</a>
                    </nav>
                </div>

                <!-- Customers -->
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCustomers"
                    aria-expanded="false" aria-controls="collapseCustomers" id="customers-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-group"></i></div>
                    Customers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCustomers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/customers/customer_list.php" id="all-customers-link">All Customers</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/customers/add_customer.php" id="add-customer-link">Add New Customer</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Users (Admin Only) -->
                <?php if ($canManageUsers): ?>
                <div class="sb-sidenav-menu-heading">Administration</div>
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="false" aria-controls="collapseUsers" id="users-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-cog"></i></div>
                    Users
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseUsers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/users/users.php" id="all-users-link">All Users</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/users/add_user.php" id="add-user-link">Add New User</a>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/users/user_logs.php" id="user-logs-link">Activity Logs</a>
                    </nav>
                </div>

                <!-- Settings (Admin Only) -->
                <a class="nav-link" href="<?= BASE_URL ?>modules/settings/company_settings.php" id="company-settings-link">
                    <div class="sb-nav-link-icon"><i class="fas fa-cog"></i></div>
                    Settings
                </a>
                <?php endif; ?>

                <!-- Products -->
                <div class="sb-sidenav-menu-heading">Catalog</div>
                <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseProducts"
                    aria-expanded="false" aria-controls="collapseProducts" id="products-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                    Products
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProducts" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?= BASE_URL ?>modules/products/product_list.php" id="all-products-link">All Products</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="<?= BASE_URL ?>modules/products/add_product.php" id="add-product-link">Add New Product</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Active State Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop().split('?')[0];
    document.querySelectorAll('.sb-sidenav-menu .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#' && href !== '#!' && href !== 'javascript:void(0);') {
            if (currentPage === href.split('/').pop()) {
                link.classList.add('active');
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    new bootstrap.Collapse(parentCollapse, { toggle: false }).show();
                    const toggle = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
                    if (toggle) {
                        toggle.classList.add('parent-active');
                        toggle.classList.remove('collapsed');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        }
    });

    // Overlay click to close sidebar on mobile
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            document.body.classList.remove('sb-sidenav-toggled');
        });
    }
});
</script>