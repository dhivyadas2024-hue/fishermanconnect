<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['dalal', 'admin']); // Only Dalals and Admins

$pdo = get_db_connection();
$user = current_user();

// Handle Action: Update / Insert Rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_rate'])) {
    $species_id = intval($_POST['species_id']);
    $price_per_kg = floatval($_POST['price_per_kg']);
    $market_trend = trim($_POST['market_trend'] ?? 'Stable');
    $harbour_name = trim($_POST['harbour_name'] ?? $user['harbour_name']);

    if ($species_id && $price_per_kg > 0) {
        // Check if rate entry exists for this dalal & species
        $chk = $pdo->prepare("SELECT id FROM fish_rates WHERE dalal_user_id = ? AND species_id = ?");
        $chk->execute([$user['id'], $species_id]);
        $existing = $chk->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE fish_rates SET price_per_kg = ?, market_trend = ?, harbour_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$price_per_kg, $market_trend, $harbour_name, $existing['id']]);
            set_flash('success', 'Market rate updated successfully!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO fish_rates (dalal_user_id, species_id, price_per_kg, market_trend, harbour_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $species_id, $price_per_kg, $market_trend, $harbour_name]);
            set_flash('success', 'New species rate published!');
        }
    }
    header('Location: /dalal/rates.php');
    exit;
}

// Handle Action: Update Pre-Landing Catch Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_catch_status'])) {
    $catch_id = intval($_POST['catch_id']);
    $new_status = trim($_POST['new_status']);
    
    $stmt = $pdo->prepare("UPDATE catch_declarations SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $catch_id]);
    set_flash('success', 'Catch declaration status updated to ' . $new_status);
    header('Location: /dalal/rates.php');
    exit;
}

// Handle Action: Add New Species
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_species'])) {
    $species_name = trim($_POST['species_name']);
    $category = trim($_POST['category'] ?? 'Pelagic');
    $image_icon = trim($_POST['image_icon'] ?? '🐟');

    if ($species_name) {
        $stmt = $pdo->prepare("INSERT INTO fish_species (name, category, image_icon) VALUES (?, ?, ?)");
        $stmt->execute([$species_name, $category, $image_icon]);
        set_flash('success', "Species '{$species_name}' added to catalogue.");
    }
    header('Location: /dalal/rates.php');
    exit;
}

// Fetch all species
$species_list = $pdo->query("SELECT * FROM fish_species ORDER BY name ASC")->fetchAll();

// Fetch Dalal's existing rate overrides
$rates_by_dalal = [];
$stmt_r = $pdo->prepare("SELECT * FROM fish_rates WHERE dalal_user_id = ?");
$stmt_r->execute([$user['id']]);
foreach ($stmt_r->fetchAll() as $r) {
    $rates_by_dalal[$r['species_id']] = $r;
}

// Fetch incoming pre-landing catch declarations (assigned to this dalal or pending)
$stmt_catches = $pdo->prepare("
    SELECT c.*, s.name as species_name, s.image_icon, v.name as vessel_name, v.vessel_code, u.full_name as captain_name, u.phone as captain_phone
    FROM catch_declarations c
    JOIN fish_species s ON c.species_id = s.id
    JOIN vessels v ON c.vessel_id = v.id
    JOIN users u ON c.captain_user_id = u.id
    WHERE c.target_dalal_id = ? OR c.target_dalal_id IS NULL
    ORDER BY c.created_at DESC
");
$stmt_catches->execute([$user['id']]);
$incoming_catches = $stmt_catches->fetchAll();

$page_title = "Dalal Live Market Auction Console";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-0"><i class="bi bi-cash-coin text-success me-2"></i> Dalal Live Auction & Rate Desk</h2>
        <p class="text-muted mb-0">Agent: <strong><?= sanitize($user['full_name']) ?></strong> | Port: <?= sanitize($user['harbour_name']) ?></p>
    </div>
    <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddSpecies">
        <i class="bi bi-plus-lg me-1"></i> Catalog New Species
    </button>
</div>

<div class="row g-4">
    <!-- Live Rate Updating Table -->
    <div class="col-lg-7">
        <div class="card card-maritime p-4 shadow-sm">
            <h4 class="fw-bold text-navy mb-3"><i class="bi bi-pencil-square text-primary me-2"></i> Live Rate Bidding Engine</h4>
            <p class="text-muted small mb-3">Update rates per species. Price changes instantly reflect on public buyer portals and boat crew consoles.</p>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Species</th>
                            <th>Current Price (₹/kg)</th>
                            <th>Trend</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($species_list as $sp): 
                            $curr_rate = $rates_by_dalal[$sp['id']] ?? null;
                            $price = $curr_rate ? $curr_rate['price_per_kg'] : '0.00';
                            $trend = $curr_rate ? $curr_rate['market_trend'] : 'Stable';
                        ?>
                        <tr>
                            <form method="POST">
                                <input type="hidden" name="action_save_rate" value="1">
                                <input type="hidden" name="species_id" value="<?= $sp['id'] ?>">
                                <td>
                                    <span class="fs-4 me-1"><?= $sp['image_icon'] ?></span>
                                    <strong><?= sanitize($sp['name']) ?></strong>
                                    <div class="small text-muted"><?= sanitize($sp['category']) ?></div>
                                </td>
                                <td style="width: 140px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.5" name="price_per_kg" class="form-control fw-bold text-success" value="<?= $price ?>" required>
                                    </div>
                                </td>
                                <td style="width: 130px;">
                                    <select name="market_trend" class="form-select form-select-sm">
                                        <option value="Up" <?= $trend === 'Up' ? 'selected' : '' ?>>▲ Up</option>
                                        <option value="Stable" <?= $trend === 'Stable' ? 'selected' : '' ?>>➔ Stable</option>
                                        <option value="Down" <?= $trend === 'Down' ? 'selected' : '' ?>>▼ Down</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">Sync Rate</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Incoming Pre-Landing Catch Declarations -->
    <div class="col-lg-5">
        <div class="card card-maritime p-4 shadow-sm h-100">
            <h5 class="fw-bold text-navy mb-2"><i class="bi bi-box-seam text-info me-2"></i> Incoming Pre-Landing Declarations</h5>
            <p class="text-muted small mb-3">Declarations submitted by captains prior to docking for advance auction negotiations.</p>

            <?php if (empty($incoming_catches)): ?>
                <div class="alert alert-light text-center py-4">No incoming catch notifications at this time.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($incoming_catches as $c): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fs-5 me-1"><?= $c['image_icon'] ?></span>
                                    <strong class="text-navy"><?= sanitize($c['species_name']) ?></strong>
                                    <div class="badge bg-warning text-dark ms-1 fs-6"><?= number_format($c['estimated_qty_kg']) ?> kg</div>
                                </div>
                                <span class="badge bg-info text-dark"><?= sanitize($c['status']) ?></span>
                            </div>

                            <div class="small text-muted mb-2">
                                <i class="bi bi-ship me-1"></i> Vessel: <strong><?= sanitize($c['vessel_name']) ?></strong> (<?= sanitize($c['vessel_code']) ?>)<br>
                                <i class="bi bi-person me-1"></i> Capt. <?= sanitize($c['captain_name']) ?> (<?= sanitize($c['captain_phone']) ?>)<br>
                                <i class="bi bi-clock me-1"></i> ETA: <?= date('H:i, M j', strtotime($c['estimated_eta'])) ?>
                            </div>

                            <div class="d-flex gap-2">
                                <?php if ($c['status'] === 'Pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_update_catch_status" value="1">
                                        <input type="hidden" name="catch_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="new_status" value="Accepted">
                                        <button type="submit" class="btn btn-sm btn-primary fw-bold">Accept Offer</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($c['status'] !== 'Landed'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_update_catch_status" value="1">
                                        <input type="hidden" name="catch_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="new_status" value="Landed">
                                        <button type="submit" class="btn btn-sm btn-outline-success fw-bold">Mark Landed</button>
                                    </form>
                                <?php endif; ?>
                                <a href="/chat/index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat me-1"></i> Chat Capt</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Catalog New Species -->
<div class="modal fade" id="modalAddSpecies" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action_add_species" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy">Add New Fish Species to Catalogue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Species Name *</label>
                        <input type="text" name="species_name" class="form-control" placeholder="e.g. Red Snapper (Rani)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="Pelagic">Pelagic</option>
                            <option value="Demersal">Demersal</option>
                            <option value="Crustacean">Crustacean</option>
                            <option value="Cephalopod">Cephalopod</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Display Emoji Icon</label>
                        <input type="text" name="image_icon" class="form-control" value="🐟">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Species</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Sponsor Ad Banner -->
    <?= render_ad_banner('dashboard') ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
