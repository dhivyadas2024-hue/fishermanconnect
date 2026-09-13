<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    set_flash('info', 'Please sign in to access inter-vessel communications.');
    header('Location: /login.php');
    exit;
}

$pdo = get_db_connection();
$user = current_user();

$active_channel = $_GET['channel'] ?? 'p2p';
if (!in_array($active_channel, ['p2p', 'dalal', 'broadcast'])) {
    $active_channel = 'p2p';
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_send_message'])) {
    $message = trim($_POST['message'] ?? '');
    $channel_type = trim($_POST['channel_type'] ?? 'p2p');
    $receiver_id = !empty($_POST['receiver_id']) ? intval($_POST['receiver_id']) : null;
    $vessel_id = !empty($_POST['vessel_id']) ? intval($_POST['vessel_id']) : null;

    if ($message) {
        // Find user's vessel if not explicitly passed
        if (!$vessel_id) {
            $stmt_v = $pdo->prepare("SELECT id FROM vessels WHERE captain_user_id = ? LIMIT 1");
            $stmt_v->execute([$user['id']]);
            $vessel_id = $stmt_v->fetchColumn() ?: null;
        }

        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, channel_type, vessel_id, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $receiver_id, $channel_type, $vessel_id, $message]);
        set_flash('success', 'Message transmitted!');
    }
    header("Location: /chat/index.php?channel={$channel_type}");
    exit;
}

// Fetch Messages for current channel
$query = "
    SELECT m.*, u.full_name as sender_name, u.role as sender_role, v.name as vessel_name, v.vessel_code
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.id
    LEFT JOIN vessels v ON m.vessel_id = v.id
    WHERE m.channel_type = ?
    ORDER BY m.created_at ASC
";
$stmt_msg = $pdo->prepare($query);
$stmt_msg->execute([$active_channel]);
$messages = $stmt_msg->fetchAll();

// Fetch Dalals for Boat-to-Dalal Direct messaging selector
$dalals = $pdo->query("SELECT id, full_name, phone FROM users WHERE role = 'dalal'")->fetchAll();

// Fetch User's primary vessel
$user_vessel = $pdo->prepare("SELECT * FROM vessels WHERE captain_user_id = ? LIMIT 1");
$user_vessel->execute([$user['id']]);
$my_vessel = $user_vessel->fetch();

$page_title = "Inter-Vessel Communication Hub";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mb-4">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="fw-bold text-navy mb-0"><i class="bi bi-chat-quote-fill text-primary me-2"></i> Inter-Vessel & Harbour Connectivity Desk</h2>
                <p class="text-muted mb-0">Secure maritime networking channels connecting boat crews, Dalals, and harbour authorities.</p>
            </div>
            <?php if ($my_vessel): ?>
                <span class="badge bg-primary fs-6 p-2"><i class="bi bi-ship me-1"></i> Transmitting from: <?= sanitize($my_vessel['name']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Channel Selector Navigation Pills -->
        <ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 shadow-sm border mb-4">
            <li class="nav-item">
                <a class="nav-link fw-bold <?= $active_channel === 'p2p' ? 'active bg-primary' : 'text-dark' ?>" href="/chat/index.php?channel=p2p">
                    <i class="bi bi-people-fill me-1"></i> Peer-to-Peer Inter-Vessel Chat
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold <?= $active_channel === 'dalal' ? 'active bg-success' : 'text-dark' ?>" href="/chat/index.php?channel=dalal">
                    <i class="bi bi-shop me-1"></i> Boat-to-Dalal Direct Messaging
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold <?= $active_channel === 'broadcast' ? 'active bg-warning text-dark' : 'text-dark' ?>" href="/chat/index.php?channel=broadcast">
                    <i class="bi bi-broadcast me-1"></i> Authority Broadcasts & Notices
                </a>
            </li>
        </ul>

        <!-- Chat Stream Box -->
        <div class="card card-maritime shadow-sm overflow-hidden">
            <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold fs-6">
                    <?php if ($active_channel === 'p2p'): ?>
                        <i class="bi bi-compass me-2"></i> Channel: Inter-Vessel Fishing Ground & Navigation Insights
                    <?php elseif ($active_channel === 'dalal'): ?>
                        <i class="bi bi-cash-stack me-2"></i> Channel: Catch Negotiation & Pricing Direct Line
                    <?php else: ?>
                        <i class="bi bi-megaphone-fill me-2 text-warning"></i> Channel: Harbour Official Broadcast Channel
                    <?php endif; ?>
                </span>
                <span class="badge bg-light text-dark"><i class="bi bi-wifi text-success me-1"></i> Live Mesh Link</span>
            </div>

            <div class="card-body bg-light p-4" style="min-height: 380px; max-height: 500px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-dots fs-1 d-block text-secondary mb-2"></i>
                        No messages in this channel yet. Send the first transmission below!
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): 
                        $is_me = ($msg['sender_id'] == $user['id']);
                        $role_badge = 'bg-secondary';
                        if ($msg['sender_role'] === 'captain') $role_badge = 'bg-primary';
                        elseif ($msg['sender_role'] === 'dalal') $role_badge = 'bg-success';
                        elseif ($msg['sender_role'] === 'admin') $role_badge = 'bg-danger';
                    ?>
                        <div class="d-flex mb-3 <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="p-3 rounded-3 shadow-sm <?= $is_me ? 'bg-primary text-white' : 'bg-white text-dark' ?>" style="max-width: 75%; border: 1px solid rgba(0,0,0,0.08);">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="<?= $is_me ? 'text-white' : 'text-navy' ?> small"><?= sanitize($msg['sender_name']) ?></strong>
                                    <span class="badge <?= $role_badge ?> text-uppercase" style="font-size: 0.65rem;"><?= sanitize($msg['sender_role']) ?></span>
                                    <?php if ($msg['vessel_name']): ?>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">⛵ <?= sanitize($msg['vessel_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fs-6"><?= nl2br(sanitize($msg['message'])) ?></div>
                                <div class="text-end mt-1" style="font-size: 0.7rem; opacity: 0.8;">
                                    <?= time_ago($msg['created_at']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Input Footer -->
            <div class="card-footer bg-white p-3">
                <form method="POST" action="/chat/index.php?channel=<?= $active_channel ?>">
                    <input type="hidden" name="action_send_message" value="1">
                    <input type="hidden" name="channel_type" value="<?= $active_channel ?>">
                    <?php if ($my_vessel): ?>
                        <input type="hidden" name="vessel_id" value="<?= $my_vessel['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-2">
                        <?php if ($active_channel === 'dalal'): ?>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <select name="receiver_id" class="form-select form-select-sm" required>
                                    <option value="">-- Select Dalal Agent --</option>
                                    <?php foreach ($dalals as $dal): ?>
                                        <option value="<?= $dal['id'] ?>"><?= sanitize($dal['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                        <?php else: ?>
                            <div class="col-12">
                        <?php endif; ?>
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type transmission message (e.g. Weather updates, catch inquiries, navigation alert)..." required>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-send-fill me-1"></i> Transmit
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
