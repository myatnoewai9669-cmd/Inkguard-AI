<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$success = "";
$error = "";

// Add new rule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_rule'])) {
    $forbidden = mysqli_real_escape_string($conn, $_POST['forbidden_word']);
    $replacement = mysqli_real_escape_string($conn, $_POST['replacement']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    $sql = "INSERT INTO brand_rules (user_id, forbidden_word, replacement, reason) 
            VALUES ('$user_id', '$forbidden', '$replacement', '$reason')";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Rule added successfully!";
    } else {
        $error = "Failed to add rule!";
    }
}

// Delete rule
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $sql = "DELETE FROM brand_rules WHERE id='$delete_id' AND user_id='$user_id'";
    mysqli_query($conn, $sql);
    header("Location: brand_rules.php");
    exit();
}

// Check text against rules
$check_result = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_text'])) {
    $check_text = $_POST['check_text'];
    $check_lower = strtolower($check_text);
    
    $rules = mysqli_query($conn, "SELECT * FROM brand_rules WHERE user_id='$user_id'");
    while ($rule = mysqli_fetch_assoc($rules)) {
        if (strpos($check_lower, strtolower($rule['forbidden_word'])) !== false) {
            $check_result[] = $rule;
        }
    }
}

// Get all rules
$rules = mysqli_query($conn, "SELECT * FROM brand_rules WHERE user_id='$user_id' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brand Rules -  InkGuard </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>🛡️InkGuard /h1>
        <div class="user-info">
            <span>👤 <?= $username ?></span>
            <a href="dashboard.php">🔍 Analyzer</a>
            <a href="history.php">📋 History</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>

    <!-- Add Rule Form -->
    <div class="analyzer-box">
        <h2>📋 Brand Rules</h2>
        <p style="color:#aaa; margin-bottom:20px">
            Set forbidden words and their replacements to protect your brand voice.
        </p>

        <?php if($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="color:#aaa; display:block; margin-bottom:5px">
                        ❌ Forbidden Word
                    </label>
                    <input type="text" name="forbidden_word" 
                        placeholder="e.g. cheap"
                        required
                        style="width:100%; padding:10px; background:#0a0a1a; 
                        border:1px solid #333; border-radius:8px; color:#fff;">
                </div>
                <div>
                    <label style="color:#aaa; display:block; margin-bottom:5px">
                        ✅ Replacement Word
                    </label>
                    <input type="text" name="replacement"
                        placeholder="e.g. affordable"
                        style="width:100%; padding:10px; background:#0a0a1a;
                        border:1px solid #333; border-radius:8px; color:#fff;">
                </div>
            </div>
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">
                    📝 Reason (optional)
                </label>
                <input type="text" name="reason"
                    placeholder="e.g. Maintains premium brand image"
                    style="width:100%; padding:10px; background:#0a0a1a;
                    border:1px solid #333; border-radius:8px; color:#fff;">
            </div>
            <button type="submit" name="add_rule"
                style="background:linear-gradient(135deg, #7c7cff, #5555dd);
                color:white; border:none; padding:12px 25px;
                border-radius:8px; cursor:pointer; font-size:1rem;">
                ➕ Add Rule
            </button>
        </form>
    </div>

    <!-- Check Text Against Rules -->
    <div class="analyzer-box" style="margin-top:20px">
        <h2>🔍 Check Text Against Rules</h2>
        <form method="POST">
            <textarea name="check_text" rows="5"
                placeholder="Paste text here to check against your brand rules..."
                style="width:100%; padding:15px; background:#0a0a1a;
                border:1px solid #333; border-radius:8px; color:#fff;
                margin-bottom:15px; resize:vertical;">
            </textarea>
            <button type="submit" name="check_text"
                style="background:linear-gradient(135deg, #ff6b35, #ff4444);
                color:white; border:none; padding:12px 25px;
                border-radius:8px; cursor:pointer; font-size:1rem;">
                🔍 Check Brand Rules
            </button>
        </form>

        <?php if (!empty($check_result)): ?>
        <div style="margin-top:20px; padding:20px; background:rgba(255,68,68,0.1);
            border:1px solid #ff4444; border-radius:10px;">
            <h3 style="color:#ff4444; margin-bottom:15px">
                ⚠️ Brand Rule Violations Found!
            </h3>
            <?php foreach($check_result as $violation): ?>
            <div style="background:#0a0a1a; padding:12px; border-radius:8px; margin-bottom:10px;">
                <p>❌ Forbidden: <strong style="color:#ff4444"><?= $violation['forbidden_word'] ?></strong></p>
                <?php if($violation['replacement']): ?>
                <p>✅ Use instead: <strong style="color:#00cc66"><?= $violation['replacement'] ?></strong></p>
                <?php endif; ?>
                <?php if($violation['reason']): ?>
                <p style="color:#aaa; font-size:0.9rem">📝 <?= $violation['reason'] ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_text'])): ?>
        <div style="margin-top:20px; padding:20px; background:rgba(0,204,102,0.1);
            border:1px solid #00cc66; border-radius:10px;">
            <h3 style="color:#00cc66">✅ No Brand Rule Violations Found!</h3>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rules List -->
    <div class="analyzer-box" style="margin-top:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px">
            <h2>📜 Your Brand Rules</h2>
            <span style="color:#aaa; font-size:0.9rem">
                <?= mysqli_num_rows($rules) ?> rules
            </span>
        </div>

        <?php if(mysqli_num_rows($rules) == 0): ?>
            <p style="color:#aaa; text-align:center; padding:20px">
                No rules yet. Add your first brand rule above!
            </p>
        <?php else: ?>
        <table class="history-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>❌ Forbidden</th>
                    <th>✅ Replacement</th>
                    <th>📝 Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($rule = mysqli_fetch_assoc($rules)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><span style="color:#ff4444"><?= $rule['forbidden_word'] ?></span></td>
                    <td><span style="color:#00cc66"><?= $rule['replacement'] ?: '-' ?></span></td>
                    <td style="color:#aaa; font-size:0.9rem"><?= $rule['reason'] ?: '-' ?></td>
                    <td>
                        <a href="brand_rules.php?delete=<?= $rule['id'] ?>"
                            onclick="return confirm('Delete this rule?')"
                            style="color:#ff4444; text-decoration:none;">
                            🗑️ Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>