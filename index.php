<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sonar Samaj - Golden Community Portal</title>

  <!-- SEO -->
  <meta name="description" content="Sonar Samaj - Golden Community Portal connecting Goldsmith families, heritage, and traditions with events, family trees, and global networking.">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <!-- AOS Animate on Scroll -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <!-- Slick Slider -->
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      overflow-x: hidden;
      background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
      min-height: 100vh;
    }

    /* Golden background overlay */
    .golden-bg {
      position: fixed;
      inset: 0;
      background: url('assets/images/gold-glitter.png') repeat;
      background-size: cover;
      opacity: 0.2;
      animation: glitterMove 60s linear infinite;
      z-index: 0;
      pointer-events: none;
    }
    @keyframes glitterMove {
      0% { background-position: 0 0; }
      100% { background-position: 1000px 1000px; }
    }

    /* Golden Theme */
    .text-gold { color: #b8860b !important; }
    .btn-gold { background-color: #b8860b; color: white; border: none; }
    .btn-gold:hover { background-color: #daa520; }
    .btn-outline-gold { border: 2px solid #b8860b; color: #b8860b; }
    .btn-outline-gold:hover { background-color: #b8860b; color: white; }

    /* Glitter Particle */
    .glitter {
      position: absolute;
      width: 8px;
      height: 8px;
      background: gold;
      border-radius: 50%;
      pointer-events: none;
      animation: glitter-fade 1s linear forwards;
      z-index: 1000;
    }
    @keyframes glitter-fade {
      0% { opacity: 1; transform: scale(1); }
      100% { opacity: 0; transform: scale(2); }
    }

    /* Navbar */
    .navbar-brand img {
      height: 50px;
      margin-right: 10px;
    }
    .navbar-nav .nav-link {
      font-weight: 500;
    }

    /* Hero */
    .hero img.hero-overlay {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      z-index: 2;
      opacity: 1;
      max-width: 30%;
    }
    @media (max-width: 768px) {
      .hero img.hero-overlay {
        max-width: 60%;
      }
    }

    /* Slick Dots Hide */
    .slick-dots {
      display: none !important;
    }

    /* Floating Button */
    .floating-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1001;
      border-radius: 50px;
      padding: 12px 24px;
    }

    /* Gallery Responsive */
    @media (max-width: 576px) {
      #gallery .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }
  </style>
</head>

<body>

<!-- Golden Animation Background -->
<div class="golden-bg"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" 
     style="z-index:1001;background: linear-gradient(to left, #f59e0b, #fcd34d, #ffffff);">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center text-black fw-bold" href="#">
      <img src="https://pushkarajjyotishkaryalay.com/vanshavali/logo512.png" alt="Sonar Samaj Logo">
      Sonar Samaj
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link text-black" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link text-black" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link text-black" href="#events">Events</a></li>
        <li class="nav-item"><a class="nav-link text-black" href="#gallery">Gallery</a></li>
        <li class="nav-item"><a class="nav-link text-black" href="#testimonials">Testimonials</a></li>
        <li class="nav-item"><a class="nav-link text-black" href="register.php">Register</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero d-flex align-items-center" style="min-height:90vh; position: relative; overflow: hidden;">

  <!-- 🎥 Video Background -->
  <video autoplay muted loop playsinline aria-hidden="true"
         style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0;">
    <source src="https://cdn.pixabay.com/video/2022/12/20/143668-784138097_large.mp4" type="video/mp4">
  </video>

  <!-- ✨ Image Overlay -->
  <img src="assets/images/nalharimaharaj.png" alt="Shri Sant Narhari Maharaj Sonar" 
       class="hero-overlay">

  <!-- ✨ Content -->
  <div class="container text-center" data-aos="zoom-in" style="position: relative; z-index: 2;">
    <h1 class="display-4 fw-bold text-gold">श्री संत शिरोमणी नरहरी महाराज सोनार</h1>
    <p class="lead mb-4" style="color:#fcd34d;">श्री संत नरहरी सोनार</p>
    <a href="register.php" class="btn btn-gold btn-lg me-2">Join Now</a>
    <a href="login.php" class="btn btn-outline-gold btn-lg">Login</a>
  </div>
</section>

<!-- About -->
<section id="about" class="py-5 bg-white">
  <div class="container" data-aos="fade-up">
    <div class="row align-items-center">
      <div class="col-md-6 mb-4">
        <img src="https://pushkarajjyotishkaryalay.com/vanshavali/logo512.png" 
             class="img-fluid rounded shadow" alt="Sonar Samaj Logo Large">
      </div>
      <div class="col-md-6">
        <h2 class="text-gold mb-3">About Sonar Samaj</h2>
        <p>We are proud custodians of the rich heritage of Goldsmith traditions. Our community is built on values of trust, excellence, family unity, and social progress.</p>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section id="features" class="py-5" style="background: linear-gradient(to right, #fff8e1, #fffaf0);">
  <div class="container" data-aos="fade-up">
    <div class="text-center mb-5">
      <h2 class="text-gold">Community Features</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4" data-aos="flip-left">
        <div class="card feature-card text-center shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title text-gold">Global Directory</h5>
            <p>Connect and network with members around the world.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4" data-aos="flip-up">
        <div class="card feature-card text-center shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title text-gold">Family Tree</h5>
            <p>Explore your ancestral roots and celebrate family bonds.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4" data-aos="flip-right">
        <div class="card feature-card text-center shadow-sm h-100">
          <div class="card-body">
            <h5 class="card-title text-gold">Events & Celebrations</h5>
            <p>Participate in cultural, religious, and festive gatherings.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Events -->
<section id="events" class="py-5 bg-white">
  <div class="container" data-aos="fade-up">
    <div class="text-center mb-5">
      <h2 class="text-gold">Upcoming Events</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6" data-aos="fade-right">
        <div class="event-card p-4 rounded shadow-sm h-100">
          <h5 class="text-gold">Annual Gathering 2025</h5>
          <p class="small text-muted">10th June 2025 | Mumbai</p>
          <p>Join us for a grand celebration of traditions, awards, and performances!</p>
        </div>
      </div>
      <div class="col-md-6" data-aos="fade-left">
        <div class="event-card p-4 rounded shadow-sm h-100">
          <h5 class="text-gold">Cultural Festival</h5>
          <p class="small text-muted">15th August 2025 | Delhi</p>
          <p>A colorful event showcasing the traditions, arts, and crafts of our Sonar community.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Gallery -->
<section id="gallery" class="py-5" style="background: linear-gradient(to right, #fffaf0, #fff8e1);">
  <div class="container" data-aos="fade-up">
    <div class="text-center mb-5">
      <h2 class="text-gold">Gallery</h2>
    </div>
    <div class="row g-3">
      <div class="col-6 col-md-3" data-aos="zoom-in">
        <img src="assets/images/gallery1.png" class="img-fluid rounded shadow-sm" alt="Community Event 1">
      </div>
      <div class="col-6 col-md-3" data-aos="zoom-in">
        <img src="assets/images/gallery2.jpg" class="img-fluid rounded shadow-sm" alt="Community Event 2">
      </div>
      <div class="col-6 col-md-3" data-aos="zoom-in">
        <img src="assets/images/gallery3.jpg" class="img-fluid rounded shadow-sm" alt="Community Event 3">
      </div>
      <div class="col-6 col-md-3" data-aos="zoom-in">
        <img src="assets/images/gallery4.jpg" class="img-fluid rounded shadow-sm" alt="Community Event 4">
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-5" style="background: linear-gradient(to right, #fffaf0, #fff8e1);">
  <div class="container" data-aos="fade-up">
    <div class="text-center mb-5">
      <h2 class="text-gold" data-aos="zoom-in">What Our Members Say</h2>
      <p class="text-muted" data-aos="fade-up" data-aos-delay="200">Voices that strengthen our community.</p>
    </div>

    <div class="testimonial-slider">
      <div class="testimonial-card" data-aos="flip-left" data-aos-delay="100">
        <div class="testimonial-inner p-4 shadow-sm">
          <i class="bi bi-quote text-gold display-1"></i>
          <p class="text-muted">"Being part of Sonar Samaj has brought me closer to my heritage."</p>
          <h6 class="text-gold mt-3">- Rajesh Soni</h6>
        </div>
      </div>
      <div class="testimonial-card" data-aos="flip-left" data-aos-delay="200">
        <div class="testimonial-inner p-4 shadow-sm">
          <i class="bi bi-quote text-gold display-1"></i>
          <p class="text-muted">"Amazing networking opportunities within our Samaj."</p>
          <h6 class="text-gold mt-3">- Pooja Soni</h6>
        </div>
      </div>
      <div class="testimonial-card" data-aos="flip-left" data-aos-delay="300">
        <div class="testimonial-inner p-4 shadow-sm">
          <i class="bi bi-quote text-gold display-1"></i>
          <p class="text-muted">"I found my old friends and made new ones at community events."</p>
          <h6 class="text-gold mt-3">- Dinesh Soni</h6>
        </div>
      </div>
      <div class="testimonial-card" data-aos="flip-left" data-aos-delay="400">
        <div class="testimonial-inner p-4 shadow-sm">
          <i class="bi bi-quote text-gold display-1"></i>
          <p class="text-muted">"The family tree feature is an amazing initiative!"</p>
          <h6 class="text-gold mt-3">- Kavita Soni</h6>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Floating Button -->
<a href="register.php" class="btn btn-gold btn-lg floating-btn" 
   data-aos="fade-left" data-aos-duration="1500">Join Us</a>

<!-- Footer -->
<footer class="bg-white text-center text-gold py-4 border-top">
  <div class="container">
    <p class="mb-0">&copy; <?php echo date('Y'); ?> Sonar Samaj | United in Tradition & Progress</p>
  </div>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<script>
AOS.init({
  duration: 1200,
  once: true,
});

$('.testimonial-slider').slick({
  dots: true,
  arrows: false,
  autoplay: true,
  autoplaySpeed: 3000,
  adaptiveHeight: true,
});

// Optimized Glitter Effect
let lastGlitter = 0;
document.addEventListener('mousemove', function(e) {
  if (Date.now() - lastGlitter > 50) {
    const glitter = document.createElement('div');
    glitter.className = 'glitter';
    glitter.style.left = (e.pageX + (Math.random() * 15 - 7)) + 'px';
    glitter.style.top = (e.pageY + (Math.random() * 15 - 7)) + 'px';
    document.body.appendChild(glitter);
    setTimeout(() => glitter.remove(), 1200);
    lastGlitter = Date.now();
  }
});
</script>

</body>
</html>
