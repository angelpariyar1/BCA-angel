<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $response = ['success' => false, 'message' => '', 'field' => ''];
    
    if (empty($login)) {
        $response['message'] = 'Username/email is required';
        $response['field'] = 'login';
    } elseif (empty($password)) {
        $response['message'] = 'Password is required';
        $response['field'] = 'password';
    } else {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['role'] = $user['role'];
        } else {
            $response['message'] = 'Invalid username/email or password';
            $response['field'] = 'login';
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
    <title>PiperPens - Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="split-container">
        <div class="brand-side">
            <div class="brand-content">
                <h1 class="brand-name">PiperPens</h1>
                <p class="brand-tagline">Your Premium Stationery Shop</p>
            </div>
        </div>
        
        <div class="form-side">
            <div class="form-card">
                <h2>Login</h2>
                
                <form id="loginForm" class="login-form">
                    <div class="form-group">
                        <label for="login">Username/email</label>
                        <input type="text" id="login" name="login" placeholder="Enter username or email" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
                        <div class="error-message" id="loginError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password">
                        <div class="error-message" id="passwordError"></div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Submit</button>
                    
                    <p class="register-link">
                        Don't have an account! <a href="register.php">Sign up here..</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/login.js"></script>
</body>
</html>