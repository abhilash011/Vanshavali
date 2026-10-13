<?php
declare(strict_types=1);
session_start();

// ========================
// SECURITY CONFIGURATION
// ========================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data: https://pushkarajjyotishkaryalay.com");

// Force HTTPS in production
if (empty($_SERVER['HTTPS']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// ========================
// APPLICATION SETTINGS
// ========================
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes
define('SESSION_DURATION', 86400 * 30); // 30 days
define('PASSWORD_MIN_LENGTH', 12);

// ========================
// AUTHENTICATION CHECK
// ========================
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

// ========================
// DATABASE CONNECTION
// ========================
$db = null;
try {
    $db = new mysqli('srv878.hstgr.io:3306', 'u568064655_Abhilash011', '9?OSG|Kp>H', 'u568064655_YONO');
    $db->set_charset("utf8mb4");
    $db->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
} catch (Exception $e) {
    error_log("Database connection error [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage());
    die(json_encode(['error' => 'System maintenance in progress']));
}

// ========================
// LOGIN PROCESSING
// ========================
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Security token mismatch';
    } else {
        // Sanitize and validate input
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email)) {
            $error = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (empty($password)) {
            $error = 'Password is required';
        } elseif (strlen($password) > 72) { // BCrypt limit
            $error = 'Invalid credentials-BCrypt';
        } else {
            // Get user data with prepared statement
            $stmt = $db->prepare("SELECT id, name, email, password, login_attempts, 
                                 UNIX_TIMESTAMP(last_attempt) as last_attempt_ts 
                                 FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            echo "<script>console.log(".$password.");</script>";
            echo "<script>console.log(" . json_encode($user) . ");</script>";
            echo "<script>alert('✅ Dsers uccessfully');</script>";
            if ($user) {
                // Check account lockout status
                $current_time = time();
                $last_attempt = (int)$user['last_attempt_ts'];
                $lockout_expires = $last_attempt + LOCKOUT_DURATION;
                
                if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS && $current_time < $lockout_expires) {
                    $remaining_time = ceil(($lockout_expires - $current_time) / 60);
                    $error = "Account locked. Try again in $remaining_time minutes.";
                } else {
                    
                    // Verify password (timing-safe comparison)
                    if (password_verify($password, $user['password'])) {
                            echo "<script>alert('✅ password uccessfully');</script>";
                        // Successful login - reset attempts
                        $reset_stmt = $db->prepare("UPDATE users SET login_attempts = 0, last_attempt = NULL WHERE id = ?");
                        $reset_stmt->bind_param("i", $user['id']);
                        $reset_stmt->execute();
                        
                        // Regenerate session ID to prevent fixation
                        session_regenerate_id(true);
                        
                        // Store minimal user data in session
                        $_SESSION['user'] = [
                            'id' => (int)$user['id'],
                            'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                            'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                            'last_login' => $current_time,
                            'ip' => $_SERVER['REMOTE_ADDR'],
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                            'remember' => $remember
                        ];
                        
                        // Set secure session cookie
                        $cookie_params = session_get_cookie_params();
                        setcookie(
                            session_name(),
                            session_id(),
                            [
                                'expires' => $remember ? $current_time + SESSION_DURATION : 0,
                                'path' => $cookie_params['path'],
                                'domain' => $cookie_params['domain'],
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );
                        echo "<script>alert('✅ dashboard uccessfully');</script>";
                        // Redirect to secure area
                        header('Location: dashboard.php');
                        exit();
                    } else {
                            echo "<script>alert('✅ password Failed  "+$user['password']+"</script> ";            
                            // Failed attempt - increment counter
                        $attempts = min($user['login_attempts'] + 1, MAX_LOGIN_ATTEMPTS + 1);
                        $update_stmt = $db->prepare("UPDATE users SET login_attempts = ?, last_attempt = NOW() WHERE id = ?");
                        $update_stmt->bind_param("ii", $attempts, $user['id']);
                        $update_stmt->execute();
                        
                        // Random delay to prevent timing attacks
                        usleep(random_int(200000, 800000));
                        $error = 'Invalid credentials-1'+$user['password'];
                    }
                }
            } else {
                echo "<script>alert('Simulate password verification');</script>";
                // Simulate password verification
                password_verify($password, '$2y$10$' . str_repeat('a', 53));
                usleep(random_int(300000, 700000));
                $error = 'Invalid credentials';
            }
        }
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sonar Samaj member login portal">
  <meta name="robots" content="noindex, nofollow">
  <title>Sonar Samaj - Member Login</title>
  
  <!-- Preload critical assets -->
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" as="style">
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" as="style">
  
  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    :root {
      --gold-primary: #b8860b;
      --gold-secondary: #daa520;
      --gold-light: #fcd34d;
      --gold-dark: #8d6e00;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    
    .golden-bg {
      position: fixed;
      inset: 0;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none"/><path d="M20,20 L80,20 L80,80 L20,80 Z" fill="rgba(218,165,32,0.1)"/></svg>');
      opacity: 0.15;
      animation: glitterMove 60s linear infinite;
      z-index: -1;
    }
    
    .login-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      overflow: hidden;
      backdrop-filter: blur(8px);
      background-color: rgba(255, 253, 245, 0.95);
    }
    
    .login-header {
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
      color: white;
      padding: 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .login-header::before {
      content: "";
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,215,0,0.2) 0%, transparent 70%);
      animation: pulse 8s infinite alternate;
    }
    
    .login-logo {
      height: 80px;
      margin-bottom: 1rem;
      filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
      transition: transform 0.3s ease;
    }
    
    .login-logo:hover {
      transform: scale(1.05);
    }
    
    .btn-gold {
      background: linear-gradient(to right, var(--gold-primary), var(--gold-secondary));
      color: white;
      border: none;
      padding: 12px 28px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1));
    }
    
    .btn-gold:hover {
      background: linear-gradient(to right, var(--gold-secondary), var(--gold-primary));
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15));
    }
    
    .form-control:focus {
      border-color: var(--gold-primary);
      box-shadow: 0 0 0 0.25rem rgba(184, 134, 11, 0.25);
    }
    
    .text-gold {
      color: var(--gold-primary) !important;
    }
    
    .password-toggle {
      cursor: pointer;
      transition: color 0.2s;
    }
    
    .password-toggle:hover {
      color: var(--gold-secondary) !important;
    }
    
    @keyframes glitterMove {
      0% { background-position: 0 0; }
      100% { background-position: 100px 100px; }
    }
    
    @keyframes pulse {
      0% { transform: scale(1); opacity: 0.5; }
      100% { transform: scale(1.2); opacity: 0.8; }
    }
    
    /* Dark mode */
    @media (prefers-color-scheme: dark) {
      body {
        background: linear-gradient(135deg, #121212 0%, #1e1e1e 100%);
      }
      
      .login-card {
        background-color: rgba(30, 30, 30, 0.95);
        color: #f0f0f0;
      }
      
      .form-control, .form-control:focus {
        background-color: #2a2a2a;
        color: #fff;
        border-color: #444;
      }
      
      .input-group-text {
        background-color: #333;
        color: #ddd;
        border-color: #444;
      }
      
      .alert {
        background-color: #2a2a2a;
        border-color: #444;
      }
    }
    
    /* Accessibility focus styles */
    :focus-visible {
      outline: 3px solid var(--gold-light);
      outline-offset: 2px;
    }
  </style>
</head>
<body>
  <!-- Golden Background -->
  <div class="golden-bg"></div>
  
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="login-card card">
          <div class="login-header">
            <img src="https://pushkarajjyotishkaryalay.com/vanshavali/logo512.png" alt="Sonar Samaj Logo" class="login-logo" width="80" height="80">
            <h1 class="h3 mb-2">Member Login</h1>
            <p class="mb-0 opacity-75">Access your family genealogy portal</p>
          </div>
          
          <div class="card-body p-4 p-md-5">
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              
              <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                  <input type="email" class="form-control" id="email" name="email" required 
                         autocomplete="email" placeholder="your@email.com"
                         value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
              </div>
              
              <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-key"></i></span>
                  <input type="password" class="form-control" id="password" name="password" 
                         required autocomplete="current-password" placeholder="••••••••"
                         minlength="<?= PASSWORD_MIN_LENGTH ?>">
                  <button type="button" class="input-group-text password-toggle" id="togglePassword">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="form-text mt-1 d-flex justify-content-between">
                  <a href="forgot-password.php" class="text-gold text-decoration-none">Forgot password?</a>
                  <small class="text-muted">Minimum <?= PASSWORD_MIN_LENGTH ?> characters</small>
                </div>
              </div>
              
              <div class="mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember" name="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="remember">Keep me logged in</label>
                </div>
              </div>
              
              <div class="d-grid gap-2 mb-4">
                <button type="submit" class="btn btn-gold btn-lg rounded-pill">
                  <i class="bi bi-box-arrow-in-right me-2"></i> Login
                </button>
              </div>
              
              <div class="text-center pt-3 border-top">
                <p class="mb-2">New to Sonar Samaj?</p>
                <a href="register.php" class="btn btn-outline-secondary rounded-pill">
                  <i class="bi bi-person-plus me-2"></i> Create Account
                </a>
              </div>
            </form>
          </div>
        </div>
        
        <div class="text-center mt-4">
          <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Homepage
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Password visibility toggle
      const passwordInput = document.getElementById('password');
      const toggleButton = document.getElementById('togglePassword');
      
      toggleButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
      
      // Auto-focus email field
      const emailField = document.getElementById('email');
      if (emailField.value === '') {
        emailField.focus();
      } else {
        document.getElementById('password').focus();
      }
      
      // System theme detection
      function updateTheme() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
      }
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme);
      updateTheme();
    });
  </script>
</body>
</html>