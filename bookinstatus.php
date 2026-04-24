<?php
require 'connection.php';
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];

// Get user's name
$userQuery = "SELECT CONCAT(FNAME) as userName FROM users WHERE EMAIL = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("s", $email);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();
$userName = $userData['userName'];

// Get user's bookings
$query = "SELECT b.*, c.CAR_NAME, c.CAR_IMG, c.FUEL_TYPE, c.CAPACITY, c.PRICE 
          FROM booking b 
          JOIN cars c ON b.CAR_ID = c.CAR_ID 
          WHERE b.EMAIL = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status - CaRs</title>
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
            min-height: 100vh;
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
            color: #4158D0;
            font-size: 1.8rem;
            font-weight: 700;
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

        .nav-link:hover, .nav-link.active {
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

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .booking-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .booking-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .booking-details {
            padding: 1.5rem;
        }

        .booking-car-name {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .info-item i {
            color: #4158D0;
            font-size: 1.2rem;
        }

        .booking-dates {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .date-item i {
            color: #4158D0;
        }

        .booking-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            text-align: center;
            margin-bottom: 1rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-price {
            font-size: 1.5rem;
            color: #4158D0;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: right;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .cancel-btn {
            background: #dc3545;
            color: #fff;
        }

        .cancel-btn:hover {
            background: #c82333;
        }

        .view-btn {
            background: #4158D0;
            color: #fff;
        }

        .view-btn:hover {
            background: #3448a5;
        }

        .no-bookings {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-bookings h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .no-bookings p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .browse-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: #4158D0;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .browse-btn:hover {
            background: #3448a5;
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

            .header h1 {
                font-size: 2rem;
            }

            .bookings-grid {
                grid-template-columns: 1fr;
            }

            .booking-info {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
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

    <div class="container">
        <div class="header">
            <h1>My Bookings</h1>
            <p>View and manage your car bookings</p>
        </div>

        <div class="bookings-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($booking = $result->fetch_assoc()) {
            ?>
            <div class="booking-card">
                <img src="images/<?php echo htmlspecialchars($booking['CAR_IMG']); ?>" alt="<?php echo htmlspecialchars($booking['CAR_NAME']); ?>" class="booking-image">
                <div class="booking-details">
                    <h2 class="booking-car-name"><?php echo htmlspecialchars($booking['CAR_NAME']); ?></h2>
                    <div class="booking-info">
                        <div class="info-item">
                            <i class="fas fa-gas-pump"></i>
                            <span><?php echo htmlspecialchars($booking['FUEL_TYPE']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo htmlspecialchars($booking['CAPACITY']); ?> Seater</span>
                        </div>
                    </div>
                    <div class="booking-dates">
                        <div class="date-item">
                            <i class="fas fa-calendar-plus"></i>
                            <span>From: <?php echo date('d M Y', strtotime($booking['BOOK_DATE'])); ?></span>
                        </div>
                        <div class="date-item">
                            <i class="fas fa-calendar-minus"></i>
                            <span>To: <?php echo date('d M Y', strtotime($booking['RETURN_DATE'])); ?></span>
                        </div>
                    </div>
                    <div class="booking-status <?php echo 'status-' . strtolower($booking['BOOK_STATUS']); ?>">
                        <?php echo htmlspecialchars($booking['BOOK_STATUS']); ?>
                    </div>
                    <div class="booking-price">
                        ₹<?php echo number_format($booking['PRICE'] ?? 0); ?>
                    </div>
                    <div class="action-buttons">
                        <?php if ($booking['BOOK_STATUS'] != 'Cancelled') { ?>
                        <a href="cancel_booking.php?id=<?php echo $booking['BOOK_ID']; ?>" class="action-btn cancel-btn">
                            <i class="fas fa-times-circle"></i>
                            Cancel
                        </a>
                        <?php } ?>
                        <a href="view_booking.php?id=<?php echo $booking['BOOK_ID']; ?>" class="action-btn view-btn">
                            <i class="fas fa-eye"></i>
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="no-bookings">
                <h2>No Bookings Found</h2>
                <p>You haven't made any car bookings yet.</p>
                <a href="cardetails.php" class="browse-btn">
                    <i class="fas fa-car"></i>
                    Browse Cars
                </a>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');

        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            hamburger.innerHTML = navMenu.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });

        // Toggle profile dropdown
        const profileLink = document.getElementById('profile-link');
        const profileDropdown = document.getElementById('profile-dropdown');

        profileLink.addEventListener('click', (e) => {
            e.preventDefault();
            profileDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileLink.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    navMenu.classList.remove('active');
                    hamburger.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        });
    </script>
</body>
</html>