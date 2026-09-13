<?php
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Platform Packages & Tier Comparison";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Banner -->
<div class="text-center py-4 mb-4">
    <span class="badge bg-primary px-3 py-2 text-uppercase mb-2"><i class="bi bi-box-seam me-1"></i> Project Fisherman Ecosystem Tiers</span>
    <h1 class="fw-bold text-navy display-5">Platform Service Packages & Comparison</h1>
    <p class="lead text-muted mx-auto" style="max-width: 750px;">
        Empowering coastal supply chains with transparent pricing, mesh networking, real-time fleet radar, and emergency distress capabilities.
    </p>
</div>

<!-- Package Cards Grid -->
<div class="row g-4 mb-5">
    <!-- Tier 1: Public Buyer -->
    <div class="col-lg-3 col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100 border-top border-4 border-info">
            <div class="text-center mb-3">
                <span class="fs-1 text-info"><i class="bi bi-basket-fill"></i></span>
                <h4 class="fw-bold text-navy mt-2">Public Consumer / Local Buyer</h4>
                <div class="fs-2 fw-bold text-success my-2">Free <span class="fs-6 text-muted font-normal">/ lifetime</span></div>
                <p class="small text-muted">For regional fish buyers, restaurants, and local consumers seeking transparent dock landing rates.</p>
            </div>
            <ul class="list-unstyled small mb-4 flex-grow-1">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Live Fish Rate Feed</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Pre-Landing Catch Stream</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Harbour Statistics & Notices</li>
                <li class="mb-2 text-muted"><i class="bi bi-dash-circle text-secondary me-2"></i> GPS AIS Radar Access</li>
                <li class="mb-2 text-muted"><i class="bi bi-dash-circle text-secondary me-2"></i> Vessel Chat & Comm</li>
            </ul>
            <a href="/register.php?role=public" class="btn btn-outline-info w-100 fw-bold">Register Free Buyer</a>
        </div>
    </div>

    <!-- Tier 2: Captain & Crew -->
    <div class="col-lg-3 col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100 border-top border-4 border-primary position-relative">
            <span class="position-absolute top-0 end-0 badge bg-primary m-3">Popular</span>
            <div class="text-center mb-3">
                <span class="fs-1 text-primary"><i class="bi bi-compass-fill"></i></span>
                <h4 class="fw-bold text-navy mt-2">Captain & Fleet Crew</h4>
                <div class="fs-2 fw-bold text-primary my-2">Free Vessel Suite</div>
                <p class="small text-muted">For vessel captains and crew members operating offshore fishing voyages.</p>
            </div>
            <ul class="list-unstyled small mb-4 flex-grow-1">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Pre-Landing Catch Declaration</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Continuous GPS Telemetry</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Peer Inter-Vessel Chat</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> One-Touch Emergency SOS</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Onboard Solitary Games & Audio</li>
            </ul>
            <a href="/register.php?role=captain" class="btn btn-primary w-100 fw-bold">Register Boat Captain</a>
        </div>
    </div>

    <!-- Tier 3: Dalal Commission Agent -->
    <div class="col-lg-3 col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100 border-top border-4 border-success">
            <div class="text-center mb-3">
                <span class="fs-1 text-success"><i class="bi bi-cash-stack"></i></span>
                <h4 class="fw-bold text-navy mt-2">Dalal Auction Pro</h4>
                <div class="fs-2 fw-bold text-success my-2">Agent Desk</div>
                <p class="small text-muted">For harbour commission agents (Dalals) conducting live fish auctions and trade logistics.</p>
            </div>
            <ul class="list-unstyled small mb-4 flex-grow-1">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Live Auction Bidding Desk</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Pre-Landing Catch Bidding</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Boat-to-Dalal Direct Messaging</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Catalog Custom Species</li>
                <li class="mb-2 text-muted"><i class="bi bi-dash-circle text-secondary me-2"></i> Fleet Management Console</li>
            </ul>
            <a href="/register.php?role=dalal" class="btn btn-success w-100 fw-bold text-white">Register Dalal Agent</a>
        </div>
    </div>

    <!-- Tier 4: Harbour Authority Enterprise -->
    <div class="col-lg-3 col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100 border-top border-4 border-danger">
            <div class="text-center mb-3">
                <span class="fs-1 text-danger"><i class="bi bi-shield-check"></i></span>
                <h4 class="fw-bold text-navy mt-2">Harbour Enterprise</h4>
                <div class="fs-2 fw-bold text-navy my-2">Authority Suite</div>
                <p class="small text-muted">For port authorities, coast guard rescue response, and administrative teams.</p>
            </div>
            <ul class="list-unstyled small mb-4 flex-grow-1">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Full Vessel Fleet Registration</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Interactive AIS Radar Map</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> MAYDAY Rescue Coordination</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Authority Push Broadcasts</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Active Crew Roster Audit</li>
            </ul>
            <a href="/register.php?role=admin" class="btn btn-dark w-100 fw-bold">Register Admin Officer</a>
        </div>
    </div>
</div>

<!-- Detailed Package Feature Comparison Matrix -->
<div class="card card-maritime p-4 shadow-sm mb-5">
    <h3 class="fw-bold text-navy mb-3"><i class="bi bi-table text-primary me-2"></i> Feature Comparison Matrix</h3>
    <p class="text-muted small mb-4">Detailed side-by-side feature comparison across user account roles.</p>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-start" style="width: 30%;">Platform Specification Feature</th>
                    <th style="width: 17.5%;">Public Consumer</th>
                    <th style="width: 17.5%;">Captain & Crew</th>
                    <th style="width: 17.5%;">Dalal Agent</th>
                    <th style="width: 17.5%;">Harbour Admin</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start fw-semibold">Live Fish Landing Rates Display</td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Pre-Landing Catch Declarations Transmit</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i> (Receive)</td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Dynamic Rate Bidding & Cataloging</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Interactive Fleet Radar Map (Leaflet)</td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i> (View Only)</td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i> (Full Control)</td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Peer-to-Peer Inter-Vessel Chat</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">One-Touch Emergency SOS Trigger</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i> (Rescue Mgmt)</td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Onboard Offline Games & Leaderboards</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
                <tr>
                    <td class="text-start fw-semibold">Vessel Directory & Crew Roster Audit</td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-x-circle text-muted fs-5"></i></td>
                    <td><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- CTA Register Section -->
<div class="card card-maritime p-5 text-center bg-navy text-white shadow-sm mb-4" style="background: linear-gradient(135deg, #002B49 0%, #005F73 100%);">
    <h2 class="fw-bold mb-3">Ready to Join Project Fisherman?</h2>
    <p class="lead opacity-90 mx-auto mb-4" style="max-width: 650px;">
        Register your account today or use our quick demo logins to experience the platform instantly.
    </p>
    <div>
        <a href="/register.php" class="btn btn-warning btn-lg fw-bold px-4 me-2">Register User Account</a>
        <a href="/login.php" class="btn btn-outline-light btn-lg fw-bold px-4">Sign In / Demo Switch</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
