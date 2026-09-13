<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
$user = current_user();

// Handle Action: Submit Score to Leaderboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_score'])) {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to post scores to harbour leaderboards.');
        header('Location: /login.php');
        exit;
    }

    $game_name = trim($_POST['game_name']);
    $score = intval($_POST['score']);
    $harbour_name = trim($_POST['harbour_name'] ?? 'Mumbai Sassoon Dock');

    // Find user's vessel if assigned
    $stmt_v = $pdo->prepare("SELECT id FROM vessels WHERE captain_user_id = ? LIMIT 1");
    $stmt_v->execute([$user['id']]);
    $vessel_id = $stmt_v->fetchColumn() ?: null;

    if ($game_name && $score > 0) {
        $stmt = $pdo->prepare("INSERT INTO leaderboard_scores (user_id, vessel_id, game_name, score, harbour_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $vessel_id, $game_name, $score, $harbour_name]);
        set_flash('success', "High Score of {$score} synced to Harbour Leaderboard for {$game_name}!");
    }
    header('Location: /entertainment/index.php?tab=leaderboard');
    exit;
}

$active_tab = $_GET['tab'] ?? 'games';

// Fetch Leaderboards
$stmt_lead = $pdo->query("
    SELECT l.*, u.full_name, v.name as vessel_name
    FROM leaderboard_scores l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN vessels v ON l.vessel_id = v.id
    ORDER BY l.score DESC LIMIT 20
");
$leaderboards = $stmt_lead->fetchAll();

$page_title = "Onboard Solitary Entertainment & Solace Module";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card card-maritime bg-dark text-white p-4 mb-4" style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge bg-warning text-dark fw-bold mb-2"><i class="bi bi-controller"></i> OFFSHORE WELLBEING & SOLACE MODULE</span>
            <h1 class="display-6 fw-bold mb-1">Onboard Solitary & Mesh Entertainment</h1>
            <p class="mb-0 opacity-90">
                Mitigating fatigue and isolation during extended offshore voyages with offline single-player games, low-bandwidth audiobooks, local mesh multiplayer, and asynchronous harbour leaderboards.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-success p-2 fs-6"><i class="bi bi-battery-charging"></i> Low-Power Night Watch Mode</span>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 shadow-sm border mb-4">
    <li class="nav-item">
        <a class="nav-link fw-bold <?= $active_tab === 'games' ? 'active bg-primary' : 'text-dark' ?>" href="/entertainment/index.php?tab=games">
            <i class="bi bi-joystick me-1"></i> Interactive Minigames
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold <?= $active_tab === 'leaderboard' ? 'active bg-success' : 'text-dark' ?>" href="/entertainment/index.php?tab=leaderboard">
            <i class="bi bi-trophy-fill me-1"></i> Harbour Leaderboards
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold <?= $active_tab === 'mesh' ? 'active bg-info text-dark' : 'text-dark' ?>" href="/entertainment/index.php?tab=mesh">
            <i class="bi bi-cpu-fill me-1"></i> Offline Mesh Multiplayer
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-bold <?= $active_tab === 'media' ? 'active bg-secondary' : 'text-dark' ?>" href="/entertainment/index.php?tab=media">
            <i class="bi bi-headphones me-1"></i> Audiobooks & Podcasts
        </a>
    </li>
</ul>

<?php if ($active_tab === 'games'): ?>
<!-- Playable Mini Games -->
<div class="row g-4">
    <!-- Fishing Catch Puzzle Game -->
    <div class="col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100">
            <h4 class="fw-bold text-navy mb-2"><i class="bi bi-tsunami text-primary me-2"></i> Fishing Catch Puzzle Sim</h4>
            <p class="text-muted small">Tap to cast net and haul fish shoals before night watch timer runs out!</p>

            <div class="bg-light p-4 rounded text-center my-3 border">
                <div class="fs-1 my-2" id="fishIcon">🐟</div>
                <div class="fs-4 fw-bold text-success mb-2">Score: <span id="gameScore">0</span> pts</div>
                <div class="text-muted mb-3" style="font-size: 0.85rem;">Time Remaining: <span id="gameTimer" class="fw-bold text-danger">30</span>s</div>

                <button id="btnCastNet" class="btn btn-primary btn-lg fw-bold px-4 py-2 my-2">
                    <i class="bi bi-bounding-box-circles me-1"></i> Cast Net / Catch Fish!
                </button>

                <form method="POST" id="scoreForm" class="d-none mt-3">
                    <input type="hidden" name="action_submit_score" value="1">
                    <input type="hidden" name="game_name" value="Fishing Puzzle Sim">
                    <input type="hidden" name="score" id="finalScoreInput" value="0">
                    <button type="submit" class="btn btn-success fw-bold w-100"><i class="bi bi-cloud-upload"></i> Sync High Score to Leaderboard</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Navigation Reaction Speed Test -->
    <div class="col-md-6">
        <div class="card card-maritime p-4 shadow-sm h-100">
            <h4 class="fw-bold text-navy mb-2"><i class="bi bi-compass-fill text-warning me-2"></i> Navigation Reaction Test</h4>
            <p class="text-muted small">Test captain reaction time when navigational light signals change color!</p>

            <div class="bg-light p-4 rounded text-center my-3 border">
                <div id="lightSignal" class="p-4 rounded-circle mx-auto my-3 bg-danger text-white fs-3 fw-bold shadow-sm" style="width: 120px; height: 120px; line-height: 70px;">
                    WAIT
                </div>
                <div class="fs-5 fw-bold text-navy my-2" id="reactionResult">Click Start to Begin</div>

                <button id="btnStartReaction" class="btn btn-warning fw-bold px-4 my-2">Start Reaction Test</button>

                <form method="POST" id="reactionScoreForm" class="d-none mt-3">
                    <input type="hidden" name="action_submit_score" value="1">
                    <input type="hidden" name="game_name" value="Navigation Reaction Test">
                    <input type="hidden" name="score" id="reactionScoreInput" value="0">
                    <button type="submit" class="btn btn-success fw-bold w-100"><i class="bi bi-cloud-upload"></i> Sync Score to Leaderboard</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Fishing Game Script
let score = 0;
let timeLeft = 30;
let gameInterval = null;
let gameStarted = false;

document.getElementById('btnCastNet').addEventListener('click', function() {
    if (!gameStarted) {
        gameStarted = true;
        score = 0;
        timeLeft = 30;
        document.getElementById('gameScore').innerText = score;
        document.getElementById('scoreForm').classList.add('d-none');
        
        gameInterval = setInterval(function() {
            timeLeft--;
            document.getElementById('gameTimer').innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(gameInterval);
                gameStarted = false;
                alert('Time up! Your score: ' + score);
                document.getElementById('finalScoreInput').value = score;
                document.getElementById('scoreForm').classList.remove('d-none');
            }
        }, 1000);
    }
    if (timeLeft > 0) {
        score += 100;
        document.getElementById('gameScore').innerText = score;
        const icons = ['🐟', '🐠', '🦐', '🦑', '🦈'];
        document.getElementById('fishIcon').innerText = icons[Math.floor(Math.random() * icons.length)];
    }
});

// Reaction Test Script
let startTime = 0;
let reactionTimer = null;
const signalEl = document.getElementById('lightSignal');
const resEl = document.getElementById('reactionResult');
const btnStart = document.getElementById('btnStartReaction');

btnStart.addEventListener('click', function() {
    resEl.innerText = "Wait for GREEN signal...";
    signalEl.className = "p-4 rounded-circle mx-auto my-3 bg-danger text-white fs-3 fw-bold shadow-sm";
    signalEl.innerText = "WAIT";

    const delay = Math.floor(Math.random() * 3000) + 1500;
    setTimeout(function() {
        startTime = Date.now();
        signalEl.className = "p-4 rounded-circle mx-auto my-3 bg-success text-white fs-3 fw-bold shadow-sm";
        signalEl.innerText = "CLICK!";
    }, delay);
});

signalEl.addEventListener('click', function() {
    if (startTime > 0) {
        const elapsed = Date.now() - startTime;
        const scoreVal = Math.max(1, 1500 - elapsed);
        resEl.innerText = "Reaction Time: " + elapsed + " ms! Score: " + scoreVal;
        document.getElementById('reactionScoreInput').value = scoreVal;
        document.getElementById('reactionScoreForm').classList.remove('d-none');
        startTime = 0;
    }
});
</script>

<?php elseif ($active_tab === 'leaderboard'): ?>
<!-- Harbour Leaderboards -->
<div class="card card-maritime p-4 shadow-sm">
    <h4 class="fw-bold text-navy mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i> Asynchronous Harbour Leaderboards</h4>
    <p class="text-muted small mb-3">Compete across all vessels in harbour. High scores stored offline automatically sync upon docking or re-entering cellular range.</p>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Rank</th>
                    <th>Crew Member / Captain</th>
                    <th>Vessel</th>
                    <th>Game / Sim</th>
                    <th>High Score</th>
                    <th>Date Logged</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboards as $idx => $lb): ?>
                <tr>
                    <td>
                        <?php if ($idx == 0): ?>
                            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-award-fill"></i> 1st</span>
                        <?php elseif ($idx == 1): ?>
                            <span class="badge bg-secondary fs-6"><i class="bi bi-award-fill"></i> 2nd</span>
                        <?php elseif ($idx == 2): ?>
                            <span class="badge bg-danger fs-6"><i class="bi bi-award-fill"></i> 3rd</span>
                        <?php else: ?>
                            <strong class="text-muted ms-2">#<?= $idx + 1 ?></strong>
                        <?php endif; ?>
                    </td>
                    <td><strong class="text-navy"><?= sanitize($lb['full_name']) ?></strong></td>
                    <td><?= $lb['vessel_name'] ? sanitize($lb['vessel_name']) : '<span class="text-muted">General</span>' ?></td>
                    <td><span class="badge bg-light text-dark border"><?= sanitize($lb['game_name']) ?></span></td>
                    <td><span class="fs-5 fw-bold text-success"><?= number_format($lb['score']) ?></span> pts</td>
                    <td><small class="text-muted"><?= time_ago($lb['created_at']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($active_tab === 'mesh'): ?>
<!-- Offline Multi-Vessel Minigames Mesh Simulator -->
<div class="card card-maritime p-4 shadow-sm">
    <h4 class="fw-bold text-navy mb-2"><i class="bi bi-cpu-fill text-info me-2"></i> Local Mesh Multi-Vessel Lobbies</h4>
    <p class="text-muted small mb-4">Device-to-Device local Wi-Fi / Bluetooth P2P games connecting nearby boats at sea without internet.</p>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-3 border-primary h-100 shadow-sm text-center">
                <span class="fs-1 text-primary">♟️</span>
                <h5 class="fw-bold mt-2">Offshore Maritime Chess</h5>
                <p class="small text-muted">Play turn-based chess with crew on neighboring vessels within 2 NM mesh range.</p>
                <button class="btn btn-outline-primary btn-sm fw-bold mt-auto" onclick="alert('Searching for nearby Bluetooth/Wi-Fi mesh chess hosts...');">Host Mesh Room</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-success h-100 shadow-sm text-center">
                <span class="fs-1 text-success">🃏</span>
                <h5 class="fw-bold mt-2">Boat Deck Card Tournament</h5>
                <p class="small text-muted">Multiplayer card game supporting 4 vessels simultaneously over local mesh.</p>
                <button class="btn btn-outline-success btn-sm fw-bold mt-auto" onclick="alert('Joining local mesh card table...');">Join Table</button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-warning h-100 shadow-sm text-center">
                <span class="fs-1 text-warning">❓</span>
                <h5 class="fw-bold mt-2">Nautical Quiz & Trivia</h5>
                <p class="small text-muted">Test navigation knowledge and sea lore against peer crews.</p>
                <button class="btn btn-outline-warning text-dark btn-sm fw-bold mt-auto" onclick="alert('Trivia session starting!');">Start Quiz</button>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Single Player Offline Media Library & Audiobooks -->
<div class="card card-maritime p-4 shadow-sm">
    <h4 class="fw-bold text-navy mb-2"><i class="bi bi-headphones text-secondary me-2"></i> Low-Power Offline Media Library</h4>
    <p class="text-muted small mb-4">Curated low-bitrate audiobooks and sea stories optimized for night watches and solitary rest.</p>

    <div class="list-group">
        <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
            <div>
                <strong class="text-navy">🎙️ Night Watch Navigation Lore & Weather Reading</strong>
                <div class="small text-muted">Audiobook Episode 1 • 24 mins • Low Power MP3</div>
            </div>
            <audio controls style="max-width: 250px;">
                <source src="data:audio/mp3;base64," type="audio/mp3">
                Audio not supported
            </audio>
        </div>
        <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
            <div>
                <strong class="text-navy">📻 Deep Sea Stories & Solitary Sea Songs</strong>
                <div class="small text-muted">Audiobook Episode 2 • 38 mins • Low Power MP3</div>
            </div>
            <audio controls style="max-width: 250px;">
                <source src="data:audio/mp3;base64," type="audio/mp3">
                Audio not supported
            </audio>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
