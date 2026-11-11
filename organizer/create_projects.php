<?php
session_start();
require_once '../includes/db.php'; 

// CRITICAL FIX: Database Connection Check
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
} elseif (isset($pdo) && !isset($conn)) {
    $conn = $pdo;
}

// 1. Check User (Organizer)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header('Location: ../public/login.php');
    exit();
}

$organizer_id = $_SESSION['user_id'];
$organizer_name = $_SESSION['full_name'] ?? 'Organizer';

// 2. Categories
$categories = [
    1 => 'Education', 2 => 'Health', 3 => 'Environment', 4 => 'Animal Welfare',
    5 => 'Community', 6 => 'Arts & Culture', 7 => 'Technology', 8 => 'Disaster Relief'
];

// ==========================================
// HANDLE AJAX REQUEST (GET DONORS FOR MODAL)
// ==========================================
if (isset($_GET['fetch_donors']) && isset($_GET['project_id'])) {
    $pid = intval($_GET['project_id']);
    $sql = "SELECT d.amount, d.created_at, u.full_name 
            FROM donations d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.project_id = :pid 
            ORDER BY d.created_at DESC LIMIT 10";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pid' => $pid]);
    
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($donors);
    exit;
}

// ==========================================
// HANDLE CREATE PROJECT SUBMISSION
// ==========================================
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category']);
    $category_name = $categories[$category_id]; 
    $goal = floatval($_POST['target_amount']);
    $deadline = $_POST['end_date'];
    $desc = trim($_POST['description']);
    $image_path = '../assets/images/default_project.jpg';
 
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === 0) {
        $upload_dir = '../uploads/projects/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('proj_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $upload_dir . $filename)) {
          $image_path = '../uploads/projects/' . $filename;
        }
    }

    // Default status is 'pending'
    $sql = "INSERT INTO projects (user_id, project_title, category_name, description,
            target_amount, current_amount, start_date, end_date,
            status, cover_photo) 
            VALUES (:uid, :title, :cat, :desc,
            :goal, 0, NOW(), :end_date,
            'pending', :photo)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $organizer_id,
            ':title' => $title,
            ':cat' => $category_name,
            ':desc' => $desc,
            ':goal' => $goal,
            ':end_date' => $deadline,
            ':photo' => $image_path
        ]);
        $msg = "Project created! It is now PENDING admin approval.";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Error creating project: " . $e->getMessage();
        $msg_type = "error";
    }
}

// ==========================================
// FILTER & PAGINATION LOGIC
// ==========================================
$search = $_GET['search'] ?? '';
$filter_cat = (isset($_GET['category']) && $_GET['category'] !== '') ? $_GET['category'] : '';
$filter_category_name = '';

if ($filter_cat !== '' && array_key_exists((int)$filter_cat, $categories)) {
    $filter_category_name = $categories[(int)$filter_cat];
}

$filter_stat = $_GET['status'] ?? ''; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; 
$limit = 8;
$offset = ($page - 1) * $limit;

$params = [];
$where_clauses = ["1=1"];
$where_clauses[] = "p.user_id = ?";
$params[] = $organizer_id;

if ($search) {
    $where_clauses[] = "p.project_title ILIKE ?"; 
    $params[] = "%$search%";
}
if ($filter_category_name) {
    $where_clauses[] = "p.category_name ILIKE ?";
    $params[] = "%".trim($filter_category_name)."%";
}
if ($filter_stat) {
    $where_clauses[] = "p.status = ?"; 
    $params[] = $filter_stat;
}

$where_sql = implode(' AND ', $where_clauses);

$count_query = "SELECT COUNT(*) FROM projects p WHERE $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params); 
$total_projects = $stmt_count->fetchColumn(); 
$total_pages = ceil($total_projects / $limit);

// FIX IS HERE: Changed p.created_at back to p.start_date
$query = "
    SELECT p.*, u.full_name AS organizer_name,
    (SELECT COUNT(*) FROM donations d WHERE d.project_id = p.id) AS donor_count
    FROM projects p
    JOIN users u ON p.user_id = u.id
    WHERE $where_sql
    ORDER BY p.start_date DESC 
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query);
$param_index = 1;
foreach ($params as $val) {
    $stmt->bindValue($param_index++, $val);
}
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for Status Colors
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'active': return '#059669'; // Green
        case 'pending': return '#D97706'; // Orange/Gold
        case 'completed': return '#2563EB'; // Blue
        case 'failed': return '#DC2626'; // Red
        default: return '#6B7280'; // Gray
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects Panel - CauseVia</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* =========================================
           1. MODERN VARIABLES & RESET
           ========================================= */
        :root {
            --bg-body: #F9F5EB; 
            --primary: #75162D; 
            --primary-dark: #560B18;
            --accent: #D4B472; 
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --white: #FFFFFF;
            --border-light: #E5E7EB;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --radius-lg: 16px;
            --radius-md: 10px;
            --sidebar-width: 320px;
        }

        * { box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased; 
        }

        /* =========================================
           2. LAYOUT & STRUCTURE
           ========================================= */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2.5rem 3rem;
            min-height: 100vh;
            max-width: 1600px;
        }

        /* =========================================
           3. HEADER & FILTERS
           ========================================= */
        .page-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .filter-container {
            display: flex;
            gap: 0.5rem;
            background: var(--white);
            padding: 0.75rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            align-items: center;
        }

        .form-input-search, .form-select {
            padding: 0.6rem 1rem;
            border: 1px solid transparent; 
            border-radius: 6px;
            font-size: 0.95rem;
            color: var(--text-main);
            outline: none;
            background: transparent;
            transition: all 0.2s;
        }
        
        .filter-divider {
            width: 1px;
            height: 24px;
            background: #E5E7EB;
        }

        .form-input-search:focus, .form-select:focus {
            background: #F9FAFB;
            border-color: #E5E7EB;
        }

        .btn-filter {
            background-color: var(--primary);
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            margin-left: 0.5rem;
        }
        .btn-filter:hover { background-color: var(--primary-dark); }

        .btn-create-new {
            background: linear-gradient(135deg, var(--primary) 0%, #962441 100%);
            color: white;
            padding: 0.9rem 2rem;
            border-radius: 50px; 
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(117, 22, 45, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-create-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(117, 22, 45, 0.35);
        }

        /* =========================================
           4. CARD GRID
           ========================================= */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 2.5rem; 
        }

        .project-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03); 
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card-img-wrapper {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .project-card:hover .card-img-top { transform: scale(1.05); }

        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            backdrop-filter: blur(4px);
            z-index: 2;
        }
        
        .card-body {
            padding: 1.8rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 0.5rem 0;
            line-height: 1.3;
        }

        .card-date {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .progress-block { margin-top: auto; }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .raised-txt { color: var(--primary); }
        .goal-txt { color: var(--text-muted); font-weight: 400; }

        .progress-track {
            height: 8px;
            background: #F3F4F6;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #D4B472, #75162D);
            border-radius: 10px;
        }

        .card-footer-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .btn-view {
            margin-top: 1.2rem;
            width: 100%;
            padding: 0.8rem;
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        /* =========================================
           5. MODAL
           ========================================= */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(31, 41, 55, 0.5); 
            backdrop-filter: blur(5px); 
        }

        .modal-content {
            background-color: #fff;
            margin: 3% auto;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 800px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideIn 0.3s ease-out;
            position: relative;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 { margin: 0; font-size: 1.5rem; color: var(--primary); }

        .close-modal {
            background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #9ca3af;
        }
        .close-modal:hover { color: var(--primary); }

        .modal-body { padding: 2rem; max-height: 70vh; overflow-y: auto; }

        .modal-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-top: 0.4rem;
            font-family: inherit;
            transition: 0.2s;
        }
        .modal-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(212, 180, 114, 0.2);
            outline: none;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 2px;
            display: block;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #F3F4F6;
            background: #F9FAFB;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-primary { background: var(--primary); color: white; padding: 0.8rem 1.5rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: white; border: 1px solid #d1d5db; color: #374151; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .btn-secondary:hover { background: #f3f4f6; }

        .view-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) {
            .view-layout { grid-template-columns: 1fr; }
            .projects-grid { grid-template-columns: 1fr; }
            .page-header-actions { flex-direction: column; align-items: stretch; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }

        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 3rem; }
        .page-link {
            padding: 0.5rem 1rem; border: 1px solid #e5e7eb; background: white; text-decoration: none; color: var(--text-main); border-radius: 6px;
        }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert.success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert.error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
    </style>
</head>
<body>

    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        
        <?php if($msg): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="page-header-actions">
            <div>
                <h1 style="margin:0; font-size:1.8rem; color:var(--primary); font-weight:800;">Campaign Projects</h1>
                <p style="margin:5px 0 0; color:var(--text-muted);">Manage and track your fundraising activities.</p>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap:wrap;">
                
                <form method="GET" action="" class="filter-container">
                    <i class="fas fa-search" style="color:#9CA3AF; margin-left:5px;"></i>
                    <input type="text" name="search" placeholder="Search title..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="form-input-search">
                    
                    <div class="filter-divider"></div>

                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo ($filter_stat == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo ($filter_stat == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo ($filter_stat == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo ($filter_stat == 'failed') ? 'selected' : ''; ?>>Failed</option>
                    </select>

                    <div class="filter-divider"></div>

                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ((string)$filter_cat === (string)$id) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-filter">Apply</button>
                </form>

                <button type="button" class="btn-create-new" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create New
                </button>
            </div>
        </div>

        <div class="projects-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach($projects as $row): 
                    $percent = ($row['target_amount'] > 0) ? ($row['current_amount'] / $row['target_amount']) * 100 : 0;
                    $percent = min(100, $percent); 
                    $donor_text = ($row['donor_count'] > 1) ? "Donors" : "Donor";
                    $photo = !empty($row['cover_photo']) ? $row['cover_photo'] : 'assets/images/placeholder.jpg';
                    $statColor = getStatusColor($row['status']);
                ?>
                <div class="project-card">
                    <div class="card-img-wrapper">
                        <div class="category-badge"><?php echo htmlspecialchars($row['category_name']); ?></div>
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Project Cover" class="card-img-top">
                    </div>
                    
                    <div class="card-body">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div class="card-date">
                                <i class="far fa-calendar-alt"></i> Due: <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                            </div>
                            <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; color:<?php echo $statColor; ?>; background:<?php echo $statColor.'20'; ?>; padding:3px 8px; border-radius:4px;">
                                <?php echo $row['status']; ?>
                            </span>
                        </div>

                        <h3 class="card-title"><?php echo htmlspecialchars($row['project_title']); ?></h3>
                        
                        <div class="progress-block">
                            <div class="progress-info">
                                <span class="raised-txt">₱<?php echo number_format($row['current_amount']); ?></span>
                                <span class="goal-txt">of ₱<?php echo number_format($row['target_amount']); ?></span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>

                        <div class="card-footer-stats">
                            <span><i class="fas fa-users"></i> <?php echo $row['donor_count'] . " " . $donor_text; ?></span>
                        </div>

                        <button type="button" class="btn-view" onclick='openViewModal(<?php echo json_encode($row); ?>)'>
                            View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background:white; border-radius:16px; border:1px dashed #d1d5db;">
                    <h3 style="color:#374151;">No projects found.</h3>
                    <p style="color:#6b7280;">Try adjusting your filters or create a new campaign!</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_cat); ?>&status=<?php echo urlencode($filter_stat); ?>" 
                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Start New Campaign</h2>
                <button class="close-modal" onclick="closeCreateModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="createForm">
                <input type="hidden" name="create_project" value="1">
                
                <div class="modal-body">
                    <div id="step1" class="step-section active">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label class="form-label">Project Title</label>
                                <input type="text" name="title" id="inp_title" required class="modal-input" placeholder="e.g. Feeding Program">
                            </div>
                            <div>
                                <label class="form-label">Category</label>
                                <select name="category" id="inp_cat" required class="modal-input">
                                    <?php foreach($categories as $id => $name): ?>
                                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Target Amount (₱)</label>
                                <input type="number" name="target_amount" id="inp_goal" required class="modal-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" id="inp_date" required class="modal-input">
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="inp_desc" rows="4" required class="modal-input" placeholder="Explain your cause..."></textarea>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <label class="form-label">Cover Photo</label>
                            <input type="file" name="cover_photo" class="modal-input" style="padding: 0.5rem;">
                        </div>
                    </div>

                    <div id="step2" class="step-section">
                        <h3 style="color:var(--primary);">Confirm Details</h3>
                        <p style="color: #d97706; font-size: 0.9rem; background: #fffbeb; padding: 10px; border-radius: 6px;">
                            <i class="fas fa-info-circle"></i> Note: This project will be submitted as <strong>PENDING</strong> and requires Admin approval before going live.
                        </p>
                        <p><strong>Title:</strong> <span id="rev_title"></span></p>
                        <p><strong>Goal:</strong> ₱<span id="rev_goal"></span></p>
                    </div>
                </div>

                <div class="modal-footer">
                    <div id="btns_step1">
                        <button type="button" class="btn-secondary" onclick="closeCreateModal()">Cancel</button>
                        <button type="button" class="btn-primary" onclick="goToStep2()">Next Step</button>
                    </div>
                    <div id="btns_step2" style="display:none; gap:1rem;">
                        <button type="button" class="btn-secondary" onclick="goToStep1()">Back</button>
                        <button type="submit" class="btn-primary">Submit for Approval</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <h2 style="margin:0;">Project Overview</h2>
                    <span id="view_status_badge" style="font-size:0.8rem; padding:4px 10px; border-radius:20px; color:white; font-weight:bold;"></span>
                </div>
                <button class="close-modal" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="view-layout">
                    <div class="view-left">
                        <img id="view_img" src="" alt="Project" style="width:100%; border-radius:12px; height:300px; object-fit:cover; margin-bottom:1rem;">
                        <h2 id="view_title" style="color:var(--primary); margin-bottom:0.5rem;"></h2>
                        <p id="view_desc" style="color:#4b5563; line-height:1.7; white-space: pre-wrap;"></p>
                    </div>
                    <div class="view-right">
                        <div style="background:#F9FAFB; padding:1.5rem; border-radius:12px; border:1px solid #e5e7eb;">
                            <h4 style="margin-top:0;">Fundraising Status</h4>
                            <div class="progress-track">
                                <div id="view_progress" class="progress-fill"></div>
                            </div>
                            <div style="margin-top:10px; display:flex; justify-content:space-between; font-weight:bold;">
                                <span style="color:var(--primary);">Raised: ₱<span id="view_raised"></span></span>
                                <span style="color:#6b7280;">Goal: ₱<span id="view_goal"></span></span>
                            </div>
                        </div>
                        <h3 style="margin-top:2rem; border-bottom:1px solid #eee; padding-bottom:10px;">Recent Donors</h3>
                        <div id="donor_list_container" style="max-height:250px; overflow-y:auto;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const createModal = document.getElementById('createModal');
        const viewModal = document.getElementById('viewModal');
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const btns1 = document.getElementById('btns_step1');
        const btns2 = document.getElementById('btns_step2');

        function openCreateModal() {
            createModal.style.display = 'block';
            goToStep1();
        }
        function closeCreateModal() { createModal.style.display = 'none'; }

        function goToStep2() {
            if(!document.getElementById('inp_title').value || !document.getElementById('inp_goal').value) {
                alert("Please fill in required fields."); return;
            }
            document.getElementById('rev_title').innerText = document.getElementById('inp_title').value;
            document.getElementById('rev_goal').innerText = document.getElementById('inp_goal').value;
            
            step1.style.display = 'none';
            step1.classList.remove('active');
            step2.style.display = 'block';
            setTimeout(() => step2.classList.add('active'), 10);
            btns1.style.display = 'none';
            btns2.style.display = 'flex';
        }

        function goToStep1() {
            step2.style.display = 'none';
            step2.classList.remove('active');
            step1.style.display = 'block';
            setTimeout(() => step1.classList.add('active'), 10);
            btns2.style.display = 'none';
            btns1.style.display = 'flex';
        }

        function openViewModal(data) {
            viewModal.style.display = 'block';
            document.getElementById('view_title').innerText = data.project_title;
            document.getElementById('view_desc').innerText = data.description;
            document.getElementById('view_goal').innerText = parseFloat(data.target_amount).toLocaleString();
            document.getElementById('view_raised').innerText = parseFloat(data.current_amount).toLocaleString();
            
            let percent = 0;
            if(data.target_amount > 0) percent = (data.current_amount / data.target_amount) * 100;
            document.getElementById('view_progress').style.width = Math.min(percent, 100) + "%";
            
            let photo = data.cover_photo ? data.cover_photo : '../assets/images/placeholder.jpg';
            document.getElementById('view_img').src = photo;

            const badge = document.getElementById('view_status_badge');
            badge.innerText = data.status;
            let color = '#6B7280';
            if(data.status === 'active') color = '#059669';
            else if(data.status === 'pending') color = '#D97706';
            else if(data.status === 'failed') color = '#DC2626';
            else if(data.status === 'completed') color = '#2563EB';
            badge.style.backgroundColor = color;

            const donorContainer = document.getElementById('donor_list_container');
            donorContainer.innerHTML = '<p style="color:#6b7280;">Loading donors...</p>';
            
            fetch('?fetch_donors=1&project_id=' + data.id)
                .then(response => response.json())
                .then(donors => {
                    if(donors.length === 0) {
                        donorContainer.innerHTML = '<p style="color:#6b7280; font-style:italic;">No donations yet.</p>';
                    } else {
                        let html = '<ul style="list-style:none; padding:0;">';
                        donors.forEach(d => {
                            html += `
                                <li style="padding:10px 0; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between;">
                                    <div>
                                        <div style="font-weight:600; font-size:0.95rem;">${d.full_name}</div>
                                        <div style="font-size:0.8rem; color:#9ca3af;">${d.created_at}</div>
                                    </div>
                                    <div style="font-weight:700; color:#059669;">+₱${parseFloat(d.amount).toLocaleString()}</div>
                                </li>`;
                        });
                        html += '</ul>';
                        donorContainer.innerHTML = html;
                    }
                })
                .catch(err => {
                    donorContainer.innerHTML = '<p style="color:red;">Error loading donors.</p>';
                });
        }

        function closeViewModal() { viewModal.style.display = 'none'; }

        window.onclick = function(event) {
            if (event.target == createModal) closeCreateModal();
            if (event.target == viewModal) closeViewModal();
        }
    </script>
</body>
</html>