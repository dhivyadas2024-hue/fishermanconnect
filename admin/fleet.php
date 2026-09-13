<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin'); // Only admins access fleet management

$pdo = get_db_connection();

// Handle Actions: Add Vessel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_vessel'])) {
    $vessel_code = trim($_POST['vessel_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gross_tonnage = floatval($_POST['gross_tonnage'] ?? 15.0);
    $gear_type = trim($_POST['gear_type'] ?? 'Gillnetter');
    $home_port = trim($_POST['home_port'] ?? 'Mumbai Sassoon Dock');
    $captain_user_id = !empty($_POST['captain_user_id']) ? intval($_POST['captain_user_id']) : null;
    $status = trim($_POST['status'] ?? 'Docked');

    if ($vessel_code && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO vessels (vessel_code, name, gross_tonnage, gear_type, home_port, captain_user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vessel_code, $name, $gross_tonnage, $gear_type, $home_port, $captain_user_id, $status]);
            set_flash('success', "Vessel '{$name}' ({$vessel_code}) successfully registered!");
        } catch (PDOException $e) {
            set_flash('danger', 'Error creating vessel: ' . $e->getMessage());
        }
    } else {
        set_flash('warning', 'Vessel code and name are required.');
    }
    header('Location: /admin/fleet.php');
    exit;
}

// Handle Action: Update Ad Status (Approve/Reject/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ad_status'])) {
    $ad_id = intval($_POST['ad_id']);
    $status = trim($_POST['status']);
    
    if ($status === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
        $stmt->execute([$ad_id]);
        set_flash('success', 'Advertisement entry deleted.');
    } else {
        $stmt = $pdo->prepare("UPDATE ads SET status = ? WHERE id = ?");
        $stmt->execute([$status, $ad_id]);
        set_flash('success', 'Advertisement status updated to ' . strtoupper($status));
    }
    header('Location: /admin/fleet.php#ads-reports');
    exit;
}

// Handle Action: Update Vessel Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_status'])) {
    $vessel_id = intval($_POST['vessel_id']);
    $new_status = trim($_POST['new_status']);
    
    $stmt = $pdo->prepare("UPDATE vessels SET status = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_status, $vessel_id]);
    set_flash('success', 'Vessel operational status updated to ' . strtoupper($new_status));
    header('Location: /admin/fleet.php');
    exit;
}

// Handle Action: Assign Crew
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_crew'])) {
    $vessel_id = intval($_POST['vessel_id']);
    $crew_user_id = intval($_POST['crew_user_id']);
    $role_on_vessel = trim($_POST['role_on_vessel'] ?? 'Deckhand');

    if ($vessel_id && $crew_user_id) {
        // Prevent duplicate crew
        $chk = $pdo->prepare("SELECT id FROM crew_roster WHERE vessel_id = ? AND user_id = ?");
        $chk->execute([$vessel_id, $crew_user_id]);
        if (!$chk->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO crew_roster (vessel_id, user_id, role_on_vessel) VALUES (?, ?, ?)");
            $stmt->execute([$vessel_id, $crew_user_id, $role_on_vessel]);
            set_flash('success', 'Crew member added to vessel roster.');
        } else {
            set_flash('warning', 'Crew member is already on this vessel roster.');
        }
    }
    header('Location: /admin/fleet.php');
    exit;
}

// Handle Action: Post Harbour Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_post_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $priority = trim($_POST['priority'] ?? 'Medium');

    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, priority, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $content, $priority, current_user()['id']]);
        set_flash('success', 'Broadcast Announcement published.');
    }
    header('Location: /admin/fleet.php');
    exit;
}

// Fetch Vessels with Captain Name & Crew Count
$stmt = $pdo->query("
    SELECT v.*, u.full_name as captain_name, u.phone as captain_phone,
           (SELECT COUNT(*) FROM crew_roster WHERE vessel_id = v.id) as crew_count
    FROM vessels v
    LEFT JOIN users u ON v.captain_user_id = u.id
    ORDER BY v.id DESC
");
$vessels = $stmt->fetchAll();

// Fetch Captain Users & Crew Candidates for Dropdowns
$captains = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'captain'")->fetchAll();
$all_users = $pdo->query("SELECT id, full_name, role, username FROM users WHERE role IN ('captain', 'crew')")->fetchAll();

// Fetch All Ads for Corporate Analytics & Reports
$all_ads_admin = $pdo->query("SELECT * FROM ads ORDER BY id DESC")->fetchAll();

// Calculate Administrative Metrics & Reports
$total_ad_views = $pdo->query("SELECT SUM(views_count) FROM ads")->fetchColumn() ?: 0;
$total_ad_clicks = $pdo->query("SELECT SUM(clicks_count) FROM ads")->fetchColumn() ?: 0;
$overall_ctr = $total_ad_views > 0 ? round(($total_ad_clicks / $total_ad_views) * 100, 2) : 0;
$est_ad_revenue = count($all_ads_admin) * 199.00;

$page_title = "Harbour & Fleet Management Admin";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-0"><i class="bi bi-gear-fill me-2"></i> Harbour & Fleet Admin Console</h2>
        <p class="text-muted mb-0">Manage vessel registrations, tonnage, gear types, active crew rosters, and harbour broadcasts.</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalRegisterVessel">
        <i class="bi bi-plus-circle me-1"></i> Register New Vessel
    </button>
</div>

<!-- Fleet Overview Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-maritime p-3 text-center border-start border-4 border-primary shadow-sm">
            <span class="text-muted small text-uppercase fw-bold">Total Registered Fleet</span>
            <div class="fs-2 fw-bold text-navy"><?= count($vessels) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-maritime p-3 text-center border-start border-4 border-success shadow-sm">
            <span class="text-muted small text-uppercase fw-bold">Docked at Harbour</span>
            <div class="fs-2 fw-bold text-success">
                <?= count(array_filter($vessels, fn($v) => $v['status'] === 'Docked')) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-maritime p-3 text-center border-start border-4 border-info shadow-sm">
            <span class="text-muted small text-uppercase fw-bold">Active at Sea</span>
            <div class="fs-2 fw-bold text-info">
                <?= count(array_filter($vessels, fn($v) => in_array($v['status'], ['Fishing', 'Cruising', 'Anchored']))) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-maritime p-3 text-center border-start border-4 border-danger shadow-sm">
            <span class="text-muted small text-uppercase fw-bold">Emergency Distress</span>
            <div class="fs-2 fw-bold text-danger">
                <?= count(array_filter($vessels, fn($v) => $v['status'] === 'Distress')) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Vessels Table -->
    <div class="col-lg-8">
        <div class="card card-maritime p-4 shadow-sm">
            <h4 class="fw-bold text-navy mb-3"><i class="bi bi-water text-primary me-2"></i> Fleet Asset Directory</h4>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Vessel / Reg Code</th>
                            <th>Tonnage & Gear</th>
                            <th>Captain</th>
                            <th>Crew Roster</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vessels as $v): ?>
                        <tr>
                            <td>
                                <strong class="text-navy"><?= sanitize($v['name']) ?></strong>
                                <div class="badge bg-secondary font-monospace"><?= sanitize($v['vessel_code']) ?></div>
                                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= sanitize($v['home_port']) ?></div>
                            </td>
                            <td>
                                <div><strong><?= number_format($v['gross_tonnage'], 1) ?></strong> GT</div>
                                <span class="badge bg-light text-dark border"><?= sanitize($v['gear_type']) ?></span>
                            </td>
                            <td>
                                <?php if ($v['captain_name']): ?>
                                    <div class="fw-semibold"><?= sanitize($v['captain_name']) ?></div>
                                    <small class="text-muted"><?= sanitize($v['captain_phone']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted italic small">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-people-fill"></i> <?= $v['crew_count'] ?> Active
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status_badge = 'bg-secondary';
                                if ($v['status'] === 'Docked') $status_badge = 'bg-success';
                                elseif ($v['status'] === 'Fishing') $status_badge = 'bg-primary';
                                elseif ($v['status'] === 'Cruising') $status_badge = 'bg-info text-dark';
                                elseif ($v['status'] === 'Distress') $status_badge = 'bg-danger badge-sos';
                                ?>
                                <span class="badge <?= $status_badge ?>"><?= sanitize($v['status']) ?></span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Manage</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><h6 class="dropdown-header">Change Operational Status</h6></li>
                                        <?php foreach (['Docked', 'Cruising', 'Fishing', 'Anchored', 'Distress'] as $st): ?>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action_update_status" value="1">
                                                    <input type="hidden" name="vessel_id" value="<?= $v['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $st ?>">
                                                    <button type="submit" class="dropdown-item <?= $v['status'] === $st ? 'active' : '' ?>">
                                                        Set to <?= $st ?>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#modalCrew<?= $v['id'] ?>">
                                                <i class="bi bi-person-plus me-1"></i> Add Crew to Roster
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <!-- Crew Assignment Modal for Vessel -->
                        <div class="modal fade" id="modalCrew<?= $v['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="action_add_crew" value="1">
                                        <input type="hidden" name="vessel_id" value="<?= $v['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Crew to <?= sanitize($v['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Select User</label>
                                                <select name="crew_user_id" class="form-select" required>
                                                    <?php foreach ($all_users as $u): ?>
                                                        <option value="<?= $u['id'] ?>"><?= sanitize($u['full_name']) ?> (<?= strtoupper($u['role']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Role on Vessel</label>
                                                <input type="text" name="role_on_vessel" class="form-control" value="Deckhand" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary fw-bold">Add to Roster</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Authority Broadcast Console -->
    <div class="col-lg-4">
        <div class="card card-maritime p-4 shadow-sm mb-4">
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-broadcast me-2 text-warning"></i> Authority Announcement</h5>
            <form method="POST">
                <input type="hidden" name="action_post_announcement" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title / Headline</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Weather Advisory: Storm Warning" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Priority Level</label>
                    <select name="priority" class="form-select">
                        <option value="Low">Low Priority</option>
                        <option value="Medium" selected>Medium Priority</option>
                        <option value="High">High Priority</option>
                        <option value="Urgent">Urgent / Emergency</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Announcement Content</label>
                    <textarea name="content" class="form-control" rows="3" placeholder="Enter broadcast details for vessel captains..." required></textarea>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">Publish Broadcast Alert &raquo;</button>
            </form>
        </div>
    </div>
</div>

<!-- ==================== AD MANAGEMENT & ADMINISTRATIVE REPORTS ==================== -->
<div class="mt-5" id="ads-reports">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold text-navy mb-0"><i class="bi bi-graph-up-arrow text-warning me-2"></i> Corporate Ad Management & Administrative Reports</h3>
            <p class="text-muted mb-0">Track registered companies, impression views, clicks, CTR performance, and manage campaign approvals.</p>
        </div>
        <a href="/public/register_ad.php" class="btn btn-outline-warning fw-bold text-dark">
            <i class="bi bi-plus-lg me-1"></i> Register New Sponsor Ad
        </a>
    </div>

    <!-- Administrative Ad Reports Summary Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-maritime p-3 text-center border-start border-4 border-info shadow-sm">
                <span class="text-muted small text-uppercase fw-bold">Total Ad Views / Impressions</span>
                <div class="fs-2 fw-bold text-navy"><?= number_format($total_ad_views) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-maritime p-3 text-center border-start border-4 border-success shadow-sm">
                <span class="text-muted small text-uppercase fw-bold">Total Ad Clicks</span>
                <div class="fs-2 fw-bold text-success"><?= number_format($total_ad_clicks) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-maritime p-3 text-center border-start border-4 border-warning shadow-sm">
                <span class="text-muted small text-uppercase fw-bold">Average Click-Through Rate (CTR)</span>
                <div class="fs-2 fw-bold text-dark"><?= $overall_ctr ?>%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-maritime p-3 text-center border-start border-4 border-primary shadow-sm">
                <span class="text-muted small text-uppercase fw-bold">Estimated Ad Subscriptions Revenue</span>
                <div class="fs-2 fw-bold text-primary">$<?= number_format($est_ad_revenue, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Registered Companies & Ad Metrics Table -->
    <div class="card card-maritime p-4 shadow-sm mb-4">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-building me-2 text-info"></i> Registered Companies & Ad Performance Analytics</h5>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Company & Contact</th>
                        <th>Ad Title & Placement</th>
                        <th>Target URL</th>
                        <th class="text-center">Views / Impressions</th>
                        <th class="text-center">Clicks Recorded</th>
                        <th class="text-center">CTR %</th>
                        <th>Status</th>
                        <th>Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_ads_admin as $ad): 
                        $views = intval($ad['views_count']);
                        $clicks = intval($ad['clicks_count']);
                        $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;
                        
                        $status_badge = 'bg-success';
                        if ($ad['status'] === 'Pending') $status_badge = 'bg-warning text-dark';
                        elseif ($ad['status'] === 'Rejected') $status_badge = 'bg-danger';
                    ?>
                    <tr>
                        <td>
                            <strong class="text-navy"><?= sanitize($ad['company_name']) ?></strong>
                            <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= sanitize($ad['contact_email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?= sanitize($ad['title']) ?></div>
                            <span class="badge bg-info text-dark text-uppercase"><?= sanitize($ad['ad_type']) ?></span>
                        </td>
                        <td>
                            <a href="<?= sanitize($ad['target_url']) ?>" target="_blank" class="small text-truncate d-inline-block" style="max-width: 150px;">
                                <?= sanitize($ad['target_url']) ?>
                            </a>
                        </td>
                        <td class="text-center fw-bold text-navy"><?= number_format($views) ?></td>
                        <td class="text-center fw-bold text-success"><?= number_format($clicks) ?></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border font-monospace"><?= $ctr ?>%</span>
                        </td>
                        <td>
                            <span class="badge <?= $status_badge ?>"><?= sanitize($ad['status']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if ($ad['status'] !== 'Approved'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_ad_status" value="1">
                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                        <input type="hidden" name="status" value="Approved">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve Ad"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($ad['status'] !== 'Rejected'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action_ad_status" value="1">
                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                        <input type="hidden" name="status" value="Rejected">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Reject Ad"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this ad submission permanently?');">
                                    <input type="hidden" name="action_ad_status" value="1">
                                    <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                    <input type="hidden" name="status" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Entry"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Register New Vessel -->
<div class="modal fade" id="modalRegisterVessel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action_add_vessel" value="1">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-ship"></i> Register New Vessel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vessel Code / Reg Registration *</label>
                        <input type="text" name="vessel_code" class="form-control" placeholder="e.g. IND-MH-5500" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vessel Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. M.V. Ocean Victory" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Gross Tonnage (GT)</label>
                            <input type="number" step="0.1" name="gross_tonnage" class="form-control" value="20.0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Gear Type</label>
                            <input type="text" name="gear_type" class="form-control" value="Trawler" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Home Port Assignment</label>
                        <input type="text" name="home_port" class="form-control" value="Mumbai Sassoon Dock" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign Captain User</label>
                        <select name="captain_user_id" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($captains as $cap): ?>
                                <option value="<?= $cap['id'] ?>"><?= sanitize($cap['full_name']) ?> (@<?= sanitize($cap['username']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Initial Status</label>
                        <select name="status" class="form-select">
                            <option value="Docked">Docked</option>
                            <option value="Cruising">Cruising</option>
                            <option value="Fishing">Fishing</option>
                            <option value="Anchored">Anchored</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Register Vessel &raquo;</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
