<?php
require_once 'config.php';
requireAuth(); 

$page_title = "Dashboard";
require_once 'header.php'; 

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- 1. FETCH USER DATA ---
$full_name = "Alumni Member"; 
try {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $user_data = $stmt->fetch();
    
    if ($user_data && !empty($user_data['full_name'])) {
        $full_name = $user_data['full_name'];
    }
} catch (PDOException $e) {
    error_log("Error fetching user name: " . $e->getMessage());
}

// --- 2. HANDLE DONATION SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    try {
        $proj_id = $_POST['project_id'];
        $amount = $_POST['amount'];
        $method = $_POST['payment_method'];
        $msg = $_POST['message'] ?? '';
        $trans_ref = $_POST['transaction_ref'] ?? null;
        
        $payment_proof = null;
        if ($method === 'GCash' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
            $target_dir = "uploads/proofs/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES["payment_proof"]["name"], PATHINFO_EXTENSION);
            $new_filename = "proof_" . time() . "_" . $user_id . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
                $payment_proof = $new_filename;
            }
        }

        $status = 'pending'; 
        $sql = "INSERT INTO donations (user_id, project_id, amount, payment_method, status, transaction_ref, payment_proof, created_at, message) 
                VALUES (:uid, :pid, :amt, :method, :stat, :ref, :proof, NOW(), :msg)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $user_id,
            ':pid' => $proj_id,
            ':amt' => $amount,
            ':method' => $method,
            ':stat' => $status,
            ':ref' => $trans_ref,
            ':proof' => $payment_proof,
            ':msg' => $msg
        ]);

        $message = "Thank you! Your donation has been submitted for verification.";

    } catch (PDOException $e) {
        $error = "System Error: " . $e->getMessage();
    }
}

// --- 3. FETCH DASHBOARD STATS ---
try {
    // A. Financial Stats
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $donations_total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM general_donation WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $general_total = $stmt->fetchColumn();

    $my_donation = $donations_total + $general_total;

    // B. Projects Count
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT project_id) FROM donations WHERE user_id = :uid AND status = 'verified'");
    $stmt->execute([':uid' => $user_id]);
    $projects_supported = $stmt->fetchColumn();

    // C. General Giving
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM general_donation WHERE user_id = :uid AND status = 'verified'");
    $stmt->execute([':uid' => $user_id]);
    $total_contributions = $stmt->fetchColumn();

    // D. Ongoing Projects
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE status = 'active' ORDER BY id DESC");
    $stmt->execute();
    $ongoing_projects = $stmt->fetchAll();

    // E. Recent History
    $history_sql = "
        SELECT 'Project Donation' as type, amount, status, created_at as date, transaction_ref 
        FROM donations WHERE user_id = :uid
        UNION ALL
        SELECT 'General Donation' as type, amount, status, donation_date as date, reference_number as transaction_ref
        FROM general_donation WHERE user_id = :uid
        ORDER BY date DESC LIMIT 5
    ";
    $stmt = $pdo->prepare($history_sql);
    $stmt->execute([':uid' => $user_id]);
    $recent_activities = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $ongoing_projects = [];
    $recent_activities = [];
}

?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary: #800000;       /* Deep Crimson */
        --primary-dark: #4a040b;
        --secondary: #1e293b;     /* Slate */
        --accent: #d4af37;        /* Gold */
        --bg-body: #f8fafc;
        --surface: #ffffff;
        --border: #e2e8f0;
        --text-head: #0f172a;
        --text-body: #64748b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.05);
        --shadow-hover: 0 20px 40px -10px rgba(0,0,0,0.1);
    }

    body {
        font-family: 'Inter', sans-serif;
       background-color: #F9F5EB;
        color: var(--text-body);
        line-height: 1.6;
    }

    .container-xl {
    max-width: 1600px; /* or 100% for full width */
    margin: 0 auto;
    padding: 40px 24px;
}


    /* ALERTS */
    .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 12px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* HERO BANNER */
    .hero-card {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border-radius: 20px;
        padding: 14px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
        margin-bottom: 40px;
    }
    
    .hero-card::after {
        content: "";
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        background-image: radial-gradient(circle at 90% 10%, rgba(255,255,255,0.1) 0%, transparent 60%);
        pointer-events: none;
    }

    .hero-content { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; }
    .hero-text h1 { margin: 0; font-weight: 300; font-size: 1.1rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
    .hero-text h2 { margin: 8px 0 0; font-weight: 700; font-size: 2.5rem; letter-spacing: -1px; }
    
    .badge-pill {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 12px 24px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .badge-icon { font-size: 1.8rem; }
    .badge-info span { display: block; font-size: 0.75rem; text-transform: uppercase; opacity: 0.8; letter-spacing: 1px; }
    .badge-info strong { font-size: 1rem; color: #fff; }

/* STATS GRID */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
    gap: 24px; 
    margin-bottom: 40px; 
}

/* STAT CARDS */
.stat-card {
    background-color: #F2E5C6; /* soft champagne/light beige */
    padding: 24px;
    border-radius: 16px;
    border: 2px solid #560B18;  /* dark maroon for luxury contrast */
    box-shadow: 0 8px 20px -5px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    color: #3B010B; /* deep wine for numbers/headings */
}

.stat-card:hover { 
    transform: translateY(-4px); 
    box-shadow: 0 15px 30px -5px rgba(0,0,0,0.15);
}

/* STAT ICONS */
.stat-icon { 
    width: 48px; height: 48px; 
    border-radius: 12px; 
    display: flex; align-items: center; justify-content: center; 
    font-size: 1.25rem; 
    margin-bottom: 16px; 
}

.bg-crimson { background: #ffffff; color: #75162D; } /* soft gold bg + burgundy icon */
.bg-blue { background: #ffffff; color: #560B18; }    /* champagne bg + dark maroon icon */
.bg-amber { background: #ffffff; color: #3B010B; }   /* sand gold bg + deep wine icon */

/* VALUES AND LABELS */
.stat-value { font-size: 2rem; font-weight: 700; color: #3B010B; }  /* deep wine for numbers */
.stat-label { font-size: 0.875rem; font-weight: 500; color: #560B18; } /* dark maroon for text */


    /* SPLIT SECTION */
    .split-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 48px; }
    
    .content-card { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-soft); height: 100%; display: flex; flex-direction: column; }
    
    .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff; }
    .card-title { font-size: 1rem; font-weight: 700; color: var(--text-head); margin: 0; }
    .card-link { font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600; }
    
    /* TABLE */
    .table-responsive { overflow-x: auto; flex-grow: 1; }
    .table-minimal { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .table-minimal th { text-align: left; padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .table-minimal td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: var(--text-head); }
    .table-minimal tr:last-child td { border-bottom: none; }
    
    .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .st-verified { color: var(--success); background: #d1fae5; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .st-pending { color: var(--warning); background: #fef3c7; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .st-rejected { color: var(--danger); background: #fee2e2; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

    /* PROMO BOX */
    .promo-box {
        background: linear-gradient(180deg, #fffbeb 0%, #fff 100%);
        border: 1px solid #fcd34d;
        padding: 32px;
        text-align: center;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        height: 100%;
    }
    .promo-btn {
        margin-top: 20px;
        background: var(--accent);
        color: #fff;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    }
    .promo-btn:hover { background: #b45309; transform: translateY(-2px); }

    /* PROJECTS */
    .section-head { font-size: 1.5rem; font-weight: 800; color: var(--secondary); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
    .section-head::before { content: ''; width: 6px; height: 28px; background: var(--primary); border-radius: 4px; }
    
    .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
    
    .project-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-soft);
        transition: all 0.3s ease;
        display: flex; flex-direction: column;
    }
    .project-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-hover); }
    
    .pc-img-wrap { height: 200px; position: relative; background: #e2e8f0; }
    .pc-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .pc-tag { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; }
    
    .pc-body { padding: 24px; flex-grow: 1; display: flex; flex-direction: column; }
    .pc-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-head); }
    .pc-desc { font-size: 0.9rem; color: var(--text-body); margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    
    .progress-track { height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
    .progress-fill { height: 100%; background: var(--primary); border-radius: 10px; transition: width 1s ease; }
    
    .btn-block-donate {
        width: 100%;
        background: var(--surface);
        border: 1px solid var(--primary);
        color: var(--primary);
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-block-donate:hover { background: var(--primary); color: white; }

    /* MODAL */
    .modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0; transition: opacity 0.3s;
    }
    .modal-backdrop.active { display: flex; opacity: 1; }
    
    .modal-panel {
        background: white;
        width: 90%; max-width: 900px;
        border-radius: 24px;
        overflow: hidden;
        display: flex;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .modal-backdrop.active .modal-panel { transform: scale(1); }
    
    .modal-media { width: 40%; background: #f8fafc; padding: 40px; border-right: 1px solid var(--border); display: flex; flex-direction: column; justify-content: center; }
    .modal-form-area { width: 60%; padding: 40px; position: relative; max-height: 90vh; overflow-y: auto; }
    
    .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 8px; }
    .form-input, .form-select {
        width: 100%; padding: 12px 16px;
        border: 1px solid #cbd5e1; border-radius: 8px;
        font-size: 0.95rem; background: #fff;
        transition: border 0.2s, box-shadow 0.2s;
    }
    .form-input:focus, .form-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.1); }
    
    .gcash-box { background: #f0fdf4; border: 1px dashed #22c55e; padding: 20px; border-radius: 12px; margin-top: 15px; display: none; }
    
    @media (max-width: 900px) {
        .split-layout { grid-template-columns: 1fr; }
        .hero-content { flex-direction: column; text-align: center; gap: 20px; }
        .modal-panel { flex-direction: column; }
        .modal-media, .modal-form-area { width: 100%; padding: 24px; }
        .modal-media { display: none; } /* Hide image on mobile for cleaner UX */
    }
</style>

<main class="container-xl">

    <?php if($message): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="hero-card">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Welcome Back,</h1>
                <h2><?php echo htmlspecialchars($full_name); ?></h2>
            </div>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-crimson">₱</div>
            <div class="stat-value">₱<?php echo number_format($my_donation, 2); ?></div>
            <div class="stat-label">Total Lifetime Giving</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue">📂</div>
            <div class="stat-value"><?php echo $projects_supported; ?></div>
            <div class="stat-label">Projects Supported</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber">🤝</div>
            <div class="stat-value">₱<?php echo number_format($total_contributions, 2); ?></div>
            <div class="stat-label">General Fund Support</div>
        </div>
    </div>

    <div class="split-layout">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Recent Transactions</h3>
                <a href="my-donations.php" class="card-link">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_activities)): ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color:#94a3b8;">No recent activity.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_activities as $act): 
                                $statusClass = match(strtolower($act['status'])) {
                                    'verified', 'completed' => 'st-verified',
                                    'rejected' => 'st-rejected',
                                    default => 'st-pending'
                                };
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($act['type']); ?></div>
                                    <div style="font-size:0.75rem; color:#94a3b8;"><?php echo $act['transaction_ref'] ? '#'.htmlspecialchars($act['transaction_ref']) : '-'; ?></div>
                                </td>
                                <td><?php echo date("M d, Y", strtotime($act['date'])); ?></td>
                                <td style="font-weight:600;">₱<?php echo number_format($act['amount']); ?></td>
                                <td><span class="<?php echo $statusClass; ?>"><?php echo ucfirst($act['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <div class="promo-box">
                <div style="font-size: 3rem; margin-bottom: 10px;">🎁</div>
                <h3 style="color: #92400e; font-weight: 700; margin-bottom: 8px;">General Donation</h3>
                <p style="color: #b45309; font-size: 0.9rem; line-height: 1.5; margin-bottom: 0;">
                    Help the association directly by donating cash or in-kind goods to our general fund.
                </p>
                <a href="my_donation.php" class="promo-btn">Make General Donation</a>
            </div>
        </div>
    </div>

    <h2 class="section-head">Active Campaigns</h2>
    
    <?php if (empty($ongoing_projects)): ?>
        <div style="text-align: center; padding: 80px; background: white; border-radius: 16px; border: 1px dashed #cbd5e1;">
            <div style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;">📂</div>
            <h3 style="margin: 0; color: var(--secondary);">No active campaigns</h3>
            <p style="color: var(--text-body);">Check back later for new fundraising initiatives.</p>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($ongoing_projects as $proj): 
                $percent = ($proj['target_amount'] > 0) ? ($proj['current_amount'] / $proj['target_amount']) * 100 : 0;
                $percent = min(100, $percent);
            ?>
            <article class="project-card">
                <div class="pc-img-wrap">
                    <img src="<?php echo htmlspecialchars($proj['cover_photo']); ?>" alt="Project Cover">
                    <span class="pc-tag"><?php echo htmlspecialchars($proj['category_name']); ?></span>
                </div>
                <div class="pc-body">
                    <h3 class="pc-title"><?php echo htmlspecialchars($proj['project_title']); ?></h3>
                    <p class="pc-desc"><?php echo htmlspecialchars($proj['description']); ?></p>
                    
                    <div style="margin-top: auto;">
                        <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:6px; font-weight:600; color:#64748b;">
                            <span>Raised: ₱<?php echo number_format($proj['current_amount']); ?></span>
                            <span><?php echo number_format($percent, 0); ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        
                        <button class="btn-block-donate" 
                            onclick="openDonateModal(this)"
                            data-id="<?php echo $proj['id']; ?>"
                            data-title="<?php echo htmlspecialchars($proj['project_title']); ?>"
                            data-cat="<?php echo htmlspecialchars($proj['category_name']); ?>"
                            data-desc="<?php echo htmlspecialchars($proj['description']); ?>"
                            data-img="<?php echo htmlspecialchars($proj['cover_photo']); ?>"
                            data-target="<?php echo number_format($proj['target_amount']); ?>"
                        >
                            Donate Now
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<div id="donateModal" class="modal-backdrop">
    <div class="modal-panel">
        <div class="modal-media">
            <img id="modalImg" src="" alt="Project" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <span id="modalCat" style="align-self: flex-start; background: #e2e8f0; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; color: var(--secondary); margin-bottom: 12px; text-transform: uppercase;">CATEGORY</span>
            <h3 id="modalTitle" style="font-size: 1.5rem; font-weight: 800; color: var(--secondary); margin-bottom: 12px; line-height: 1.3;"></h3>
            <p id="modalDesc" style="font-size: 0.9rem; color: #64748b; margin-bottom: 20px;"></p>
            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: auto;">
                <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Goal Amount</span>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);">₱<span id="modalTarget"></span></div>
            </div>
        </div>

        <div class="modal-form-area">
            <button onclick="closeDonateModal()" style="position: absolute; top: 20px; right: 20px; font-size: 1.5rem; color: #94a3b8; background: none; border: none; cursor: pointer;">&times;</button>
            
            <h3 style="margin-bottom: 8px; font-weight: 800; color: var(--primary); font-size: 1.5rem;">Secure Donation</h3>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 24px;">Please fill in the details below.</p>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="project_id" id="modalProjectId">
                <input type="hidden" name="submit_donation" value="1">
                
                <div style="margin-bottom: 16px;">
                    <label class="form-label">Donation Amount (₱)</label>
                    <input type="number" name="amount" class="form-input" placeholder="e.g. 1000" required min="1">
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="paymentMethod" class="form-select" onchange="togglePaymentFields()" required>
                        <option value="" disabled selected>Select method...</option>
                        <option value="Cash">Cash (Walk-in / Office)</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>

                <div id="gcashFields" class="gcash-box">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/GCash_logo.svg/1280px-GCash_logo.svg.png" style="height: 20px;">
                        <span style="font-weight: 600; color: #166534;">0912-345-6789</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="transaction_ref" id="refInput" class="form-input" placeholder="Ref No. from SMS/App">
                    </div>
                    
                    <div>
                        <label class="form-label">Upload Screenshot</label>
                        <input type="file" name="payment_proof" id="fileInput" class="form-input" accept="image/*" style="padding: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label class="form-label">Message (Optional)</label>
                    <textarea name="message" class="form-input" rows="2" placeholder="Leave a message of support..."></textarea>
                </div>

                <button type="submit" style="width: 100%; background: var(--primary); color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.2s;">
                    Confirm Donation
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openDonateModal(btn) {
        // Data population
        document.getElementById('modalProjectId').value = btn.dataset.id;
        document.getElementById('modalTitle').innerText = btn.dataset.title;
        document.getElementById('modalCat').innerText = btn.dataset.cat;
        document.getElementById('modalDesc').innerText = btn.dataset.desc;
        document.getElementById('modalImg').src = btn.dataset.img;
        document.getElementById('modalTarget').innerText = btn.dataset.target;

        // UI Open
        const modal = document.getElementById('donateModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeDonateModal() {
        document.getElementById('donateModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function togglePaymentFields() {
        const method = document.getElementById('paymentMethod').value;
        const gcashDiv = document.getElementById('gcashFields');
        const refInput = document.getElementById('refInput');
        const fileInput = document.getElementById('fileInput');

        if (method === 'GCash') {
            gcashDiv.style.display = 'block';
            refInput.required = true;
            fileInput.required = true;
        } else {
            gcashDiv.style.display = 'none';
            refInput.required = false;
            fileInput.required = false;
            refInput.value = '';
            fileInput.value = '';
        }
    }

    // Close modal when clicking outside content
    window.onclick = function(event) {
        const modal = document.getElementById('donateModal');
        if (event.target == modal) {
            closeDonateModal();
        }
    }
</script>

<?php require_once 'footer.php'; ?>