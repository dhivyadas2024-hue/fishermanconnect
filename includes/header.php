<?php
require_once __DIR__ . '/functions.php';
$active_sos_list = get_active_sos_alerts();
$sos_count = count($active_sos_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' | ' : '' ?>Project Fisherman</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Maritime Theme CSS -->
    <style>
        :root {
            --maritime-navy: #002B49;
            --maritime-teal: #007791;
            --maritime-seafoam: #20B2AA;
            --maritime-sand: #F4F6F9;
            --sos-red: #D9534F;
        }
        body {
            background-color: var(--maritime-sand);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-maritime {
            background-color: var(--maritime-navy);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .navbar-maritime .navbar-brand {
            font-weight: 700;
            color: #ffffff;
            font-size: 1.35rem;
            letter-spacing: 0.5px;
        }
        .navbar-maritime .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
        .navbar-maritime .nav-link:hover, .navbar-maritime .nav-link.active {
            color: #ffffff;
            border-bottom: 2px solid var(--maritime-seafoam);
        }
        .card-maritime {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #ffffff;
        }
        .badge-sos {
            background-color: var(--sos-red);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        .footer-maritime {
            margin-top: auto;
            background-color: var(--maritime-navy);
            color: #A0AEC0;
        }
        .stat-card {
            border-left: 4px solid var(--maritime-teal);
        }
        .stat-card-danger {
            border-left: 4px solid var(--sos-red);
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/nav.php'; ?>

<!-- Global SOS Banner Notice if active emergency -->
<?php if ($sos_count > 0): ?>
<div class="bg-danger text-white py-2 px-3 shadow-sm d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <span class="badge bg-white text-danger fw-bold me-2 px-2 py-1"><i class="bi bi-exclamation-triangle-fill"></i> MAYDAY ALERT</span>
        <span><strong><?= $sos_count ?> ACTIVE EMERGENCY DISTRESS SIGNAL(S)!</strong> Vessel in distress: 
        <?php 
            $v_names = array_map(fn($s) => $s['vessel_name'] . ' (' . $s['emergency_type'] . ')', $active_sos_list);
            echo htmlspecialchars(implode(', ', $v_names));
        ?>
        </span>
    </div>
    <a href="/sos/index.php" class="btn btn-sm btn-light text-danger fw-bold ms-3">View SOS Radar & Responses &raquo;</a>
</div>
<?php endif; ?>

<main class="container py-4 flex-grow-1">

<?php 
$flash = get_flash();
if ($flash): 
?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
