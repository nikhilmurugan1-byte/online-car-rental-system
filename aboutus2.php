<?php
session_start();
include('connection.php');

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header('location: login.php');
    exit();
}

// Get user's name from database
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT FNAME FROM users WHERE EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userName = $user ? $user['FNAME'] : 'Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CaRs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .navbar {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4158D0;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            padding: 0.5rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: #4158D0;
        }

        .nav-link.active {
            color: #4158D0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .logout-btn {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .logout-btn:hover {
            color: #4158D0;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-menu {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 80px);
                flex-direction: column;
                background: #fff;
                padding: 2rem;
                transition: 0.3s ease-in-out;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .nav-menu.active {
                left: 0;
            }

            .nav-link {
                font-size: 1.2rem;
                padding: 1rem 0;
            }

            .user-profile {
                flex-direction: column;
                text-align: center;
                padding: 1rem 0;
            }

            .logout-btn {
                padding: 1rem 0;
                width: 100%;
                text-align: center;
                border-top: 1px solid #eee;
                margin-top: 1rem;
            }
        }

        .hero {
            background: linear-gradient(rgba(65, 88, 208, 0.8), rgba(65, 88, 208, 0.8)), url('images/carbg2.jpg');
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 74px;
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero-content p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 1rem;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .about-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .about-card:hover {
            transform: translateY(-5px);
        }

        .about-card i {
            font-size: 2.5rem;
            color: #4158D0;
            margin-bottom: 1rem;
        }

        .about-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .about-card p {
            color: #666;
        }

        .features {
            background: #fff;
            padding: 4rem 0;
            margin-top: 4rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .feature-item i {
            font-size: 1.5rem;
            color: #4158D0;
        }

        .feature-content h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .feature-content p {
            color: #666;
            font-size: 0.9rem;
        }

        .team {
            padding: 4rem 0;
            text-align: center;
        }

        .section-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: #4158D0;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: #3448a5;
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .nav-menu {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
        <div class="nav-container">
            <a href="cardetails.php" class="logo">
                <i class="fas fa-car"></i>
                CaRs
            </a>
            
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>

            <div class="nav-menu" id="nav-menu">
                <a href="cardetails.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="aboutus2.php" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    About
                </a>
                <a href="contactus2.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    Contact
                </a>
                <a href="bookinstatus.php" class="nav-link">
                    <i class="fas fa-bookmark"></i>
                    My Bookings
                </a>
                <a href="feedback.php" class="nav-link active">
                    <i class="fas fa-comment"></i>
                    Feedback
                </a>
                <div class="user-profile">
                    <img src="images/profile.png" alt="Profile" class="user-avatar">
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>About CaRs</h1>
            <p>Your trusted partner in car rentals, providing quality vehicles and exceptional service since 2023</p>
        </div>
    </section>

    <div class="container">
        <div class="about-grid">
            <div class="about-card">
                <i class="fas fa-car"></i>
                <h3>Quality Vehicles</h3>
                <p>We maintain a fleet of well-maintained, modern vehicles to ensure your comfort and safety on every journey.</p>
            </div>
            <div class="about-card">
                <i class="fas fa-handshake"></i>
                <h3>Customer Service</h3>
                <p>Our dedicated team is committed to providing exceptional service and support throughout your rental experience.</p>
            </div>
            <div class="about-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Safe & Reliable</h3>
                <p>All our vehicles undergo regular maintenance and safety checks to ensure worry-free travel.</p>
            </div>
        </div>
    </div>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <div class="feature-content">
                        <h4>24/7 Support</h4>
                        <p>Round-the-clock customer support for your convenience</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="feature-content">
                        <h4>Best Rates</h4>
                        <p>Competitive pricing with no hidden charges</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-map-marked-alt"></i>
                    <div class="feature-content">
                        <h4>Flexible Pickup</h4>
                        <p>Convenient pickup and drop-off locations</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <div class="feature-content">
                        <h4>Easy Booking</h4>
                        <p>Simple and quick online booking process</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="team">
        <div class="container">
            <h2 class="section-title">Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <img src="images/profile.png" alt="Team Member">
                    <h3>John Doe</h3>
                    <p>Founder & CEO</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/profile.png" alt="Team Member">
                    <h3>Jane Smith</h3>
                    <p>Operations Manager</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/profile.png" alt="Team Member">
                    <h3>Mike Johnson</h3>
                    <p>Customer Service Head</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Toggle mobile menu
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');

        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            // Change hamburger icon
            const icon = hamburger.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
                const icon = hamburger.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking a link
        document.querySelectorAll('.nav-link, .logout-btn').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    navMenu.classList.remove('active');
                    const icon = hamburger.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });
    </script>
</body>
</html>
