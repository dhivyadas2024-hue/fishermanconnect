<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'public');
    $phone = trim($_POST['phone'] ?? '');
    $harbour_name = trim($_POST['harbour_name'] ?? 'Mumbai Sassoon Dock');

    $valid_roles = ['public', 'captain', 'crew', 'dalal', 'admin'];
    if (!in_array($role, $valid_roles)) {
        $role = 'public';
    }

    if ($username && $password && $full_name) {
        $pdo = get_db_connection();
        
        // Check existing username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            set_flash('danger', 'Username already taken. Please choose another.');
        } else {
            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            // Default pending for specialized operational roles (captain, dalal, crew)
            $status = in_array($role, ['captain', 'dalal', 'crew']) ? 'Pending' : 'Approved';

            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, phone, harbour_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_pass, $full_name, $role, $phone, $harbour_name, $status]);
            
            $user_id = $pdo->lastInsertId();

            if ($status === 'Pending') {
                set_flash('warning', 'Registration submitted! Your ' . strtoupper($role) . ' account is pending verification and approval by Harbour Admin.');
                header('Location: /login.php');
                exit;
            } else {
                $_SESSION['user'] = [
                    'id' => $user_id,
                    'username' => $username,
                    'full_name' => $full_name,
                    'role' => $role,
                    'phone' => $phone,
                    'harbour_name' => $harbour_name,
                    'status' => 'Approved'
                ];

                set_flash('success', 'Registration successful! Welcome to Project Fisherman.');
                header('Location: /public/index.php');
                exit;
            }
        }
    } else {
        set_flash('warning', 'Please fill in all required fields.');
    }
}

$page_title = "Register User Account";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6">
        <div class="card card-maritime p-4 shadow">
            <h3 class="fw-bold text-navy text-center mb-1"><i class="bi bi-person-plus-fill"></i> User Registration</h3>
            <p class="text-muted text-center small mb-4">Join the Maritime & Harbour Ecosystem</p>

            <form method="POST" action="/register.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. ramesh_k" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+91 98000 00000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Account Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="public">Public Consumer / Local Buyer</option>
                            <option value="captain">Boat Captain</option>
                            <option value="crew">Crew Member</option>
                            <option value="dalal">Dalal (Commission Agent)</option>
                            <option value="admin">Harbour / Rescue Authority Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Home Harbour Port</label>
                        <input type="text" name="harbour_name" class="form-control" value="Mumbai Sassoon Dock">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mt-4 fw-bold">Complete Registration &raquo;</button>
            </form>

            <div class="text-center mt-3">
                <span class="text-muted small">Already have an account?</span> 
                <a href="/login.php" class="fw-bold">Sign in here</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
