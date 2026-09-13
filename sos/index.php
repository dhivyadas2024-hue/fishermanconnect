<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
$user = current_user();

// Handle Action: Trigger One-Touch Emergency SOS Distress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_trigger_sos'])) {
    if (!is_logged_in()) {
        set_flash('danger', 'Authentication required to trigger emergency distress signal.');
        header('Location: /login.php');
        exit;
    }

    $vessel_id = intval($_POST['vessel_id']);
    $emergency_type = trim($_POST['emergency_type']);
    $lat = floatval($_POST['lat']);
    $lng = floatval($_POST['lng']);
    $description = trim($_POST['description'] ?? '');

    if ($vessel_id && $emergency_type && $lat && $lng) {
        // 1. Insert into SOS alerts
        $stmt = $pdo->prepare("INSERT INTO sos_alerts (vessel_id, captain_user_id, lat, lng, emergency_type, description, status) VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')");
        $stmt->execute([$vessel_id, $user['id'], $lat, $lng, $emergency_type, $description]);

        // 2. Set vessel status to Distress
        $stmt_v = $pdo->prepare("UPDATE vessels SET status = 'Distress', lat = ?, lng = ? WHERE id = ?");
        $stmt_v->execute([$lat, $lng, $vessel_id]);

        // 3. Post emergency broadcast message in chat
        $sos_msg = "MAYDAY MAYDAY! Vessel Distress Alert triggered by Capt. {$user['full_name']} at GPS Coords: {$lat}, {$lng}. Emergency Type: {$emergency_type}. Description: {$description}";
        $pdo->prepare("INSERT INTO chat_messages (sender_id, channel_type, vessel_id, message) VALUES (?, 'broadcast', ?, ?)")
            ->execute([$user['id'], $vessel_id, $sos_msg]);

        set_flash('danger', 'MAYDAY DISTRESS SIGNAL BROADCASTED TO ALL NEARBY VESSELS & HARBOUR AUTHORITIES!');
    }
    header('Location: /sos/index.php');
    exit;
}

// Handle Action: Resolve SOS Signal (For Admin or Captain)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_resolve_sos'])) {
    $sos_id = intval($_POST['sos_id']);
    $vessel_id = intval($_POST['vessel_id']);

    $stmt = $pdo->prepare("UPDATE sos_alerts SET status = 'RESOLVED', resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$sos_id]);

    // Restore vessel status to Docked or Fishing
    $stmt_v = $pdo->prepare("UPDATE vessels SET status = 'Anchored' WHERE id = ?");
    $stmt_v->execute([$vessel_id]);

    set_flash('success', 'SOS Emergency Distress alert marked as RESOLVED. Vessel status updated.');
    header('Location: /sos/index.php');
    exit;
}

// Fetch Active SOS Alerts
$stmt_active = $pdo->query("
    SELECT s.*, v.name as vessel_name, v.vessel_code, u.full_name as captain_name, u.phone as captain_phone
    FROM sos_alerts s
    JOIN vessels v ON s.vessel_id = v.id
    JOIN users u ON s.captain_user_id = u.id
    WHERE s.status = 'ACTIVE'
    ORDER BY s.created_at DESC
");
$active_sos = $stmt_active->fetchAll();

// Fetch Resolved SOS Alerts
$stmt_resolved = $pdo->query("
    SELECT s.*, v.name as vessel_name, v.vessel_code, u.full_name as captain_name
    FROM sos_alerts s
    JOIN vessels v ON s.vessel_id = v.id
    JOIN users u ON s.captain_user_id = u.id
    WHERE s.status = 'RESOLVED'
    ORDER BY s.resolved_at DESC LIMIT 5
");
$resolved_sos = $stmt_resolved->fetchAll();

// Fetch User Vessels for Trigger Modal
if (has_role('admin')) {
    $my_vessels = $pdo->query("SELECT * FROM vessels")->fetchAll();
} else {
    $stmt_my = $pdo->prepare("SELECT * FROM vessels WHERE captain_user_id = ?");
    $stmt_my->execute([$user['id'] ?? 0]);
    $my_vessels = $stmt_my->fetchAll();
    if (empty($my_vessels)) {
        $my_vessels = $pdo->query("SELECT * FROM vessels LIMIT 2")->fetchAll();
    }
}

$page_title = "Emergency & Safety SOS Protocol";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card bg-danger text-white p-4 mb-4 shadow">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge bg-white text-danger fw-bold mb-2 fs-6"><i class="bi bi-shield-exclamation"></i> MARITIME DISTRESS PROTOCOL</span>
            <h1 class="display-6 fw-bold mb-1">One-Touch Emergency SOS System</h1>
            <p class="mb-0 opacity-90">
                Engineered for high-reliability crisis management. Broadcasts exact GPS coordinates and distress metrics instantly to all nearby vessels, commission agents, and harbour rescue teams.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-light text-danger fw-bold btn-lg shadow pulse" data-bs-toggle="modal" data-bs-target="#modalTriggerSOS">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> TRIGGER SOS DISTRESS
            </button>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Active SOS Emergencies -->
    <div class="col-lg-7">
        <div class="card card-maritime p-4 shadow-sm mb-4 border-top border-4 border-danger">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-danger mb-0"><i class="bi bi-bell-fill me-2"></i> Active Distress Signals</h4>
                <span class="badge bg-danger fs-6"><?= count($active_sos) ?> Active</span>
            </div>

            <?php if (empty($active_sos)): ?>
                <div class="alert alert-success text-center py-4 my-2">
                    <i class="bi bi-check-circle-fill fs-3 d-block text-success mb-2"></i>
                    <strong>All Sectors Clear!</strong> No active vessel emergency distress signals in harbour perimeter.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($active_sos as $sos): ?>
                        <div class="list-group-item p-3 mb-3 border rounded bg-light border-danger">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-danger fs-6 me-2"><i class="bi bi-lightning-fill"></i> <?= sanitize($sos['emergency_type']) ?></span>
                                    <strong class="text-navy fs-5"><?= sanitize($sos['vessel_name']) ?></strong>
                                    <span class="badge bg-dark font-monospace ms-1"><?= sanitize($sos['vessel_code']) ?></span>
                                </div>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> <?= time_ago($sos['created_at']) ?></span>
                            </div>

                            <div class="p-2 bg-white rounded border mb-2 fs-6">
                                <strong>Distress Description:</strong> <?= sanitize($sos['description']) ?>
                            </div>

                            <div class="row g-2 text-center bg-white p-2 rounded border mb-3 small">
                                <div class="col-6 border-end">
                                    <span class="text-muted d-block">Captain in Command</span>
                                    <strong>Capt. <?= sanitize($sos['captain_name']) ?></strong> (<?= sanitize($sos['captain_phone']) ?>)
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Distress GPS Coordinates</span>
                                    <strong class="font-monospace text-danger"><?= number_format($sos['lat'], 4) ?>°N, <?= number_format($sos['lng'], 4) ?>°E</strong>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/telemetry/map.php" class="btn btn-sm btn-outline-danger fw-bold"><i class="bi bi-radar me-1"></i> View on Radar Map</a>
                                <form method="POST">
                                    <input type="hidden" name="action_resolve_sos" value="1">
                                    <input type="hidden" name="sos_id" value="<?= $sos['id'] ?>">
                                    <input type="hidden" name="vessel_id" value="<?= $sos['vessel_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg me-1"></i> Confirm Rescue & Resolve</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resolved Incident Logs & Rescue Directives -->
    <div class="col-lg-5">
        <div class="card card-maritime p-4 shadow-sm mb-4">
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-shield-check text-success me-2"></i> Safety Directives & Rescue Protocol</h5>
            <ol class="small text-muted mb-0 ps-3">
                <li class="mb-2">All vessels within 10 Nautical Miles must maintain radio/mesh contact upon SOS trigger.</li>
                <li class="mb-2">Commission Agents (Dalals) clear pier docking spaces for incoming rescue towing.</li>
                <li class="mb-2">Harbour Rescue Authorities automatically dispatch Coast Guard tug boats to exact coordinates.</li>
            </ol>
        </div>

        <div class="card card-maritime p-4 shadow-sm">
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-journal-text me-2"></i> Recent Resolved Emergencies</h5>
            <?php if (empty($resolved_sos)): ?>
                <div class="text-muted small">No past resolved incidents recorded.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($resolved_sos as $rsos): ?>
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-navy"><?= sanitize($rsos['vessel_name']) ?></strong>
                                <span class="badge bg-success">RESOLVED</span>
                            </div>
                            <div class="small text-muted"><?= sanitize($rsos['emergency_type']) ?> • Capt. <?= sanitize($rsos['captain_name']) ?></div>
                            <div class="small text-secondary" style="font-size: 0.75rem;">Resolved <?= time_ago($rsos['resolved_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Trigger One-Touch SOS -->
<div class="modal fade" id="modalTriggerSOS" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <form method="POST">
                <input type="hidden" name="action_trigger_sos" value="1">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> ACTIVATE EMERGENCY MAYDAY SOS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <strong>Warning:</strong> Only trigger in genuine maritime emergency situations (engine failure, severe weather, medical crisis, or accident).
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Vessel in Distress *</label>
                        <select name="vessel_id" class="form-select" required>
                            <?php foreach ($my_vessels as $mv): ?>
                                <option value="<?= $mv['id'] ?>"><?= sanitize($mv['name']) ?> (<?= sanitize($mv['vessel_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Emergency Nature *</label>
                        <select name="emergency_type" class="form-select" required>
                            <option value="Engine Failure">Engine Failure / Propulsion Loss</option>
                            <option value="Severe Weather">Severe Weather / Water Ingress</option>
                            <option value="Medical Crisis">Medical Emergency / Onboard Injury</option>
                            <option value="Accident">Collision / Grounding Accident</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Latitude (°N)</label>
                            <input type="number" step="0.0001" name="lat" class="form-control" value="18.8900" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Longitude (°E)</label>
                            <input type="number" step="0.0001" name="lng" class="form-control" value="72.8200" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Distress Situation Details</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe assistance needed (e.g., Immediate tug towing required, medical evacuation requested)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 py-2"><i class="bi bi-broadcast me-1"></i> BROADCAST MAYDAY NOW</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
