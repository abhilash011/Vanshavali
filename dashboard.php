<?php
/*
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
// AUTHENTICATION CHECK
// ========================
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Verify session integrity
if ($_SESSION['user']['ip'] !== $_SERVER['REMOTE_ADDR'] || 
    $_SESSION['user']['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// ========================
// APPLICATION SETTINGS
// ========================
define('PASSWORD_MIN_LENGTH', 12);

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

// Get user data
$user = $_SESSION['user'];
$stmt = $db->prepare("SELECT name, email, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Update last activity time
$_SESSION['user']['last_activity'] = time();

// Generate new CSRF token for forms
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
*/
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sonar Samaj member portal">
  <title>Sonar Samaj - Member Portal</title>
  
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
    }
    
    .golden-bg {
      position: fixed;
      inset: 0;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none"/><path d="M20,20 L80,20 L80,80 L20,80 Z" fill="rgba(218,165,32,0.1)"/></svg>');
      opacity: 0.15;
      animation: glitterMove 60s linear infinite;
      z-index: -1;
    }
    
    .navbar-brand img {
      height: 40px;
      transition: transform 0.3s ease;
    }
    
    .navbar-brand img:hover {
      transform: scale(1.1);
    }
    
    .btn-gold {
      background: linear-gradient(to right, var(--gold-primary), var(--gold-secondary));
      color: white;
      border: none;
      padding: 8px 20px;
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
    
    .card {
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      border: none;
      overflow: hidden;
    }
    
    .card-header {
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
      color: white;
      font-weight: 600;
    }
    
    .text-gold {
      color: var(--gold-primary) !important;
    }
    
    .profile-img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border: 4px solid white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    @keyframes glitterMove {
      0% { background-position: 0 0; }
      100% { background-position: 100px 100px; }
    }
    
    /* Dark mode */
    @media (prefers-color-scheme: dark) {
      body {
        background: linear-gradient(135deg, #121212 0%, #1e1e1e 100%);
        color: #f0f0f0;
      }
      
      .card {
        background-color: #2a2a2a;
        color: #f0f0f0;
      }
      
      .card-header {
        background: linear-gradient(135deg, var(--gold-dark), var(--gold-primary));
      }
      
      .table {
        color: #f0f0f0;
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
  
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--gold-dark), var(--gold-primary));">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="https://pushkarajjyotishkaryalay.com/vanshavali/logo512.png" alt="Sonar Samaj Logo" class="me-2">
        <span>Sonar Samaj</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active" href="#"><i class="bi bi-house-door me-1"></i> Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-people me-1"></i> Family Tree</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-calendar-event me-1"></i> Events</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
              <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="POST" action="logout.php">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                </form>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container my-5">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-lg-3 mb-4">
        <div class="card mb-4">
          <div class="card-body text-center">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['name']) ?>&background=daa520&color=fff&size=120" 
                 alt="Profile" class="profile-img rounded-circle mb-3">
            <h5 class="card-title"><?= htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="text-muted mb-2">Member since <?= date('M Y', strtotime($user_data['last_login'])) ?></p>
            <div class="d-flex justify-content-center">
              <a href="#" class="btn btn-sm btn-gold me-2"><i class="bi bi-pencil"></i> Edit</a>
              <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope"></i> Message</a>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <i class="bi bi-speedometer2 me-2"></i> Quick Links
          </div>
          <div class="list-group list-group-flush">
            <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-tree me-2"></i> Family Tree</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-book me-2"></i> Genealogy Records</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-calendar-check me-2"></i> Upcoming Events</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i> Community Directory</a>
            <a href="#" class="list-group-item list-group-item-action"><i class="bi bi-chat-square-text me-2"></i> Discussion Forum</a>
          </div>
        </div>
      </div>
      
      <!-- Main Content Area -->
      <div class="col-lg-9">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-house-door me-2"></i> Dashboard</span>
            <small class="text-white">Last login: <?= date('M j, Y g:i a', strtotime($user_data['last_login'])) ?></small>
          </div>
          <div class="card-body">
            <h4 class="text-gold mb-4">Welcome back, <?= htmlspecialchars(explode(' ', $user_data['name'])[0], ENT_QUOTES, 'UTF-8') ?>!</h4>
            
            <div class="row g-4 mb-4">
              <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-gold">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Family Members</h6>
                        <h3 class="mb-0">24</h3>
                      </div>
                      <div class="bg-gold bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people-fill text-gold" style="font-size: 1.5rem;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-gold">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">Upcoming Events</h6>
                        <h3 class="mb-0">3</h3>
                      </div>
                      <div class="bg-gold bg-opacity-10 p-3 rounded">
                        <i class="bi bi-calendar-event text-gold" style="font-size: 1.5rem;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card h-100 border-start border-4 border-gold">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="text-muted">New Messages</h6>
                        <h3 class="mb-0">5</h3>
                      </div>
                      <div class="bg-gold bg-opacity-10 p-3 rounded">
                        <i class="bi bi-envelope text-gold" style="font-size: 1.5rem;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="card mb-4">
              <div class="card-header">
                <i class="bi bi-bell me-2"></i> Recent Activity
              </div>
              <div class="card-body">
                <div class="list-group list-group-flush">
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <i class="bi bi-person-plus text-success me-2"></i>
                      <span>New family member added: <strong>Rahul Sharma</strong></span>
                    </div>
                    <small class="text-muted">2 hours ago</small>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <i class="bi bi-calendar-event text-primary me-2"></i>
                      <span>New event: <strong>Annual Gathering 2023</strong></span>
                    </div>
                    <small class="text-muted">1 day ago</small>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <i class="bi bi-chat-left-text text-info me-2"></i>
                      <span>New message from <strong>Priya Patel</strong></span>
                    </div>
                    <small class="text-muted">3 days ago</small>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="card">
              <div class="card-header">
                <i class="bi bi-calendar3 me-2"></i> Upcoming Events
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Location</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Nov 15, 2023</td>
                        <td>Annual Gathering</td>
                        <td>Community Hall</td>
                        <td><a href="#" class="btn btn-sm btn-gold">Register</a></td>
                      </tr>
                      <tr>
                        <td>Dec 24, 2023</td>
                        <td>Winter Celebration</td>
                        <td>Central Park</td>
                        <td><a href="#" class="btn btn-sm btn-gold">Details</a></td>
                      </tr>
                      <tr>
                        <td>Jan 14, 2024</td>
                        <td>New Year Puja</td>
                        <td>Temple</td>
                        <td><a href="#" class="btn btn-sm btn-gold">RSVP</a></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
          <h5 class="text-gold mb-3">Sonar Samaj</h5>
          <p>Preserving our heritage and connecting generations through our family genealogy portal.</p>
        </div>
        <div class="col-md-4 mb-4 mb-md-0">
          <h5 class="text-gold mb-3">Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white text-decoration-none">About Us</a></li>
            <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
            <li><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="col-md-4">
          <h5 class="text-gold mb-3">Connect With Us</h5>
          <div class="d-flex gap-3">
            <a href="#" class="text-white"><i class="bi bi-facebook" style="font-size: 1.5rem;"></i></a>
            <a href="#" class="text-white"><i class="bi bi-twitter" style="font-size: 1.5rem;"></i></a>
            <a href="#" class="text-white"><i class="bi bi-instagram" style="font-size: 1.5rem;"></i></a>
            <a href="#" class="text-white"><i class="bi bi-whatsapp" style="font-size: 1.5rem;"></i></a>
          </div>
        </div>
      </div>
      <hr class="my-4">
      <div class="text-center">
        <small>&copy; <?= date('Y') ?> Sonar Samaj. All rights reserved.</small>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // System theme detection
      function updateTheme() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
      }
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme);
      updateTheme();
      
      // Inactivity timer
      let inactivityTime = function() {
        let time;
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        
        function logout() {
          // AJAX call to logout or redirect
          window.location.href = 'logout.php?timeout=1';
        }
        
        function resetTimer() {
          clearTimeout(time);
          time = setTimeout(logout, 30 * 60 * 1000); // 30 minutes
        }
      };
      
      inactivityTime();
    });
  </script>
</body>
</html>