<?php
require_once __DIR__ . '/db.php';

// 2. SESSION HANDLING
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check kung naka-login (Assuming nag set kag $_SESSION['user_id'])
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, kung walay session, pag-set ug dummy ID or redirect
    // header("Location: login.php"); exit();
    die("Error: Please login first.");
}

$user_id = $_SESSION['user_id'];

// 3. FETCH USER DATA (PostgreSQL Way)
// Note: Gigamit ang parameterized query ($1) para security
$sql = "SELECT id, full_name, username, email, batch_year, role FROM users WHERE id = $1";
$result = pg_query_params($dbconn, $sql, array($user_id));

if (!$result) {
    die("Error in SQL query: " . pg_last_error());
}

if (pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    
    // Assign Variables
    $org_name = $row['full_name'];
    $org_username = $row['username'];
    $org_email = $row['email'];
    $org_role = $row['role']; // Example: "Organizer"
    $batch_year = $row['batch_year'];
    
    // Initial for Avatar
    $initial = strtoupper(substr($org_name, 0, 1));
} else {
    die("User not found.");
}

// pg_close($dbconn); // Optional here, usually closed at end of script
?>
<form action="update_profile.php" method="POST"></form>
<nav class="header">
    <div class="header-inner">
        <div class="logo-section">
            <h1>CauseVia</h1>
            <span class="role-badge"><?php echo htmlspecialchars($org_role); ?></span>
        </div>

        <div class="user-section" onclick="toggleUserDropdown(event)">
            
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($org_name); ?></p>
                <p class="user-role">Organizer Account</p>
            </div>

            <div class="user-avatar">
                <?php echo $initial; ?>
            </div>

            <div class="custom-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <p class="dd-name"><?php echo htmlspecialchars($org_name); ?></p>
                    <p class="dd-email"><?php echo htmlspecialchars($org_email); ?></p>
                </div>
                <ul class="dropdown-list">
                    <li onclick="openOrgModal(event)">
                        <i class="fas fa-id-card-alt"></i> My Profile
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>
<div class="header-spacer"></div>

<div id="orgInfoModal" class="org-modal-overlay">
    <div class="org-card">
        <div class="org-header-bg">
            <button class="close-modal" onclick="closeOrgModal()">&times;</button>
        </div>
        <div class="org-avatar-large">
            <?php echo $initial; ?>
        </div>
        
        <div class="org-body">
            <form id="profileForm" action="update_profile.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div id="viewMode">
                    <h2><?php echo htmlspecialchars($org_name); ?></h2>
                    <span class="org-badge">Verified <?php echo htmlspecialchars($org_role); ?></span>
                    
                    <div class="org-details">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <p><?php echo htmlspecialchars($org_name); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Username</label>
                            <p>@<?php echo htmlspecialchars($org_username); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Email Address</label>
                            <p><?php echo htmlspecialchars($org_email); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Batch Year</label>
                            <p><?php echo htmlspecialchars($batch_year); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Role</label>
                            <p><?php echo htmlspecialchars($org_role); ?></p>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-edit-profile" onclick="toggleEditMode(true)">Edit Profile</button>
                </div>

                <div id="editMode" style="display: none;">
                    <h3>Update Profile</h3>
                    <div class="org-details">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($org_name); ?>" class="edit-input" required>
                        </div>
                        <div class="detail-item">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($org_username); ?>" class="edit-input" required>
                        </div>
                        <div class="detail-item">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($org_email); ?>" class="edit-input" required>
                        </div>
                         <div class="detail-item">
                            <label>Batch Year</label>
                            <input type="text" name="batch_year" value="<?php echo htmlspecialchars($batch_year); ?>" class="edit-input">
                        </div>
                        <div class="detail-item">
                            <label>Role</label>
                            <input type="text" name="role" value="<?php echo htmlspecialchars($org_role); ?>" class="edit-input" readonly style="background-color: #f3f4f6; color: #6b7280;">
                        </div>
                        
                        <div class="detail-item">
                            <label style="color: #75162D;">New Password (Optional)</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current" class="edit-input" style="border-color: #FECDD3;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn-save">Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="toggleEditMode(false)">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* --- CORE HEADER STYLES --- */
.header { background-color: #ffffff; border-bottom: 1px solid #E5E7EB; position: fixed; top: 0; left: 0; right: 0; z-index: 50; padding: 0.7rem 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); box-sizing: border-box; }
.header-inner { width: 100%; display: flex; justify-content: space-between; align-items: center; }

/* LOGO */
.logo-section { display: flex; align-items: center; gap: 0.75rem; margin-left: 280px; transition: margin 0.3s; }
@media (max-width: 768px) { .logo-section { margin-left: 60px; } }
.logo-section h1 { font-size: 1.4rem; font-weight: 800; color: #75162D; margin: 0; font-family: 'Playfair Display', serif; }
.role-badge { background: #FFF1F2; color: #9F1239; padding: 0.2rem 0.6rem; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; border-radius: 50px; border: 1px solid #FECDD3; }

/* USER SECTION */
.user-section { display: flex; align-items: center; gap: 1rem; margin-left: auto; cursor: pointer; position: relative; padding: 5px 10px; border-radius: 12px; transition: background 0.2s; }
.user-section:hover { background-color: #F9FAFB; }
.user-info { text-align: right; }
.user-name { font-size: 0.9rem; font-weight: 700; color: #1F2937; margin: 0; }
.user-role { font-size: 0.75rem; color: #6B7280; margin: 0; }
.user-avatar { height: 40px; width: 40px; border-radius: 50%; background: linear-gradient(135deg, #75162D, #9F1239); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

/* DROPDOWN */
.custom-dropdown { position: absolute; top: 130%; right: 0; width: 240px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15); border: 1px solid #F3F4F6; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); z-index: 60; overflow: hidden; }
.custom-dropdown.active { opacity: 1; visibility: visible; transform: translateY(0); }
.dropdown-header { background: #F9FAFB; padding: 15px 20px; border-bottom: 1px solid #E5E7EB; }
.dd-name { font-weight: 700; color: #111; margin: 0; font-size: 0.95rem; }
.dd-email { color: #6B7280; margin: 0; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; }
.dropdown-list { list-style: none; padding: 8px; margin: 0; }
.dropdown-list li { padding: 10px 15px; border-radius: 8px; color: #374151; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 10px; transition: all 0.2s; }
.dropdown-list li:hover { background-color: #F3F4F6; color: #111; }

/* MODAL */
.org-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.4); backdrop-filter: blur(5px); z-index: 200; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s; }
.org-modal-overlay.show { opacity: 1; }
.org-card { background: #fff; width: 350px; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; }
.org-modal-overlay.show .org-card { transform: scale(1); }
.org-header-bg { height: 80px; background: linear-gradient(to right, #75162D, #3B010B); position: relative; }
.close-modal { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.2); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }
.close-modal:hover { background: rgba(0,0,0,0.4); }
.org-avatar-large { width: 80px; height: 80px; background: #fff; border-radius: 50%; position: absolute; top: 40px; left: 50%; transform: translateX(-50%); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #75162D; font-weight: 800; border: 4px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.org-body { padding: 50px 25px 30px; text-align: center; }
.org-body h2 { margin: 0; color: #111; font-family: 'Playfair Display', serif; }
.org-badge { display: inline-block; background: #ECFDF5; color: #047857; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 12px; margin-top: 5px; }

/* DETAILS & EDIT */
.org-details { margin-top: 25px; text-align: left; display: grid; gap: 15px; }
.detail-item label { display: block; font-size: 0.7rem; text-transform: uppercase; color: #9CA3AF; font-weight: 700; letter-spacing: 0.5px; }
.detail-item p { margin: 2px 0 0; color: #374151; font-weight: 500; font-size: 0.95rem; }
.edit-input { width: 100%; padding: 8px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 0.9rem; margin-top: 4px; box-sizing: border-box; }
.btn-edit-profile { margin-top: 25px; width: 100%; padding: 10px; border-radius: 8px; background: #75162D; color: white; border: none; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-edit-profile:hover { background: #560B18; }
.btn-save { flex: 1; padding: 10px; border-radius: 8px; background: #059669; color: white; border: none; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: #047857; }
.btn-cancel { flex: 1; padding: 10px; border-radius: 8px; background: #F3F4F6; color: #374151; border: none; font-weight: 600; cursor: pointer; }
.btn-cancel:hover { background: #E5E7EB; }
.header-spacer { height: 80px; }
</style>

<script>
    function toggleUserDropdown(e) {
        e.stopPropagation();
        const dd = document.getElementById('profileDropdown');
        dd.classList.toggle('active');
    }

    window.addEventListener('click', function(e) {
        const dd = document.getElementById('profileDropdown');
        if (!e.target.closest('.user-section')) {
            dd.classList.remove('active');
        }
    });

    const orgModal = document.getElementById('orgInfoModal');

    function openOrgModal(e) {
        if(e) e.stopPropagation();
        orgModal.style.display = 'flex';
        document.getElementById('profileDropdown').classList.remove('active');
        setTimeout(() => { orgModal.classList.add('show'); }, 10);
    }

    function closeOrgModal() {
        orgModal.classList.remove('show');
        setTimeout(() => { 
            orgModal.style.display = 'none'; 
            toggleEditMode(false); // Reset to view mode
        }, 300);
    }

    orgModal.addEventListener('click', function(e) {
        if (e.target === orgModal) closeOrgModal();
    });

    function toggleEditMode(enable) {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');

        if(enable) {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
        } else {
            editMode.style.display = 'none';
            viewMode.style.display = 'block';
        }
    }
</script>