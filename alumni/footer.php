</main>

<style>
    /* --- PRO FOOTER STYLES (Scoped with 'pf-' prefix) --- */
    .pro-footer {
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb; /* Light border */
        padding: 48px 0 24px 0;
        margin-top: 60px; /* Space from content */
        font-family: 'Inter', sans-serif;
    }

    .pf-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    /* Top Section: Brand + Links */
    .pf-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 40px;
        border-bottom: 1px solid #f3f4f6;
    }

    /* Brand Area */
    .pf-brand-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pf-logo-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pf-logo-box {
        width: 32px;
        height: 32px;
        background-color: #6C131F; /* Your Brand Color */
        color: white;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pf-brand-name {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
    }

    .pf-tagline {
        font-size: 13px;
        color: #6b7280;
        margin-left: 2px;
    }

    /* Links Area */
    .pf-links {
        display: flex;
        gap: 32px;
        align-items: center;
    }

    .pf-link {
        text-decoration: none;
        color: #6b7280; /* Gray text */
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .pf-link:hover {
        color: #6C131F; /* Red on hover */
    }

    /* Bottom Copyright */
    .pf-bottom {
        padding-top: 24px;
        text-align: center;
    }

    .pf-copyright {
        font-size: 13px;
        color: #9ca3af; /* Lighter gray */
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .pf-top {
            flex-direction: column;
            gap: 30px;
            align-items: center;
            text-align: center;
        }

        .pf-links {
            flex-direction: column; /* Stack links on mobile */
            gap: 16px;
        }
        
        .pf-brand-group {
            align-items: center;
        }
    }
</style>

<footer class="pro-footer">
    <div class="pf-container">
        
        <div class="pf-top">
            <div class="pf-brand-group">
                <div class="pf-logo-row">
                    <div class="pf-logo-box">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="pf-brand-name">CauseVia</span>
                </div>
                <span class="pf-tagline">Connecting Alumni, Funding Dreams.</span>
            </div>

            <div class="pf-links">
                <a href="privacy.php" class="pf-link">Privacy Policy</a>
                <a href="terms.php" class="pf-link">Terms of Service</a>
                <a href="contact.php" class="pf-link">Contact Us</a>
                <a href="help.php" class="pf-link">Help Center</a>
            </div>
        </div>

        <div class="pf-bottom">
            <p class="pf-copyright">&copy; <?php echo date('Y'); ?> CauseVia Alumni Portal. All rights reserved.</p>
        </div>

    </div>
</footer>

</body>
</html>