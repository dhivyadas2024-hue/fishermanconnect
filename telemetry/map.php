<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
$user = current_user();

// Handle Action: Update GPS Telemetry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_telemetry'])) {
    if (!is_logged_in()) {
        set_flash('danger', 'Please login to update telemetry.');
        header('Location: /login.php');
        exit;
    }

    $vessel_id = intval($_POST['vessel_id']);
    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);
    $speed_knots = floatval($_POST['speed_knots']);
    $heading = floatval($_POST['heading']);
    $status = trim($_POST['status']);

    if ($vessel_id && $lat && $lng) {
        $stmt = $pdo->prepare("UPDATE vessels SET lat = ?, lng = ?, speed_knots = ?, heading = ?, status = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$lat, $lng, $speed_knots, $heading, $status, $vessel_id]);
        set_flash('success', 'Vessel GPS Telemetry & Status updated on Radar Map!');
    }
    header('Location: /telemetry/map.php');
    exit;
}

// Fetch all vessels for Radar Map rendering
$stmt = $pdo->query("
    SELECT v.*, u.full_name as captain_name, u.phone as captain_phone
    FROM vessels v
    LEFT JOIN users u ON v.captain_user_id = u.id
    ORDER BY v.status DESC
");
$vessels = $stmt->fetchAll();

// Fetch vessels for logged in captain/admin dropdown controls
if (has_role('admin')) {
    $my_vessels = $vessels;
} else {
    $stmt_my = $pdo->prepare("SELECT * FROM vessels WHERE captain_user_id = ?");
    $stmt_my->execute([$user['id'] ?? 0]);
    $my_vessels = $stmt_my->fetchAll();
}

$page_title = "Live GPS Radar Map & Marine Telemetry";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="fw-bold text-navy mb-0"><i class="bi bi-radar text-info me-2"></i> Marine Radar & Telemetry Display</h2>
        <p class="text-muted mb-0">Real-time GPS positioning, heading vectors, speed metrics, and vessel activity monitoring.</p>
    </div>
    <?php if (!empty($my_vessels)): ?>
        <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalUpdateTelemetry">
            <i class="bi bi-geo-alt-fill me-1"></i> Transmit GPS Coordinate Update
        </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Interactive Radar Map Box -->
    <div class="col-lg-8">
        <div class="card card-maritime shadow-sm overflow-hidden">
            <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold"><i class="bi bi-globe-central-south-asia me-2 text-info"></i> Interactive Sassoon Dock Coastal Sector Radar Map</span>
                <span class="badge bg-success"><i class="bi bi-broadcast"></i> AIS Satellite Receiver Active</span>
            </div>
            <div id="radarMap" style="height: 520px; width: 100%; background: #001f3f;"></div>
        </div>
    </div>

    <!-- Live Telemetry Stream Sidebar -->
    <div class="col-lg-4">
        <div class="card card-maritime p-3 shadow-sm h-100" style="max-height: 570px; overflow-y: auto;">
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-activity text-primary me-2"></i> Offshore Telemetry Feed</h5>

            <div class="list-group list-group-flush">
                <?php foreach ($vessels as $v): 
                    $status_badge = 'bg-secondary';
                    if ($v['status'] === 'Docked') $status_badge = 'bg-success';
                    elseif ($v['status'] === 'Fishing') $status_badge = 'bg-primary';
                    elseif ($v['status'] === 'Cruising') $status_badge = 'bg-info text-dark';
                    elseif ($v['status'] === 'Distress') $status_badge = 'bg-danger badge-sos';
                ?>
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong class="text-navy fs-6"><?= sanitize($v['name']) ?></strong>
                                <span class="badge bg-light text-dark border font-monospace ms-1"><?= sanitize($v['vessel_code']) ?></span>
                            </div>
                            <span class="badge <?= $status_badge ?>"><?= sanitize($v['status']) ?></span>
                        </div>

                        <div class="small text-muted mb-2">
                            <i class="bi bi-person me-1"></i> Capt: <?= $v['captain_name'] ? sanitize($v['captain_name']) : 'Unassigned' ?>
                        </div>

                        <div class="row g-2 text-center bg-light p-2 rounded mb-2" style="font-size: 0.8rem;">
                            <div class="col-4 border-end">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Speed</div>
                                <strong class="text-navy"><?= number_format($v['speed_knots'], 1) ?> kts</strong>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Heading</div>
                                <strong class="text-navy"><?= number_format($v['heading'], 0) ?>°</strong>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Tonnage</div>
                                <strong class="text-navy"><?= number_format($v['gross_tonnage'], 1) ?> GT</strong>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                            <span class="font-monospace text-secondary">
                                GPS: <?= number_format($v['lat'], 4) ?>°N, <?= number_format($v['lng'], 4) ?>°E
                            </span>
                            <span class="text-muted"><i class="bi bi-clock"></i> <?= time_ago($v['last_updated']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Update GPS Telemetry -->
<?php if (!empty($my_vessels)): ?>
<div class="modal fade" id="modalUpdateTelemetry" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action_update_telemetry" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-geo-alt"></i> Transmit GPS & Telemetry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Vessel *</label>
                        <select name="vessel_id" class="form-select" required>
                            <?php foreach ($my_vessels as $mv): ?>
                                <option value="<?= $mv['id'] ?>"><?= sanitize($mv['name']) ?> (<?= sanitize($mv['vessel_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Latitude (°N) *</label>
                            <input type="number" step="0.0001" name="lat" class="form-control" value="18.9180" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Longitude (°E) *</label>
                            <input type="number" step="0.0001" name="lng" class="form-control" value="72.8400" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Speed (Knots)</label>
                            <input type="number" step="0.1" name="speed_knots" class="form-control" value="7.5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Heading (Degrees 0-360)</label>
                            <input type="number" step="1" name="heading" class="form-control" value="135" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Vessel Activity Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Fishing">Fishing Operation</option>
                            <option value="Cruising">Cruising / Transit</option>
                            <option value="Anchored">Anchored at Sea</option>
                            <option value="Docked">Docked at Harbour</option>
                            <option value="Distress">Distress / Emergency SOS</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Broadcast Position &raquo;</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Script to initialize Leaflet Map with Vessels Data -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Center around Sassoon Dock / Mumbai Harbour
    var map = L.map('radarMap').setView([18.9220, 72.8347], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors | Project Fisherman AIS Telemetry'
    }).addTo(map);

    var vesselsData = <?= json_encode($vessels) ?>;

    vesselsData.forEach(function(v) {
        var markerColor = '#007791';
        if (v.status === 'Docked') markerColor = '#28a745';
        if (v.status === 'Fishing') markerColor = '#0d6efd';
        if (v.status === 'Distress') markerColor = '#dc3545';

        var customIcon = L.divIcon({
            className: 'custom-vessel-marker',
            html: `<div style="background-color: ${markerColor}; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.5);"></div>`,
            iconSize: [18, 18],
            iconAnchor: [9, 9]
        });

        var popupContent = `
            <div style="font-family: sans-serif; padding: 2px;">
                <h6 style="margin: 0 0 5px 0; color: #002B49;"><strong>${v.name}</strong> (${v.vessel_code})</h6>
                <p style="margin: 0 0 5px 0; font-size: 0.85rem;">
                    Status: <strong>${v.status}</strong><br>
                    Captain: ${v.captain_name || 'Unassigned'}<br>
                    Speed: ${v.speed_knots} kts | Heading: ${v.heading}°<br>
                    Coords: ${v.lat}, ${v.lng}
                </p>
                <a href="/chat/index.php" style="font-size: 0.8rem; color: #0d6efd;">Open Chat Channel &raquo;</a>
            </div>
        `;

        L.marker([v.lat, v.lng], { icon: customIcon })
            .addTo(map)
            .bindPopup(popupContent);
    });
});
</script>

<!-- Sponsor Ad Banner -->
<div class="container mt-4">
    <?= render_ad_banner('dashboard') ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
