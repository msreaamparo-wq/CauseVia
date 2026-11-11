<?php
// header.php
require_once 'config.php';

// --- Data Fetching Logic ---
$user_id = $_SESSION['user_id'] ?? null;
$h_fullname = 'Guest';
$h_initials = 'G';
$h_batch = '';
$h_course = '';
$h_email = '';
$h_role = '';

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, email, batch_year, course, role FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $u = $stmt->fetch();
        
        if ($u) {
            $h_fullname = $u['full_name'] ?? 'Alumni';
            $h_email = $u['email'];
            $h_batch = $u['batch_year'] ?? '';
            $h_course = $u['course'] ?? '';
            $h_role = ucfirst($u['role'] ?? 'Alumni');

            // Initials Logic
            if (!empty($h_fullname)) {
                $parts = explode(' ', trim($h_fullname));
                $h_initials = (count($parts) >= 2) 
                    ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1)) 
                    : strtoupper(substr($parts[0], 0, 1));
            }
        }
    } catch (PDOException $e) { /* Silent Fail */ }
}

// Active Link Logic
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CauseVia</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- CSS RESET & VARIABLES --- */
        :root {
            --primary: #6C131F;
            --primary-light: #FDF2F4;
            --text-main: #111827;
            --text-sub: #6B7280;
            --border: #E5E7EB;
            --white: #FFFFFF;
            --font-main: 'Inter', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-main);
            background-color: #F9FAFB;
            color: var(--text-main);
            /* Base font size increased slightly */
            font-size: 16px; 
        }

        /* --- NAVBAR CONTAINER (BIGGER HEIGHT) --- */
        .top-nav {
            background: var(--white);
            height: 90px; /* INCREASED from 70px to 90px */
            width: 100%;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px; /* Wider padding */
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        /* --- LOGO AREA (BIGGER) --- */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 15px; /* More gap */
            text-decoration: none;
        }

        .logo-box {
            width: 52px;  /* INCREASED from 40px */
            height: 52px; /* INCREASED from 40px */
            background-color: var(--primary);
            color: var(--white);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px; /* Bigger text inside logo */
            flex-shrink: 0;
        }

        .brand-text h1 {
            font-size: 24px; /* INCREASED from 18px */
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .brand-text span {
            font-size: 13px; /* INCREASED from 11px */
            color: var(--text-sub);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* --- CENTER LINKS (BIGGER TEXT) --- */
        .nav-links {
            display: flex;
            gap: 40px; /* More spacing between links */
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-sub);
            font-size: 16px; /* INCREASED from 14px */
            font-weight: 500;
            padding: 10px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            background-color: var(--primary-light);
            font-weight: 600;
        }

        /* --- RIGHT SIDE / PROFILE (BIGGER) --- */
        .nav-profile {
            position: relative;
            display: flex;
            align-items: center;
        }

        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px;
            border-radius: 50px;
            transition: background 0.2s;
        }

        .profile-btn:hover {
            background-color: #F3F4F6;
        }

        .avatar-circle {
            width: 48px;  /* INCREASED from 36px */
            height: 48px; /* INCREASED from 36px */
            background-color: var(--primary);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px; /* Bigger initials */
            font-weight: 600;
            border: 2px solid var(--white);
            box-shadow: 0 0 0 1px var(--border);
        }

        .user-meta {
            text-align: left;
            margin-right: 6px;
        }

        .user-name {
            display: block;
            font-size: 15px; /* INCREASED from 13px */
            font-weight: 600;
            color: var(--text-main);
        }

        .user-role {
            display: block;
            font-size: 12px; /* INCREASED from 11px */
            color: var(--text-sub);
            margin-top: 2px;
        }

        .login-link {
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            font-size: 16px;
            padding: 10px 20px;
            border: 1px solid var(--primary);
            border-radius: 8px;
        }
        
        .login-link:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* --- DROPDOWN MENU --- */
        .dropdown-menu {
            position: absolute;
            top: 75px; /* Adjusted para dili matabunan sa navbar */
            right: 0;
            width: 260px; /* Wider dropdown */
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: fadeIn 0.2s ease-out;
            z-index: 1001;
        }

        .dropdown-menu.show { display: flex; }

        .dd-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: #FAFAFA;
        }

        .dd-name { font-weight: 700; font-size: 16px; color: var(--text-main); }
        .dd-email { font-size: 13px; color: var(--text-sub); margin-bottom: 8px; }
        .dd-badge { 
            font-size: 11px; 
            background: var(--white); 
            border: 1px solid var(--border); 
            padding: 4px 8px; 
            border-radius: 6px; 
            color: var(--primary);
            font-weight: 600;
        }

        .dd-item {
            padding: 14px 20px; /* Bigger touch targets */
            text-decoration: none;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 500;
            transition: background 0.1s;
        }

        .dd-item:hover {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .dd-logout {
            border-top: 1px solid var(--border);
            color: #DC2626;
        }

        .dd-logout:hover {
            background-color: #FEF2F2;
            color: #DC2626;
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .nav-links, .user-meta { display: none; }
            .top-nav { padding: 0 20px; height: 70px; /* Shrink gamay sa mobile */ }
            .logo-box { width: 40px; height: 40px; font-size: 16px; }
            .brand-text h1 { font-size: 20px; }
            .avatar-circle { width: 36px; height: 36px; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="index.php" class="nav-brand">
        <div class="logo-box">CV</div>
        <div class="brand-text">
            <h1>CauseVia</h1>
            <span>Alumni Portal</span>
        </div>
    </a>

    <div class="nav-links">
        <a href="alum_dashboard.php" class="nav-link <?= ($current_page == 'alum_dashboard.php') ? 'active' : '' ?>">Dashboard</a>
        <a href="fund_projects.php" class="nav-link <?= ($current_page == 'fund_projects.php') ? 'active' : '' ?>">Fund Projects</a>
        <a href="my_donation.php" class="nav-link <?= ($current_page == 'my_donation.php') ? 'active' : '' ?>">My Donations</a>
    </div>

    <div class="nav-profile">
        <?php if ($user_id): ?>
            <button onclick="toggleDropdown()" class="profile-btn" id="profileTrigger">
                <div class="user-meta">
                    <span class="user-name"><?php echo htmlspecialchars($h_fullname); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($h_role); ?></span>
                </div>
                <div class="avatar-circle">
                    <?php echo $h_initials; ?>
                </div>
            </button>

            <div id="userDropdown" class="dropdown-menu">
                <div class="dd-header">
                    <div class="dd-name"><?php echo htmlspecialchars($h_fullname); ?></div>
                    <div class="dd-email"><?php echo htmlspecialchars($h_email); ?></div>
                    <span class="dd-badge">Batch <?php echo htmlspecialchars($h_batch); ?></span>
                </div>
                
                <a href="profile.php" class="dd-item">My Profile</a>
                <a href="settings.php" class="dd-item">Settings</a>
                <a href="logout.php" class="dd-item dd-logout">Sign Out</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="login-link">Log In</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    function toggleDropdown() {
        const dd = document.getElementById('userDropdown');
        dd.classList.toggle('show');
    }

    // Close when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest('.nav-profile')) {
            const dropdowns = document.getElementsByClassName("dropdown-menu");
            for (let i = 0; i < dropdowns.length; i++) {
                if (dropdowns[i].classList.contains('show')) {
                    dropdowns[i].classList.remove('show');
                }
            }
        }
    }
</script>