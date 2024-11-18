<?php
session_start();

// Database connection (update with your setup)
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'election_db';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id INT NOT NULL UNIQUE,
    vice_president VARCHAR(255),
    joint_secretary VARCHAR(255),
    FOREIGN KEY (voter_id) REFERENCES users(id)
)");

// Initialize messages
$success = $error = '';

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = $_POST['name'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $department = $conn->real_escape_string($_POST['department']);
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, gender, department, email, password) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $name, $gender, $department, $email, $password);
        if ($stmt->execute()) {
            echo "Sign-up successful!";
            header("Location: ?page=login");
            exit();
        } else {
            echo "Error executing query: " . $stmt->error;
        }
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}


// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: ?page=election");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}

// Handle Voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $user_id = $_SESSION['user_id'];
    $vice_president = $_POST['vice_president'];
    $joint_secretary = $_POST['joint_secretary'];

    $stmt = $conn->prepare("INSERT INTO votes (voter_id, vice_president, joint_secretary) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE vice_president = ?, joint_secretary = ?");
    $stmt->bind_param("issss", $user_id, $vice_president, $joint_secretary, $vice_president, $joint_secretary);

    if ($stmt->execute()) {
        $success = "Your votes have been recorded!";
    } else {
        $error = "An error occurred while recording your votes.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?page=login");
    exit();
}

// Get Voted Details
$vote = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT vice_president, joint_secretary FROM votes WHERE voter_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $vote = $result->fetch_assoc();
    }
}

// Define candidates for VP and JS
$vice_presidents = ['ISMANKHAN Y M', 'ARIVAZHAGAN E', 'PRAGADESHWARAN R', 'DINESH R', 'MOHAMED SALMAN KHAN S', 'VEERAMANI R'];
$joint_secretaries = ['MANOJ R', 'MADESHWARAN M', 'RAGUL A', 'KRANGARAJAN B', 'ARUN KUMAR R', 'AKARAM MEERAN '];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election System</title>
    <style>
        body { font-family: Arial, sans-serif;
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/gce.png'); /* Replace with your image URL */
            background-size: cover; /* Ensures the image covers the entire element */
            background-repeat: no-repeat; /* Prevents the image from repeating */
            background-position: center center; /* Centers the image in the element */
            height: 980px; /* Makes sure the body takes up the full viewport height */
            margin: 0;
            padding: 0;
            
            
        }
        .container { max-width: 400px; margin: 50px auto; padding: 25px; background: white; border-radius: 10px; }
        h2 { text-align: center;
            font-family: sans-serif;
            font-style: sans-serif;
            font-weight: bolder;
            font-size: 35px;
            color:#007BFF;
            padding:0;
            margin:10px 0px 40px 0px;
         }
         
        form { display: flex; flex-direction: column; }
       /* label { margin: 20px 0 5px; } */
        input,select,  button { font-size: 15px; padding: 10px; margin-bottom: 15px; border: 2px solid #ccc; border-radius: 10px; }
        button { background: #007BFF; color: white; cursor: pointer; }
        button:hover { background: #0056b3; }
        .logout { text-align: center; }
        .success { color: green; }
        .error { color: red; }
        #gender, #department{
                color: gray;
                    }

    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_GET['page'])): ?>
        <h2>Sign up</h2>
      
        <?php if ($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
           
            <input type="text" name="name" id="name" required placeholder="Enter Your Name">

           
                <select id="gender" name="gender" required>
                <option value="a" disabled selected>Select your gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                </select>

                <select id="department" name="department" required>
                    <option value="" disabled selected>Select your Department</option>
                    <option value="CSE">CSE</option>
                    <option value="ECE">ECE</option>
                    <option value="EEE">EEE</option>
                    <option value="MECH">MECH</option>
                    <option value="CIVIL">CIVIL</option>
                    </select>
                
            <input type="email" name="email" id="email" required placeholder="Enter Your Email-id">
            
            <input type="password" name="password" id="password" required placeholder="Enter Your Password">
            
            <button type="submit" name="signup">Sign up</button>
        </form>
        <p>Already have an account? <a href="?page=login">Login here</a>.</p>

    <?php elseif ($_GET['page'] == 'login'): ?>
        <h2>Hi, Welcome back!</h2>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            
            <input type="email" name="email" id="email" required placeholder="Enter your Email-id">
            
            <input type="password" name="password" id="password" required placeholder="Enter your Password">
            <button type="submit" name="login">Login</button>
        </form>
        <p>Don't have an account? <a href="?page=signup">Sign up here</a>.</p>

    <?php elseif ($_GET['page'] == 'election'): ?>
        <h2>Election Page</h2>
        <?php if ($vote): ?>
            <p>You voted for:</p>
            <p>Vice President: <?php echo htmlspecialchars($vote['vice_president']); ?></p>
            <p>Joint Secretary: <?php echo htmlspecialchars($vote['joint_secretary']); ?></p>
        <?php else: ?>
            <form method="POST">
                <h3>Vote for Vice President</h3>
                <?php foreach ($vice_presidents as $candidate): ?>
                    <label>
                        <input type="radio" name="vice_president" value="<?php echo $candidate; ?>" required>
                        <?php echo $candidate; ?>
                    </label><br>
                <?php endforeach; ?>
                <h3>Vote for Joint Secretary</h3>
                <?php foreach ($joint_secretaries as $candidate): ?>
                    <label>
                        <input type="radio" name="joint_secretary" value="<?php echo $candidate; ?>" required>
                        <?php echo $candidate; ?>
                    </label><br>
                <?php endforeach; ?>
                <button type="submit" name="vote">Submit Votes</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="logout">
            <a href="?logout">Logout</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
