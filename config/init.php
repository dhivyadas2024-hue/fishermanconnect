<?php
// config/init.php - Database auto-initializer and seed script

require_once __DIR__ . '/db.php';

function initialize_database() {
    $pdo = get_db_connection();

    // Check if tables already exist
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $table_check = false;

    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $table_check = (bool)$stmt->fetch();
    } else {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $table_check = (bool)$stmt->fetch();
    }

    if (!$table_check) {
        $schema_file = __DIR__ . '/../database/schema.sql';
        $sql = file_get_contents($schema_file);

        if ($driver === 'mysql') {
            // Adjust sqlite AUTOINCREMENT syntax for mysql if needed
            $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
        }

        // Execute multiple statements
        $pdo->exec($sql);
        seed_initial_data($pdo);
    } else {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'Approved'");
        } catch (Exception $e) {
            // Column already exists
        }
    }
}

function seed_initial_data($pdo) {
    // 1. Seed Users
    $password_hash = password_hash('password123', PASSWORD_BCRYPT);

    $users = [
        ['admin', $password_hash, 'Harbour Admin Officer', 'admin', '+91 98200 11111', 'Mumbai Sassoon Dock'],
        ['dalal1', $password_hash, 'Ramesh Dalal (Commission Agent)', 'dalal', '+91 98200 22222', 'Mumbai Sassoon Dock'],
        ['dalal2', $password_hash, 'Suresh Traders', 'dalal', '+91 98200 33333', 'Mumbai Sassoon Dock'],
        ['captain_vikram', $password_hash, 'Capt. Vikram SeaKing', 'captain', '+91 98200 44444', 'Mumbai Sassoon Dock'],
        ['captain_rahul', $password_hash, 'Capt. Rahul OceanStar', 'captain', '+91 98200 55555', 'Mumbai Sassoon Dock'],
        ['crew_anil', $password_hash, 'Anil Crewman', 'crew', '+91 98200 66666', 'Mumbai Sassoon Dock'],
        ['public_buyer', $password_hash, 'Gourmet Fish Market Buyer', 'public', '+91 98200 77777', 'Mumbai Sassoon Dock'],
    ];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, phone, harbour_name) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $stmt->execute($user);
    }

    // 2. Seed Vessels
    $vessels = [
        ['IND-MH-1024', 'M.V. Sagar Ratna', 24.50, 'Trawler', 'Mumbai Sassoon Dock', 4, 'Fishing', 18.9180, 72.8400, 6.5, 140.0],
        ['IND-MH-2048', 'M.V. Matsya Kanya', 18.00, 'Gillnetter', 'Mumbai Sassoon Dock', 5, 'Cruising', 18.9300, 72.8550, 8.2, 90.0],
        ['IND-MH-3090', 'M.V. Ocean Pearl', 32.10, 'Longliner', 'Mumbai Sassoon Dock', NULL, 'Docked', 18.9220, 72.8347, 0.0, 0.0],
        ['IND-MH-4120', 'M.V. Samudra Storm', 15.00, 'Purseseiner', 'Mumbai Sassoon Dock', NULL, 'Distress', 18.8900, 72.8200, 1.2, 210.0],
    ];

    $stmt = $pdo->prepare("INSERT INTO vessels (vessel_code, name, gross_tonnage, gear_type, home_port, captain_user_id, status, lat, lng, speed_knots, heading) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($vessels as $v) {
        $stmt->execute($v);
    }

    // 3. Crew Roster
    $pdo->exec("INSERT INTO crew_roster (vessel_id, user_id, role_on_vessel) VALUES (1, 6, 'First Mate')");

    // 4. Fish Species
    $species = [
        ['Pomfret (Silver)', 'Pelagic', '🐟'],
        ['Kingfish (Surmai)', 'Pelagic', '🐟'],
        ['Prawns (Tiger)', 'Crustacean', '🦐'],
        ['Mackerel (Bangda)', 'Pelagic', '🐟'],
        ['Squid (Calamari)', 'Cephalopod', '🦑'],
        ['Tuna (Yellowfin)', 'Pelagic', '🐟'],
        ['Bombay Duck (Bombil)', 'Demersal', '🐟'],
    ];

    $stmt = $pdo->prepare("INSERT INTO fish_species (name, category, image_icon) VALUES (?, ?, ?)");
    foreach ($species as $s) {
        $stmt->execute($s);
    }

    // 5. Fish Rates
    $rates = [
        [2, 1, 850.00, 'Up', 'Mumbai Sassoon Dock'],
        [2, 2, 620.00, 'Stable', 'Mumbai Sassoon Dock'],
        [2, 3, 750.00, 'Up', 'Mumbai Sassoon Dock'],
        [3, 4, 180.00, 'Down', 'Mumbai Sassoon Dock'],
        [3, 5, 420.00, 'Stable', 'Mumbai Sassoon Dock'],
        [3, 6, 350.00, 'Up', 'Mumbai Sassoon Dock'],
    ];

    $stmt = $pdo->prepare("INSERT INTO fish_rates (dalal_user_id, species_id, price_per_kg, market_trend, harbour_name) VALUES (?, ?, ?, ?, ?)");
    foreach ($rates as $r) {
        $stmt->execute($r);
    }

    // 6. Catch Declarations
    $pdo->exec("INSERT INTO catch_declarations (vessel_id, captain_user_id, species_id, estimated_qty_kg, estimated_eta, target_dalal_id, status) VALUES 
        (1, 4, 1, 350.0, datetime('now', '+3 hours'), 2, 'Pending'),
        (1, 4, 2, 500.0, datetime('now', '+3 hours'), 2, 'Pending'),
        (2, 5, 4, 1200.0, datetime('now', '+1 hours'), 3, 'Accepted')
    ");

    // 7. Chat Messages
    $pdo->exec("INSERT INTO chat_messages (sender_id, receiver_id, channel_type, vessel_id, message) VALUES 
        (4, NULL, 'p2p', 1, 'Good weather south of Light Vessel! Heavy Pomfret shoals reported at 18.91N.'),
        (5, NULL, 'p2p', 2, 'Thanks Capt Vikram! Heading south now.'),
        (4, 2, 'dalal', 1, 'Ramesh bhai, bringing 350kg Silver Pomfret and 500kg Surmai. Expect arrival by 6 PM.'),
        (1, NULL, 'broadcast', NULL, 'NOTICE: High Tide advisory issued for 20:00 hrs. All docked vessels check mooring lines.')
    ");

    // 8. SOS Alerts
    $pdo->exec("INSERT INTO sos_alerts (vessel_id, captain_user_id, lat, lng, emergency_type, description, status) VALUES 
        (4, 4, 18.8900, 72.8200, 'Engine Failure', 'Main propeller shaft jammed 5 NM off Mumbai Light. Request immediate tug assistance.', 'ACTIVE')
    ");

    // 9. Leaderboard Scores
    $pdo->exec("INSERT INTO leaderboard_scores (user_id, vessel_id, game_name, score, harbour_name) VALUES 
        (4, 1, 'Fishing Puzzle Sim', 14500, 'Mumbai Sassoon Dock'),
        (6, 1, 'Navigation Reaction Test', 980, 'Mumbai Sassoon Dock'),
        (5, 2, 'Fishing Puzzle Sim', 12800, 'Mumbai Sassoon Dock')
    ");

    // 10. Announcements
    $pdo->exec("INSERT INTO announcements (title, content, priority, created_by) VALUES 
        ('Harbour Maintenance Alert', 'Pier 3 will undergo depth dredging tomorrow from 06:00 to 12:00. Please clear access lanes.', 'High', 1),
        ('New Maritime Safety Mandate', 'All vessel captains must ensure GPS AIS transmitters remain powered on during night fishing.', 'Medium', 1)
    ");

    // 11. Ads
    $ads = [
        ['OceanNav Electronics Corp', 'sales@oceannav.com', 'banner', 'Heavy Duty Marine Radar & AIS GPS Transmitters', 'Upgrade your boat safety with 30% discount on solar AIS transponders.', 'https://picsum.photos/1200/250?maritime', 'https://oceannav.example.com', 'Approved', 1420, 118],
        ['SeaIce Cold Chains Ltd', 'info@seaice.com', 'frontpage', 'Ultra-Fast Harbour Blast Chillers & Ice Supplies', 'Ensure premium catch freshness with automated port ice dispenser tokens.', 'https://picsum.photos/800/350?ocean', 'https://seaice.example.com', 'Approved', 2890, 245],
        ['Maritime Diesel & Gear', 'support@maritimediesel.com', 'sidebar', 'Marine Diesel Engine Services & Spare Parts', 'Free harbor inspection for registered vessel fleets.', 'https://picsum.photos/600/300?boat', 'https://maritimediesel.example.com', 'Approved', 850, 64],
        ['Coastal Insurance Hub', 'policy@coastalinsure.com', 'dashboard', 'Comprehensive Vessel & Crew Marine Risk Coverage', 'Instant claims settlement for storm engine damages.', 'https://picsum.photos/800/250?ship', 'https://coastalinsure.example.com', 'Approved', 1940, 152]
    ];

    $stmt = $pdo->prepare("INSERT INTO ads (company_name, contact_email, ad_type, title, description, image_url, target_url, status, views_count, clicks_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($ads as $ad) {
        $stmt->execute($ad);
    }
}

// Auto run initializer
initialize_database();
