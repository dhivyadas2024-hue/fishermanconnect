<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $ad_type = trim($_POST['ad_type'] ?? 'frontpage');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $target_url = trim($_POST['target_url'] ?? '');

    if (empty($image_url)) {
        $image_url = 'https://picsum.photos/800/250?maritime';
    }

    if (empty($company_name) || empty($contact_email) || empty($title) || empty($description)) {
        $error_msg = "Please fill in all required fields marked with *.";
    } else {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("INSERT INTO ads (company_name, contact_email, ad_type, title, description, image_url, target_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Approved')");
        if ($stmt->execute([$company_name, $contact_email, $ad_type, $title, $description, $image_url, $target_url])) {
            $success_msg = "Success! Your ad for <strong>" . htmlspecialchars($company_name) . "</strong> has been registered and activated instantly on the platform (" . htmlspecialchars($ad_type) . " placement).";
        } else {
            $error_msg = "Database error while registering advertisement.";
        }
    }
}

// Fetch all registered ads
$all_ads = get_active_ads();

$page_title = "Company Ad Registration Portal";
?>

<div class="bg-maritime text-white py-5 border-bottom border-secondary mb-4">
    <div class="container text-center">
        <span class="badge bg-warning text-dark text-uppercase px-3 py-2 mb-2 fw-bold" style="letter-spacing: 1.5px;">Corporate Sponsorships & Ads</span>
        <h1 class="fw-bold display-5">Advertise with Project Fisherman</h1>
        <p class="lead text-light max-w-750 mx-auto">
            Promote marine equipment, cold-chain logistics, vessel repairs, insurance, and seafood services directly to boat captains, dalals, and coastal buyers.
        </p>
    </div>
</div>

<div class="container pb-5">

    <div class="row g-4">
        
        <!-- Registration Form Column -->
        <div class="col-lg-7">
            <div class="card bg-dark-eval border-secondary shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-white fw-bold mb-3"><i class="bi bi-badge-ad text-warning me-2"></i>Register New Advertisement</h3>

                    <?php if ($success_msg): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                            <div><?= $success_msg ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                            <div><?= $error_msg ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-light">Company / Organization Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control bg-dark text-white border-secondary" placeholder="e.g. OceanNav Electronics" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-light">Corporate Contact Email <span class="text-danger">*</span></label>
                                <input type="email" name="contact_email" class="form-control bg-dark text-white border-secondary" placeholder="e.g. sales@oceannav.com" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-light">Ad Placement / Type <span class="text-danger">*</span></label>
                                <select name="ad_type" class="form-select bg-dark text-white border-secondary" required>
                                    <option value="frontpage">Front Page Main Showcase ($199/mo)</option>
                                    <option value="banner">Global Header/Footer Banner ($299/mo)</option>
                                    <option value="sidebar">Sidebar Widget (About / Contact) ($149/mo)</option>
                                    <option value="dashboard">Customer & Vessel Dashboard ($249/mo)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-light">Ad Target URL Link</label>
                                <input type="url" name="target_url" class="form-control bg-dark text-white border-secondary" placeholder="https://yourcompany.com/landing">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-light">Ad Headline / Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control bg-dark text-white border-secondary" placeholder="e.g. Heavy Duty Marine Radar & AIS Transmitters" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-light">Ad Promotional Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Briefly describe your offer, discount code, or service benefits..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-light">Ad Image Banner URL (Optional)</label>
                            <input type="url" name="image_url" class="form-control bg-dark text-white border-secondary" placeholder="https://picsum.photos/800/250?maritime">
                            <small class="text-muted">Leave blank for automatic maritime stock graphic assignment.</small>
                        </div>

                        <button type="submit" class="btn btn-warning btn-lg fw-bold text-dark px-4">
                            <i class="bi bi-rocket-takeoff me-2"></i>Submit & Publish Advertisement
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Active Live Ads List Column -->
        <div class="col-lg-5">
            <h4 class="text-white fw-bold mb-3"><i class="bi bi-body-text text-info me-2"></i>Active Sponsored Ads</h4>
            
            <?php foreach ($all_ads as $ad_item): ?>
                <div class="card bg-dark-eval border-secondary mb-3 shadow-sm">
                    <img src="<?= sanitize($ad_item['image_url']) ?>" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Ad Image">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-warning fw-bold text-uppercase"><?= sanitize($ad_item['company_name']) ?></small>
                            <span class="badge bg-info text-dark text-uppercase" style="font-size: 0.65rem;"><?= sanitize($ad_item['ad_type']) ?></span>
                        </div>
                        <h6 class="text-white fw-bold mb-1"><?= sanitize($ad_item['title']) ?></h6>
                        <p class="text-muted small mb-2"><?= sanitize($ad_item['description']) ?></p>
                        <a href="<?= sanitize($ad_item['target_url']) ?>" target="_blank" class="btn btn-sm btn-outline-info">Visit Website &raquo;</a>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
