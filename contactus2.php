<?php
session_start();
include('connection.php');

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header('location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    if ($stmt->execute()) {
        echo "<script>alert('Message sent successfully!');</script>";
    } else {
        echo "<script>alert('Error sending message. Please try again.');</script>";
    }
    $stmt->close();
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
    <title>Contact Us - CaRs</title>
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

        .hero {
            background: linear-gradient(rgba(65, 88, 208, 0.8), rgba(65, 88, 208, 0.8)), url('images/carbg2.jpg');
            background-size: cover;
            background-position: center;
            height: 40vh;
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

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: -100px;
            position: relative;
        }

        .contact-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item i {
            width: 40px;
            height: 40px;
            background: #4158D0;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .info-content h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .info-content p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #eee;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4158D0;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: #4158D0;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            background: #3448a5;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: #f5f5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4158D0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #4158D0;
            color: white;
            transform: translateY(-3px);
        }

        .map {
            margin-top: 4rem;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .map iframe {
            width: 100%;
            height: 400px;
            border: none;
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

            .hero-content h1 {
                font-size: 2rem;
            }

            .contact-grid {
                margin-top: 2rem;
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
                <a href="contactus2.php" class="nav-link active">
                    <i class="fas fa-envelope"></i>
                    Contact
                </a>
                <a href="bookinstatus.php" class="nav-link">
                    <i class="fas fa-bookmark"></i>
                    My Bookings
                </a>
                <a href="feedback.php" class="nav-link">
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
            <h1>Contact Us</h1>
            <p>Get in touch with us for any questions or support</p>
        </div>
    </section>

    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Get In Touch</h2>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="info-content">
                        <h3>Location</h3>
                        <p>4671 Sugar Camp Road, Owagonna, Minnesota, 55060</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div class="info-content">
                        <h3>Phone</h3>
                        <p>507-475-6094</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p>contactuscars@gmail.com</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Mon - Sat: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <form class="contact-form" method="POST" action="" style="background: #fff; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 8px; width: 550px;">
        <h2 style="margin-bottom: 15px; text-align: center; color: #333;">Send Message</h2>
        <div class="form-group" style="position: relative; margin-bottom: 20px;">
            <input type="text" name="name" required placeholder=" " style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; transition: border-color 0.3s;">
            <label style="position: absolute; top: 10px; left: 12px; color: #777; transition: 0.3s; pointer-events: none;">Name</label>
        </div>
        <div class="form-group" style="position: relative; margin-bottom: 20px;">
            <input type="email" name="email" required placeholder=" " style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; transition: border-color 0.3s;">
            <label style="position: absolute; top: 10px; left: 12px; color: #777; transition: 0.3s; pointer-events: none;">Email</label>
        </div>
        <div class="form-group" style="position: relative; margin-bottom: 20px;">
            <input type="text" name="subject" required placeholder=" " style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; transition: border-color 0.3s;">
            <label style="position: absolute; top: 10px; left: 12px; color: #777; transition: 0.3s; pointer-events: none;">Subject</label>
        </div>
        <div class="form-group" style="position: relative; margin-bottom: 20px;">
            <textarea name="message" required placeholder=" " style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; outline: none; transition: border-color 0.3s;"></textarea>
            <label style="position: absolute; top: 10px; left: 12px; color: #777; transition: 0.3s; pointer-events: none;">Message</label>
        </div>
        <button type="submit" class="submit-btn" style="width: 100%; padding: 10px; border: none; background: #007bff; color: white; font-size: 16px; border-radius: 5px; cursor: pointer; transition: 0.3s;">Send Message</button>
    </form>
        </div>

        <div class="map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3888.0129550594097!2d79.1639447!3d12.971022699999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bad47a28d145d6f%3A0xd1465a973c93afe3!2sSJT%2C%20VIT%20University%2C%20Vellore%2C%20Tamil%20Nadu%20632014!5e0!3m2!1sen!2sin!4v1741153885129!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

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