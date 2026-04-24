<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT FNAME FROM users WHERE EMAIL = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$userName = $user ? $user['FNAME'] : 'Account';

// Get filter values from GET parameters
$fuelType = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';
$capacity = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 0;
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+3 days'));

// Build the SQL query with filters
$sql = "SELECT * FROM cars WHERE AVAILABLE = 'YES'";

// Add fuel type filter if selected
if (!empty($fuelType)) {
    $sql .= " AND FUEL_TYPE = '" . mysqli_real_escape_string($conn, $fuelType) . "'";
}

// Add capacity filter if selected
if ($capacity > 0) {
    $sql .= " AND CAPACITY = " . $capacity;
}

// Add price range filter
$sql .= " AND PRICE BETWEEN " . $minPrice . " AND " . $maxPrice;

// Exclude cars that are booked for the selected dates
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND CAR_ID NOT IN (
        SELECT DISTINCT b.CAR_ID FROM booking b 
        WHERE b.BOOK_STATUS IN ('PENDING', 'APPROVED', 'CONFIRMED') 
        AND (
            (b.BOOK_DATE <= '" . mysqli_real_escape_string($conn, $endDate) . "' AND b.RETURN_DATE >= '" . mysqli_real_escape_string($conn, $startDate) . "') OR 
            (b.BOOK_DATE <= '" . mysqli_real_escape_string($conn, $startDate) . "' AND b.RETURN_DATE >= '" . mysqli_real_escape_string($conn, $startDate) . "') OR
            (b.BOOK_DATE >= '" . mysqli_real_escape_string($conn, $startDate) . "' AND b.RETURN_DATE <= '" . mysqli_real_escape_string($conn, $endDate) . "')
        )
    )";
}

// Add sorting
$sql .= " ORDER BY CAR_NAME";

$result = $conn->query($sql);

// Get distinct fuel types for filter dropdown
$fuelTypesQuery = "SELECT DISTINCT FUEL_TYPE FROM cars ORDER BY FUEL_TYPE";
$fuelTypesResult = $conn->query($fuelTypesQuery);

// Get distinct capacities for filter dropdown
$capacitiesQuery = "SELECT DISTINCT CAPACITY FROM cars ORDER BY CAPACITY";
$capacitiesResult = $conn->query($capacitiesQuery);

// Get min and max prices for the price slider
$priceRangeQuery = "SELECT MIN(PRICE) as min_price, MAX(PRICE) as max_price FROM cars";
$priceRangeResult = $conn->query($priceRangeQuery);
$priceRange = $priceRangeResult->fetch_assoc();
$dbMinPrice = $priceRange['min_price'];
$dbMaxPrice = $priceRange['max_price'];

// Set default price range if not specified
if ($minPrice == 0) {
    $minPrice = $dbMinPrice;
}
if ($maxPrice == 100000) {
    $maxPrice = $dbMaxPrice;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Cars - CaRs</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.css">
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

        .nav-link:hover {
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
            margin-bottom: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Filter section styles */
        .filter-section {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 500;
            color: #555;
        }

        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            border-color: #4158D0;
        }

        .price-range {
            padding: 0 10px;
            margin-bottom: 1.5rem;
        }

        .price-inputs {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .price-display {
            font-weight: 500;
            color: #333;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .apply-btn {
            background: #4158D0;
            color: #fff;
        }

        .apply-btn:hover {
            background: #3448a8;
        }

        .reset-btn {
            background: #f1f1f1;
            color: #333;
        }

        .reset-btn:hover {
            background: #e1e1e1;
        }

        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .car-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .car-img {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .car-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .car-card:hover .car-img img {
            transform: scale(1.05);
        }

        .car-details {
            padding: 1.5rem;
            flex: 1;
        }

        .car-name {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .car-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .car-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .car-info-item i {
            color: #4158D0;
        }

        .car-actions {
            margin-top: auto;
        }

        .book-btn {
            display: block;
            background: #4158D0;
            color: #fff;
            text-align: center;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .book-btn:hover {
            background: #3448a8;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-menu {
                position: fixed;
                left: -100%;
                top: 70px;
                flex-direction: column;
                background: #fff;
                width: 100%;
                padding: 2rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
                z-index: 999;
            }

            .nav-menu.active {
                left: 0;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        /* noUiSlider custom styles */
        .noUi-connect {
            background: #4158D0;
        }

        .noUi-handle {
            border: 1px solid #4158D0;
            background: #fff;
            border-radius: 50%;
        }

        .noUi-handle:before,
        .noUi-handle:after {
            display: none;
        }

        .date-filter {
            grid-column: span 2;
        }
        
        .date-inputs {
            display: flex;
            gap: 10px;
        }
        
        .date-field {
            flex: 1;
        }
        
        .date-field label {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }
        
        .date-filter-info {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .date-filter-info i {
            color: #4158D0;
        }
        
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
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
            <h1>Available Cars</h1>
            <p>Choose from our wide selection of quality vehicles</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h2 class="filter-title">
                <i class="fas fa-filter"></i> Filter Cars
            </h2>
            <form class="filter-form" method="GET" action="cardetails.php">
                <div class="filter-group">
                    <label class="filter-label" for="fuel_type">Fuel Type</label>
                    <select class="filter-select" name="fuel_type" id="fuel_type">
                        <option value="">All Fuel Types</option>
                        <?php while($fuelTypeRow = $fuelTypesResult->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($fuelTypeRow['FUEL_TYPE']); ?>" <?php echo ($fuelType == $fuelTypeRow['FUEL_TYPE']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fuelTypeRow['FUEL_TYPE']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label" for="capacity">Seating Capacity</label>
                    <select class="filter-select" name="capacity" id="capacity">
                        <option value="0">All Capacities</option>
                        <?php while($capacityRow = $capacitiesResult->fetch_assoc()): ?>
                            <option value="<?php echo $capacityRow['CAPACITY']; ?>" <?php echo ($capacity == $capacityRow['CAPACITY']) ? 'selected' : ''; ?>>
                                <?php echo $capacityRow['CAPACITY']; ?> Seater
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group date-filter">
                    <label>Booking Dates</label>
                    <div class="date-inputs">
                        <div class="date-field">
                            <label for="start_date">From</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="date-field">
                            <label for="end_date">To</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="date-filter-info">
                        <i class="fas fa-info-circle"></i>
                        <span>Showing cars available from <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></span>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label" for="price_range">Price Range (₹/day)</label>
                    <div class="price-range" id="price-slider"></div>
                    <div class="price-inputs">
                        <span class="price-display">₹<span id="min-price-display"><?php echo $minPrice; ?></span></span>
                        <span class="price-display">₹<span id="max-price-display"><?php echo $maxPrice; ?></span></span>
                    </div>
                    <input type="hidden" name="min_price" id="min_price" value="<?php echo $minPrice; ?>">
                    <input type="hidden" name="max_price" id="max_price" value="<?php echo $maxPrice; ?>">
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="filter-btn apply-btn">Apply Filters</button>
                    <a href="cardetails.php" class="filter-btn reset-btn">Reset</a>
                </div>
            </form>
        </div>

        <div class="cars-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($car = $result->fetch_assoc()) {
            ?>
            <div class="car-card">
                <div class="car-img">
                    <img src="images/<?php echo htmlspecialchars($car['CAR_IMG']); ?>" alt="<?php echo htmlspecialchars($car['CAR_NAME']); ?>">
                    <?php if (!empty($startDate) && !empty($endDate)): ?>
                    <div class="availability-badge">
                        <i class="fas fa-calendar-check"></i> Available
                    </div>
                    <?php endif; ?>
                </div>
                <div class="car-details">
                    <h2 class="car-name"><?php echo htmlspecialchars($car['CAR_NAME']); ?></h2>
                    <div class="car-info">
                        <div class="car-info-item">
                            <i class="fas fa-gas-pump"></i>
                            <span><?php echo htmlspecialchars($car['FUEL_TYPE']); ?></span>
                        </div>
                        <div class="car-info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $car['CAPACITY']; ?> Seater</span>
                        </div>
                        <div class="car-info-item price">
                            <i class="fas fa-rupee-sign"></i>
                            <span><?php echo number_format($car['PRICE']); ?>/day</span>
                        </div>
                    </div>
                    <div class="car-actions">
                        <a href="booking.php?id=<?php echo $car['CAR_ID']; ?>" class="btn book-btn">Book Now</a>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div style="text-align: center; grid-column: 1 / -1; padding: 2rem;">
                <h2>No cars available with the selected filters</h2>
                <p>Please try different filter options or <a href="cardetails.php">reset all filters</a></p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.js"></script>
    <script>
        // Toggle mobile menu
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');

        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Initialize price range slider
        const priceSlider = document.getElementById('price-slider');
        const minPriceInput = document.getElementById('min_price');
        const maxPriceInput = document.getElementById('max_price');
        const minPriceDisplay = document.getElementById('min-price-display');
        const maxPriceDisplay = document.getElementById('max-price-display');

        noUiSlider.create(priceSlider, {
            start: [<?php echo $minPrice; ?>, <?php echo $maxPrice; ?>],
            connect: true,
            step: 100,
            range: {
                'min': <?php echo $dbMinPrice; ?>,
                'max': <?php echo $dbMaxPrice; ?>
            }
        });

        priceSlider.noUiSlider.on('update', function (values, handle) {
            const value = Math.round(values[handle]);
            
            if (handle === 0) {
                minPriceInput.value = value;
                minPriceDisplay.textContent = value;
            } else {
                maxPriceInput.value = value;
                maxPriceDisplay.textContent = value;
            }
        });

        // Date validation
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && new Date(endDateInput.value) < new Date(this.value)) {
                endDateInput.value = this.value;
            }
            
            // Auto-submit form when dates change
            document.querySelector('.filter-form').submit();
        });
        
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && new Date(this.value) < new Date(startDateInput.value)) {
                this.value = startDateInput.value;
            }
            
            // Auto-submit form when dates change
            document.querySelector('.filter-form').submit();
        });
        
        // Set default dates if not already set
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            // If no dates are in the URL, set default values
            if (!window.location.search.includes('start_date')) {
                const today = new Date();
                startDateInput.value = today.toISOString().split('T')[0];
                
                const threeDaysLater = new Date();
                threeDaysLater.setDate(today.getDate() + 3);
                endDateInput.value = threeDaysLater.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>