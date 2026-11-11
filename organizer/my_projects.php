<?php
session_start();
require_once '../includes/db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit();
}

$tab = $_GET['tab'] ?? 'all';
$params = [];

// --- SQL CONSTRUCTION (Keep existing logic) ---
// --- SQL CONSTRUCTION (Updated for Uniformity) ---
// Project Donations
$sql_project = "SELECT 
                    d.id, 
                    'project' as source_type, 
                    u.full_name as donor_name, 
                    p.project_title as fund_name, 
                    'Cash' as donation_type, 
                    d.amount::text as amount,           -- Standardized name
                    NULL::text as item_description,     -- Project donations are usually cash
                    NULL::text as quantity, 
                    NULL::text as unit,
                    d.status, 
                    d.created_at::timestamp as date_time, 
                    d.payment_method, 
                    d.transaction_ref as reference_number, -- Standardized name
                    d.payment_proof,                    -- Standardized name
                    d.message
                FROM donations d 
                JOIN users u ON d.user_id = u.id 
                JOIN projects p ON d.project_id = p.id";

// General Donations
$sql_general = "SELECT 
                    NULL as id, 
                    'general' as source_type, 
                    u.full_name as donor_name, 
                    'General Fund' as fund_name, 
                    g.donation_type, 
                    g.amount::text as amount, 
                    g.item_description, 
                    g.quantity::text as quantity, 
                    g.unit,
                    g.status, 
                    g.donation_date::timestamp as date_time, 
                    g.payment_method, 
                    g.reference_number, 
                    g.proof as payment_proof,           -- Renamed to match project table
                    g.message
                FROM general_donation g 
                JOIN users u ON g.user_id = u.id";

if ($tab === 'project') {
    $final_sql = $sql_project . " ORDER BY date_time DESC";
} elseif ($tab === 'general') {
    $final_sql = $sql_general . " ORDER BY date_time DESC";
} else {
    $final_sql = "($sql_project) UNION ALL ($sql_general) ORDER BY date_time DESC";
}

try {
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donations Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>

    <style>
        :root {
            /* Elegant Color Palette */
            --primary: #722F37;    /* Merlot / Deep Wine */
            --primary-light: #964B55; 
            --gold: #C5A059;       /* Muted Gold */
            --bg-body: #F9F7F2;    /* Pearl / Cream Background */
            --surface: #FFFFFF;    /* Pure White */
            --text-main: #2C2C2C;  /* Charcoal */
            --text-muted: #666666;
            --border: #EAE5D8;
            
            /* Status Colors (Muted/Pastel for elegance) */
            --status-success-bg: #E3F9E5; --status-success-text: #1F7A38;
            --status-pending-bg: #FFF8E1; --status-pending-text: #B47D00;
            --status-rejected-bg: #FDECEA; --status-rejected-text: #C62828;
            
            --shadow-soft: 0 10px 30px rgba(114, 47, 55, 0.05);
            --sidebar-width: 300px; /* Adjust this if your sidebar is wider */
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-body); 
            margin: 0; 
            color: var(--text-main);
            
            /* --- LAYOUT FIX --- */
            /* Assuming sidebar is fixed left. We pad the body to push content right */
            padding: 40px;
            padding-left: calc(var(--sidebar-width) + 40px);
            min-height: 100vh;
        }

        h1, h2, h3 { font-family: 'Playfair Display', serif; }

        /* HEADER AREA */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2rem;
            color: var(--primary);
            margin: 0;
            font-weight: 700;
        }
        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 5px;
        }

        /* TABS - ELEGANT PILLS */
        .tabs { 
            display: inline-flex; 
            background: white;
            padding: 6px;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }
        .tab-link {
            padding: 0.6rem 1.5rem;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .tab-link:hover { color: var(--primary); background: #FAF5F6; }
        .tab-link.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(114, 47, 55, 0.2);
        }

        /* CARD CONTAINER */
        .table-container { 
            background: var(--surface); 
            border-radius: 16px; 
            box-shadow: var(--shadow-soft); 
            overflow: hidden; 
            border: 1px solid white;
        }

        /* TABLE STYLING */
        table { width: 100%; border-collapse: collapse; }
        
        thead {
            background: linear-gradient(to right, #722F37, #581C23);
            color: white;
        }
        
        th { 
            padding: 1.2rem 1.5rem; 
            text-align: left; 
            font-size: 0.8rem; 
            font-weight: 500; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            opacity: 0.9;
            font-family: 'Inter', sans-serif;
        }
        
        td { 
            padding: 1.2rem 1.5rem; 
            border-bottom: 1px solid var(--border); 
            font-size: 0.95rem; 
            vertical-align: middle; 
            transition: background 0.2s;
        }
        
        tr:hover td { background-color: #FCFBF8; }
        tr:last-child td { border-bottom: none; }

        /* DONOR NAME */
        .donor-name { font-weight: 600; color: var(--primary); font-family: 'Playfair Display', serif; letter-spacing: 0.5px; }

        /* BADGES */
        .badge { 
            padding: 6px 12px; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.5px;
        }
        
        .type-cash { background: #EBF5FF; color: #1E40AF; border: 1px solid #BFDBFE; }
        .type-item { background: #F3E8FF; color: #6B21A8; border: 1px solid #E9D5FF; }
        .type-other { background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB; }

        .status-completed { background: var(--status-success-bg); color: var(--status-success-text); border: 1px solid #C6F6D5; }
        .status-pending { background: var(--status-pending-bg); color: var(--status-pending-text); border: 1px solid #FEEBC8; }
        .status-rejected { background: var(--status-rejected-bg); color: var(--status-rejected-text); border: 1px solid #FED7D7; }

        /* ACTION BUTTON */
        .btn-view { 
            background: transparent;
            color: var(--gold); 
            border: 1px solid var(--border);
            padding: 6px 16px;
            border-radius: 50px;
            cursor: pointer; 
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-view:hover { 
            background: var(--primary); 
            color: white; 
            border-color: var(--primary);
        }

        /* MODAL ELEGANCE */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(44, 44, 44, 0.4); /* Dark charcoal tint */
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; }

        .modal-content {
            background: #FFF;
            width: 100%;
            max-width: 550px;
            border-radius: 20px;
            padding: 0; /* Remove padding here, use inner containers */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }
        .modal-overlay.show .modal-content { transform: translateY(0); }

        .modal-header { 
            background: var(--primary); 
            padding: 1.5rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-header h2 { margin: 0; color: white; font-size: 1.4rem; font-weight: 500; }
        .close-modal { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: rgba(255,255,255,0.8); transition: 0.2s;}
        .close-modal:hover { color: white; transform: rotate(90deg); }

        .modal-body { padding: 2rem; max-height: 70vh; overflow-y: auto; }

        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 12px 0; 
            border-bottom: 1px dashed var(--border); 
        }
        .detail-row:last-child { border-bottom: none; }
        
        .info-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .info-value { font-size: 1rem; color: var(--text-main); font-weight: 600; text-align: right; }
        
        .message-box {
            background: #F9F7F2;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
            border-left: 3px solid var(--gold);
        }
        .message-box p { margin: 0; font-style: italic; color: #555; }

        .proof-img-container { 
            margin-top: 1.5rem; 
            border-radius: 12px; 
            overflow: hidden; 
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .proof-img-container img { width: 100%; display: block; transition: transform 0.3s; }
        .proof-img-container:hover img { transform: scale(1.02); }

    </style>
</head>
<body>

    <div class="page-header">
        <h1 class="page-title">Donations Overview</h1>
        <p class="page-subtitle">Track and manage incoming contributions.</p>
    </div>

    <div class="tabs">
        <a href="?tab=all" class="tab-link <?php echo $tab === 'all' ? 'active' : ''; ?>">All Records</a>
        <a href="?tab=project" class="tab-link <?php echo $tab === 'project' ? 'active' : ''; ?>">Project Funds</a>
        <a href="?tab=general" class="tab-link <?php echo $tab === 'general' ? 'active' : ''; ?>">General Funds</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Donor</th>
                    <th>Destination</th>
                    <th>Type</th>
                    <th>Amount / Item</th>
                    <th>Status</th>
                    <th style="text-align: right;">Details</th>
                </tr>
            </thead>
         <tbody>
    <?php if (count($donations) > 0): ?>
        <?php foreach ($donations as $row): 
            // 1. Style Logic (Cash vs Item colors)
            $typeClass = (stripos($row['donation_type'], 'Cash') !== false) ? 'type-cash' : 'type-item';
            $statusClass = 'status-' . strtolower($row['status']);
            
            // 2. Display Logic (Gi-update para sa bag-ong SQL names)
            $displayValue = '';
            
            if ($row['donation_type'] === 'Cash') {
                // Kung Cash, gamita ang 'amount' column
                $displayValue = '₱ ' . number_format((float)$row['amount'], 2);
            } else {
                // Kung dili Cash (In-Kind), gamita ang 'item_description'
                // Check nato kung naa bay sulod, kung wala, basig quantity ang ibutang
                $desc = $row['item_description'] ?? 'Item Donation';
                $displayValue = strlen($desc) > 25 ? substr($desc, 0, 25)."..." : $desc;
            }
        ?>
        <tr>
            <td><div class="donor-name"><?php echo htmlspecialchars($row['donor_name']); ?></div></td>
            
            <td style="color: var(--text-muted);">
                <?php echo htmlspecialchars($row['fund_name']); ?>
            </td>
            
            <td>
                <span class="badge <?php echo $typeClass; ?>">
                    <?php echo htmlspecialchars(ucfirst($row['donation_type'])); ?>
                </span>
            </td>
            
            <td style="font-family: 'Inter', monospace; font-weight: 600;">
                <?php echo htmlspecialchars($displayValue); ?>
            </td>
            
            <td>
                <span class="badge <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                </span>
            </td>
            
            <td style="text-align: right;">
                <button class="btn-view" onclick='openModal(<?php echo json_encode($row); ?>)'>
                    View <i class="fas fa-arrow-right" style="font-size: 0.7em; margin-left: 5px;"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center; padding: 3rem; color: var(--text-muted);">No donation records found.</td></tr>
    <?php endif; ?>
</tbody>
        </table>
    </div>

    <div class="modal-overlay" id="donationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
    <div class="detail-row">
        <span class="info-label">Donor Name</span>
        <span class="info-value donor-name" style="font-size: 1.2rem;" id="m_name"></span>
    </div>
    
    <div class="detail-row">
        <span class="info-label">Date & Time</span>
        <span class="info-value" id="m_date"></span>
    </div>

    <div class="detail-row">
        <span class="info-label">Designation</span>
        <span class="info-value" id="m_destination"></span>
    </div>

    <div class="detail-row">
        <span class="info-label">Type</span>
        <span class="info-value" id="m_type"></span>
    </div>

    <div class="detail-row" id="row_amount">
        <span class="info-label">Amount</span>
        <span class="info-value" style="color: var(--primary); font-size: 1.1rem;" id="m_amount"></span>
    </div>

    <div class="detail-row" id="row_method">
        <span class="info-label">Payment Channel</span>
        <span class="info-value" id="m_method"></span>
    </div>

    <div class="detail-row" id="row_ref">
        <span class="info-label">Reference No.</span>
        <span class="info-value" style="font-family: monospace;" id="m_ref"></span>
    </div>

    <div class="detail-row" id="row_item">
        <span class="info-label">Item Description</span>
        <span class="info-value" id="m_item"></span>
    </div>

    <div class="detail-row" id="row_qty">
        <span class="info-label">Quantity</span>
        <span class="info-value" id="m_qty"></span>
    </div>

    <div class="message-box" id="row_message">
        <span class="info-label" style="display:block; margin-bottom:5px;">Message attached:</span>
        <p id="m_message_text"></p>
    </div>

    <div class="detail-row" style="border:none; margin-top: 10px;">
        <span class="info-label">Current Status</span>
        <span class="info-value" id="m_status"></span>
    </div>

    <div id="row_proof" style="display:none;">
        <div class="proof-img-container">
            <img id="m_proof_img" src="" alt="Proof of Donation">
        </div>
        <div style="text-align:center; margin-top: 5px; font-size: 0.8rem; color: #999;">Proof of Transaction</div>
    </div>
</div>
          
        </div>
    </div>

   <script>
    const modal = document.getElementById('donationModal');

    // Helper function to hide/show rows based on data
    function toggleField(rowId, valueElementId, value, prefix = '', suffix = '') {
        const row = document.getElementById(rowId);
        const valEl = document.getElementById(valueElementId);
        
        // Check if value exists (not null, not empty string, not undefined)
        if (value && value.toString().trim() !== "") {
            row.style.display = (rowId === 'row_message' || rowId === 'row_proof') ? 'block' : 'flex'; // Block for boxes, Flex for rows
            if (valEl) {
                valEl.innerText = prefix + value + suffix;
            }
            return true;
        } else {
            row.style.display = 'none';
            return false;
        }
    }

    function openModal(data) {
        console.log(data); // Debugging: See what data is coming in

        // 1. Basic Info (Always shown)
        document.getElementById('m_name').innerText = data.donor_name;
        document.getElementById('m_date').innerText = new Date(data.date_time).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
        document.getElementById('m_destination').innerText = data.fund_name;
        document.getElementById('m_type').innerText = data.donation_type;
        document.getElementById('m_status').innerText = data.status.toUpperCase();

        // 2. Conditional Fields - "If Null, Hide" logic
        
        // Amount (Only if value exists)
        // Format number if it exists
        let formattedAmount = data.amount ? parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2}) : "";
        toggleField('row_amount', 'm_amount', formattedAmount, '₱ ');

        // Payment Method
        toggleField('row_method', 'm_method', data.payment_method);

        // Reference Number
        toggleField('row_ref', 'm_ref', data.reference_number);

        // Item Details
        toggleField('row_item', 'm_item', data.item_description);
        
        // Quantity & Unit combined
        let qtyDisplay = (data.quantity || '') + ' ' + (data.unit || '');
        if (!data.quantity) qtyDisplay = ""; // Force empty if no quantity
        toggleField('row_qty', 'm_qty', qtyDisplay);

        // Message
        toggleField('row_message', 'm_message_text', data.message);

        // Proof Image
        const proofRow = document.getElementById('row_proof');
        const proofImg = document.getElementById('m_proof_img');
        
        if (data.payment_proof && data.payment_proof.trim() !== "") {
            proofRow.style.display = 'block';
            proofImg.src = '../' + data.payment_proof; // Adjust path as needed
        } else {
            proofRow.style.display = 'none';
        }

        // Show Modal with Animation
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('show'); }, 10);
    }

    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
</body>
</html>