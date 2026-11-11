<?php
session_start();
if(isset($_SESSION['login_message'])){
    $login_message = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CauseVia | Alumni Network</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== CSS RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
    --pale: #E8D9C4;        /* light bg, main background */
    --gold: #785D32;        /* accent, buttons, headers */
    --rough: #3E160C;       /* dark text on light bg */
    --off-navy: #050A30;    /* dark background, footer/nav */
    --white: #ffffff;       /* still needed for contrast */
    --glass-white: rgba(232, 217, 196, 0.92); /* pale translucent for glass card */
}
.bg-glass {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(232, 217, 196, 0.3), rgba(245, 237, 224, 0.3));
    backdrop-filter: blur(6px) saturate(120%);
    z-index: -1;
}


      body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    /* Old: background-color: var(--pale); */
    background: linear-gradient(135deg, #E8D9C4, #F5EDE0); /* pale to slightly warmer pale */
    color: var(--rough);
    line-height: 1.6;
    position: relative;
    overflow-x: hidden;
}


        /* ===== CANVAS BACKGROUND ===== */
        #alumniCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            display: block;
        }

        /* ===== MAIN CONTAINER ===== */
        .main-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            z-index: 2;
        }

        /* ===== GLASS FORM CARD ===== */
        .glass-card {
            width: 100%;
            max-width: 580px;
            background: var(--glass-white);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            border-radius: 20px;
            padding: 50px 45px;
            box-shadow: 
                0 20px 60px rgba(108, 19, 31, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(232, 217, 196, 0.4);
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--rough));
        }

        /* ===== LOGO SECTION ===== */
        .logo-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(108, 19, 31, 0.1);
        }

        .logo-icon {
            font-size: 3.2rem;
            color: var(--burgundy);
            margin-bottom: 20px;
            display: inline-block;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 4px 8px rgba(108, 19, 31, 0.2));
        }

        .logo-icon:hover {
            transform: scale(1.1) rotate(-5deg);
        }

        .logo-header h1 {
            font-family: 'Inter', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--burgundy);
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .logo-header .tagline {
            font-size: 1rem;
            color: var(--muted-rose);
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ===== MESSAGES ===== */
        .error-message {
           background: linear-gradient(135deg, rgba(62, 22, 12, 0.08), rgba(62, 22, 12, 0.05));
           border-left: 4px solid var(--rough);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 30px;
           color: var(--rough);
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            backdrop-filter: blur(4px);
            animation: slideIn 0.5s ease-out;
        }

        .success-message {
              background: linear-gradient(135deg, rgba(120, 93, 50, 0.08), rgba(120, 93, 50, 0.05));
            border-left: 4px solid var(--gold);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 30px;
             color: var(--gold);
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            backdrop-filter: blur(4px);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== FORM STYLES ===== */
        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
           color: var(--rough);
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .input-with-icon {
            color: var(--rough);
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-rose);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        /* ===== INPUT FIELDS ===== */
        input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            background: rgba(255, 255, 255, 0.9);
             border: 2px solid var(--pale); /* light border */
            border-radius: 12px;
            font-size: 1rem;
            color: var(--rough);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: 'Inter', sans-serif;
        }
        /* I-add ni para mawala ang default eye sa browser */
        input::-ms-reveal,
        input::-ms-clear {
            display: none;
        }
        
        input::-webkit-password-reveal-button {
            display: none;
            -webkit-appearance: none;
        }

        input:focus {
            outline: none;
             border-color: var(--gold);
           box-shadow: 0 0 0 3px rgba(120, 93, 50, 0.1); 
            background: white;
            transform: translateY(-2px);
        }

        input:focus + i {
            color: var(--burgundy);
            transform: translateY(-50%) scale(1.2);
        }

        input::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }

        /* ===== PASSWORD TOGGLE ===== */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            z-index: 2;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

       
        /* ===== REMEMBER ME & FORGOT PASSWORD ===== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--burgundy);
        }

        .forgot-password {
               color: var(--rough);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
              color: var(--rough);
            text-decoration: underline;
        }

        /* ===== BURGUNDY BUTTON ===== */
        .heritage-btn {
            background-color: var(--gold);
             color: var(--pale);
            border: none;
            border-radius: 12px;
            padding: 18px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(108, 19, 31, 0.2);
        }

        .heritage-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
            transition: 0.5s;
        }

        .heritage-btn:hover {
           background-color: var(--rough);
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(108, 19, 31, 0.3);
        }

        .heritage-btn:hover::before {
            left: 100%;
        }

        .heritage-btn:active {
            transform: translateY(-1px);
        }

        /* ===== REGISTER LINK ===== */
        .register-link {
            text-align: center;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(108, 19, 31, 0.1);
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .register-link a {
              color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding-bottom: 2px;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--burgundy);
            transition: width 0.3s ease;
        }

        .register-link a:hover {
            color: var(--rough);
        }

        .register-link a:hover::after {
            width: 100%;
        }

        /* ===== FORM VALIDATION STYLES ===== */
        .valid {
            border-color: #28a745 !important;
        }

        .invalid {
            border-color: #dc3545 !important;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 600px) {
            .glass-card {
                padding: 35px 25px;
                margin: 0 15px;
                border-radius: 16px;
            }
            
            .logo-header h1 {
                font-size: 2.2rem;
            }
            
            .logo-icon {
                font-size: 2.5rem;
            }
            
            input {
                padding: 14px 14px 14px 45px;
                font-size: 0.95rem;
            }
            
            .form-group label {
                font-size: 0.9rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 400px) {
            .glass-card {
                padding: 30px 20px;
            }
            
            .logo-header h1 {
                font-size: 1.8rem;
            }
            
            .logo-icon {
                font-size: 2rem;
            }
        }

        /* ===== ANIMATIONS ===== */
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== LOADING SPINNER ===== */
        .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- HTML5 Canvas for Interactive Background -->
  
  <div class="bg-glass"></div>
    <div class="main-container">
        <div class="glass-card fade-in">
            <!-- Logo Section -->
            <div class="logo-header">
                <div class="logo-icon">
                    <i class="fas fa-university"></i>
                </div>
                <h1>CauseVia</h1>
                <div class="tagline">Alumni Network Portal</div>
            </div>

            <!-- Error/Success Message -->
            <?php if(isset($login_message)): ?>
                <div class="error-message" id="messageDisplay">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="../auth/login_process.php" autocomplete="off" id="loginForm">
                <div class="form-group">
                    <label for="username">USERNAME</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" 
                               placeholder="Enter your username" 
                               autocomplete="off" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" 
                               placeholder="Enter your password" 
                               autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                

                <button type="submit" class="heritage-btn">
                    <i class="fas fa-sign-in-alt"></i> SIGN IN
                </button>
            </form>

            <div class="register-link">
                New to CauseVia? <a href="register.php">Create an alumni account</a>
            </div>
        </div>
    </div>

    <!-- Vanilla JavaScript for Canvas Animation -->
    <script>
       

        // ===== FORM FUNCTIONALITY =====
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const eyeIcon = this.querySelector('i');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
            
            // Focus effect
            passwordInput.style.borderColor = 'var(--burgundy)';
            passwordInput.style.boxShadow = '0 0 0 3px rgba(108, 19, 31, 0.1)';
            setTimeout(() => {
                if(passwordInput !== document.activeElement) {
                    passwordInput.style.borderColor = '';
                    passwordInput.style.boxShadow = '';
                }
            }, 1000);
        });
        
        const inputs = document.querySelectorAll('input, select');

input.addEventListener('blur', function() {
    this.style.transform = 'translateY(0)';
    if(this.value.trim() === '') {
        this.style.borderColor = 'var(--border-light)';
        this.style.boxShadow = 'none';
    }

    const icon = this.previousElementSibling;
    if(icon && icon.tagName === 'I') {
        icon.style.color = 'var(--muted-rose)';
        icon.style.transform = 'translateY(-50%)';
    }
});

input.addEventListener('input', function() {
    if(this.value.trim() !== '') {
        this.classList.add('valid');
        this.classList.remove('invalid');
    } else {
        this.classList.remove('valid', 'invalid');
    }
});

        
        // Auto-hide message
        const messageDisplay = document.getElementById('messageDisplay');
        if(messageDisplay) {
            setTimeout(() => {
                messageDisplay.style.opacity = '0';
                messageDisplay.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    if(messageDisplay.parentNode) {
                        messageDisplay.parentNode.removeChild(messageDisplay);
                    }
                }, 500);
            }, 5000);
        }
        
        // Form submission enhancement
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function(e) {
            // Simple validation
            let isValid = true;
            const requiredInputs = this.querySelectorAll('input[required]');
            
            requiredInputs.forEach(input => {
                if(!input.value.trim()) {
                    input.classList.add('invalid');
                    isValid = false;
                    
                    // Add shake animation
                    input.style.animation = 'shake 0.5s ease';
                    setTimeout(() => {
                        input.style.animation = '';
                    }, 500);
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = this.querySelector('.heritage-btn');
            const originalText = submitBtn.innerHTML;
            const originalIcon = '<i class="fas fa-sign-in-alt"></i>';
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> AUTHENTICATING...';
            submitBtn.disabled = true;
            
            // Simulate processing
            setTimeout(() => {
                submitBtn.innerHTML = originalIcon + ' SIGN IN';
                submitBtn.disabled = false;
            }, 2000);
        });
        
        // Add shake animation for invalid inputs
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // Auto-focus on username field
        document.getElementById('username')?.focus();
    </script>
</body>
</html>