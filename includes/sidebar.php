<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- FONT AWESOME (safe bisan naa na sa header) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="sidebar">

    <div class="sidebar-inner">

        <p class="menu-title">Main Menu</p>

        <nav class="menu">
            <a href="org_dashboard.php" class="<?= $current_page === 'org_dashboard.php' ? 'active' : ''; ?>">
                <span>📊</span> Dashboard
            </a>

            <a href="create_projects.php" class="<?= $current_page === 'create_projects.php' ? 'active' : ''; ?>">
                <span>➕</span> Projects
            </a>

            <a href="my_projects.php" class="<?= $current_page === 'my_projects.php' ? 'active' : ''; ?>">
                <span>📂</span> Donations
            </a>
        </nav>

        <div class="account-section">
            <p class="menu-title">Settings</p>
            <nav class="menu">
                <a href="#" class="logout" onclick="confirmLogout(event)">
                    <span>🚪</span> Logout
                </a>
            </nav>
        </div>

    </div>
</aside>

<!-- LOGOUT MODAL -->
<div id="logoutConfirmModal" class="logout-modal-overlay">
    <div class="logout-modal-card">
        <div class="logout-icon-box">
            <i class="fas fa-power-off"></i>
        </div>

        <h3>Signing Out?</h3>
        <p>You are about to end your secure session.<br>Are you sure you want to proceed?</p>

        <div class="logout-actions">
            <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
         <a href="../organizer/logout.php" class="btn-confirm-logout">Yes, Log Out</a>


        </div>
    </div>
</div>

<style>
/* ===== SIDEBAR ===== */
.sidebar {
    width: 260px;
    background-color: #3B010B;
    border-right: 1px solid #560B18;
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0;
    padding: 100px 1.5rem 2rem;
    color: #F2E5C6;
    z-index: 40;
}

.menu-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #F2D9A0;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1rem;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.9rem 1rem;
    font-size: 0.9rem;
    border-radius: 8px;
    color: #F2E5C6;
    margin-bottom: 0.5rem;
    text-decoration: none;
    transition: 0.3s;
}

.menu a:hover {
    background: rgba(242,217,160,0.1);
    transform: translateX(5px);
}

.menu a.active {
    background: #75162D;
    color: #fff;
    border: 1px solid #560B18;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.logout {
    color: #F2D9A0 !important;
    border-top: 1px solid rgba(242,217,160,0.2);
    margin-top: 1rem;
}

.logout:hover {
    background: #560B18 !important;
}

.account-section { margin-top: 3rem; }

/* ===== LOGOUT MODAL ===== */
.logout-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(59,1,11,0.6);
    backdrop-filter: blur(5px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.logout-modal-overlay.active { display: flex; }

.logout-modal-card {
    background: #fff;
    width: 90%;
    max-width: 380px;
    padding: 30px;
    border-radius: 16px;
    text-align: center;
}

.logout-icon-box {
    width: 60px;
    height: 60px;
    background: #FFF1F2;
    color: #9F1239;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 24px;
}

.logout-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn-cancel {
    background: white;
    border: 1px solid #ddd;
    padding: 10px 24px;
    border-radius: 50px;
    cursor: pointer;
}

.btn-confirm-logout {
    background: #9F1239;
    color: white;
    padding: 10px 24px;
    border-radius: 50px;
    text-decoration: none;
}
</style>

<script>
function confirmLogout(e) {
    e.preventDefault();
    document.getElementById('logoutConfirmModal').classList.add('active');
}

function closeLogoutModal() {
    document.getElementById('logoutConfirmModal').classList.remove('active');
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('logoutConfirmModal');
    if (e.target === modal) closeLogoutModal();
});
</script>
