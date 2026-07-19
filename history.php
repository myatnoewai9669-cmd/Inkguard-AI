<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Delete single record
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $user_id = $_SESSION['user_id'];
    $sql = "DELETE FROM analyses WHERE id='$delete_id' AND user_id='$user_id'";
    mysqli_query($conn, $sql);
    header("Location: history.php");
    exit();
}

// Delete all records
if (isset($_GET['delete_all'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "DELETE FROM analyses WHERE user_id='$user_id'";
    mysqli_query($conn, $sql);
    header("Location: history.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$sql = "SELECT * FROM analyses WHERE user_id='$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>History - InkGuard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <div class="header">
        <h1>🛡️ InkGuard</h1>
        <div class="user-info">
            <span>👤 <?= $username ?></span>
            <a href="dashboard.php">🔍 Analyzer</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>

    <div class="analyzer-box">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>📋 Analysis History</h2>
            <?php if(mysqli_num_rows($result) > 0): ?>
            <a href="history.php?delete_all=1" 
                onclick="return confirm('Delete all history?')"
                style="background:#ff4444; color:white; padding:8px 15px; 
                border-radius:8px; text-decoration:none; font-size:0.9rem;">
                🗑️ Delete All
            </a>
            <?php endif; ?>
        </div>

        <?php if (mysqli_num_rows($result) == 0): ?>
            <p style="color:#aaa; text-align:center; padding:20px;">
                No analyses yet. Go analyze some content!
            </p>
        <?php else: ?>
        <table class="history-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Text Preview</th>
                    <th>Result</th>
                    <th>Confidence</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= substr($row['text_input'], 0, 50) ?>...</td>
                    <td>
                        <span class="badge <?= strpos($row['result'], 'AI') !== false ? 'badge-ai' : 'badge-human' ?>">
                            <?= strpos($row['result'], 'AI') !== false ? '🤖' : '👤' ?>
                            <?= $row['result'] ?>
                        </span>
                    </td>
                    <td><?= $row['confidence'] ?>%</td>
                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="history.php?delete=<?= $row['id'] ?>"
                            onclick="return confirm('Delete this record?')"
                            style="color:#ff4444; text-decoration:none; 
                            font-size:0.9rem;">
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