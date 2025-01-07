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
    <title>Login - The Dental Clinic</title>
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="../assets/images/logo.jpeg" alt="Dental Clinic Logo" class="login-logo">
            <h2>Welcome Back!</h2>
            
            <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="login-form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="login-input" 
                           placeholder="Enter your username"
                           required>
                </div>

                <div class="login-form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="login-input" 
                           placeholder="Enter your password"
                           required>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p>The Dental Clinic Management System</p>
            </div>
        </div>
    </div>
</body>
</html> 