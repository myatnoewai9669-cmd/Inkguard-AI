<?php
session_start();
require 'config.php';
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

$error = "";

$remembered_email = "";
if (isset($_COOKIE['remember_user'])) {
    $remembered_email = $_COOKIE['remember_user'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT * FROM users WHERE email='$email' OR username='$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        if ($remember) {
            setcookie('remember_user', $email,
                time() + (30 * 24 * 60 * 60), '/');
        } else {
            setcookie('remember_user', '', time() - 3600, '/');
        }

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InkGuard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #050510;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: 
                radial-gradient(ellipse at 20% 50%, 
                    rgba(124,124,255,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, 
                    rgba(0,204,102,0.08) 0%, transparent 60%);
            z-index: 0;
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 40px 36px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 12px;
            box-shadow: 0 8px 25px rgba(124,124,255,0.4);
        }

        .brand h1 {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #7c7cff, #00cc66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .brand p {
            color: #888;
            font-size: 14px;
        }

        .alert-error {
            background: rgba(255,68,68,0.15);
            border: 1px solid rgba(255,68,68,0.4);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #888;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
            outline: none;
        }

        .form-input:focus {
            border-color: rgba(124,124,255,0.6);
            background: rgba(124,124,255,0.05);
        }

        .form-input::placeholder { color: #555; }

        /* Fix autofill */
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover,
        .form-input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #0a0a1a inset !important;
            -webkit-text-fill-color: #fff !important;
            border-color: rgba(124,124,255,0.4) !important;
        }

        .pw-wrap {
            position: relative;
        }

        .pw-wrap .form-input {
            padding-right: 46px;
        }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 17px;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }

        .pw-toggle:hover { color: #7c7cff; }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #888;
            cursor: pointer;
            user-select: none;
        }

        .remember-label input {
            width: 15px;
            height: 15px;
            accent-color: #7c7cff;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #7c7cff;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #00cc66;
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(124,124,255,0.3);
            margin-bottom: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124,124,255,0.5);
        }

        .btn-login:active { transform: scale(0.98); }

        .divider {
            text-align: center;
            font-size: 12px;
            color: #555;
            margin-bottom: 16px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #333;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 20px;
        }

        .google-btn:hover {
            background: #f5f5f5;
            transform: translateY(-1px);
        }

        .register-link {
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .register-link a {
            color: #7c7cff;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            color: #00cc66;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon">🛡️</div>
            <h1>InkGuard</h1>
            <p>Welcome Back! Sign in to continue</p>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert-error">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" autocomplete="off">

            <!-- Email -->
            <div class="form-group">
                <label>Email Address</label>
                <input type="email"
                    name="email"
                    class="form-input"
                    placeholder="your@email.com"
                    value="<?= htmlspecialchars($remembered_email) ?>"
                    required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label>Password</label>
                <div class="pw-wrap">
                    <input type="password"
                        name="password"
                        id="pwField"
                        class="form-input"
                        placeholder="Enter your password"
                        autocomplete="new-password"
                        required>
                    <button type="button"
                        class="pw-toggle"
                        id="pwToggle"
                        onclick="togglePw()">👁️</button>
                </div>
            </div>

            <!-- Remember + Forgot -->
            <div class="options-row">
                <label class="remember-label">
                    <input type="checkbox"
                        name="remember"
                        <?= $remembered_email ? 'checked' : '' ?>>
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">
                    Forgot Password?
                </a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login">
                🔐 Sign In
            </button>

        </form>

        <!-- Divider -->
        <div class="divider">or continue with</div>

        <!-- Google -->
        <!-- Google -->
<a href="google_login.php" class="google-btn">
    <svg viewBox="0 0 48 48" width="20" height="20">
        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.6 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/>
        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 16 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.5 6 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.5-5.2l-6.2-5.2C29.3 35.4 26.8 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.5 39.6 16.2 44 24 44z"/>
        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.2-4.2 5.6l6.2 5.2C40.9 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-3.5z"/>
    </svg>
    Sign in with Google
</a>

        <!-- Register -->
        <div class="register-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </div>

    </div>
</div>

<script>
function togglePw() {
    const field = document.getElementById('pwField');
    const btn = document.getElementById('pwToggle');
    if (field.type === 'password') {
        field.type = 'text';
        btn.textContent = '🙈';
    } else {
        field.type = 'password';
        btn.textContent = '👁️';
    }
}

history.pushState(null, null, location.href);
window.onpopstate = function() {
    window.location.replace('login.php');
};
</script>
</body>
</html>