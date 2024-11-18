<?php
// Start session to manage login status and user sessions
session_start();

// Define the default page to show if no page is specified
$page = isset($_GET['page']) ? $_GET['page'] : 'signup';

// Redirect to signup or login page
if (!isset($_SESSION['user_id']) && $page !== 'signup' && $page !== 'login') {
    header("Location: ?page=login");
    exit();
}

// Include the election.php file or a part of it based on the page parameter
switch ($page) {
    case 'signup':
        include('signup.php');
        break;
    case 'login':
        include('login.php');
        break;
    case 'election':
        include('election.php');
        break;
    default:
        include('signup.php');
        break;
}
?>
