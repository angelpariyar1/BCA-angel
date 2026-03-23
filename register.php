<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $retype_password = $_POST['retype_password'] ?? '';
    
    $response = ['success' => false, 'message' => '', 'fields' => []];
    
    // Validation
    if (empty($username)) {
        $response['fields']['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $response['fields']['username'] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $response['fields']['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['fields']['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $response['fields']['password'] = 'Password is required';
    } else {
        $password_pattern = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/';
        if (!preg_match($password_pattern, $password)) {
            $response['fields']['password'] = 'Password must be at least 8 characters with letters, numbers, and special characters';
        }
    }
    
    if (empty($retype_password)) {
        $response['fields']['retype_password'] = 'Please retype password';
    } elseif ($password !== $retype_password) {
        $response['fields']['retype_password'] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    if (empty($response['fields'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $response['fields']['general'] = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please login.';
            } else {
                $response['fields']['general'] = 'Registration failed. Please try again.';
            }
        }
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiperPens - Register</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="split-container">
        <div class="form-side">
            <div class="form-card">
                <h2>Register</h2>
                
                <form id="registerForm" class="register-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Choose a username">
                        <div class="error-message" id="usernameError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email">
                        <div class="error-message" id="emailError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password"> 
                        <small class="hint">At least 8 characters with letters, numbers, and special characters</small>
                        <div class="error-message" id="passwordError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="retype_password">Retype password</label>
                        <input type="password" id="retype_password" name="retype_password" placeholder="Retype your password">
                        <div class="error-message" id="retype_passwordError"></div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Register</button>
                    
                    <p class="login-link">
                        Already have an account! <a href="login.php">Login here..</a>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="brand-side">
            <div class="brand-content">
                <h1 class="brand-name">PiperPens</h1>
                <p class="brand-tagline">Your Premium Stationery Shop</p>
            </div>
        </div>
    </div>
    
    <script src="js/register.js"></script>
</body>
</html>