<?php
// includes/functions.php - Helper utility functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/init.php';

function current_user() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function is_logged_in() {
    return current_user() !== null;
}

function has_role($roles) {
    $user = current_user();
    if (!$user) return false;
    if (is_string($roles)) $roles = [$roles];
    return in_array($user['role'], $roles);
}

function require_role($roles) {
    if (!is_logged_in()) {
        set_flash('danger', 'Please log in to access this page.');
        header('Location: /login.php');
        exit;
    }
    if (!has_role($roles)) {
        set_flash('warning', 'Access restricted for your account role.');
        header('Location: /public/index.php');
        exit;
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return $diff . " seconds ago";
    if ($diff < 3600) return floor($diff / 60) . " mins ago";
    if ($diff < 86400) return floor($diff / 3600) . " hours ago";
    return date('M j, Y H:i', $time);
}

function get_active_sos_alerts() {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT s.*, v.name as vessel_name, u.full_name as captain_name, u.phone 
                         FROM sos_alerts s 
                         JOIN vessels v ON s.vessel_id = v.id 
                         JOIN users u ON s.captain_user_id = u.id 
                         WHERE s.status = 'ACTIVE' 
                         ORDER BY s.created_at DESC");
    return $stmt->fetchAll();
}

function get_active_ads($ad_type = null) {
    $pdo = get_db_connection();
    if ($ad_type) {
        $stmt = $pdo->prepare("SELECT * FROM ads WHERE status = 'Approved' AND (ad_type = ? OR ad_type = 'banner') ORDER BY id DESC");
        $stmt->execute([$ad_type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM ads WHERE status = 'Approved' ORDER BY id DESC");
    }
    return $stmt->fetchAll();
}

function render_ad_banner($ad_type = 'banner') {
    $ads = get_active_ads($ad_type);
    if (empty($ads)) return '';
    $ad = $ads[array_rand($ads)];
    
    // Increment view count dynamically
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("UPDATE ads SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$ad['id']]);

    $click_url = "/public/ad_click.php?id=" . $ad['id'];
    
    ob_start();
    ?>
    <div class="my-4">
        <div class="card bg-dark-eval border-info shadow-sm overflow-hidden position-relative">
            <div class="position-absolute top-0 end-0 m-2">
                <span class="badge bg-secondary text-light opacity-75" style="font-size: 0.65rem;">Sponsor Ad</span>
            </div>
            <div class="row g-0 align-items-center">
                <div class="col-md-4">
                    <img src="<?= sanitize($ad['image_url']) ?>" class="img-fluid rounded-start w-100" style="max-height: 160px; object-fit: cover;" alt="Advertisement">
                </div>
                <div class="col-md-8">
                    <div class="card-body p-3">
                        <small class="text-info fw-bold text-uppercase d-block mb-1"><i class="bi bi-megaphone me-1"></i> <?= sanitize($ad['company_name']) ?></small>
                        <h5 class="card-title text-white fw-bold mb-1"><?= sanitize($ad['title']) ?></h5>
                        <p class="card-text text-muted small mb-2"><?= sanitize($ad['description']) ?></p>
                        <a href="<?= $click_url ?>" target="_blank" rel="noopener" class="btn btn-sm btn-info fw-bold text-dark">
                            Learn More & Click <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
