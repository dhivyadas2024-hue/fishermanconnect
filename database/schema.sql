-- Project Fisherman Database Schema

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'public', -- 'public', 'captain', 'crew', 'dalal', 'admin'
    phone VARCHAR(20),
    harbour_name VARCHAR(100) DEFAULT 'Mumbai Sassoon Dock',
    status VARCHAR(20) DEFAULT 'Approved', -- 'Pending', 'Approved', 'Rejected'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vessels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vessel_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gross_tonnage DECIMAL(10,2) NOT NULL DEFAULT 15.0,
    gear_type VARCHAR(100) NOT NULL DEFAULT 'Gillnet & Trawl',
    home_port VARCHAR(100) NOT NULL DEFAULT 'Mumbai Sassoon Dock',
    captain_user_id INTEGER NULL,
    status VARCHAR(20) DEFAULT 'Docked', -- 'Docked', 'At Sea', 'Fishing', 'Anchored', 'Distress'
    lat DECIMAL(10,6) DEFAULT 18.9220,
    lng DECIMAL(10,6) DEFAULT 72.8347,
    speed_knots DECIMAL(5,2) DEFAULT 0.0,
    heading DECIMAL(5,2) DEFAULT 0.0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(captain_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS crew_roster (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vessel_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role_on_vessel VARCHAR(50) DEFAULT 'Deckhand',
    FOREIGN KEY(vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS fish_species (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) DEFAULT 'Pelagic',
    image_icon VARCHAR(50) DEFAULT '🐟'
);

CREATE TABLE IF NOT EXISTS fish_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dalal_user_id INTEGER NOT NULL,
    species_id INTEGER NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    market_trend VARCHAR(20) DEFAULT 'Stable', -- 'Up', 'Down', 'Stable'
    harbour_name VARCHAR(100) DEFAULT 'Mumbai Sassoon Dock',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(dalal_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(species_id) REFERENCES fish_species(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS catch_declarations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vessel_id INTEGER NOT NULL,
    captain_user_id INTEGER NOT NULL,
    species_id INTEGER NOT NULL,
    estimated_qty_kg DECIMAL(10,2) NOT NULL,
    estimated_eta DATETIME NOT NULL,
    target_dalal_id INTEGER NULL,
    status VARCHAR(20) DEFAULT 'Pending', -- 'Pending', 'Accepted', 'Landed'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY(captain_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(species_id) REFERENCES fish_species(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NULL, -- NULL for general/broadcast
    channel_type VARCHAR(20) NOT NULL DEFAULT 'p2p', -- 'p2p', 'dalal', 'broadcast'
    vessel_id INTEGER NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sos_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vessel_id INTEGER NOT NULL,
    captain_user_id INTEGER NOT NULL,
    lat DECIMAL(10,6) NOT NULL,
    lng DECIMAL(10,6) NOT NULL,
    emergency_type VARCHAR(50) NOT NULL, -- 'Engine Failure', 'Severe Weather', 'Medical Crisis', 'Accident'
    description TEXT,
    status VARCHAR(20) DEFAULT 'ACTIVE', -- 'ACTIVE', 'RESOLVED'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY(vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY(captain_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS leaderboard_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    vessel_id INTEGER NULL,
    game_name VARCHAR(50) NOT NULL,
    score INTEGER NOT NULL,
    harbour_name VARCHAR(100) DEFAULT 'Mumbai Sassoon Dock',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'Medium', -- 'Low', 'Medium', 'High', 'Urgent'
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name VARCHAR(150) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    ad_type VARCHAR(50) NOT NULL DEFAULT 'frontpage', -- 'frontpage', 'banner', 'sidebar', 'dashboard'
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT 'https://picsum.photos/800/200?maritime',
    target_url VARCHAR(255) NOT NULL DEFAULT '#',
    status VARCHAR(20) DEFAULT 'Approved', -- 'Pending', 'Approved', 'Rejected'
    views_count INTEGER DEFAULT 0,
    clicks_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
