<?php
session_start();
require_once '../config/database.php';

// Kick out anyone who isn't logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch available rooms
$rooms_sql = "
    SELECT r.id, r.room_number, r.floor, rt.type_name, rt.price_per_night, rt.description 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE r.status = 'Available'
    ORDER BY rt.price_per_night ASC
";
$available_rooms = $conn->query($rooms_sql);

// Fallback images for the room cards
$room_images = [
    'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=800&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1582719478250-c89404bb8a0e?q=80&w=800&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?q=80&w=800&auto=format&fit=crop'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --navy: #0c2340;
            --emerald: #10b981;
            --emerald-hover: #059669;
            --text-dark: #1e293b;
            --text-gray: #64748b;
            --bg-light: #f8fafc;
        }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: var(--text-dark); padding-top: 80px; }
        
        /* Navbar */
        .guest-navbar { background-color: var(--navy); padding: 15px 0; border-bottom: 3px solid var(--emerald); }
        .navbar-brand { color: #fff !important; font-weight: 800; font-size: 1.4rem; letter-spacing: -0.5px; text-transform: uppercase; }
        .navbar-brand i { color: var(--emerald); margin-right: 10px; }
        .nav-link { color: #cbd5e1 !important; font-weight: 600; transition: color 0.3s; }
        .nav-link:hover { color: #fff !important; }
        
        /* Page Header */
        .portal-header { background: #fff; padding: 40px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }
        
        /* Room Cards */
        .room-card { border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s; overflow: hidden; background: #fff; }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); border-color: var(--emerald); }
        .room-img { width: 100%; height: 250px; object-fit: cover; }
        .room-badge { position: absolute; top: 15px; right: 15px; background: rgba(12, 35, 64, 0.85); color: #fff; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; backdrop-filter: blur(4px); }
        .room-body { padding: 25px; }
        .room-title { font-weight: 800; color: var(--navy); font-size: 1.4rem; margin-bottom: 5px; }
        .room-meta { font-size: 0.9rem; color: var(--text-gray); margin-bottom: 15px; font-weight: 500; }
        .room-desc { font-size: 0.95rem; color: var(--text-gray); line-height: 1.6; margin-bottom: 20px; }
        .amenities-list { list-style: none; padding: 0; margin-bottom: 25px; }
        .amenities-list li { font-size: 0.9rem; color: var(--text-dark); margin-bottom: 8px; font-weight: 500; }
        .amenities-list i { color: var(--emerald); width: 20px; }
        .price-tag { font-size: 2rem; font-weight: 800; color: var(--navy); }
        .price-tag span { font-size: 1rem; font-weight: 500; color: var(--text-gray); }
        
        .btn-emerald { background-color: var(--emerald); color: #fff; border-radius: 8px; font-weight: 700; padding: 12px 24px; transition: all 0.3s; width: 100%; display: block; text-align: center; border: none; text-decoration: none; }
        .btn-emerald:hover { background-color: var(--emerald-hover); color: #fff; transform: translateY(-2px); text-decoration: none; }
    </style>
</head>
<body>

<!-- Secure Guest Navbar -->
<nav class="navbar navbar-expand-lg guest-navbar fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="rooms.php"><i class="fas fa-bed"></i>Afrik Hotel</a>
        <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#guestNav">
            <i class="fas fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="guestNav">
            <div class="ml-auto d-flex align-items-center">
                <span class="text-light mr-4 d-none d-lg-block">Welcome, <span class="text-white font-weight-bold"><?php echo htmlspecialchars($_SESSION['guest_name']); ?></span></span>
                <a href="my-bookings.php" class="nav-link"><i class="fas fa-list-alt mr-1"></i> My Bookings</a>
                <a href="logout.php" class="nav-link text-danger font-weight-bold"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="portal-header">
    <div class="container text-center">
        <h2 class="font-weight-bold text-dark mb-2" style="font-size: 2.2rem;">Select Your Room</h2>
        <p class="text-muted mb-0" style="font-size: 1.1rem;">Browse our live inventory and book your stay instantly.</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <?php if ($available_rooms->num_rows > 0): ?>
            <?php 
            $i = 0;
            while($room = $available_rooms->fetch_assoc()): 
                $img = $room_images[$i % count($room_images)];
                $i++;
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="room-card position-relative h-100 d-flex flex-column">
                        <img src="<?php echo $img; ?>" class="room-img" alt="Room Image">
                        <div class="room-badge">Room <?php echo $room['room_number']; ?></div>
                        
                        <div class="room-body d-flex flex-column flex-grow-1">
                            <h4 class="room-title"><?php echo htmlspecialchars($room['type_name']); ?></h4>
                            <div class="room-meta"><i class="fas fa-layer-group mr-1"></i> Floor <?php echo $room['floor']; ?></div>
                            <p class="room-desc flex-grow-1"><?php echo htmlspecialchars($room['description']); ?></p>
                            
                            <ul class="amenities-list">
                                <li><i class="fas fa-wifi"></i> Free High-Speed WiFi</li>
                                <li><i class="fas fa-concierge-bell"></i> 24/7 Room Service</li>
                                <li><i class="fas fa-snowflake"></i> Air Conditioning</li>
                            </ul>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4 pt-3 border-top">
                                <div class="price-tag">$<?php echo number_format($room['price_per_night'], 0); ?><span> / night</span></div>
                            </div>
                            
                            <a href="checkout.php?room_id=<?php echo $room['id']; ?>" class="btn-emerald">
                                Book This Room <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 my-5">
                <div style="width: 100px; height: 100px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                    <i class="fas fa-door-closed fa-3x text-secondary"></i>
                </div>
                <h3 class="font-weight-bold text-dark">Fully Booked</h3>
                <p class="text-muted" style="font-size: 1.1rem;">We are currently at full capacity. Please check back later for availability.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>