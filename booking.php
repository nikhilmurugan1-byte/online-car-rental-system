<?php 
require_once('connection.php');
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Check if car_id is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$car_id = mysqli_real_escape_string($conn, $_GET['id']);
$email = $_SESSION['email'];

// Get car details
$sql = "SELECT * FROM cars WHERE CAR_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$car = $result->fetch_assoc();

// Get user details
$sql = "SELECT * FROM users WHERE EMAIL = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_place = mysqli_real_escape_string($conn, $_POST['book_place']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $book_date = mysqli_real_escape_string($conn, $_POST['book_date']);
    $return_date = mysqli_real_escape_string($conn, $_POST['return_date']);
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : $user['PHONE_NUMBER'];
    
    // Validate dates
    $today = date('Y-m-d');
    $start = new DateTime($book_date);
    $end = new DateTime($return_date);
    $now = new DateTime($today);
    
    // Check if dates are valid
    if ($start < $now) {
        $booking_error = "Booking date cannot be in the past. Please select a future date.";
    } elseif ($end <= $start) {
        $booking_error = "Return date must be after the booking date.";
    } else {
        // Calculate duration and price
        $duration = $start->diff($end)->days;
        
        if ($duration < 1) {
            $booking_error = "Minimum rental duration is 1 day.";
        } else {
            $price = $duration * $car['PRICE'];
            
            // Check if the car is already booked for the requested dates
            $checkSql = "SELECT * FROM booking 
                        WHERE CAR_ID = ? 
                        AND BOOK_STATUS IN ('PENDING', 'APPROVED', 'CONFIRMED') 
                        AND (
                            (BOOK_DATE <= ? AND RETURN_DATE >= ?) OR 
                            (BOOK_DATE <= ? AND RETURN_DATE >= ?) OR
                            (BOOK_DATE >= ? AND RETURN_DATE <= ?)
                        )";
            
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("issssss", $car_id, $return_date, $book_date, $book_date, $book_date, $book_date, $return_date);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Car is already booked for these dates
                $booking_error = "Sorry, this car is not available for the selected dates. Please choose different dates or another car.";
            } else {
                // Insert booking
                $sql = "INSERT INTO booking (CAR_ID, EMAIL, BOOK_PLACE, DESTINATION, BOOK_DATE, RETURN_DATE, DURATION, PRICE, BOOK_STATUS, PHONE_NUMBER) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssiis", $car_id, $email, $book_place, $destination, $book_date, $return_date, $duration, $price, $phone);
                
                if ($stmt->execute()) {
                    $booking_id = $stmt->insert_id;
                    header("Location: payment.php?booking_id=" . $booking_id);
                    exit();
                } else {
                    $booking_error = "An error occurred while processing your booking. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Car - CaRs</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            padding-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        .car-preview {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }

        .car-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .car-details h2 {
            color: #333;
            margin-bottom: 1rem;
        }

        .car-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            color: #666;
        }

        .info-item strong {
            display: block;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .price-tag {
            background: #4158D0;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .booking-form {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #eee;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4158D0;
            box-shadow: 0 0 0 3px rgba(65, 88, 208, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #4158D0;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: #3448a5;
        }

        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .user-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .user-info strong {
            color: #333;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }

            .car-preview {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="car-preview">
            <img src="images/<?php echo htmlspecialchars($car['CAR_IMG']); ?>" alt="<?php echo htmlspecialchars($car['CAR_NAME']); ?>" class="car-image">
            <div class="car-details">
                <h2><?php echo htmlspecialchars($car['CAR_NAME']); ?></h2>
                <div class="car-info">
                    <div class="info-item">
                        <strong>Fuel Type</strong>
                        <?php echo htmlspecialchars($car['FUEL_TYPE']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Capacity</strong>
                        <?php echo htmlspecialchars($car['CAPACITY']); ?> Seater
                    </div>
                </div>
                <div class="price-tag">
                    ₹<?php echo number_format($car['PRICE']); ?> per day
                </div>
            </div>
        </div>

        <div class="booking-form">
            <div class="form-header">
                <h1>Book Your Car</h1>
                <p>Fill in the details to complete your booking</p>
            </div>

            <div class="user-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['FNAME'] . ' ' . $user['LNAME']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['EMAIL']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['PHONE_NUMBER']); ?></p>
            </div>

            <form action="" method="POST" class="booking-form">
                <?php if (isset($booking_error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $booking_error; ?>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="book_place"><i class="fas fa-map-marker-alt"></i> Pickup Location</label>
                    <input type="text" class="form-control" id="book_place" name="book_place" required>
                </div>

                <div class="form-group">
                    <label for="destination"><i class="fas fa-map-pin"></i> Destination</label>
                    <input type="text" class="form-control" id="destination" name="destination" required>
                </div>

                <div class="form-group">
                    <label for="book_date"><i class="far fa-calendar-alt"></i> Pickup Date</label>
                    <input type="date" class="form-control" id="book_date" name="book_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="return_date"><i class="far fa-calendar-alt"></i> Return Date</label>
                    <input type="date" class="form-control" id="return_date" name="return_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Confirm Booking
                </button>
            </form>
        </div>
    </div>

    <script>
        // Date validation
        document.addEventListener('DOMContentLoaded', function() {
            const bookDateInput = document.getElementById('book_date');
            const returnDateInput = document.getElementById('return_date');
            
            bookDateInput.addEventListener('change', function() {
                returnDateInput.min = this.value;
                if (returnDateInput.value && new Date(returnDateInput.value) < new Date(this.value)) {
                    returnDateInput.value = this.value;
                }
            });
            
            returnDateInput.addEventListener('change', function() {
                if (bookDateInput.value && new Date(this.value) < new Date(bookDateInput.value)) {
                    this.value = bookDateInput.value;
                }
            });
        });
    </script>
</body>
</html>