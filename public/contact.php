<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields (Name, Email, Message).";
    } else {
        $success_msg = "Thank you, " . htmlspecialchars($name) . "! Your message has been routed to our Harbour Operations & Support team. We will respond within 24 hours.";
    }
}

$page_title = "Contact Us & Harbour Operations Support";
?>

<div class="bg-maritime text-white py-5 border-bottom border-secondary mb-4">
    <div class="container text-center">
        <span class="badge bg-info text-dark text-uppercase px-3 py-2 mb-2 fw-bold" style="letter-spacing: 1.5px;">Get In Touch</span>
        <h1 class="fw-bold display-5">Contact Harbour Operations</h1>
        <p class="lead text-light max-w-750 mx-auto">
            Have questions about fleet integration, Dalal rate subscriptions, or maritime distress support? Reach out to our dedicated operations team.
        </p>
    </div>
</div>

<div class="container pb-5">

    <div class="row g-4">

        <!-- Contact Form Column -->
        <div class="col-lg-7">
            <div class="card bg-dark-eval border-secondary shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-white fw-bold mb-3"><i class="bi bi-envelope-paper text-info me-2"></i>Send Us a Message</h3>

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
                                <label class="form-label text-light">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control bg-dark text-white border-secondary" placeholder="e.g. Capt. Rajesh Kumar" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-light">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control bg-dark text-white border-secondary" placeholder="e.g. rajesh@maritime.com" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-light">User Role / Stakeholder</label>
                                <select name="role" class="form-select bg-dark text-white border-secondary">
                                    <option value="Captain / Crew">Boat Captain / Crew</option>
                                    <option value="Dalal / Agent">Dalal / Commission Agent</option>
                                    <option value="Public Buyer">Public Buyer / Seafood Business</option>
                                    <option value="Harbour Authority">Harbour Authority / Port Admin</option>
                                    <option value="Investor / Press">Investor / Media</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-light">Subject</label>
                                <input type="text" name="subject" class="form-control bg-dark text-white border-secondary" placeholder="e.g. Vessel Hardware Setup">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-light">Message Details <span class="text-danger">*</span></label>
                            <textarea name="message" rows="5" class="form-control bg-dark text-white border-secondary" placeholder="Describe your inquiry, harbour port location, or system feedback..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-info btn-lg fw-bold text-dark px-4">
                            <i class="bi bi-send me-2"></i>Send Inquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Cards & Hotlines Column -->
        <div class="col-lg-5">
            
            <!-- Emergency Red Card -->
            <div class="card bg-maritime border-danger shadow-sm mb-4">
                <div class="card-body p-4 text-center">
                    <span class="badge bg-danger text-uppercase px-3 py-2 mb-2">24/7 Maritime Crisis Emergency</span>
                    <h4 class="text-white fw-bold mb-2"><i class="bi bi-broadcast text-danger me-2"></i>Emergency SOS Hotline</h4>
                    <p class="text-light small mb-3">For immediate distress at sea, engine breakdown, or medical evacuation, trigger the in-app SOS or dial directly:</p>
                    <div class="p-3 bg-dark rounded border border-danger">
                        <h3 class="text-warning fw-bold mb-0">1554 / +91 (22) 2202-0000</h3>
                        <small class="text-muted">Coast Guard & Port Rescue Control</small>
                    </div>
                </div>
            </div>

            <!-- HQ Details Card -->
            <div class="card bg-dark-eval border-secondary shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="text-white fw-bold mb-3"><i class="bi bi-geo-alt text-info me-2"></i>Headquarters & Port Desks</h4>
                    
                    <div class="d-flex mb-3">
                        <div class="fs-4 text-info me-3"><i class="bi bi-building"></i></div>
                        <div>
                            <h6 class="text-white mb-0">Main Administrative Office</h6>
                            <p class="small text-muted mb-0">Dockmaster Complex, Gate 4, Sassoon Docks, Colaba, Mumbai, MH - 400005</p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="fs-4 text-info me-3"><i class="bi bi-telephone"></i></div>
                        <div>
                            <h6 class="text-white mb-0">General Support Line</h6>
                            <p class="small text-muted mb-0">+91 (22) 5550-FISHER (Mon - Sat, 6:00 AM - 8:00 PM)</p>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="fs-4 text-info me-3"><i class="bi bi-envelope"></i></div>
                        <div>
                            <h6 class="text-white mb-0">Email Inquiries</h6>
                            <p class="small text-muted mb-0">support@projectfisherman.org / dalals@projectfisherman.org</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Sponsor Ad Banner -->
    <?= render_ad_banner('sidebar') ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
