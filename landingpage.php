<?php
session_start();

// ==========================================
// DATABASE CONNECTION & LOGIC
// ==========================================
require_once __DIR__ . '/includes/db.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. GET TOTAL FUNDS RAISED
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM donations WHERE status = 'Approved'");
    $project_donations = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(amount) as total FROM general_donation WHERE status = 'Approved'");
    $gen_donations = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $total_raised = $project_donations + $gen_donations;

    // 2. GET ACTIVE ALUMNI COUNT
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
    $active_alumni = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // 3. FETCH FEATURED CAMPAIGNS (Projects)
    // FIX: Case-insensitive search for 'Active' status
    $stmt = $pdo->query("SELECT * FROM projects WHERE TRIM(status) ILIKE 'active' ORDER BY id DESC LIMIT 3");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CauseVia | Alumni Legacy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== ELEGANT CSS RESET ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            /* Palette: Sophisticated Burgundy & Cream */
            --bg-color: #FAFAFA;
            --white: #ffffff;
            --primary: #75162D;  
            --primary-dark: #500818;
            --accent: #D4AF37;   
            --text-main: #2C2C2C; 
            --text-muted: #666666;
            --border-light: #EAEAEA;
            --shadow-soft: 0 10px 40px rgba(0,0,0,0.04);
            --shadow-hover: 0 15px 40px rgba(117, 22, 45, 0.08);
        }

        html { scroll-behavior: smooth; font-size: 16px; }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
            background-color: var(--bg-color);
            -webkit-font-smoothing: antialiased;
        }

        /* ===== TYPOGRAPHY ===== */
        h1, h2, h3 { 
            font-family: 'Playfair Display', serif; 
            font-weight: 600; 
            color: var(--primary);
            letter-spacing: -0.01em;
        }

        h1 { font-size: 3rem; line-height: 1.1; margin-bottom: 1.5rem; color: var(--white); }
        h2 { font-size: 2.2rem; margin-bottom: 1rem; text-align: center; }
        
        .section-header { text-align: center; margin-bottom: 3.5rem; max-width: 600px; margin-left: auto; margin-right: auto; }
        .section-header p { color: var(--text-muted); font-size: 1.1rem; margin-top: 0.5rem; font-weight: 300; }
        
        .section-header h2::after {
            content: ''; display: block; width: 40px; height: 2px; 
            background: var(--accent); margin: 15px auto 0;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed; top: 0; left: 0; width: 100%;
            padding: 1rem 5%;
            display: flex; justify-content: space-between; align-items: center;
            z-index: 1000;
            transition: all 0.4s ease;
            background: transparent;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.8rem 5%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon { font-size: 1.5rem; color: var(--white); transition: color 0.3s; }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: var(--white); transition: color 0.3s; }

        .navbar.scrolled .logo-icon, .navbar.scrolled .logo-text { color: var(--primary); }
        .navbar.scrolled .nav-links a { color: var(--text-main); }
        
        .nav-links { display: flex; gap: 2.5rem; }
        .nav-links a { 
            text-decoration: none; color: rgba(255,255,255,0.9); 
            font-size: 0.95rem; font-weight: 500; transition: 0.3s; 
        }
        .nav-links a:hover { color: var(--accent); }
        .navbar.scrolled .nav-links a:hover { color: var(--primary); }

        .btn-nav {
            padding: 0.6rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 600; 
            text-decoration: none; transition: 0.3s;
        }
        .btn-login { color: var(--white); border: 1px solid rgba(255,255,255,0.3); margin-right: 10px; }
        .navbar.scrolled .btn-login { color: var(--primary); border-color: var(--primary); }
        .btn-join { background: var(--white); color: var(--primary); }
        .navbar.scrolled .btn-join { background: var(--primary); color: var(--white); }
        
        .btn-login:hover, .btn-join:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        /* ===== HERO SECTION ===== */
        .hero {
            position: relative; height: 90vh;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }

        .slider-container, .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .slide { opacity: 0; transition: opacity 1.5s ease; transform: scale(1.1); transition: transform 6s ease, opacity 1.5s ease; }
        .slide.active { opacity: 1; transform: scale(1); z-index: 1; }
        .slide img { width: 100%; height: 100%; object-fit: cover; }

        .hero-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(50, 10, 20, 0.4), rgba(50, 10, 20, 0.7));
            z-index: 2;
        }

        .hero-content {
            position: relative; z-index: 3; text-align: center; max-width: 700px; padding: 0 20px;
        }
        
        .hero p { color: rgba(255,255,255,0.9); font-size: 1.15rem; margin-bottom: 2rem; font-weight: 300; }

        .hero-buttons { display: flex; gap: 1rem; justify-content: center; }
        .btn-hero {
            padding: 0.9rem 2.2rem; border-radius: 50px; font-weight: 500; text-decoration: none; 
            transition: all 0.3s ease; font-size: 1rem; letter-spacing: 0.5px;
        }
        .btn-primary { background: var(--primary); color: white; border: 1px solid var(--primary); }
        .btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.6); backdrop-filter: blur(5px); }
        
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
        .btn-secondary:hover { background: white; color: var(--primary); border-color: white; }

        /* ===== IMPACT SECTION ===== */
        .impact-section { padding: 5rem 5%; background: var(--white); }
        .impact-grid { 
            display: grid; grid-template-columns: repeat(3, 1fr); 
            gap: 2rem; max-width: 1000px; margin: 0 auto; 
            text-align: center;
        }
        .stat-item { padding: 1rem; }
        .stat-number { 
            font-family: 'Playfair Display', serif; font-size: 3rem; 
            color: var(--primary); font-weight: 700; display: block; line-height: 1; margin-bottom: 0.5rem;
        }
        .stat-label { 
            font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; 
            color: var(--text-muted); font-weight: 500;
        }

        /* ===== CAMPAIGNS (OVERVIEW CARDS) ===== */
        .campaigns-section { padding: 5rem 5%; background: #FAFAFA; }
        .campaigns-grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 2rem; max-width: 1200px; margin: 0 auto;
        }

        .campaign-card {
            background: white; border-radius: 12px; overflow: hidden;
            box-shadow: var(--shadow-soft); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex; flex-direction: column; border: 1px solid var(--border-light);
            height: 100%;
        }

        .campaign-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-hover); }

        .campaign-image { height: 200px; overflow: hidden; position: relative; }
        .campaign-image img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .campaign-card:hover .campaign-image img { transform: scale(1.05); }

        .campaign-content { padding: 2rem; display: flex; flex-direction: column; flex-grow: 1; }
        
        .campaign-title { font-size: 1.3rem; margin-bottom: 0.8rem; color: var(--primary); line-height: 1.3; }
        
        .campaign-desc { 
            font-size: 0.95rem; color: var(--text-muted); margin-bottom: 1.5rem; 
            flex-grow: 1; line-height: 1.6; font-weight: 300;
        }

        /* Status Indicator (Instead of Donate) */
        .status-container {
            border-top: 1px solid var(--border-light);
            padding-top: 1.2rem;
            margin-top: auto;
        }

        .progress-labels { 
            display: flex; justify-content: space-between; 
            font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; 
        }
        
        .progress-bar-bg { height: 4px; background: #F0F0F0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary); border-radius: 10px; }

        .view-link {
            display: inline-block;
            margin-top: 1.2rem;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .view-link i { margin-left: 5px; transition: margin-left 0.3s; }
        .view-link:hover { color: var(--accent); }
        .view-link:hover i { margin-left: 10px; }

        /* ===== FOOTER ===== */
        .footer { background: #1a1a1a; color: #aaa; padding: 4rem 5% 2rem; font-size: 0.9rem; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 3rem; max-width: 1200px; margin: 0 auto 3rem; }
        .footer h3 { color: white; font-size: 1.1rem; margin-bottom: 1.2rem; letter-spacing: 0.5px; }
        .footer p { line-height: 1.8; max-width: 300px; }
        .footer a { color: #aaa; text-decoration: none; display: block; margin-bottom: 0.6rem; transition: 0.3s; }
        .footer a:hover { color: white; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; text-align: center; font-size: 0.8rem; }

        /* Responsive */
        @media (max-width: 900px) {
            .impact-grid { grid-template-columns: 1fr; gap: 3rem; }
            .stat-number { font-size: 2.5rem; }
            .nav-links { display: none; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .footer p { margin: 0 auto; }
            h1 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <a href="#" class="logo">
            <i class="fas fa-graduation-cap logo-icon"></i>
            <span class="logo-text">CauseVia</span>
        </a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#campaigns">Campaigns</a>
            <a href="#impact">Our Impact</a>
        </div>
        <div class="nav-buttons">
            <a href="public/login.php" class="btn-nav btn-login">Log In</a>
            <a href="public/register.php" class="btn-nav btn-join">Join Us</a>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="hero-overlay"></div>

        <div class="slider-container">
            <div class="slide active"><img src="pic/1.png" alt="University"></div>
            <div class="slide"><img src="pic/2.jpg" alt="Students"></div>
            <div class="slide"><img src="pic/3.jpg" alt="Alumni"></div>
        </div>

        <div class="hero-content">
            <h1>Honoring Tradition, <br>Funding the Future</h1>
            <p>Connect with fellow alumni to create lasting impact. Together, we build bridges between generations.</p>
            <div class="hero-buttons">
                <a href="public/register.php" class="btn-hero btn-primary">Start Giving</a>
                <a href="#campaigns" class="btn-hero btn-secondary">Explore Causes</a>
            </div>
        </div>
    </section>

    <section class="impact-section" id="impact">
        <div class="section-header">
            <h2>Our Collective Impact</h2>
            <p>Transparency and progress in numbers</p>
        </div>

        <div class="impact-grid">
            <div class="stat-item">
                <span class="stat-number">₱<?php echo number_format($total_raised); ?></span>
                <span class="stat-label">Funds Raised</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($active_alumni); ?></span>
                <span class="stat-label">Active Alumni</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($projects); ?></span>
                <span class="stat-label">Active Campaigns</span>
            </div>
        </div>
    </section>

    <section class="campaigns-section" id="campaigns">
        <div class="section-header">
            <h2>Featured Initiatives</h2>
            <p>A glimpse into the projects shaping our community's future</p>
        </div>

        <div class="campaigns-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $proj): 
                    $target = $proj['target_amount'];
                    // Handling naming safety
                    $current = $proj['cureent_amount'] ?? $proj['current_amount'] ?? 0; 
                    
                    $percent = ($target > 0) ? round(($current / $target) * 100) : 0;
                    if($percent > 100) $percent = 100;

                    $img = !empty($proj['cover_photo']) ? "uploads/".$proj['cover_photo'] : "pic/default.jpg";
                ?>
                <div class="campaign-card">
                    <div class="campaign-image">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Campaign">
                    </div>
                    <div class="campaign-content">
                        <h3 class="campaign-title"><?php echo htmlspecialchars($proj['project_title']); ?></h3>
                        <p class="campaign-desc"><?php echo htmlspecialchars(substr($proj['description'], 0, 110)) . '...'; ?></p>
                        
                        <div class="status-container">
                            <div class="progress-labels">
                                <span>Status</span>
                                <span><?php echo $percent; ?>% Funded</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            
                            <a href="public/login.php" class="view-link">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <p style="color: #999; font-size: 1.1rem; font-style: italic;">No active featured campaigns at this moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>CauseVia</h3>
                <p>An official platform for alumni to give back, connect, and empower the next generation of leaders.</p>
            </div>
            <div class="footer-column">
                <h3>Quick Navigation</h3>
                <a href="#home">Home</a>
                <a href="#campaigns">Campaigns</a>
                <a href="public/login.php">Member Login</a>
            </div>
            <div class="footer-column">
                <h3>Contact Us</h3>
                <a href="#">hello@causevia.com</a>
                <a href="#">Guindulman, Bohol</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CauseVia. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Navbar Scroll Effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });

        // Simple Auto Slider
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        
        function nextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }
        setInterval(nextSlide, 5000);
    </script>
</body>
</html>