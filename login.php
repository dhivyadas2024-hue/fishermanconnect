<?php
require_once __DIR__ . '/includes/functions.php';

// Quick demo login handler via URL parameter
if (isset($_GET['demo'])) {
    $demo_role = $_GET['demo'];
    $pdo = get_db_connection();
    
    $username_map = [
        'admin' => 'admin',
        'dalal' => 'dalal1',
        'captain' => 'captain_vikram',
        'crew' => 'crew_anil',
        'public' => 'public_buyer'
    ];

    $target_user = $username_map[$demo_role] ?? 'public_buyer';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$target_user]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        set_flash('success', 'Logged in as Demo User: ' . $user['full_name'] . ' (' . strtoupper($user['role']) . ')');
        header('Location: /public/index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $user_status = $user['status'] ?? 'Approved';
            if ($user_status === 'Pending') {
                set_flash('warning', 'Your registration is currently pending Harbour Admin review and approval.');
            } elseif ($user_status === 'Rejected') {
                set_flash('danger', 'Your registration request was rejected by Harbour Administration.');
            } else {
                $_SESSION['user'] = $user;
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                
                if ($user['role'] === 'admin') header('Location: /admin/fleet.php');
                elseif ($user['role'] === 'dalal') header('Location: /dalal/rates.php');
                elseif ($user['role'] === 'captain') header('Location: /captain/catch_declaration.php');
                else header('Location: /public/index.php');
                exit;
            }
        } else {
            set_flash('danger', 'Invalid username or password.');
        }
    } else {
        set_flash('warning', 'Please enter username and password.');
    }
}

$page_title = "User Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-5">
        <div class="card card-maritime p-4 shadow">
            <div class="text-center mb-4">
                <span class="fs-1 text-primary">⚓</span>
                <h3 class="fw-bold text-navy">Project Fisherman Login</h3>
                <p class="text-muted small">Access Harbour, Vessel & Auction Dashboard</p>
            </div>

            <form method="POST" action="/login.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. captain_vikram" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Sign In &raquo;</button>
            </form>

            <hr class="my-4">

            <div>
                <h6 class="fw-bold text-center text-muted mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Demo Logins</h6>
                <div class="d-grid gap-2">
                    <a href="/login.php?demo=admin" class="btn btn-outline-dark btn-sm text-start">
                        <i class="bi bi-shield-lock-fill me-2 text-danger"></i> <strong>Harbour Admin Officer</strong> (Full Control)
                    </a>
                    <a href="/login.php?demo=dalal" class="btn btn-outline-dark btn-sm text-start">
                        <i class="bi bi-cash-stack me-2 text-success"></i> <strong>Ramesh Dalal</strong> (Commission Agent / Auction)
                    </a>
                    <a href="/login.php?demo=captain" class="btn btn-outline-dark btn-sm text-start">
                        <i class="bi bi-compass-fill me-2 text-primary"></i> <strong>Capt. Vikram</strong> (Vessel & Catch Manager)
                    </a>
                    <a href="/login.php?demo=crew" class="btn btn-outline-dark btn-sm text-start">
                        <i class="bi bi-person-badge me-2 text-info"></i> <strong>Anil Crewman</strong> (Peer Chat & Games)
                    </a>
                    <a href="/login.php?demo=public" class="btn btn-outline-dark btn-sm text-start">
                        <i class="bi bi-basket me-2 text-secondary"></i> <strong>Public Fish Buyer</strong> (Live Rates)
                    </a>
                </div>
            </div>

            <div class="text-center mt-3">
                <span class="text-muted small">Don't have an account?</span> 
                <a href="/register.php" class="fw-bold">Register here</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
