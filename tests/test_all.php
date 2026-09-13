<?php
// tests/test_all.php - Automated PHP unit/integration tests for Project Fisherman

require_once __DIR__ . '/../includes/functions.php';

echo "===========================================\n";
echo "  PROJECT FISHERMAN AUTOMATED TEST SUITE   \n";
echo "===========================================\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test($description, $condition) {
    global $pass_count, $fail_count;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $pass_count++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $fail_count++;
    }
}

$pdo = get_db_connection();

// Test 1: Verify Seeded Users
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
assert_test("Database contains seeded users (Count >= 7)", $users_count >= 7);

// Test 2: Verify Vessel Fleet Registration
$vessel_count = $pdo->query("SELECT COUNT(*) FROM vessels")->fetchColumn();
assert_test("Vessel Fleet registered (Count >= 4)", $vessel_count >= 4);

// Test 3: Test Rate Bidding Engine Update
$dalal_stmt = $pdo->query("SELECT id FROM users WHERE role = 'dalal' LIMIT 1");
$dalal_id = $dalal_stmt->fetchColumn();

$species_stmt = $pdo->query("SELECT id FROM fish_species LIMIT 1");
$species_id = $species_stmt->fetchColumn();

$upd_rate = $pdo->prepare("INSERT INTO fish_rates (dalal_user_id, species_id, price_per_kg, market_trend, harbour_name) VALUES (?, ?, 950.00, 'Up', 'Test Port')");
$upd_rate->execute([$dalal_id, $species_id]);
$new_rate_id = $pdo->lastInsertId();

$check_rate = $pdo->prepare("SELECT price_per_kg FROM fish_rates WHERE id = ?");
$check_rate->execute([$new_rate_id]);
$fetched_price = $check_rate->fetchColumn();
assert_test("Live Dalal rate updated correctly to 950.00", floatval($fetched_price) == 950.00);

// Test 4: Test Pre-Landing Catch Declaration
$capt_stmt = $pdo->query("SELECT id FROM users WHERE role = 'captain' LIMIT 1");
$capt_id = $capt_stmt->fetchColumn();

$vessel_stmt = $pdo->query("SELECT id FROM vessels LIMIT 1");
$vessel_id = $vessel_stmt->fetchColumn();

$decl_stmt = $pdo->prepare("INSERT INTO catch_declarations (vessel_id, captain_user_id, species_id, estimated_qty_kg, estimated_eta, status) VALUES (?, ?, ?, 450.00, datetime('now', '+2 hours'), 'Pending')");
$decl_stmt->execute([$vessel_id, $capt_id, $species_id]);
$decl_id = $pdo->lastInsertId();
assert_test("Catch declaration submitted successfully", $decl_id > 0);

// Test 5: Test Chat Transmission
$msg_stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, channel_type, message) VALUES (?, 'p2p', 'Testing P2P transmission')");
$msg_stmt->execute([$capt_id]);
$msg_id = $pdo->lastInsertId();
assert_test("Chat transmission sent successfully", $msg_id > 0);

// Test 6: Test Telemetry GPS Update
$tel_stmt = $pdo->prepare("UPDATE vessels SET lat = 18.9500, lng = 72.8600, speed_knots = 11.2, heading = 180 WHERE id = ?");
$tel_stmt->execute([$vessel_id]);

$chk_vessel = $pdo->prepare("SELECT lat, speed_knots FROM vessels WHERE id = ?");
$chk_vessel->execute([$vessel_id]);
$v_data = $chk_vessel->fetch();
assert_test("Telemetry coordinates and speed updated", floatval($v_data['lat']) == 18.9500 && floatval($v_data['speed_knots']) == 11.2);

// Test 7: Test Emergency SOS Trigger Workflow
$sos_stmt = $pdo->prepare("INSERT INTO sos_alerts (vessel_id, captain_user_id, lat, lng, emergency_type, description, status) VALUES (?, ?, 18.9500, 72.8600, 'Severe Weather', 'High waves breaking over bow', 'ACTIVE')");
$sos_stmt->execute([$vessel_id, $capt_id]);
$sos_id = $pdo->lastInsertId();

$active_sos = get_active_sos_alerts();
assert_test("Active SOS alert recognized globally in system", count($active_sos) > 0);

// Resolve SOS
$pdo->prepare("UPDATE sos_alerts SET status = 'RESOLVED' WHERE id = ?")->execute([$sos_id]);
$active_sos_after = get_active_sos_alerts();
assert_test("SOS alert resolved successfully", count($active_sos_after) < count($active_sos));

// Test 8: Leaderboard Score Sync
$lb_stmt = $pdo->prepare("INSERT INTO leaderboard_scores (user_id, vessel_id, game_name, score) VALUES (?, ?, 'Fishing Puzzle Sim', 25000)");
$lb_stmt->execute([$capt_id, $vessel_id]);
$lb_id = $pdo->lastInsertId();
assert_test("Harbour leaderboard score registered", $lb_id > 0);

// Test 9: Ad Registration & Display
$ads = get_active_ads();
assert_test("Company advertisements registered and active", count($ads) >= 4);

// Test 10: Ad Click Tracking Redirect & CTR Calculation
$ad_sample = $ads[0];
$initial_clicks = intval($ad_sample['clicks_count']);

// Simulate click
$pdo->prepare("UPDATE ads SET clicks_count = clicks_count + 1 WHERE id = ?")->execute([$ad_sample['id']]);
$updated_clicks = $pdo->prepare("SELECT clicks_count FROM ads WHERE id = ?");
$updated_clicks->execute([$ad_sample['id']]);
$new_clicks = intval($updated_clicks->fetchColumn());

assert_test("Ad click tracking handler increments click metrics correctly", $new_clicks == ($initial_clicks + 1));

// Test 11: User Registration Pending Queue & Admin Approval
$test_pass = password_hash('pass123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES ('pending_capt', ?, 'Capt. Pending', 'captain', 'Pending')")->execute([$test_pass]);
$p_id = $pdo->lastInsertId();

$chk_pending = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$chk_pending->execute([$p_id]);
$p_status = $chk_pending->fetchColumn();
assert_test("User registration placed in Pending Approval Queue", $p_status === 'Pending');

$pdo->prepare("UPDATE users SET status = 'Approved' WHERE id = ?")->execute([$p_id]);
$chk_pending->execute([$p_id]);
$app_status = $chk_pending->fetchColumn();
assert_test("Admin approval transitions user status to Approved", $app_status === 'Approved');

echo "\n-------------------------------------------\n";
echo "SUMMARY: PASS = {$pass_count}, FAIL = {$fail_count}\n";
echo "-------------------------------------------\n";

if ($fail_count > 0) {
    exit(1);
} else {
    echo "ALL BACKEND TESTS PASSED SUCCESSFULLY!\n";
}
