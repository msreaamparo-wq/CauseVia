<?php
require_once 'config.php';
requireAuth();

$page_title = "Fund Projects";
require_once 'header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- 1. HANDLE DONATION SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    try {
        $proj_id = $_POST['project_id'];
        $amount = $_POST['amount'];
        $method = $_POST['payment_method'];
        $msg = $_POST['message'] ?? '';
        $trans_ref = $_POST['transaction_ref'] ?? null;
        
        $payment_proof = null;
        // Handle GCash Proof Upload
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

// --- 2. FETCH PROJECTS WITH FILTERS ---
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? ''; 
$sort = $_GET['sort'] ?? 'newest';

try {
    // Base Query
    $query = "
        SELECT *, 
               (current_amount / target_amount * 100) as progress_percentage
        FROM projects 
        WHERE 1=1
    ";

    $params = [];
    
    if ($search) {
        $query .= " AND project_title LIKE ?";
        $params[] = "%$search%";
    }

    if ($category) {
        $query .= " AND category_name = ?";
        $params[] = $category;
    }

    if ($status_filter) {
        $query .= " AND status = ?";
        $params[] = $status_filter;
    }

    if ($sort === 'oldest') {
        $query .= " ORDER BY start_date ASC";
    } else {
        $query .= " ORDER BY start_date DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Fund Projects Error: " . $e->getMessage());
    $projects = [];
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: #800000;       
        --primary-dark: #4a040b;
        --secondary: #1e293b;   
        --bg-body: #f8fafc;
        --border: #e2e8f0;
        --text-head: #0f172a;
        --text-body: #64748b;
        --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.05);
    }

    body { font-family: 'Inter', sans-serif; background-color: #F9F5EB; color: var(--text-body); }
    .container-xl { max-width: 1400px; margin: 0 auto; padding: 40px 24px; }

    /* Alerts */
    .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* === UPDATED FILTER BAR CSS === */
    .filter-bar {
        background: white; 
        padding: 20px; 
        border-radius: 16px; 
        border: 1px solid var(--border);
        box-shadow: var(--shadow-soft); 
        margin-bottom: 32px; 
        display: flex; 
        align-items: center; /* Align vertically center */
        flex-wrap: wrap; 
        gap: 15px;
    }

    /* Long Search Bar */
    .search-input {
        flex-grow: 1; /* Mukaon sa tanang available space */
        min-width: 250px;
        padding: 12px 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.95rem;
    }

    /* Uniform Dropdowns */
    .filter-select {
        width: 200px; /* Fixed width para hapsay */
        padding: 12px 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.95rem;
        flex-shrink: 0; /* Dili mupiit kung gamay screen */
        background-color: white;
        cursor: pointer;
    }

    /* Filter Button */
    .filter-btn {
        background: var(--secondary);
        color: white;
        padding: 12px 28px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .filter-btn:hover { background: #334155; }

    /* Responsive for Mobile */
    @media (max-width: 768px) {
        .filter-bar { flex-direction: column; align-items: stretch; }
        .filter-select, .filter-btn { width: 100%; }
    }

    /* Project Grid */
    .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
    .project-card {
        background: white; border-radius: 16px; overflow: hidden; border: 1px solid var(--border);
        box-shadow: var(--shadow-soft); transition: all 0.3s ease; display: flex; flex-direction: column;
    }
    .project-card:hover { transform: translateY(-6px); }
    .pc-img-wrap { height: 220px; position: relative; overflow: hidden; }
    .pc-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .pc-tag { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.95); padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; }
    .pc-body { padding: 24px; flex-grow: 1; display: flex; flex-direction: column; }
    .pc-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: var(--text-head); }
    .pc-desc { font-size: 0.9rem; color: var(--text-body); margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .progress-track { height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-bottom: 12px; }
    .progress-fill { height: 100%; background: var(--primary); border-radius: 10px; }
    
    .btn-view-details {
        width: 100%; background: white; color: var(--primary); border: 1px solid var(--primary);
        padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;
    }
    .btn-view-details:hover { background: #fff1f2; }

    /* === SINGLE MODAL SYSTEM === */
    .modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
        z-index: 1000; display: none; justify-content: center; align-items: center;
        opacity: 0; transition: opacity 0.3s;
    }
    .modal-backdrop.active { display: flex; opacity: 1; }
    
    .modal-panel {
        background: white; width: 90%; max-width: 900px; border-radius: 24px;
        overflow: hidden; display: flex; max-height: 90vh; position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.95); transition: transform 0.3s ease;
    }
    .modal-backdrop.active .modal-panel { transform: scale(1); }

    .modal-left { width: 45%; background: #f8fafc; padding: 40px; border-right: 1px solid var(--border); overflow-y: auto; }
    .modal-right { width: 55%; padding: 40px; position: relative; overflow-y: auto; }
    .view-container { width: 100%; height: 100%; display: flex; }
    
    .modal-image-large { width: 100%; height: 250px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; }
    .modal-heading { font-size: 1.75rem; font-weight: 800; color: var(--secondary); margin-bottom: 12px; }
    
    .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 8px; }
    .form-input, .form-select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 16px; }
    .gcash-box { background: #f0fdf4; border: 1px dashed #22c55e; padding: 20px; border-radius: 12px; margin-top: 5px; margin-bottom: 15px; display: none; }
    
    .btn-donate-submit { width: 100%; background: var(--primary); color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
    .close-modal { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; color: #94a3b8; background: none; border: none; cursor: pointer; z-index: 20; }

    @media (max-width: 900px) {
        .modal-panel { flex-direction: column; overflow-y: auto; }
        .view-container { flex-direction: column; }
        .modal-left, .modal-right { width: 100%; padding: 24px; border-right: none; height: auto; }
        .modal-image-large { height: 180px; }
    }
</style>

<main class="container-xl">
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--secondary); margin-bottom: 10px;">Fund Projects</h1>
        <p style="color: var(--text-body);">Support ongoing initiatives and make a difference.</p>
    </div>

    <form class="filter-bar" method="GET" action="">
        <input type="text" name="search" class="search-input" placeholder="Search projects by title..." value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <option value="Education" <?php echo $category == 'Education' ? 'selected' : ''; ?>>Education</option>
            <option value="Health" <?php echo $category == 'Health' ? 'selected' : ''; ?>>Health</option>
            <option value="Outreach" <?php echo $category == 'Outreach' ? 'selected' : ''; ?>>Outreach</option>
        </select>
        
        <select name="status" class="filter-select">
            <option value="">All Status</option>
            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        
        <button type="submit" class="filter-btn">Filter</button>
    </form>

    <?php if (empty($projects)): ?>
        <div style="text-align: center; padding: 80px; opacity: 0.6;"><h3>No projects found.</h3></div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($projects as $proj): 
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
                    
                    <div style="margin-top:auto;">
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600; color:#64748b; margin-bottom:5px;">
                            <span>Raised: ₱<?php echo number_format($proj['current_amount']); ?></span>
                            <span><?php echo number_format($percent, 0); ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        
                        <button class="btn-view-details" 
                            onclick="openUnifiedModal(this)"
                            data-id="<?php echo $proj['id']; ?>"
                            data-title="<?php echo htmlspecialchars($proj['project_title']); ?>"
                            data-cat="<?php echo htmlspecialchars($proj['category_name']); ?>"
                            data-desc="<?php echo htmlspecialchars($proj['description']); ?>"
                            data-img="<?php echo htmlspecialchars($proj['cover_photo']); ?>"
                            data-target="<?php echo number_format($proj['target_amount']); ?>"
                            data-percent="<?php echo number_format($percent, 0); ?>"
                            data-status="<?php echo htmlspecialchars($proj['status']); ?>"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div id="unifiedModal" class="modal-backdrop">
    <div class="modal-panel">
        <button class="close-modal" onclick="closeUnifiedModal()">&times;</button>
        
        <div id="viewDetails" class="view-container">
            <div class="modal-left" style="display: flex; flex-direction: column; justify-content: center;">
                <img id="detImg" src="" class="modal-image-large">
                <div style="background: #e2e8f0; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; margin-bottom: 5px;">Goal Amount</div>
                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">₱<span id="detTarget"></span></div>
                    <div style="margin-top: 15px; width: 100%; background: #cbd5e1; height: 6px; border-radius: 10px;">
                        <div id="detBar" style="height: 100%; background: var(--primary); width: 0%; border-radius: 10px;"></div>
                    </div>
                    <div style="margin-top: 5px; font-size: 0.85rem; color: #64748b;"><span id="detPercent"></span>% Raised</div>
                </div>
            </div>

            <div class="modal-right">
                <span id="detCat" style="font-size: 0.8rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">CATEGORY</span>
                <h3 id="detTitle" class="modal-heading"></h3>
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 30px;">
                    <p id="detDesc" style="color: #475569; line-height: 1.7;"></p>
                </div>
                <button id="btnToDonate" class="btn-donate-submit" onclick="switchToDonate()">Donate Now</button>
            </div>
        </div>

        <div id="viewDonate" class="view-container" style="display: none;">
            <div class="modal-left">
                <h3 style="font-weight: 800; color: var(--secondary); margin-bottom: 20px;">You are supporting:</h3>
                <img id="donImg" src="" style="width: 100%; height: 180px; object-fit: cover; border-radius: 12px; margin-bottom: 16px;">
                <h4 id="donTitle" style="font-size: 1.2rem; font-weight: 700; color: var(--text-head);"></h4>
                <p style="font-size: 0.9rem; color: #64748b; margin-top: 8px;">Target Goal: ₱<span id="donTarget"></span></p>
                <button onclick="switchToDetails()" style="margin-top: 20px; background: none; border: none; color: #64748b; text-decoration: underline; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    &larr; Back to Details
                </button>
            </div>

            <div class="modal-right">
                <h3 style="margin-bottom: 8px; font-weight: 800; color: var(--primary); font-size: 1.5rem;">Secure Donation</h3>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 24px;">Please fill in the details below.</p>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" id="donProjectId">
                    <input type="hidden" name="submit_donation" value="1">
                    
                    <label class="form-label">Donation Amount (₱)</label>
                    <input type="number" name="amount" class="form-input" placeholder="e.g. 1000" required min="1">

                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="paymentMethod" class="form-select" onchange="togglePaymentFields()" required>
                        <option value="" disabled selected>Select method...</option>
                        <option value="Cash">Cash (Walk-in / Office)</option>
                        <option value="GCash">GCash</option>
                    </select>

                    <div id="gcashFields" class="gcash-box">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/GCash_logo.svg/1280px-GCash_logo.svg.png" style="height: 20px;">
                            <span style="font-weight: 600; color: #166534;">0912-345-6789</span>
                        </div>
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="transaction_ref" id="refInput" class="form-input" placeholder="Ref No. from SMS/App">
                        <label class="form-label">Upload Screenshot</label>
                        <input type="file" name="payment_proof" id="fileInput" class="form-input" accept="image/*" style="padding: 8px;">
                    </div>

                    <label class="form-label">Message (Optional)</label>
                    <textarea name="message" class="form-input" rows="2" placeholder="Leave a message..."></textarea>

                    <button type="submit" class="btn-donate-submit">Confirm Donation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let currentProj = {};

    function openUnifiedModal(btn) {
        currentProj = {
            id: btn.dataset.id,
            title: btn.dataset.title,
            cat: btn.dataset.cat,
            desc: btn.dataset.desc,
            img: btn.dataset.img,
            target: btn.dataset.target,
            percent: btn.dataset.percent,
            status: btn.dataset.status
        };

        document.getElementById('detImg').src = currentProj.img;
        document.getElementById('detTitle').innerText = currentProj.title;
        document.getElementById('detCat').innerText = currentProj.cat;
        document.getElementById('detDesc').innerText = currentProj.desc;
        document.getElementById('detTarget').innerText = currentProj.target;
        document.getElementById('detPercent').innerText = currentProj.percent;
        document.getElementById('detBar').style.width = currentProj.percent + '%';

        document.getElementById('donProjectId').value = currentProj.id;
        document.getElementById('donTitle').innerText = currentProj.title;
        document.getElementById('donTarget').innerText = currentProj.target;
        document.getElementById('donImg').src = currentProj.img;

        const btnDonate = document.getElementById('btnToDonate');
        if(currentProj.status !== 'active') {
            btnDonate.disabled = true;
            btnDonate.innerText = "Project " + currentProj.status.charAt(0).toUpperCase() + currentProj.status.slice(1);
            btnDonate.style.background = "#94a3b8"; 
            btnDonate.style.cursor = "not-allowed";
        } else {
            btnDonate.disabled = false;
            btnDonate.innerText = "Donate Now";
            btnDonate.style.background = "var(--primary)";
            btnDonate.style.cursor = "pointer";
        }

        document.getElementById('viewDetails').style.display = 'flex';
        document.getElementById('viewDonate').style.display = 'none';
        
        document.getElementById('unifiedModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeUnifiedModal() {
        document.getElementById('unifiedModal').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function switchToDonate() {
        document.getElementById('viewDetails').style.display = 'none';
        document.getElementById('viewDonate').style.display = 'flex'; 
    }

    function switchToDetails() {
        document.getElementById('viewDonate').style.display = 'none';
        document.getElementById('viewDetails').style.display = 'flex';
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
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('unifiedModal')) {
            closeUnifiedModal();
        }
    }
</script>

<?php require_once 'footer.php'; ?>