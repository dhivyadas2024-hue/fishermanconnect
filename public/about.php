<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';

$page_title = "About Us & Mission Platform Overview";
?>

<div class="bg-maritime text-white py-5 border-bottom border-secondary mb-4">
    <div class="container text-center">
        <span class="badge bg-info text-dark text-uppercase px-3 py-2 mb-2 fw-bold" style="letter-spacing: 1.5px;">About Project Fisherman</span>
        <h1 class="fw-bold display-5">Empowering Maritime Communities</h1>
        <p class="lead text-light max-w-750 mx-auto">
            Digitizing harbour logistics, eliminating price manipulation, protecting crew safety at sea, and enhancing offshore wellbeing.
        </p>
    </div>
</div>

<div class="container pb-5">

    <!-- Mission & Vision -->
    <div class="row align-items-center g-4 mb-5">
        <div class="col-lg-6">
            <h2 class="text-info fw-bold mb-3"><i class="bi bi-compass me-2"></i>Our Mission</h2>
            <p class="text-light lead">
                Coastal fishing is one of the world's oldest and most essential industries, yet it remains hindered by fragmented communication, opaque pricing, and critical safety hazards.
            </p>
            <p class="text-muted">
                <strong>Project Fisherman</strong> was founded to provide a modern, real-time software ecosystem that empowers everyone in the supply chain—from sea captains and harbour dalals to regulatory authorities and public consumers. Our mission is to digitize every dock, streamline pre-landing catch logistics, and save lives at sea through peer-to-peer distress networking.
            </p>
        </div>
        <div class="col-lg-6">
            <div class="bg-dark-eval p-4 rounded border border-secondary">
                <h4 class="text-white fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i>Core Pillars</h4>
                <div class="d-flex mb-3">
                    <div class="fs-2 text-info me-3"><i class="bi bi-graph-up text-info"></i></div>
                    <div>
                        <h6 class="text-white mb-1">Price Transparency</h6>
                        <p class="small text-muted mb-0">Real-time dynamic Dalal market index ensuring fair prices for every kilogram landed.</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <div class="fs-2 text-danger me-3"><i class="bi bi-life-preserver"></i></div>
                    <div>
                        <h6 class="text-white mb-1">High-Reliability SOS</h6>
                        <p class="small text-muted mb-0">Instant distress alerts routed to nearby vessels via mesh network and harbour authorities.</p>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="fs-2 text-warning me-3"><i class="bi bi-joystick"></i></div>
                    <div>
                        <h6 class="text-white mb-1">Solitary Wellbeing</h6>
                        <p class="small text-muted mb-0">Offline minigames, leaderboards, and entertainment modules to combat offshore isolation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stakeholder Roles Ecosystem -->
    <h3 class="text-white fw-bold mb-4 text-center"><i class="bi bi-people text-info me-2"></i>The Maritime Ecosystem & User Roles</h3>
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card bg-dark-eval border-secondary h-100 p-3">
                <div class="card-body">
                    <div class="badge bg-primary mb-2">Public Buyers</div>
                    <h5 class="text-white fw-bold">Consumers & Wholesalers</h5>
                    <p class="small text-muted mb-0">Gain real-time visibility into landed fish rates, availability by species, and local harbour arrival schedules without requiring registration.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark-eval border-secondary h-100 p-3">
                <div class="card-body">
                    <div class="badge bg-success mb-2">Captains & Crew</div>
                    <h5 class="text-white fw-bold">Boat Crews</h5>
                    <p class="small text-muted mb-0">Log catch declarations in advance, monitor GPS telemetry, communicate via P2P inter-vessel chat, and activate emergency SOS signals.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark-eval border-secondary h-100 p-3">
                <div class="card-body">
                    <div class="badge bg-warning text-dark mb-2">Dalals (Agents)</div>
                    <h5 class="text-white fw-bold">Commission Agents</h5>
                    <p class="small text-muted mb-0">Manage live wholesale rate auctions, receive incoming catch notifications from active boats, and streamline harbour cold storage allocations.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark-eval border-secondary h-100 p-3">
                <div class="card-body">
                    <div class="badge bg-danger mb-2">Harbour Admin</div>
                    <h5 class="text-white fw-bold">Harbour Authorities</h5>
                    <p class="small text-muted mb-0">Maintain overall fleet oversight on interactive radar tracking, verify vessel compliance, handle permissions, and direct emergency rescue operations.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Architecture & Hybrid Tech Stack -->
    <div class="card bg-dark-eval border-secondary shadow-sm mb-5">
        <div class="card-header bg-dark border-secondary">
            <h4 class="text-white fw-bold mb-0"><i class="bi bi-cpu text-info me-2"></i>Technical Architecture & Design Principles</h4>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <h6 class="text-info fw-bold"><i class="bi bi-hdd-network me-2"></i>Hybrid Database Layer</h6>
                    <p class="small text-muted">
                        Engineered with PDO abstraction, supporting enterprise MySQL/MariaDB deployments with zero-config SQLite fallback for offline port servers.
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-info fw-bold"><i class="bi bi-wifi-off me-2"></i>Mesh Communication Protocol</h6>
                    <p class="small text-muted">
                        Utilizes local device-to-device Bluetooth/Wi-Fi Direct mesh links for peer vessel chatter and offline minigames when cellular range is lost.
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-info fw-bold"><i class="bi bi-geo-alt me-2"></i>Real-time Telemetry & Radar</h6>
                    <p class="small text-muted">
                        LeafletJS map integration rendering live vessel coordinates, cruising speeds, operational states, and nautical safety radiuses.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sponsor Ad Banner -->
    <?= render_ad_banner('sidebar') ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
