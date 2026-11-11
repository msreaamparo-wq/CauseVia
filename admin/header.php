<?php
require_once 'db_connect.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Crowdfunding Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --champagne-beige: #F2E5C6;
            --sand-gold: #F2D9A0;
            --burgundy: #75162D;
            --dark-maroon: #560B18;
            --deep-wine: #3B010B;
            --black: #000000;
        }
        
        body {
            background-color: var(--champagne-beige);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--deep-wine);
            padding-top: 56px; /* For fixed navbar */
        }
        
        .navbar-custom {
            background-color: var(--deep-wine) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand, .nav-link {
            color: var(--champagne-beige) !important;
        }
        
        .nav-link:hover {
            color: var(--sand-gold) !important;
        }
        
        .btn-primary-custom {
            background-color: var(--burgundy);
            border-color: var(--burgundy);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background-color: var(--dark-maroon);
            border-color: var(--dark-maroon);
        }
        
        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-top: 4px solid var(--burgundy);
        }
        
        .card-header-custom {
            background-color: var(--dark-maroon);
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table-header-custom {
            background-color: var(--burgundy) !important;
            color: white !important;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 5px solid var(--burgundy);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--burgundy);
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-maroon);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: var(--dark-maroon);
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 250px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--burgundy);
            color: var(--sand-gold);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .logo-area {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo-text {
            color: var(--sand-gold);
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-active {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .modal-header-custom {
            background-color: var(--dark-maroon);
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--burgundy);
            box-shadow: 0 0 0 0.25rem rgba(117, 22, 45, 0.25);
        }
        
        .nav-tabs .nav-link.active {
            background-color: var(--burgundy);
            color: white;
            border-color: var(--burgundy);
        }
        
        .nav-tabs .nav-link {
            color: var(--burgundy);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .logo-text,
            .sidebar .menu-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px 0;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
        }
        
        .page-title {
            color: var(--dark-maroon);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--sand-gold);
            margin-bottom: 25px;
        }
        
        .footer {
            background-color: var(--dark-maroon);
            color: white;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        .alert-custom {
            background-color: var(--sand-gold);
            border-color: var(--burgundy);
            color: var(--deep-wine);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-hands-helping"></i> Alumni Crowdfunding Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name'] ?? 'Admin'; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="setting.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="logo-area">
            <div class="logo-text">
                <i class="fas fa-university"></i> <span class="menu-text">Admin Panel</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <li>
                <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="create_projects.php" class="<?php echo $current_page == 'create_projects.php' ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i> <span class="menu-text">Create Project</span>
                </a>
            </li>
            <li>
                <a href="donation.php" class="<?php echo $current_page == 'donation.php' ? 'active' : ''; ?>">
                    <i class="fas fa-donate"></i> <span class="menu-text">Donations</span>
                </a>
            </li>
            <li>
                <a href="setting.php" class="<?php echo $current_page == 'setting.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <span class="menu-text">Settings</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">