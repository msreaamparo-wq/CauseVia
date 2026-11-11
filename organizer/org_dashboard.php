<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'organizer') {
    header('Location: ../public/login.php');
    exit();
}

/* =======================
   DASHBOARD COUNTS
======================= */

// Total Projects
$totalProjects = $pdo->query("SELECT COUNT(id) FROM projects")->fetchColumn();

// Active Projects
$activeProjects = $pdo->query("
    SELECT COUNT(id) FROM projects WHERE status = 'active'
")->fetchColumn();

// Total Donors
$totalDonors = $pdo->query("
    SELECT COUNT(DISTINCT user_id) FROM donations
")->fetchColumn();

// Pending Approval
$pendingApproval = $pdo->query("
    SELECT COUNT(id) FROM donations WHERE status = 'pending'
")->fetchColumn();

/* =======================
   PROJECT OVERVIEW (BAR GRAPH)
======================= */

$year = $_GET['year'] ?? date('Y');

$stmt = $pdo->prepare("
    SELECT 
        EXTRACT(MONTH FROM start_date) AS month,
        SUM(current_amount) AS total
    FROM projects
    WHERE EXTRACT(YEAR FROM start_date) = ?
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$year]);

$overviewData = array_fill(1, 12, 0);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $overviewData[(int)$row['month']] = (float)$row['total'];
}

/* =======================
   TOP DONATIONS THIS MONTH
======================= */

$topDonations = $pdo->query("
    SELECT u.full_name, SUM(d.amount) AS total_amount
    FROM donations d
    JOIN users u ON d.user_id = u.id
    WHERE EXTRACT(MONTH FROM d.created_at) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM d.created_at) = EXTRACT(YEAR FROM CURRENT_DATE)
    GROUP BY u.full_name
    ORDER BY total_amount DESC
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   PROJECT DETAILS (LATEST 5)
======================= */

$projectDetails = $pdo->query("
    SELECT project_title, current_amount, target_amount, status
    FROM projects
    ORDER BY id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Organizer Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* --- GLOBAL SETTINGS --- */
body {
    font-family: 'Poppins', sans-serif; /* Soft & Round Font */
    background-color: #F9F5EB; /* Warm White / Cream */
    color: #374151; /* Soft Charcoal (Dili Pure Black) */
    margin: 0;
}

/* --- MAIN CONTENT SPACING --- */
.main-content {
    margin-left: 280px; 
    width: calc(100% - 280px);
    padding: 40px; 
    padding-top: 30px;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .main-content { margin-left: 0; width: 100%; padding: 20px; padding-top: 100px; }
}

/* --- CARDS --- */
.card {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 24px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); 
    /* Default subtle border */
    border: 1px solid rgba(0,0,0,0.05);
}
/* 1. Burgundy Border (Para sa Chart) */
.card-outline-burgundy {
    border: 1px solid #75162D; /* Burgundy Color */
    border-top-width: 4px; /* Baga sa taas para accent */
}

/* 2. Gold Border (Para sa Top Donations) */
.card-outline-gold {
    border: 1px solid #D4AF37; /* Gold Color */
    border-top-width: 4px; 
}

/* 3. Wine Border (Para sa Table) */
.card-outline-wine {
    border: 1px solid #560B18; /* Dark Maroon Color */
    border-top-width: 4px;
}

/* --- STATS CARD (Top Accent Style) --- */
.stat-card {
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 5px; /* Colored Line on Top */
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(117, 22, 45, 0.08);
}

/* Colors for Top Accent */
.accent-burgundy::after { background: #75162D; }
.accent-gold::after { background: #F2D9A0; }
.accent-wine::after { background: #3B010B; }
.accent-maroon::after { background: #560B18; }

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: #1F2937;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.85rem;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* --- GRID LAYOUT --- */
.grid { display: grid; gap: 24px; }
.grid-4 { grid-template-columns: repeat(4, 1fr); }
.grid-2 { grid-template-columns: 2fr 1fr; }
@media (max-width: 1200px) {
    .grid-4 { grid-template-columns: repeat(2, 1fr); }
    .grid-2 { grid-template-columns: 1fr; }
}

/* --- HEADERS --- */
h3 {
    margin: 0 0 20px 0;
    color: #560B18; /* Maroon Title */
    font-weight: 600;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* --- TABLE DESIGN (Fixed Headers) --- */
table { 
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0; 
}

/* HEADER STYLE (Colored Box) */
th {
    background-color: #F5E6CC; /* Champagne / Soft Gold Background */
    color: #560B18; /* Dark Maroon Text */
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 20px;
    text-align: left;
}

/* Round Corners for Header */
th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

/* BODY STYLE */
td {
    padding: 18px 20px;
    border-bottom: 1px solid #F3F4F6;
    color: #374151;
    font-size: 0.95rem;
    vertical-align: middle;
}

tr:last-child td { border-bottom: none; }

/* Hover Effect on Row */
tbody tr:hover {
    background-color: #FFFCF5; /* Very Light Gold */
}

/* --- BADGES --- */
.badge {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}
.active {
    background-color: #DCFCE7; /* Light Green */
    color: #166534;
    border: 1px solid #BBF7D0;
}
.pending {
    background-color: #FEF3C7; /* Light Yellow/Gold */
    color: #92400E;
    border: 1px solid #FDE68A;
}

/* --- DONATION LIST --- */
.donation-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px dashed #E5E7EB;
}
.donation-item:last-child { border-bottom: none; }
.donor-name { font-weight: 500; color: #111827; }
.donor-amount { color: #75162D; font-weight: 700; }

/* --- FORMS --- */
select {
    padding: 8px 16px;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
}
select:focus { outline: none; border-color: #75162D; }

</style>
</head>

<body>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">

    <div class="grid grid-4">
        <div class="card stat-card accent-burgundy">
            <div class="stat-value"><?= $totalProjects ?></div>
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="card stat-card accent-gold">
            <div class="stat-value"><?= $activeProjects ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="card stat-card accent-wine">
            <div class="stat-value"><?= $totalDonors ?></div>
            <div class="stat-label">Total Donors</div>
        </div>
        <div class="card stat-card accent-maroon">
            <div class="stat-value"><?= $pendingApproval ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>

    <br>

    <div class="grid grid-2">
        <div class="card card-outline-burgundy">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0; color:#75162D;">Funds Raised (<?= $year ?>)</h3>
                <form method="GET">
                    <select name="year" onchange="this.form.submit()">
                        <?php for ($y = 2015; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
            <canvas id="projectChart" height="150"></canvas>
        </div>

        <div class="card card-outline-gold">
            <h3 style="color:#B08D55;">Top Donations This Month</h3>
            <?php if ($topDonations): ?>
                <?php foreach ($topDonations as $don): ?>
                    <div class="donation-item">
                        <span class="donor-name"><?= htmlspecialchars($don['full_name']) ?></span>
                        <span class="donor-amount">₱<?= number_format($don['total_amount'],2) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#9CA3AF; font-size:0.9rem; font-style:italic;">No donations recorded this month.</p>
            <?php endif; ?>
        </div>
    </div>

    <br>

    <div class="card card-outline-wine">
        <h3 style="color:#560B18;">Recent Project Details</h3>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Project Title</th>
                        <th>Raised</th>
                        <th>Target</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projectDetails as $p): ?>
                    <tr>
                        <td style="font-weight:600; color:#560B18"><?= htmlspecialchars($p['project_title']) ?></td>
                        <td>₱<?= number_format($p['current_amount'],2) ?></td>
                        <td>₱<?= number_format($p['target_amount'],2) ?></td>
                        <td>
                            <span class="badge <?= $p['status']=='active'?'active':'pending' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
const ctx = document.getElementById('projectChart');

// UPDATE CHART FONT TO POPPINS
Chart.defaults.font.family = 'Poppins';
Chart.defaults.color = '#374151';

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Funds Raised',
            data: <?= json_encode(array_values($overviewData)) ?>,
            backgroundColor: '#75162D', // Burgundy
            hoverBackgroundColor: '#560B18', // Dark Maroon
            borderRadius: 4,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#3B010B',
                titleColor: '#F2E5C6',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
                titleFont: { family: 'Poppins' },
                bodyFont: { family: 'Poppins' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F3F4F6', borderDash: [5, 5] }, 
                ticks: {
                    color: '#6B7280',
                    callback: value => '₱' + value.toLocaleString()
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#6B7280' }
            }
        }
    }
});
</script>

</body>
</html>