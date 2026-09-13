<?php
// public/ad_click.php - Click tracker & redirect handler for corporate ads

require_once __DIR__ . '/../includes/functions.php';

$ad_id = intval($_GET['id'] ?? 0);

if ($ad_id > 0) {
    $pdo = get_db_connection();
    
    // Increment click count
    $stmt = $pdo->prepare("UPDATE ads SET clicks_count = clicks_count + 1 WHERE id = ?");
    $stmt->execute([$ad_id]);
    
    // Get target URL
    $stmt_url = $pdo->prepare("SELECT target_url FROM ads WHERE id = ?");
    $stmt_url->execute([$ad_id]);
    $target_url = $stmt_url->fetchColumn();
    
    if (!empty($target_url) && $target_url !== '#') {
        header('Location: ' . $target_url);
        exit;
    }
}

// Fallback redirect
header('Location: /public/index.php');
exit;
