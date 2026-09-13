<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['captain', 'crew', 'admin']);

$pdo = get_db_connection();
$user = current_user();

// Handle Action: Submit Catch Pre-Declaration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_declaration'])) {
    $vessel_id = intval($_POST['vessel_id']);
    $species_id = intval($_POST['species_id']);
    $estimated_qty_kg = floatval($_POST['estimated_qty_kg']);
    $estimated_eta = trim($_POST['estimated_eta']);
    $target_dalal_id = !empty($_POST['target_dalal_id']) ? intval($_POST['target_dalal_id']) : null;

    if ($vessel_id && $species_id && $estimated_qty_kg > 0 && $estimated_eta) {
        $stmt = $pdo->prepare("INSERT INTO catch_declarations (vessel_id, captain_user_id, species_id, estimated_qty_kg, estimated_eta, target_dalal_id, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$vessel_id, $user['id'], $species_id, $estimated_qty_kg, $estimated_eta, $target_dalal_id]);
        set_flash('success', 'Pre-landing catch declaration transmitted to harbour agents!');
    } else {
        set_flash('warning', 'Please complete all required catch details.');
    }
    header('Location: /captain/catch_declaration.php');
    exit;
}

// Fetch Vessels commanded or manned by this user (or all vessels if admin)
if (has_role('admin')) {
    $vessels = $pdo->query("SELECT * FROM vessels")->fetchAll();
} else {
    $stmt_v = $pdo->prepare("
        SELECT DISTINCT v.* FROM vessels v 
        LEFT JOIN crew_roster cr ON v.id = cr.vessel_id 
        WHERE v.captain_user_id = ? OR cr.user_id = ?
    ");
    $stmt_v->execute([$user['id'], $user['id']]);
    $vessels = $stmt_v->fetchAll();
    
    // Fallback if user is captain with no explicit vessel yet
    if (empty($vessels)) {
        $vessels = $pdo->query("SELECT * FROM vessels LIMIT 2")->fetchAll();
    }
}

// Fetch species
$species_list = $pdo->query("SELECT * FROM fish_species ORDER BY name ASC")->fetchAll();

// Fetch Dalals (Commission Agents)
$dalals = $pdo->query("SELECT id, full_name, phone FROM users WHERE role = 'dalal'")->fetchAll();

// Fetch Catch Declarations logged by captain
$vessel_ids = array_column($vessels, 'id');
if (!empty($vessel_ids)) {
    $in_clause = implode(',', array_fill(0, count($vessel_ids), '?'));
    $stmt_decl = $pdo->prepare("
        SELECT c.*, s.name as species_name, s.image_icon, v.name as vessel_name, u.full_name as dalal_name 
        FROM catch_declarations c
        JOIN fish_species s ON c.species_id = s.id
        JOIN vessels v ON c.vessel_id = v.id
        LEFT JOIN users u ON c.target_dalal_id = u.id
        WHERE c.vessel_id IN ($in_clause)
        ORDER BY c.created_at DESC
    ");
    $stmt_decl->execute($vessel_ids);
    $declarations = $stmt_decl->fetchAll();
} else {
    $declarations = [];
}

// Fetch Live Market Rates for Reference while at sea
$stmt_r = $pdo->query("
    SELECT r.*, s.name as species_name, s.image_icon, u.full_name as dalal_name
    FROM fish_rates r
    JOIN fish_species s ON r.species_id = s.id
    JOIN users u ON r.dalal_user_id = u.id
    ORDER BY r.updated_at DESC
");
$live_rates = $stmt_r->fetchAll();

$page_title = "Onboard Pre-Landing Catch Declaration";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-0"><i class="bi bi-box-seam-fill text-primary me-2"></i> Onboard Catch Pre-Declaration Console</h2>
        <p class="text-muted mb-0">Transmit estimated incoming haul quantities to Harbour Dalals prior to docking.</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalNewDeclaration">
        <i class="bi bi-broadcast me-1"></i> Transmit Catch Pre-Declaration
    </button>
</div>

<div class="row g-4">
    <!-- Logged Declarations Stream -->
    <div class="col-lg-7">
        <div class="card card-maritime p-4 shadow-sm mb-4">
            <h4 class="fw-bold text-navy mb-3"><i class="bi bi-clock-history text-info me-2"></i> My Vessel Catch Transmissions</h4>

            <?php if (empty($declarations)): ?>
                <div class="alert alert-light text-center py-4">No active catch declarations submitted yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vessel & Species</th>
                                <th>Est. Quantity</th>
                                <th>Expected ETA</th>
                                <th>Target Dalal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($declarations as $d): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-navy"><?= sanitize($d['vessel_name']) ?></div>
                                    <span class="fs-5 me-1"><?= $d['image_icon'] ?></span>
                                    <span><?= sanitize($d['species_name']) ?></span>
                                </td>
                                <td><span class="fw-bold text-success fs-6"><?= number_format($d['estimated_qty_kg']) ?> kg</span></td>
                                <td><small class="text-muted"><?= date('H:i, M j', strtotime($d['estimated_eta'])) ?></small></td>
                                <td>
                                    <?= $d['dalal_name'] ? sanitize($d['dalal_name']) : '<span class="badge bg-secondary">Open Harbour</span>' ?>
                                </td>
                                <td>
                                    <?php if ($d['status'] === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>
                                    <?php elseif ($d['status'] === 'Accepted'): ?>
                                        <span class="badge bg-primary"><i class="bi bi-check-all"></i> Accepted</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="bi bi-house-check"></i> Landed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live Market Rates Reference for Captain -->
    <div class="col-lg-5">
        <div class="card card-maritime p-4 shadow-sm">
            <h5 class="fw-bold text-navy mb-2"><i class="bi bi-currency-rupee me-1 text-success"></i> Live Harbour Market Benchmark</h5>
            <p class="text-muted small mb-3">Live rates broadcasted by Dalals at Sassoon Dock as price reference for your offshore haul.</p>

            <div class="list-group list-group-flush">
                <?php foreach ($live_rates as $lr): ?>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fs-5 me-1"><?= $lr['image_icon'] ?></span>
                            <strong><?= sanitize($lr['species_name']) ?></strong>
                            <div class="small text-muted">Agent: <?= sanitize($lr['dalal_name']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="fs-6 fw-bold text-success">₹ <?= number_format($lr['price_per_kg'], 2) ?> / kg</span>
                            <div><small class="text-muted"><?= time_ago($lr['updated_at']) ?></small></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Catch Declaration -->
<div class="modal fade" id="modalNewDeclaration" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action_submit_declaration" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-broadcast"></i> Transmit Pre-Landing Catch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Vessel *</label>
                        <select name="vessel_id" class="form-select" required>
                            <?php foreach ($vessels as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= sanitize($v['name']) ?> (<?= sanitize($v['vessel_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fish Species Haul *</label>
                        <select name="species_id" class="form-select" required>
                            <?php foreach ($species_list as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['image_icon'] ?> <?= sanitize($s['name']) ?> (<?= sanitize($s['category']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Estimated Weight (kg) *</label>
                            <input type="number" step="10" name="estimated_qty_kg" class="form-control" placeholder="e.g. 500" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Estimated Arrival (ETA) *</label>
                            <input type="datetime-local" name="estimated_eta" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+2 hours')) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Dalal (Commission Agent)</label>
                        <select name="target_dalal_id" class="form-select">
                            <option value="">-- Open Harbour Broadcast (All Agents) --</option>
                            <?php foreach ($dalals as $dal): ?>
                                <option value="<?= $dal['id'] ?>"><?= sanitize($dal['full_name']) ?> (<?= sanitize($dal['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Broadcast Catch &raquo;</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Sponsor Ad Banner -->
    <?= render_ad_banner('dashboard') ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
