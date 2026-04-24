<?php
require_once('connection.php');
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$success_message = '';
$error_message = '';

// Get user's name
$stmt = $conn->prepare("SELECT FNAME FROM users WHERE EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userName = $user ? $user['FNAME'] : 'Account';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    if (empty($rating) || empty($comment)) {
        $error_message = "Please provide both rating and comment";
    } else {
        $query = "INSERT INTO feedback (email, rating, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sis", $email, $rating, $comment);
        
        if ($stmt->execute()) {
            $success_message = "Thank you for your feedback!";
        } else {
            $error_message = "Error submitting feedback. Please try again.";
        }
        $stmt->close();
    }
}

// Get user's previous feedbacks
$query = "SELECT f.*, u.FNAME, u.LNAME FROM feedback f 
          JOIN users u ON f.email = u.EMAIL 
          WHERE f.email = ? 
          ORDER BY f.feedback_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - CaRs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4158D0;
            --secondary-color: #C850C0;
            --background: #f4f6f9;
            --text-color: #333;
            --light-text: #666;
            --success: #28a745;
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
        }

        /* Navigation Styles */
        .navbar {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 1.5em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
        }

        .nav-link i {
            font-size: 1.2em;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-name {
            color: var(--text-color);
            font-weight: 500;
        }

        .logout-btn {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .logout-btn:hover {
            color: var(--primary-color);
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.5em;
            color: var(--text-color);
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                padding: 20px;
                flex-direction: column;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-link {
                padding: 10px 0;
            }

            .user-profile {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Rest of your existing styles */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .feedback-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .feedback-header h1 {
            font-size: 2.5em;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .feedback-header p {
            color: var(--light-text);
            font-size: 1.1em;
        }

        .feedback-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .rating-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .rating-container h3 {
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .stars {
            font-size: 2em;
            color: #ffd700;
            cursor: pointer;
        }

        .stars i {
            transition: all 0.2s ease;
        }

        .stars i:hover,
        .stars i.active {
            transform: scale(1.2);
        }

        .comment-container {
            margin-bottom: 20px;
        }

        .comment-container textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 120px;
            font-size: 1em;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error);
        }

        .previous-feedbacks {
            margin-top: 40px;
        }

        .previous-feedbacks h2 {
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .feedback-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .feedback-card .rating {
            color: #ffd700;
            margin-bottom: 10px;
        }

        .feedback-card .comment {
            color: var(--text-color);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .feedback-card .meta {
            color: var(--light-text);
            font-size: 0.9em;
            display: flex;
            justify-content: space-between;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }

            .feedback-header h1 {
                font-size: 2em;
            }

            .feedback-form {
                padding: 20px;
            }

            .stars {
                font-size: 1.8em;
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
    </nav>func_get_arg
     ,

      
              b  nnnnnnbn bbb   b    bbb                    0213

    <div class="container">
        <div class="feedback-header">
            <h1>Share Your Experience</h1>
            <p>Your feedback helps us improve our service</p>
        </div>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="feedback-form">
            <form method="POST">
                <div class="rating-container">
                    <h3>Rate your experience</h3>
                    <div class="stars">
                        <i class="far fa-star" data-rating="1"></i>
                        <i class="far fa-star" data-rating="2"></i>
                        <i class="far fa-star" data-rating="3"></i>
                        <i class="far fa-star" data-rating="4"></i>
                        <i class="far fa-star" data-rating="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="selected-rating" required>
                </div>

                <div class="comment-container">
                    <textarea name="comment" placeholder="Tell us about your experience..." required></textarea>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="submit-btn">Submit Feedback</button>
                </div>
            </form>
        </div>

        <div class="previous-feedbacks">
            <h2>Your Previous Feedbacks</h2>
            <?php foreach ($feedbacks as $feedback): ?>
                <div class="feedback-card">
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $feedback['rating'] ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="comment"><?php echo htmlspecialchars($feedback['comment']); ?></div>
                    <div class="meta">
                        <span><?php echo date('F j, Y', strtotime($feedback['feedback_date'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.stars i');
        const ratingInput = document.getElementById('selected-rating');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.rating;
                ratingInput.value = rating;

                // Reset all stars
                stars.forEach(s => {
                    s.className = 'far fa-star';
                });

                // Fill stars up to selected rating
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.className = 'fas fa-star active';
                    }
                });
            });

            star.addEventListener('mouseover', () => {
                const rating = star.dataset.rating;
                
                // Preview star filling
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.className = 'fas fa-star';
                    } else {
                        s.className = 'far fa-star';
                    }
                });
            });
        });

        // Reset to selected rating when mouse leaves container
        document.querySelector('.stars').addEventListener('mouseleave', () => {
            const rating = ratingInput.value;
            
            stars.forEach(s => {
                if (s.dataset.rating <= rating) {
                    s.className = 'fas fa-star active';
                } else {
                    s.className = 'far fa-star';
                }
            });
        });

        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');

        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = hamburger.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
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
    </script>
</body>
</html>
