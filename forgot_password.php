<?php
session_start();
require 'config.php';

$error = "";
$success = "";
$step = isset($_SESSION['fp_step']) ? $_SESSION['fp_step'] : '1';

// Step 1: Request OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        mysqli_query($conn, "UPDATE users SET 
            reset_token='$otp', 
            reset_expires='$expires' 
            WHERE email='$email'");

        $_SESSION['fp_email'] = $email;
        $_SESSION['fp_otp'] = $otp;
        $_SESSION['fp_step'] = '2';
        $step = '2';
        $success = "OTP generated! Use code below (demo mode):";
        $demo_otp = $otp;
    } else {
        $error = "Email not found!";
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $entered_otp = intval($_POST['otp']);
    $stored_otp = intval($_SESSION['fp_otp'] ?? 0);

    if ($entered_otp === $stored_otp) {
        $_SESSION['fp_step'] = '3';
        $step = '3';
    } else {
        $error = "Invalid OTP! Please try again.";
        $step = '2';
    }
}

// Step 3: New Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $email = $_SESSION['fp_email'] ?? '';
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    if ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match!";
        $step = '3';
    } else {
        mysqli_query($conn, "UPDATE users SET 
            password='$new_password',
            reset_token=NULL,
            reset_expires=NULL 
            WHERE email='$email'");

        unset($_SESSION['fp_step']);
        unset($_SESSION['fp_email']);
        unset($_SESSION['fp_otp']);
        $step = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password - InkGuard</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
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
            top:0; left:0;
            width:100%; height:100%;
            background: radial-gradient(ellipse at 30% 40%,
                rgba(124,124,255,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 60%,
                rgba(0,204,102,0.08) 0%, transparent 60%);
            z-index: 0;
        }
        .wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 40px 36px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            text-align: center;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 16px;
            box-shadow: 0 8px 25px rgba(124,124,255,0.4);
        }
        h1 {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .alert-error {
            background: rgba(255,68,68,0.15);
            border: 1px solid rgba(255,68,68,0.4);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: left;
        }
        .alert-success {
            background: rgba(0,204,102,0.15);
            border: 1px solid rgba(0,204,102,0.4);
            color: #00cc66;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: left;
        }
        .otp-demo {
            background: rgba(124,124,255,0.1);
            border: 1px solid rgba(124,124,255,0.4);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
            font-size: 28px;
            font-weight: 900;
            color: #7c7cff;
            letter-spacing: 8px;
        }
        .form-group {
            text-align: left;
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
            padding: 13px 16px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-input:focus {
            border-color: rgba(124,124,255,0.6);
        }
        .form-input::placeholder { color: #555; }
        .otp-input {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 6px;
        }
        .btn {
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
            margin-bottom: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124,124,255,0.5);
        }
        .back-link {
            font-size: 13px;
            color: #888;
        }
        .back-link a {
            color: #7c7cff;
            text-decoration: none;
            font-weight: 600;
        }
        /* Steps */
        .steps {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 28px;
        }
        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #333;
            transition: all 0.3s;
        }
        .step-dot.active {
            background: #7c7cff;
            width: 24px;
            border-radius: 4px;
        }
        .step-dot.done { background: #00cc66; }
        /* Password wrap */
        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 46px; }
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
        }
        /* Strength */
        .strength-bar {
            height: 4px;
            background: #333;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 6px;
        }
        .strength-fill {
            height: 100%;
            border-radius: 99px;
            transition: all 0.3s;
            width: 0%;
        }
        .strength-text {
            font-size: 11px;
            margin-top: 4px;
            text-align: left;
        }
        /* Done */
        .done-icon {
            font-size: 60px;
            margin-bottom: 16px;
        }
        .done-title {
            font-size: 24px;
            font-weight: 800;
            color: #00cc66;
            margin-bottom: 8px;
        }
        .done-desc {
            color: #888;
            font-size: 14px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
<div class="wrap">
<div class="card">

    <!-- Brand -->
    <div class="brand-icon">🛡️</div>

    <!-- Steps -->
    <div class="steps">
        <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"></div>
        <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"></div>
        <div class="step-dot <?= $step >= 3 ? ($step == 'done' ? 'done' : 'active') : '' ?>"></div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="alert-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <?php if ($step == 'done'): ?>
    <!-- Done -->
    <div class="done-icon">✅</div>
    <div class="done-title">Password Reset!</div>
    <div class="done-desc">Your password has been changed successfully.</div>
    <a href="login.php" class="btn" style="display:block; text-decoration:none;">
        🔐 Back to Sign In
    </a>

    <?php elseif ($step == '1'): ?>
    <!-- Step 1: Email -->
    <h1>Recover Password</h1>
    <p class="subtitle">Request a password reset OTP for your account</p>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email"
                class="form-input"
                placeholder="e.g. name@domain.com"
                required>
        </div>
        <button type="submit" class="btn">
            📨 Send Recovery OTP
        </button>
    </form>
    <div class="back-link">
        Back to <a href="login.php">Sign In</a>
    </div>

    <?php elseif ($step == '2'): ?>
    <!-- Step 2: OTP -->
    <h1>Enter OTP</h1>
    <p class="subtitle">Enter the 6-digit code sent to your email</p>

    <?php if (isset($demo_otp)): ?>
    <div class="otp-demo"><?= $demo_otp ?></div>
    <p style="color:#888; font-size:11px; margin-bottom:16px;">
        ⚠️ Demo mode: OTP shown above (real app sends email)
    </p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>6-Digit OTP Code</label>
            <input type="number"
                name="otp"
                class="form-input otp-input"
                placeholder="000000"
                maxlength="6"
                required>
        </div>
        <button type="submit" class="btn">
            ✅ Verify OTP
        </button>
    </form>
    <div class="back-link">
        Wrong email? <a href="forgot_password.php">Try again</a>
    </div>

    <?php elseif ($step == '3'): ?>
    <!-- Step 3: New Password -->
    <h1>New Password</h1>
    <p class="subtitle">Enter your new password below</p>

    <form method="POST">
        <div class="form-group">
            <label>New Password</label>
            <div class="pw-wrap">
                <input type="password"
                    name="new_password"
                    id="newPw"
                    class="form-input"
                    placeholder="New password"
                    oninput="checkStr(this.value)"
                    required>
                <button type="button" class="pw-toggle"
                    onclick="togglePw('newPw',this)">👁️</button>
            </div>
            <div class="strength-bar">
                <div class="strength-fill" id="strFill"></div>
            </div>
            <div class="strength-text" id="strText"></div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="pw-wrap">
                <input type="password"
                    name="confirm_password"
                    id="conPw"
                    class="form-input"
                    placeholder="Confirm password"
                    required>
                <button type="button" class="pw-toggle"
                    onclick="togglePw('conPw',this)">👁️</button>
            </div>
        </div>

        <button type="submit" class="btn"
            onclick="return validate()">
            🔐 Reset Password
        </button>
    </form>
    <div class="back-link">
        <a href="login.php">← Back to Sign In</a>
    </div>

    <?php endif; ?>

</div>
</div>

<script>
function togglePw(id, btn) {
    const f = document.getElementById(id);
    if (f.type === 'password') {
        f.type = 'text';
        btn.textContent = '🙈';
    } else {
        f.type = 'password';
        btn.textContent = '👁️';
    }
}

function checkStr(pw) {
    const fill = document.getElementById('strFill');
    const text = document.getElementById('strText');
    let s = 0;
    if (pw.length >= 6) s += 25;
    if (pw.length >= 8) s += 25;
    if (/[A-Z]/.test(pw)) s += 25;
    if (/[0-9!@#$%]/.test(pw)) s += 25;
    let c = '#ff4444', l = '❌ Weak';
    if (s >= 75) { c = '#00cc66'; l = '💪 Strong'; }
    else if (s >= 50) { c = '#ffd700'; l = '⚠️ Medium'; }
    fill.style.width = s + '%';
    fill.style.background = c;
    text.textContent = l;
    text.style.color = c;
}

function validate() {
    const pw = document.getElementById('newPw').value;
    const cp = document.getElementById('conPw').value;
    if (pw !== cp) { alert('Passwords do not match!'); return false; }
    if (pw.length < 6) { alert('At least 6 characters!'); return false; }
    return true;
}
</script>
</body>
</html>