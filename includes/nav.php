<?php
$user = current_user();
$current_page = $_SERVER['REQUEST_URI'];
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-maritime sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/public/index.php">
            <span class="fs-3 me-2">⚓</span>
            <div>
                <span>Project Fisherman</span>
                <span class="d-block text-uppercase text-info" style="font-size: 0.65rem; letter-spacing: 1.5px;">Harbour & Maritime Ecosystem</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/index.php') !== false ? 'active' : '' ?>" href="/public/index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/telemetry/') !== false ? 'active' : '' ?>" href="/telemetry/map.php">
                        <i class="bi bi-radar me-1"></i> Fleet Radar Map
                    </a>
                </li>

                <?php if (has_role(['dalal', 'admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/dalal/') !== false ? 'active' : '' ?>" href="/dalal/rates.php">
                        <i class="bi bi-currency-rupee me-1"></i> Dalal Auction Desk
                    </a>
                </li>
                <?php endif; ?>

                <?php if (has_role(['captain', 'crew', 'admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/captain/') !== false ? 'active' : '' ?>" href="/captain/catch_declaration.php">
                        <i class="bi bi-box-seam me-1"></i> Catch Declarations
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/chat/') !== false ? 'active' : '' ?>" href="/chat/index.php">
                        <i class="bi bi-chat-dots me-1"></i> Vessel Chat & Comm
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/sos/') !== false ? 'active' : '' ?> text-warning fw-bold" href="/sos/index.php">
                        <i class="bi bi-shield-exclamation me-1"></i> SOS Emergency
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/entertainment/') !== false ? 'active' : '' ?>" href="/entertainment/index.php">
                        <i class="bi bi-controller me-1"></i> Solitary Games
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/packages.php') !== false ? 'active' : '' ?>" href="/public/packages.php">
                        <i class="bi bi-box-seam me-1"></i> Packages
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/register_ad.php') !== false ? 'active' : '' ?> text-info fw-bold" href="/public/register_ad.php">
                        <i class="bi bi-badge-ad me-1"></i> Submit Ad
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/business_plan.php') !== false ? 'active' : '' ?>" href="/public/business_plan.php">
                        <i class="bi bi-briefcase me-1"></i> Business Plan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/about.php') !== false ? 'active' : '' ?>" href="/public/about.php">
                        <i class="bi bi-info-circle me-1"></i> About
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/public/contact.php') !== false ? 'active' : '' ?>" href="/public/contact.php">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>

                <?php if (has_role('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($current_page, '/admin/') !== false ? 'active' : '' ?>" href="/admin/fleet.php">
                        <i class="bi bi-gear-fill me-1"></i> Harbour Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <strong><?= sanitize($user['full_name']) ?></strong>
                            <span class="badge bg-info text-dark text-uppercase ms-1"><?= sanitize($user['role']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header">Switch Role / Demo Accounts</h6></li>
                            <li><a class="dropdown-item" href="/login.php?demo=admin"><i class="bi bi-shield-lock me-2"></i>Admin Officer</a></li>
                            <li><a class="dropdown-item" href="/login.php?demo=dalal"><i class="bi bi-briefcase me-2"></i>Dalal (Agent)</a></li>
                            <li><a class="dropdown-item" href="/login.php?demo=captain"><i class="bi bi-compass me-2"></i>Capt. Vikram</a></li>
                            <li><a class="dropdown-item" href="/login.php?demo=crew"><i class="bi bi-person me-2"></i>Anil Crewman</a></li>
                            <li><a class="dropdown-item" href="/login.php?demo=public"><i class="bi bi-cart me-2"></i>Public Buyer</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-sm btn-outline-light">Login</a>
                    <a href="/register.php" class="btn btn-sm btn-info fw-bold text-dark">Register Free</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
