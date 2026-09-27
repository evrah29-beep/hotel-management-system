<?php
session_start();
require_once '../config/database.php';

// If already logged in, redirect straight to the secure booking portal
if (isset($_SESSION['guest_id'])) {
    header("Location: rooms.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afrik Hotel - Welcome</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --slate: #0f172a;
            --emerald: #10b981;
            --emerald-hover: #059669;
            --navy: #0c2340;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --bg-light: #f8fafc;
        }
        body { font-family: 'Inter', sans-serif; background-color: #fff; color: var(--text-dark); overflow-x: hidden; }

        /* Navbar */
        .navbar-custom { background-color: var(--slate); padding: 18px 0; border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; }
        .navbar-brand { display: flex; align-items: center; color: #fff !important; text-decoration: none; font-weight: 800; font-size: 1.4rem; letter-spacing: -0.5px; }
        
        /* Hotel Logo Image Style */
        .brand-logo { height: 40px; width: auto; margin-right: 10px; object-fit: contain; }
        
        .nav-link { color: #f8fafc !important; font-weight: 600; font-size: 0.95rem; margin: 0 15px; transition: color 0.3s; position: relative; }
        .nav-link:hover { color: var(--emerald) !important; }
        .nav-link.active::after { content: ''; position: absolute; bottom: -8px; left: 10px; right: 10px; height: 2px; background-color: var(--emerald); }
        
        .btn-login { color: #fff; font-weight: 600; text-decoration: none; margin-right: 25px; transition: color 0.3s; }
        .btn-login:hover { color: var(--emerald); text-decoration: none; }
        .btn-emerald { background-color: var(--emerald); color: #fff; border-radius: 6px; padding: 10px 24px; font-weight: 600; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-emerald:hover { background-color: var(--emerald-hover); color: #fff; text-decoration: none; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }

        /* Sliding Hero Section */
        .hero-wrapper { position: relative; height: 80vh; min-height: 600px; overflow: hidden; background-color: var(--slate); display: flex; align-items: center; justify-content: center; }
        .hero-carousel, .hero-carousel .carousel-inner, .hero-carousel .carousel-item { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .hero-carousel .carousel-item { background-size: cover; background-position: center; transition: opacity 1.5s ease-in-out; }
        .hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); z-index: 1; }
        
        /* Centered Hero Content */
        .hero-content { position: relative; z-index: 2; text-align: center; padding: 0 20px; width: 100%; margin-top: 50px; }
        .hero-title { font-size: 3.8rem; font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 20px; }
        .hero-subtitle { font-size: 1.2rem; color: #e2e8f0; font-weight: 400; margin-bottom: 40px; max-width: 750px; margin-left: auto; margin-right: auto; line-height: 1.6; }
        
        .btn-outline-white { border: 2px solid #fff; color: #fff; border-radius: 6px; padding: 14px 32px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-outline-white:hover { background: #fff; color: var(--slate); text-decoration: none; }
        .btn-hero-solid { background-color: var(--emerald); color: #fff; border-radius: 6px; padding: 14px 32px; font-weight: 700; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-hero-solid:hover { background-color: var(--emerald-hover); color: #fff; transform: translateY(-2px); text-decoration: none; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }

        /* About Section */
        .about-section { padding: 100px 0; background: #fff; }
        .section-tag { color: var(--emerald); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem; display: block; margin-bottom: 15px; }
        .about-title { font-size: 2.8rem; font-weight: 800; color: var(--text-dark); margin-bottom: 25px; line-height: 1.2; letter-spacing: -0.5px; }
        .about-text { color: var(--text-gray); font-size: 1.1rem; line-height: 1.8; margin-bottom: 20px; }
        .about-image { width: 100%; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); object-fit: cover; height: 450px; }

        /* Photo Gallery Section */
        .gallery-section { padding: 90px 0; background: var(--bg-light); border-top: 1px solid #e2e8f0; }
        .gallery-item { position: relative; border-radius: 16px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); cursor: pointer; }
        .gallery-img { width: 100%; height: 320px; object-fit: cover; transition: transform 0.6s ease; }
        .gallery-item:hover .gallery-img { transform: scale(1.08); }
        .gallery-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(12, 35, 64, 0.9) 0%, rgba(12, 35, 64, 0) 60%); opacity: 0; transition: opacity 0.3s ease; display: flex; align-items: flex-end; padding: 25px; }
        .gallery-item:hover .gallery-overlay { opacity: 1; }
        .gallery-title { color: #fff; font-weight: 700; font-size: 1.3rem; margin: 0; transform: translateY(20px); transition: transform 0.3s ease; }
        .gallery-item:hover .gallery-title { transform: translateY(0); }

        /* Amenities Bar */
        .amenities-bar { padding: 80px 0; background: #fff; border-top: 1px solid #e2e8f0; }
        .amenity-card { text-align: center; padding: 20px; }
        .amenity-card i { font-size: 2.5rem; color: var(--emerald); margin-bottom: 20px; }
        .amenity-card h5 { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin-bottom: 10px; }
        .amenity-card p { font-size: 0.9rem; color: var(--text-gray); margin: 0; }

        /* Curved Footer */
        .footer-curved { background-color: var(--slate); color: #cbd5e1; padding: 80px 0 30px; border-top-left-radius: 60px; border-top-right-radius: 60px; margin-top: 40px; position: relative; }
        .footer-brand { display: flex; align-items: center; margin-bottom: 20px; }
        .footer-brand img { height: 40px; width: auto; margin-right: 12px; object-fit: contain; }
        .footer-brand h2 { color: #fff; font-weight: 800; margin: 0; font-size: 1.5rem; }
        .footer-title { color: #fff; font-weight: 700; font-size: 1.1rem; margin-bottom: 20px; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #cbd5e1; text-decoration: none; transition: color 0.3s; font-weight: 500; }
        .footer-links a:hover { color: var(--emerald); padding-left: 5px; }
        .social-icons a { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.05); color: #fff; border-radius: 50%; margin-right: 10px; transition: all 0.3s; }
        .social-icons a:hover { background: var(--emerald); color: #fff; transform: translateY(-3px); text-decoration: none; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); margin-top: 50px; padding-top: 25px; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="index.php">
                <img src="image.png" alt="Afrik Hotel Logo" class="brand-logo"> Afrik Hotel
            </a>
            <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarNav">
                <i class="fas fa-bars text-white"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="#facilities">Facilities</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center mt-3 mt-lg-0">
                    <a href="login.php" class="btn-login">Log In</a>
                    <a href="register.php" class="btn-emerald">Sign Up to Book</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Centered Sliding Hero Section -->
    <header class="hero-wrapper">
        <!-- Background Carousel -->
        <div id="heroCarousel" class="carousel slide carousel-fade hero-carousel" data-ride="carousel" data-interval="5000" data-pause="false">
            <div class="carousel-inner">
                <!-- Image 1 -->
                <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1542314831-c6a4d1424869?q=80&w=2000&auto=format&fit=crop');"></div>
                <!-- Image 2 -->
                <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=2000&auto=format&fit=crop');"></div>
                <!-- Image 3 -->
                <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1582719478250-c89404bb8a0e?q=80&w=2000&auto=format&fit=crop');"></div>
            </div>
        </div>
        
        <!-- Dark Overlay -->
        <div class="hero-overlay"></div>

        <!-- Centered Content -->
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">Your Luxury Getaway in Mogadishu</h1>
                <p class="hero-subtitle">Experience world-class hospitality, unparalleled comfort, and state-of-the-art security. Join us to unlock real-time room availability and secure your booking today.</p>
                <div class="d-flex justify-content-center flex-wrap mt-4">
                    <a href="register.php" class="btn-hero-solid mx-2 mb-2">Create Guest Account</a>
                    <a href="login.php" class="btn-outline-white mx-2 mb-2">Guest Portal Login</a>
                </div>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0 pr-lg-5">
                    <span class="about-tag">Welcome to Afrik Hotel</span>
                    <h2 class="about-title">An Oasis of Comfort in the Heart of the City.</h2>
                    <p class="about-text">Located centrally, Afrik Hotel merges contemporary design with unmatched hospitality. Whether you are traveling for business, attending a high-level conference, or enjoying a serene getaway, our establishment is designed to cater to your every need.</p>
                    <a href="register.php" class="btn-emerald mt-3">Register to Book <i class="fas fa-arrow-right ml-2"></i></a>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1000&auto=format&fit=crop" class="about-image" alt="Afrik Hotel Resort">
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Photo Gallery -->
    <section id="gallery" class="gallery-section">
        <div class="container-fluid px-lg-5">
            <div class="row mb-5 text-center">
                <div class="col-12">
                    <span class="section-tag text-center d-block">Discover Afrik</span>
                    <h2 class="font-weight-bold text-dark mb-3" style="font-size: 2.5rem;">Our Hotel Gallery</h2>
                    <p class="text-muted mb-0" style="font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Take a glimpse into the luxurious spaces and exceptional amenities that await you.</p>
                </div>
            </div>

            <div class="row">
                <!-- Gallery Item 1: Pool -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Swimming Pool">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Oasis Swimming Pool</h4>
                        </div>
                    </div>
                </div>
                
                <!-- Gallery Item 2: Luxury Room -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Luxury Suite">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Deluxe Suites</h4>
                        </div>
                    </div>
                </div>

                <!-- Gallery Item 3: Restaurant -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Restaurant">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Fine Dining Restaurant</h4>
                        </div>
                    </div>
                </div>

                <!-- Gallery Item 4: Garden / Yard -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1585412727339-54e4bae3bbf9?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Hotel Gardens">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Tranquil Gardens</h4>
                        </div>
                    </div>
                </div>

                <!-- Gallery Item 5: Spa / Relaxation -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Spa and Wellness">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Spa & Wellness Center</h4>
                        </div>
                    </div>
                </div>

                <!-- Gallery Item 6: Lobby -->
                <div class="col-lg-4 col-md-6">
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=1000&auto=format&fit=crop" class="gallery-img" alt="Hotel Lobby">
                        <div class="gallery-overlay">
                            <h4 class="gallery-title">Grand Lobby Lounge</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="register.php" class="btn btn-outline-dark font-weight-bold px-4 py-2" style="border-radius: 8px;">Sign Up to View Available Rooms</a>
            </div>
        </div>
    </section>

    <!-- Amenities -->
    <section id="facilities" class="amenities-bar">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card">
                        <i class="fas fa-mobile-alt"></i>
                        <h5>Smart Payments</h5>
                        <p>EVC Plus, WaafiPay & Cards</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card">
                        <i class="fas fa-shield-alt"></i>
                        <h5>24/7 Security</h5>
                        <p>Complete peace of mind</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card">
                        <i class="fas fa-utensils"></i>
                        <h5>Fine Dining</h5>
                        <p>Local & International Cuisine</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="amenity-card">
                        <i class="fas fa-car"></i>
                        <h5>Airport Shuttle</h5>
                        <p>Safe & secure transfers</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Curved Footer -->
    <footer id="contact" class="footer-curved">
        <div class="container">
            <div class="row pb-4">
                <div class="col-lg-4 mb-4 mb-lg-0 pr-lg-5">
                    <div class="footer-brand">
                        <img src="image.png" alt="Afrik Hotel Logo">
                        <h2>AFRIK HOTEL</h2>
                    </div>
                    <p class="mb-4" style="line-height: 1.8;">Providing world-class hospitality, luxury accommodation, and seamless booking experiences in the heart of Mogadishu.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#about"><i class="fas fa-angle-right mr-2 small text-emerald"></i>About Us</a></li>
                        <li><a href="#gallery"><i class="fas fa-angle-right mr-2 small text-emerald"></i>Photo Gallery</a></li>
                        <li><a href="login.php"><i class="fas fa-angle-right mr-2 small text-emerald"></i>Guest Portal</a></li>
                        <li><a href="register.php"><i class="fas fa-angle-right mr-2 small text-emerald"></i>Create Account</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="footer-links">
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-map-marker-alt text-emerald mt-1 mr-3"></i> Maka Al-Mukarama Road,<br>Mogadishu, Somalia</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-phone-alt text-emerald mr-3"></i> +252 61X XXX XXX</li>
                        <li class="d-flex align-items-center"><i class="fas fa-envelope text-emerald mr-3"></i> info@afrikhotel.com</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Afrik Hotel. Powered by Afrik Management System.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        $('a.nav-link[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if( target.length ) {
                event.preventDefault();
                $('html, body').stop().animate({ scrollTop: target.offset().top - 80 }, 800);
            }
        });
        
        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $('.navbar-custom').css({'background': 'rgba(15, 23, 42, 0.98)', 'box-shadow': '0 4px 20px rgba(0,0,0,0.1)'});
            } else {
                $('.navbar-custom').css({'background': 'var(--slate)', 'box-shadow': 'none'});
            }
        });
    </script>
</body>
</html>