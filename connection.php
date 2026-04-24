<?php 
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_connect('localhost', 'root', '', 'carproject');
    if(!$conn) {
        die('Database connection failed: ' . mysqli_connect_error());
    }
?>