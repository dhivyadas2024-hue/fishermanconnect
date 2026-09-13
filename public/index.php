<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
$user = current_user();

// 1. Fetch live fish rates with dalal details and species info
$stmt_rates = $pdo->query("
    SELECT r.*, s.name as species_name, s.category, s.image_icon, u.full_name as dalal_name, u.phone as dalal_phone
    FROM fish_rates r
    JOIN fish_species s ON r.species_id = s.id
    JOIN users u ON r.dalal_user_id = u.id
    ORDER BY r.updated_at DESC
");
$rates = $stmt_rates->fetchAll();

// 2. Fetch expected incoming landings (pre-landing catch declarations)
$stmt_landings = $pdo->query("
    SELECT c.*, s.name as species_name, s.image_icon, v.name as vessel_name, v.vessel_code, u.full_name as captain_name
    FROM catch_declarations c
    JOIN fish_species s ON c.species_id = s.id
    JOIN vessels v ON c.vessel_id = v.id
    JOIN users u ON c.captain_user_id = u.id
    WHERE c.status IN ('Pending', 'Accepted')
    ORDER BY c.estimated_eta ASC
");
$landings = $stmt_landings->fetchAll();

// 3. Fetch Harbour Statistics
$total_vessels = $pdo->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
$active_at_sea = $pdo->query("SELECT COUNT(*) FROM vessels WHERE status IN ('Fishing', 'Cruising', 'Anchored')")->fetchColumn();
$docked_vessels = $pdo->query("SELECT COUNT(*) FROM vessels WHERE status = 'Docked'")->fetchColumn();
$total_incoming_qty = $pdo->query("SELECT SUM(estimated_qty_kg) FROM catch_declarations WHERE status IN ('Pending', 'Accepted')")->fetchColumn() ?: 0;

// 4. Announcements & Active SOS Alerts
$stmt_ann = $pdo->query("
    SELECT a.*, u.full_name as author
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC LIMIT 5
");
$announcements = $stmt_ann->fetchAll();

$active_sos_list = get_active_sos_alerts();
$sos_count = count($active_sos_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Fisherman - Harbour Operations & Maritime Safety Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --navy-dark: #002B49;
            --navy-light: #003D63;
            --seafoam-cyan: #007791;
            --seafoam-light: #00A3BD;
            --warning-red: #D9534F;
            --success-green: #5CB85C;
            --white-clean: #FFFFFF;
            --gray-light: #F5F7FA;
            --gray-dark: #1A1A1A;
            --shadow-sm: 0 2px 8px rgba(0, 43, 73, 0.1);
            --shadow-md: 0 8px 24px rgba(0, 43, 73, 0.15);
            --shadow-lg: 0 16px 48px rgba(0, 43, 73, 0.2);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--white-clean);
            color: var(--gray-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ==================== TYPOGRAPHY ==================== */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--navy-dark);
        }

        h1 { font-size: 3.5rem; letter-spacing: -1px; }
        h2 { font-size: 2.5rem; margin-bottom: 2rem; }
        h3 { font-size: 1.75rem; }
        h4 { font-size: 1.35rem; }

        p { color: #555; font-size: 1rem; }

        /* ==================== BUTTONS ==================== */
        .btn-primary-fisherman {
            background: linear-gradient(135deg, var(--seafoam-cyan) 0%, var(--seafoam-light) 100%);
            color: var(--white-clean);
            border: none;
            padding: 0.85rem 2.2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 119, 145, 0.2);
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-fisherman:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 119, 145, 0.35);
            color: var(--white-clean);
        }

        .btn-secondary-fisherman {
            background: var(--navy-dark);
            color: var(--white-clean);
            border: 2px solid var(--navy-dark);
            padding: 0.85rem 2.2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary-fisherman:hover {
            background: transparent;
            color: var(--navy-dark);
        }

        /* ==================== STICKY HEADER ==================== */
        .navbar-fisherman {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 43, 73, 0.1);
            padding: 0;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-fisherman.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-md);
        }

        .navbar-brand-fisherman {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--navy-dark);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            white-space: nowrap;
            text-decoration: none;
        }

        .navbar-brand-fisherman i {
            color: var(--seafoam-cyan);
            font-size: 1.8rem;
        }

        .navbar-menu-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .navbar-nav {
            list-style: none;
            display: flex;
            gap: 0;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-link-fisherman {
            color: var(--navy-dark) !important;
            font-weight: 500;
            margin: 0 0.8rem;
            position: relative;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            text-decoration: none;
            display: block;
        }

        .nav-link-fisherman:hover {
            color: var(--seafoam-cyan) !important;
        }

        /* Mobile Hamburger */
        .navbar-toggler-custom {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            gap: 5px;
        }

        .navbar-toggler-icon-custom {
            width: 25px;
            height: 3px;
            background: var(--navy-dark);
            border-radius: 2px;
        }

        @media (max-width: 767px) {
            .navbar-toggler-custom { display: flex; }
            .navbar-menu-wrapper {
                display: none;
                position: fixed;
                top: 60px;
                left: 0; right: 0;
                background: white;
                box-shadow: var(--shadow-md);
                flex-direction: column;
                padding: 1rem 0;
            }
            .navbar-menu-wrapper.show { display: flex; }
            .navbar-nav { flex-direction: column; width: 100%; }
            .nav-link-fisherman { padding: 0.8rem 1.5rem; margin: 0; }
        }

        /* ==================== EMERGENCY MAYDAY BANNER ==================== */
        .mayday-banner {
            background: linear-gradient(90deg, var(--warning-red) 0%, #c9423f 100%);
            color: var(--white-clean);
            padding: 0.9rem 1rem;
            text-align: center;
            font-weight: 600;
            z-index: 999;
            position: relative;
            box-shadow: 0 4px 16px rgba(217, 83, 79, 0.3);
        }

        .mayday-pulse {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: var(--white-clean);
            border-radius: 50%;
            margin-right: 0.8rem;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* ==================== HERO SECTION ==================== */
        .hero-section {
            position: relative;
            height: 85vh;
            min-height: 550px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white-clean);
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0, 43, 73, 0.75) 0%, rgba(0, 119, 145, 0.65) 100%),
                        url('https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1600&h=900&fit=crop') center/cover;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hero-section h1 {
            color: var(--white-clean);
            font-size: 3.8rem;
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        .hero-section p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
        }

        .hero-cta-group {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ==================== STATS COUNTER ==================== */
        .stats-section {
            background: var(--gray-light);
            padding: 4rem 2rem;
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            background: var(--white-clean);
            padding: 2rem;
            border-radius: 0.8rem;
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 4px solid var(--seafoam-cyan);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--seafoam-cyan);
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--navy-dark);
            margin-bottom: 0.5rem;
        }

        /* ==================== MARKET & STREAM ==================== */
        .market-rates-section, .catch-stream-section, .features-section, .ecosystem-section, .contact-section {
            padding: 4rem 2rem;
        }

        .rates-table-container {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--gray-light);
            border-radius: 0.8rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        .rates-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rates-table th {
            background: var(--navy-dark);
            color: var(--white-clean);
            padding: 1rem;
            text-align: left;
        }

        .rates-table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            background: var(--white-clean);
        }

        .price-badge {
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 0.4rem;
        }
        .price-up { background: rgba(92, 184, 92, 0.15); color: var(--success-green); }
        .price-down { background: rgba(217, 83, 79, 0.15); color: var(--warning-red); }
        .price-stable { background: rgba(0, 119, 145, 0.15); color: var(--seafoam-cyan); }

        .catch-card {
            background: var(--navy-light);
            color: white;
            padding: 1.5rem;
            border-radius: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .ecosystem-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
        }

        .tier-card {
            background: var(--white-clean);
            border-radius: 0.8rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border-top: 4px solid var(--seafoam-cyan);
            padding: 1.8rem;
            text-align: center;
        }

        footer {
            background: var(--navy-dark);
            color: var(--white-clean);
            padding: 3rem 2rem 1rem;
        }
    </style>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar-fisherman">
        <div class="navbar-container">
            <a class="navbar-brand-fisherman" href="/public/index.php">
                <i class="fas fa-anchor"></i>
                <span>Project Fisherman</span>
            </a>
            
            <button class="navbar-toggler-custom" id="navbarToggler">
                <span class="navbar-toggler-icon-custom"></span>
                <span class="navbar-toggler-icon-custom"></span>
                <span class="navbar-toggler-icon-custom"></span>
            </button>

            <div class="navbar-menu-wrapper" id="navbarNav">
                <ul class="navbar-nav">
                    <li><a class="nav-link-fisherman" href="/public/index.php">Home</a></li>
                    <li><a class="nav-link-fisherman" href="#live-rates">Live Rates</a></li>
                    <li><a class="nav-link-fisherman" href="/telemetry/map.php">Fleet Radar</a></li>
                    <li><a class="nav-link-fisherman" href="/chat/index.php">Vessel Chat</a></li>
                    <li><a class="nav-link-fisherman text-danger font-weight-bold" href="/sos/index.php">SOS Protocol</a></li>
                    <li><a class="nav-link-fisherman" href="/entertainment/index.php">Games</a></li>
                    <li><a class="nav-link-fisherman" href="/public/packages.php">Packages</a></li>
                    <?php if ($user): ?>
                        <li class="ms-md-2"><a class="btn btn-sm btn-outline-dark fw-bold" href="/logout.php">Logout (<?= sanitize($user['full_name']) ?>)</a></li>
                    <?php else: ?>
                        <li class="ms-md-2"><a class="btn btn-sm btn-outline-primary fw-bold" href="/login.php">Login</a></li>
                        <li class="ms-md-1"><a class="btn-primary-fisherman py-1 px-3 text-white" href="/register.php">Register Free</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ==================== MAYDAY BANNER ==================== -->
    <?php if ($sos_count > 0): ?>
    <div class="mayday-banner">
        <span class="mayday-pulse"></span>
        <span>🚨 MAYDAY EMERGENCY ALERT: <?= $sos_count ?> Active Vessel SOS Distress Signal(s) Recorded in Harbour Radius!</span>
        <a href="/sos/index.php" class="btn btn-sm btn-light text-danger fw-bold ms-3">View SOS Radar & Rescue &raquo;</a>
    </div>
    <?php endif; ?>

    <!-- ==================== HERO SECTION ==================== -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <h1>Digitizing Harbour Operations & Coastal Supply Chains</h1>
            <p>Real-time vessel tracking, live wholesale fish rates, catch forecasting, and maritime SOS safety in one unified ecosystem.</p>
            <div class="hero-cta-group">
                <?php if (!is_logged_in()): ?>
                    <a href="/register.php" class="btn-primary-fisherman"><i class="fas fa-user-plus me-2"></i>Register Free Account</a>
                <?php endif; ?>
                <a href="/public/packages.php" class="btn-secondary-fisherman"><i class="fas fa-box me-2"></i>Explore Service Packages</a>
                <a href="/telemetry/map.php" class="btn btn-outline-light btn-lg text-white font-weight-bold"><i class="fas fa-radar me-2"></i>Live GPS Radar Map</a>
            </div>
        </div>
    </section>

    <!-- ==================== STATS COUNTER ==================== -->
    <section class="stats-section">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ship"></i></div>
                <div class="stat-value" id="vesselCount"><?= $active_at_sea ?></div>
                <div class="stat-label">Offshore Vessels Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-fish"></i></div>
                <div class="stat-value" id="catchCount"><?= number_format($total_incoming_qty) ?></div>
                <div class="stat-label">Incoming Fish Haul (kg)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-anchor"></i></div>
                <div class="stat-value" id="dockedCount"><?= $docked_vessels ?></div>
                <div class="stat-label">Docked Fleet (Active Auctions)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-bell"></i></div>
                <div class="stat-value" id="alertCount"><?= $sos_count ?></div>
                <div class="stat-label">Active Emergency Distress Signals</div>
            </div>
        </div>
    </section>

    <!-- ==================== LIVE MARKET RATES SECTION ==================== -->
    <section class="market-rates-section" id="live-rates">
        <div class="text-center mb-4">
            <h2>Live Wholesale Rate Engine</h2>
            <p>Real-time fish market rates set live by harbour Dalals during dock auctions</p>
        </div>
        <div class="rates-table-container">
            <table class="rates-table">
                <thead>
                    <tr>
                        <th>Fish Species</th>
                        <th>Category</th>
                        <th>Current Rate (₹/kg)</th>
                        <th>Market Trend</th>
                        <th>Commission Agent</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $r): ?>
                    <tr>
                        <td><span class="fs-5 me-1"><?= $r['image_icon'] ?></span> <strong><?= sanitize($r['species_name']) ?></strong></td>
                        <td><?= sanitize($r['category']) ?></td>
                        <td><strong class="text-success fs-5">₹<?= number_format($r['price_per_kg'], 2) ?></strong></td>
                        <td>
                            <?php if ($r['market_trend'] === 'Up'): ?>
                                <span class="price-badge price-up">▲ Up</span>
                            <?php elseif ($r['market_trend'] === 'Down'): ?>
                                <span class="price-badge price-down">▼ Down</span>
                            <?php else: ?>
                                <span class="price-badge price-stable">➔ Stable</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= sanitize($r['dalal_name']) ?></strong> (<?= sanitize($r['dalal_phone']) ?>)</td>
                        <td><small class="text-muted"><?= time_ago($r['updated_at']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Sponsor Advertisement Banner -->
    <div class="container">
        <?= render_ad_banner('frontpage') ?>
    </div>

    <!-- ==================== PRE-LANDING CATCH STREAM ==================== -->
    <section class="catch-stream-section bg-dark text-white" id="fleet-radar">
        <h2 class="text-center text-white mb-2">Pre-Landing Catch Stream</h2>
        <p class="text-center opacity-75 mb-4">Live incoming catch declarations transmitted by captains at sea before docking</p>

        <div class="container">
            <div class="row g-3">
                <?php foreach ($landings as $l): ?>
                <div class="col-md-4">
                    <div class="catch-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold mb-0 text-info"><i class="fas fa-ship me-1"></i> <?= sanitize($l['vessel_name']) ?></h5>
                            <span class="badge bg-warning text-dark font-monospace"><?= sanitize($l['vessel_code']) ?></span>
                        </div>
                        <p class="mb-1"><strong>Haul:</strong> <?= $l['image_icon'] ?> <?= sanitize($l['species_name']) ?></p>
                        <p class="mb-1"><strong>Quantity:</strong> <span class="text-warning fw-bold"><?= number_format($l['estimated_qty_kg']) ?> kg</span></p>
                        <p class="mb-2"><small>Capt. <?= sanitize($l['captain_name']) ?></small></p>
                        <div class="badge bg-danger p-2"><i class="fas fa-clock me-1"></i> ETA: <?= date('H:i, M j', strtotime($l['estimated_eta'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ==================== ECOSYSTEM PLANS SECTION ==================== -->
    <section class="ecosystem-section" id="plans">
        <div class="text-center mb-4">
            <h2>Ecosystem Service Packages</h2>
            <p>Tailored functionality for every role in harbour operations</p>
        </div>

        <div class="ecosystem-grid">
            <div class="tier-card">
                <div class="fs-1 text-info mb-2"><i class="fas fa-basket-shopping"></i></div>
                <h4>Public Consumer</h4>
                <p class="text-muted small">Access live wholesale dock landing prices and regional harbour statistics.</p>
                <a href="/register.php?role=public" class="btn-primary-fisherman w-100">Register Consumer</a>
            </div>
            <div class="tier-card border-primary">
                <div class="fs-1 text-primary mb-2"><i class="fas fa-compass"></i></div>
                <h4>Boat Captain</h4>
                <p class="text-muted small">Pre-landing catch declarations, GPS radar telemetry, inter-vessel chat & SOS.</p>
                <a href="/register.php?role=captain" class="btn-primary-fisherman w-100">Register Captain</a>
            </div>
            <div class="tier-card border-success">
                <div class="fs-1 text-success mb-2"><i class="fas fa-cash-register"></i></div>
                <h4>Dalal Broker</h4>
                <p class="text-muted small">Live price bidding desk, catch bidding, direct boat messaging & species cataloging.</p>
                <a href="/register.php?role=dalal" class="btn-primary-fisherman w-100">Register Agent</a>
            </div>
            <div class="tier-card border-danger">
                <div class="fs-1 text-danger mb-2"><i class="fas fa-shield-halved"></i></div>
                <h4>Harbour Admin</h4>
                <p class="text-muted small">Vessel registration, interactive Leaflet AIS radar map, and MAYDAY emergency response.</p>
                <a href="/register.php?role=admin" class="btn-primary-fisherman w-100">Register Admin</a>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="/public/packages.php" class="btn btn-outline-dark font-weight-bold px-4">View Detailed Feature Comparison Matrix &raquo;</a>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer>
        <div class="container text-center">
            <h4 class="text-white mb-2"><i class="fas fa-anchor me-2"></i>Project Fisherman</h4>
            <p class="small opacity-75 mb-3">Advanced software ecosystem for harbour-based fishing operations, coastal supply chains, and maritime safety.</p>
            <p class="small text-muted mb-0">&copy; <?= date('Y') ?> Project Fisherman. Home Port: Mumbai Sassoon Dock.</p>
        </div>
    </footer>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Navbar scroll morph effect
            $(window).scroll(function() {
                if ($(window).scrollTop() > 50) {
                    $('.navbar-fisherman').addClass('scrolled');
                } else {
                    $('.navbar-fisherman').removeClass('scrolled');
                }
            });

            // Mobile menu toggle
            $('#navbarToggler').click(function() {
                $('#navbarNav').toggleClass('show');
            });
        });
    </script>
</body>
</html>
