<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/config.php';

// Add debug logging
$debug = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $debug[] = "Attempting login for username: $username";
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $debug[] = "User found: " . ($user ? 'Yes' : 'No');
        
        // Simple password comparison since we're storing plain text (not recommended for production)
        if ($user && $password === $user['password']) {
            $debug[] = "Password matched";
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            $debug[] = "Session created";
            header('Location: dashboard.php');
            exit();
        } else {
            $debug[] = "Password did not match";
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        $debug[] = "Database error: " . $e->getMessage();
        $error = "Login failed: Database error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Clinic - Login</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="../assets/images/logo.jpeg" alt="Dental Clinic Logo" class="logo">
            <h1>THE DENTAL CLINIC</h1>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>
    <?php if (!empty($debug)): ?>
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
            <h3>Debug Information:</h3>
            <pre><?php print_r($debug); ?></pre>
        </div>
    <?php endif; ?>
</body>
</html> 