<?php
ob_start();
require_once 'config.php';
requireAuth(); 

$user_id = $_SESSION['user_id'];
$message_notification = ""; 

// --- 0. HANDLE GENERAL DONATION FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_general_donation'])) {
    try {
        $donation_type = $_POST['donation_type']; // 'Cash' or 'In-Kind'
        $message       = $_POST['message'];
        $status        = 'pending';
        $date          = date('Y-m-d H:i:s');
        
        // Initialize variables
        $amount     = 0;
        $pay_method = 'N/A'; // Default for In-Kind
        $ref_number = 'N/A';
        $item_desc  = null;
        $quantity   = null;
        $unit       = null;
        
        // LOGIC SEPARATION
        if ($donation_type === 'Cash') {
            // Kung Cash, kuhaon ang payment details
            $amount     = $_POST['amount'];
            $pay_method = $_POST['payment_method']; // GCash or Cash
            $ref_number = $_POST['reference_number'];
        } else {
            // Kung In-Kind, items ang i-record. Automatic 'In-Kind' ang method.
            $pay_method = 'In-Kind'; 
            $item_desc  = $_POST['item_description'];
            $quantity   = $_POST['quantity'];
            $unit       = $_POST['unit'];
        }

        // --- IMAGE UPLOAD HANDLING ---
        $proof_filename = null;
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/'; 
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
            
            if(in_array($fileExt, $allowedTypes)){
                $proof_filename = 'proof_' . time() . '_' . $user_id . '.' . $fileExt;
                move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . $proof_filename);
            }
        }

        // Insert SQL
        $insert_sql = "INSERT INTO general_donation 
            (user_id, donation_type, amount, payment_method, reference_number, proof, item_description, quantity, unit, message, status, donation_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmt_ins = $pdo->prepare($insert_sql);
        $stmt_ins->execute([
            $user_id, $donation_type, $amount, $pay_method, $ref_number, $proof_filename, $item_desc, $quantity, $unit, $message, $status, $date
        ]);

        header("Location: ?tab=general&msg=success");
        exit;

    } catch (Exception $e) {
        $message_notification = "Error: " . $e->getMessage();
    }
}

// --- 1. HANDLE TABS & FILTERS ---
$active_tab = $_GET['tab'] ?? 'all';
$search     = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// --- 2. BUILD SQL QUERY ---
$params = [];
$sql = "";

// Project Query
$project_query = "
    SELECT 
        'project' as source_type, d.id, p.project_title as display_name, 'Project Fund' as fund_type,
        d.amount as cash_amount, NULL as item_qty, NULL as item_unit, NULL as item_desc,
        d.payment_method, d.status, d.created_at as record_date, d.transaction_ref, d.payment_proof, d.message
    FROM donations d 
    JOIN projects p ON d.project_id = p.id 
    WHERE d.user_id = ?
";

// General Query
$general_query = "
    SELECT 
        'general' as source_type, g.general_donation_id as id, g.donation_type as display_name, 'General Fund' as fund_type,
        g.amount as cash_amount, g.quantity as item_qty, g.unit as item_unit, g.item_description as item_desc,
        g.payment_method, g.status, g.donation_date as record_date, g.reference_number as transaction_ref, g.proof as payment_proof, g.message
    FROM general_donation g 
    WHERE g.user_id = ?
";

if ($active_tab == 'project') {
    $sql = $project_query;
    $params[] = $user_id;
} elseif ($active_tab == 'general') {
    $sql = $general_query;
    $params[] = $user_id;
} else {
    $sql = "($project_query) UNION ALL ($general_query)";
    $params[] = $user_id; 
    $params[] = $user_id; 
}

$final_sql = "SELECT * FROM ($sql) as combined_table WHERE 1=1";
if ($search) {
    $final_sql .= " AND display_name LIKE ?";
    $params[] = "%$search%";
}
if ($status_filter && $status_filter != 'all') {
    $final_sql .= " AND status = ?";
    $params[] = $status_filter;
}
$final_sql .= " ORDER BY record_date DESC";

$stmt = $pdo->prepare($final_sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "My Donations";
require_once 'header.php'; 
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --primary-color: #6C131F;
        --primary-hover: #8a1c2b;
        --bg-color: #F9F5EB;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    /* --- FIX START: Sticky Footer Logic --- */
    body { 
        font-family: 'Inter', sans-serif; 
        background: var(--bg-color); 
        color: var(--text-dark);
        
        /* Mao ni ang makapa-ubos sa footer */
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* Siguraduhon nga ang body mo-occupy sa tibuok screen height */
        margin: 0; /* Optional: para walay puti sa kilid */
    }

    .donations-wrapper { 
        max-width: 1400px; 
        margin: 30px auto; 
        padding: 0 20px;
        
        /* Kini nga line mopa-expand sa content para itulod ang footer paubos */
        flex: 1; 
        width: 100%; /* Siguraduhon nga dili mapiit ang width */
        box-sizing: border-box; 
    }
    /* Header & Buttons */
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; }
    .page-header h2 { margin: 0; font-size: 28px; font-weight: 700; }
    
    .btn-create {
        background-color: var(--primary-color); color: white; border: none; padding: 12px 24px;
        border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: 0.2s;
    }
    .btn-create:hover { background-color: var(--primary-hover); transform: translateY(-2px); }

    /* Card & Table */
    .donations-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border-color); overflow: hidden; }
    
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { background: #f8fafc; padding: 16px 24px; text-align: left; font-size: 13px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); }
    .clean-table td { padding: 18px 24px; border-bottom: 1px solid var(--border-color); font-size: 15px; }
    .clean-table tbody tr:hover { background-color: #f8fafc; cursor: pointer; }

    /* Badges */
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-pending { background: #fff7ed; color: #c2410c; }
    .badge-verified { background: #f0fdf4; color: #15803d; }
    .badge-failed { background: #fef2f2; color: #b91c1c; }

    /* Tabs & Filters */
    .tabs-container { display: flex; border-bottom: 1px solid var(--border-color); padding: 0 20px; }
    .tab-link { padding: 16px 24px; color: var(--text-muted); text-decoration: none; border-bottom: 3px solid transparent; font-weight: 500; }
    .tab-link.active { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 600; }
    
    .filter-toolbar { padding: 20px; display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
    .search-input { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; width: 300px; }
    .form-select { padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; }

    /* --- MODALS --- */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px);
        display: none; justify-content: center; align-items: center; z-index: 1000;
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: #fff; width: 95%; max-width: 600px; border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .modal-header { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .modal-body { padding: 25px; max-height: 75vh; overflow-y: auto; }

    /* Form Styles */
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--text-dark); }
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
    
    /* Upload Box */
    .file-upload-wrapper { position: relative; width: 100%; }
    .file-upload-box {
        border: 2px dashed #cbd5e1; background: #f8fafc; border-radius: 10px;
        padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; display: block;
    }
    .file-upload-box:hover { border-color: var(--primary-color); background: #fff5f5; }
    .file-upload-box input[type="file"] { display: none; }
    .upload-icon { font-size: 24px; color: var(--primary-color); margin-bottom: 10px; }
    .upload-text { font-size: 14px; color: var(--text-dark); font-weight: 500; }
    .upload-hint { font-size: 12px; color: var(--text-muted); display: block; margin-top: 5px; }

    .image-preview-container { margin-top: 15px; display: none; text-align: center; position: relative; }
    .preview-img-tag { max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .remove-image-btn { background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 5px; font-size: 12px; margin-top: 8px; cursor: pointer; }

    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .detail-label { color: var(--text-muted); font-size: 14px; }
    .detail-val { font-weight: 600; color: var(--text-dark); font-size: 14px; }
    .proof-display { width: 100%; margin-top: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
</style>

<div class="donations-wrapper">
    <div class="page-header">
        <div>
            <h2>My Donations</h2>
            <small style="color: var(--text-muted);">Track your contribution history</small>
        </div>
        <button class="btn-create" onclick="openCreateModal()">
            <span>+</span> Donate to General Fund
        </button>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div style="background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            Donation submitted successfully! Thank you.
        </div>
    <?php endif; ?>

    <div class="donations-card">
        <div class="tabs-container">
            <a href="?tab=all" class="tab-link <?php echo ($active_tab == 'all') ? 'active' : ''; ?>">All Records</a>
            <a href="?tab=project" class="tab-link <?php echo ($active_tab == 'project') ? 'active' : ''; ?>">Project Funds</a>
            <a href="?tab=general" class="tab-link <?php echo ($active_tab == 'general') ? 'active' : ''; ?>">General Funds</a>
        </div>

        <form method="GET" class="filter-toolbar">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
            <input type="text" name="search" class="search-input" placeholder="Search reference or name..." value="<?php echo htmlspecialchars($search); ?>">
            
            <div style="display: flex; gap: 10px;">
                <select name="status" class="form-select">
                    <option value="all">All Status</option>
                    <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="verified" <?php if($status_filter=='verified') echo 'selected'; ?>>Verified</option>
                    <option value="completed" <?php if($status_filter=='completed') echo 'selected'; ?>>Completed</option>
                </select>
                <button type="submit" class="btn-create" style="padding: 10px 20px; font-size: 14px;">Filter</button>
            </div>
        </form>

        <div style="overflow-x: auto;">
            <table class="clean-table">
                <thead>
                    <tr>
                        <th>Donation Details</th>
                        <th>Amount / Item</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): ?>
                            <?php 
                                // Formatting
                                $amount_show = ($row['cash_amount'] > 0) 
                                    ? '₱' . number_format($row['cash_amount'], 2) 
                                    : htmlspecialchars($row['item_qty'] . ' ' . $row['item_unit'] . ' - ' . $row['item_desc']);
                                
                                $badgeClass = 'badge-pending';
                                if(in_array(strtolower($row['status']), ['verified', 'completed'])) $badgeClass = 'badge-verified';
                                if(in_array(strtolower($row['status']), ['failed', 'rejected'])) $badgeClass = 'badge-failed';

                                // Proof path
                                $proofPath = $row['payment_proof'] ? 'uploads/' . htmlspecialchars($row['payment_proof']) : '';
                            ?>
                            <tr onclick='openViewModal(<?php echo json_encode($row); ?>, "<?php echo $proofPath; ?>", "<?php echo $amount_show; ?>")'>
                                <td>
                                    <strong style="color: var(--text-dark); display:block;"><?php echo htmlspecialchars($row['display_name']); ?></strong>
                                    <small style="color: var(--text-muted);"><?php echo $row['fund_type']; ?></small>
                                </td>
                                <td style="font-weight: 600;"><?php echo $amount_show; ?></td>
                                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td style="color: var(--text-muted); font-size: 13px;"><?php echo date('M d, Y', strtotime($row['record_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="createFundModal" onclick="closeModalOutside(event, 'createFundModal')">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;">Make a General Donation</h3>
            <button onclick="closeModal('createFundModal')" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
    
    <div class="form-group">
        <label class="form-label">I want to donate:</label>
        <select name="donation_type" id="inputType" class="form-control" onchange="toggleFormFields()">
            <option value="Cash">Cash (Money)</option>
            <option value="In-Kind">In-Kind (Items/Goods)</option>
        </select>
    </div>

    <div class="form-group" id="paymentMethodGroup">
        <label class="form-label">Payment Method</label>
        <select name="payment_method" id="payMethodSelect" class="form-control" onchange="toggleFormFields()">
            <option value="GCash">GCash</option>
            <option value="Cash">Cash (Walk-in)</option>
        </select>
    </div>

    <div id="cashFields">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label class="form-label">Amount (₱)</label>
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00">
            </div>
            
            <div class="form-group" id="refGroup">
                <label class="form-label">Reference No.</label>
                <input type="text" name="reference_number" class="form-control" placeholder="GCash Ref #">
            </div>
        </div>
    </div>

    <div id="kindFields" style="display: none;">
        <div class="form-group">
            <label class="form-label">Item Description</label>
            <input type="text" name="item_description" class="form-control" placeholder="e.g. 1 Sack of Rice">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-control" placeholder="pcs, packs">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Message (Optional)</label>
        <textarea name="message" class="form-control" rows="2"></textarea>
    </div>

    <div class="form-group" id="proofGroup">
        <label class="form-label" id="proofLabel">Upload Proof of Payment</label>
        
        <div class="file-upload-wrapper">
            <label class="file-upload-box" id="dropZone">
                <input type="file" name="proof" id="fileInput" accept="image/*" onchange="previewImage(this)">
                <div class="upload-icon">📷</div>
                <div class="upload-text">Click to upload image</div>
                <span class="upload-hint">Supported: JPG, PNG</span>
            </label>
        </div>

        <div class="image-preview-container" id="previewContainer">
            <img id="imagePreview" class="preview-img-tag" src="" alt="Proof Preview">
            <br>
            <button type="button" class="remove-image-btn" onclick="removeImage()">Remove / Change</button>
        </div>
    </div>

    <div style="text-align: right; margin-top: 20px;">
        <button type="submit" name="submit_general_donation" class="btn-create" style="width:100%;">Submit Donation</button>
    </div>
</form>
        </div>
    </div>
</div>
<div class="modal-overlay" id="viewModal" onclick="closeModalOutside(event, 'viewModal')">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;">Transaction Details</h3>
            <button onclick="closeModal('viewModal')" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #e2e8f0;">
                <span id="v-status" class="badge" style="margin-bottom: 10px; display: inline-block;">Pending</span>
                <h2 id="v-amount-display" style="color: var(--primary-color); margin: 0; font-size: 24px;">₱0.00</h2>
                <small id="v-fund-type" style="color: var(--text-muted);">General Fund</small>
            </div>

            <div class="details-list">
                <div class="detail-row">
                    <span class="detail-label">Donation For:</span>
                    <span class="detail-val" id="v-title" style="text-align: right;">-</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-val" id="v-method">-</span>
                </div>

                <div class="detail-row" id="v-ref-row">
                    <span class="detail-label">Reference No:</span>
                    <span class="detail-val" id="v-ref" style="font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">-</span>
                </div>

                <div id="v-inkind-details" style="display: none; background: #f8fafc; padding: 10px; border-radius: 8px; margin: 10px 0;">
                    <div class="detail-row">
                        <span class="detail-label">Item:</span>
                        <span class="detail-val" id="v-item-desc">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Qty / Unit:</span>
                        <span class="detail-val"><span id="v-qty">0</span> <span id="v-unit">pcs</span></span>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date Submitted:</span>
                    <span class="detail-val" id="v-date">-</span>
                </div>

                <div style="margin-top: 15px;">
                    <span class="detail-label" style="display:block; margin-bottom:5px;">Message / Note:</span>
                    <div id="v-message" style="background: #fdfdfd; border: 1px solid #f1f5f9; padding: 10px; border-radius: 6px; font-size: 14px; color: #334155; font-style: italic;">
                        No message provided.
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <span class="detail-label" style="display:block; margin-bottom:8px;">Proof Attachment:</span>
                <div id="v-proof-container" style="text-align: center; background: #f1f5f9; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    <img id="v-proof-img" src="" alt="Proof" class="proof-display" onclick="window.open(this.src, '_blank')" style="cursor: zoom-in; max-height: 300px; object-fit: contain;">
                    <span id="v-no-proof" style="color: #94a3b8; font-style: italic; display: none;">No proof uploaded / Not required.</span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // --- UI/Modal Functions ---
    function openCreateModal() {
        document.getElementById('createFundModal').classList.add('active');
        // Reset defaults
        document.getElementById('inputType').value = "Cash";
        document.getElementById('payMethodSelect').value = "GCash"; // Default back to GCash
        toggleFormFields();
        removeImage();
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function closeModalOutside(e, id) {
        if(e.target.id === id) closeModal(id);
    }

    // --- FORM LOGIC (FIXED) ---
    function toggleFormFields() {
        const type = document.getElementById('inputType').value; // Cash or In-Kind
        const method = document.getElementById('payMethodSelect').value; // GCash or Cash (Walk-in)
        
        // Groups
        const cashFields = document.getElementById('cashFields');
        const kindFields = document.getElementById('kindFields');
        const payGroup   = document.getElementById('paymentMethodGroup');
        const refGroup   = document.getElementById('refGroup');   // Reference Number Box
        const proofGroup = document.getElementById('proofGroup'); // Upload Box
        const proofLabel = document.getElementById('proofLabel');

        if(type === 'Cash') {
            // 1. Show General Cash Fields
            cashFields.style.display = 'block';
            kindFields.style.display = 'none';
            payGroup.style.display = 'block'; 

            // 2. CHECK PAYMENT METHOD
            if(method === 'Cash') { 
                // --- CASE: CASH (WALK-IN) ---
                // Hide Reference Number
                refGroup.style.display = 'none';
                // Hide Proof of Payment
                proofGroup.style.display = 'none';
            } else {
                // --- CASE: GCASH ---
                // Show Reference Number
                refGroup.style.display = 'block';
                // Show Proof of Payment
                proofGroup.style.display = 'block';
                proofLabel.innerText = "Upload Proof of Payment (Screenshot)";
            }

        } else {
            // --- CASE: IN-KIND ---
            cashFields.style.display = 'none';
            kindFields.style.display = 'block';
            payGroup.style.display = 'none'; // Hide Dropdown
            
            // In-Kind usually requires a photo of the items, 
            // but if you want to hide it too, just set proofGroup.style.display = 'none';
            // For now, I'll keep it visible but change the label:
            refGroup.style.display = 'none'; // No Ref # for goods
            proofGroup.style.display = 'block'; 
            proofLabel.innerText = "Upload Photo of Items";
        }
    }

    // --- IMAGE PREVIEW LOGIC ---
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('previewContainer').style.display = 'block';
                document.getElementById('dropZone').style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage() {
        document.getElementById('fileInput').value = "";
        document.getElementById('previewContainer').style.display = 'none';
        document.getElementById('dropZone').style.display = 'block';
    }

    // --- VIEW MODAL LOGIC ---
   // --- VIEW MODAL LOGIC (UPDATED) ---
    function openViewModal(data, proofPath, amountFormatted) {
        document.getElementById('viewModal').classList.add('active');
        
        // 1. Basic Info
        document.getElementById('v-title').innerText = data.display_name;
        document.getElementById('v-fund-type').innerText = data.fund_type;
        document.getElementById('v-method').innerText = data.payment_method;
        document.getElementById('v-date').innerText = data.record_date;

        // 2. Message Handling
        const msgBox = document.getElementById('v-message');
        if(data.message && data.message.trim() !== "") {
            msgBox.innerText = data.message;
            msgBox.style.color = "#334155";
        } else {
            msgBox.innerText = "No message provided.";
            msgBox.style.color = "#94a3b8";
        }

        // 3. Status Badge
        const statusSpan = document.getElementById('v-status');
        statusSpan.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        statusSpan.className = 'badge'; 
        if(['verified', 'completed'].includes(data.status.toLowerCase())) statusSpan.classList.add('badge-verified');
        else if(['failed', 'rejected'].includes(data.status.toLowerCase())) statusSpan.classList.add('badge-failed');
        else statusSpan.classList.add('badge-pending');

        // 4. Logic for CASH vs IN-KIND
        const isCashDonation = (data.payment_method === 'GCash' || data.payment_method === 'Cash');
        const refRow = document.getElementById('v-ref-row');
        const inKindBox = document.getElementById('v-inkind-details');
        const amountDisplay = document.getElementById('v-amount-display');

        if (isCashDonation) {
            // SHOW: Amount, Ref Number (if GCash)
            // HIDE: In-Kind Details
            amountDisplay.innerText = amountFormatted; // e.g., ₱1,000.00
            inKindBox.style.display = 'none';

            if(data.payment_method === 'GCash') {
                refRow.style.display = 'flex';
                document.getElementById('v-ref').innerText = data.transaction_ref || 'N/A';
            } else {
                refRow.style.display = 'none'; // Cash walk-in usually has no user-submitted ref
            }

        } else {
            // IT IS IN-KIND
            // SHOW: In-Kind Details
            // HIDE: Ref Number
            amountDisplay.innerText = "In-Kind Donation";
            inKindBox.style.display = 'block';
            refRow.style.display = 'none';

            // Populate In-Kind Data
            document.getElementById('v-item-desc').innerText = data.item_desc || 'N/A';
            document.getElementById('v-qty').innerText = data.item_qty || '0';
            document.getElementById('v-unit').innerText = data.item_unit || '';
        }

        // 5. Proof Image Handling
        const img = document.getElementById('v-proof-img');
        const noProof = document.getElementById('v-no-proof');
        
        // Check if proof exists AND path is valid
        if (proofPath && proofPath.trim() !== '' && proofPath !== 'uploads/' && data.payment_proof) {
            img.src = proofPath;
            img.style.display = 'block';
            noProof.style.display = 'none';
        } else {
            img.style.display = 'none';
            noProof.style.display = 'inline-block';
        }
    }
    
</script>

<?php require_once 'footer.php'; ?>