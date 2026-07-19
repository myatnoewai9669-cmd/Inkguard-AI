<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get stats from database
$total_sql = "SELECT COUNT(*) as total FROM analyses 
              WHERE user_id='$user_id'";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total = $total_row['total'];

$ai_sql = "SELECT COUNT(*) as count FROM analyses 
           WHERE user_id='$user_id' 
           AND result LIKE '%AI Generated%'";
$ai_result = mysqli_query($conn, $ai_sql);
$ai_row = mysqli_fetch_assoc($ai_result);
$ai_count = $ai_row['count'];

$avg_sql = "SELECT AVG(confidence) as avg_conf FROM analyses 
            WHERE user_id='$user_id'";
$avg_result = mysqli_query($conn, $avg_sql);
$avg_row = mysqli_fetch_assoc($avg_result);
$avg_confidence = round($avg_row['avg_conf'] ?? 0);

// Calculate overall risk score
$ai_ratio = $total > 0 ? ($ai_count / $total) * 100 : 0;
$risk_score = round(($ai_ratio * 0.6) + ($avg_confidence * 0.4));
$risk_score = min($risk_score, 100);

// Risk level
$risk_level = 'LOW';
$risk_color = '#00cc66';
$risk_emoji = '✅';
$risk_desc = 'Your content appears mostly human-written. Low risk detected.';

if ($risk_score >= 70) {
    $risk_level = 'CRITICAL';
    $risk_color = '#ff0000';
    $risk_emoji = '🚨';
    $risk_desc = 'Critical risk! High volume of AI-generated content detected.';
} elseif ($risk_score >= 50) {
    $risk_level = 'HIGH';
    $risk_color = '#ff4444';
    $risk_emoji = '🔴';
    $risk_desc = 'High risk detected. Significant AI content found.';
} elseif ($risk_score >= 30) {
    $risk_level = 'MEDIUM';
    $risk_color = '#ffd700';
    $risk_emoji = '⚠️';
    $risk_desc = 'Medium risk. Some AI-generated content detected.';
}

// Recent analyses for chart
$recent_sql = "SELECT result, confidence, created_at 
               FROM analyses WHERE user_id='$user_id' 
               ORDER BY created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_sql);
$recent_rows = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risk Score - Guard AI</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0a0a1a;
            min-height: 100vh;
            color: #fff;
        }
        .page-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .page-title p { color: #aaa; font-size: 14px; }
        .card {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #333;
            margin-bottom: 24px;
        }
        .card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #7c7cff;
            margin-bottom: 20px;
        }

        /* Risk Meter */
        .risk-meter-wrap {
            text-align: center;
            padding: 20px 0;
        }
        .risk-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 8px solid;
            position: relative;
        }
        .risk-num {
            font-size: 52px;
            font-weight: 900;
            line-height: 1;
        }
        .risk-label {
            font-size: 12px;
            color: #aaa;
            margin-top: 4px;
        }
        .risk-level-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 30px;
            font-size: 20px;
            font-weight: 800;
            border: 2px solid;
            margin-bottom: 12px;
        }
        .risk-desc {
            color: #aaa;
            font-size: 14px;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
        .stat-tile {
            background: #0a0a1a;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            border: 1px solid #333;
        }
        .stat-tile .num {
            font-size: 30px;
            font-weight: 800;
        }
        .stat-tile .label {
            font-size: 12px;
            color: #aaa;
            margin-top: 5px;
        }

        /* Risk Factors */
        .factor-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .factor-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            background: #0a0a1a;
            border-radius: 10px;
            border: 1px solid #333;
        }
        .factor-icon { font-size: 24px; min-width: 30px; }
        .factor-info { flex: 1; }
        .factor-info .title {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }
        .factor-info .desc {
            font-size: 12px;
            color: #aaa;
        }
        .factor-score {
            font-size: 18px;
            font-weight: 800;
            min-width: 50px;
            text-align: right;
        }
        .factor-bar-wrap {
            width: 100px;
        }
        .factor-bar {
            height: 6px;
            background: #333;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 4px;
        }
        .factor-fill {
            height: 100%;
            border-radius: 99px;
        }

        /* Recent Activity */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #0a0a1a;
            border-radius: 10px;
            border: 1px solid #333;
        }
        .activity-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .activity-info { flex: 1; }
        .activity-info .result {
            font-size: 13px;
            font-weight: 600;
        }
        .activity-info .date {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
        }
        .activity-conf {
            font-size: 14px;
            font-weight: 700;
        }

        /* Recommendations */
        .rec-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .rec-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            background: #0a0a1a;
            border-radius: 10px;
            border-left: 4px solid;
        }
        .rec-item.high { border-color: #ff4444; }
        .rec-item.medium { border-color: #ffd700; }
        .rec-item.low { border-color: #00cc66; }
        .rec-icon { font-size: 20px; }
        .rec-info .title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .rec-info .desc {
            font-size: 12px;
            color: #aaa;
            line-height: 1.5;
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .nav-tab {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: #aaa;
            background: #1a1a2e;
            border: 1px solid #333;
            transition: all 0.2s;
        }
        .nav-tab.active,
        .nav-tab:hover {
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            border-color: #7c7cff;
        }

        /* Gauge Chart */
        .gauge-wrap {
            position: relative;
            width: 220px;
            margin: 0 auto 20px;
        }
        canvas { display: block; margin: 0 auto; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>🛡️ Guard AI</h1>
    <div class="user-info">
        <span>👤 <?= $username ?></span>
        <a href="history.php">📋 History</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="page-wrap">

    <!-- Nav Tabs -->
    <div class="nav-tabs">
        <a href="dashboard.php" class="nav-tab">🏠 Dashboard</a>
        <a href="detector.php" class="nav-tab">🔍 Detector</a>
        <a href="plagiarism.php" class="nav-tab">📄 Plagiarism</a>
        <a href="brand_rules.php" class="nav-tab">📋 Brand Rules</a>
        <a href="prompt_scanner.php" class="nav-tab">🛡️ Prompt Scanner</a>
        <a href="audit_log.php" class="nav-tab">📊 Audit Log</a>
        <a href="risk_score.php" class="nav-tab active">⚠️ Risk Score</a>
    </div>

    <!-- Title -->
    <div class="page-title">
        <h1>⚠️ Risk Score Dashboard</h1>
        <p>Overall AI content risk assessment for your account</p>
    </div>

    <!-- Risk Meter -->
    <div class="card">
        <h2>🎯 Overall Risk Score</h2>
        <div class="risk-meter-wrap">

            <!-- Gauge Canvas -->
            <canvas id="gaugeCanvas" 
                width="220" height="130">
            </canvas>

            <div class="risk-level-badge"
                style="color:<?= $risk_color ?>; 
                border-color:<?= $risk_color ?>;
                background:<?= $risk_color ?>22">
                <?= $risk_emoji ?> <?= $risk_level ?> RISK
            </div>
            <br>
            <div class="risk-desc"><?= $risk_desc ?></div>
        </div>
    </div>

    <!-- Stats -->
    <div class="card">
        <h2>📊 Analysis Statistics</h2>
        <div class="stats-grid">
            <div class="stat-tile">
                <div class="num" style="color:#7c7cff">
                    <?= $total ?>
                </div>
                <div class="label">Total Scans</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ff4444">
                    <?= $ai_count ?>
                </div>
                <div class="label">🤖 AI Detected</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66">
                    <?= $total - $ai_count ?>
                </div>
                <div class="label">👤 Human Detected</div>
            </div>
            <div class="stat-tile">
                <div class="num" 
                    style="color:<?= $risk_color ?>">
                    <?= $risk_score ?>%
                </div>
                <div class="label">Risk Score</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ffd700">
                    <?= $avg_confidence ?>%
                </div>
                <div class="label">Avg Confidence</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#7c7cff">
                    <?= $total > 0 ? round($ai_ratio) : 0 ?>%
                </div>
                <div class="label">AI Ratio</div>
            </div>
        </div>
    </div>

    <!-- Risk Factors -->
    <div class="card">
        <h2>🔍 Risk Factors</h2>
        <div class="factor-list">

            <?php
            $factors = [
                [
                    'icon' => '🤖',
                    'title' => 'AI Content Rate',
                    'desc' => 'Percentage of scanned content identified as AI-generated',
                    'score' => round($ai_ratio),
                    'color' => $ai_ratio > 60 ? '#ff4444' : 
                        ($ai_ratio > 30 ? '#ffd700' : '#00cc66')
                ],
                [
                    'icon' => '📊',
                    'title' => 'Average Confidence',
                    'desc' => 'Average confidence score of AI detection results',
                    'score' => $avg_confidence,
                    'color' => $avg_confidence > 70 ? '#ff4444' : 
                        ($avg_confidence > 40 ? '#ffd700' : '#00cc66')
                ],
                [
                    'icon' => '📝',
                    'title' => 'Total Scan Volume',
                    'desc' => 'Number of content pieces analyzed',
                    'score' => min($total * 5, 100),
                    'color' => '#7c7cff'
                ],
            ];
            ?>

            <?php foreach ($factors as $f): ?>
            <div class="factor-item">
                <div class="factor-icon"><?= $f['icon'] ?></div>
                <div class="factor-info">
                    <div class="title"><?= $f['title'] ?></div>
                    <div class="desc"><?= $f['desc'] ?></div>
                    <div class="factor-bar">
                        <div class="factor-fill"
                            style="width:<?= $f['score'] ?>%; 
                            background:<?= $f['color'] ?>; 
                            height:6px; border-radius:99px;">
                        </div>
                    </div>
                </div>
                <div class="factor-score" 
                    style="color:<?= $f['color'] ?>">
                    <?= $f['score'] ?>%
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- Recent Activity -->
    <?php if (!empty($recent_rows)): ?>
    <div class="card">
        <h2>🕐 Recent Activity</h2>
        <div class="activity-list">
            <?php foreach ($recent_rows as $row):
                $isAI = strpos($row['result'], 'AI') !== false;
                $dotColor = $isAI ? '#ff4444' : '#00cc66';
                $confColor = $isAI ? '#ff4444' : '#00cc66';
            ?>
            <div class="activity-item">
                <div class="activity-dot" 
                    style="background:<?= $dotColor ?>">
                </div>
                <div class="activity-info">
                    <div class="result" 
                        style="color:<?= $dotColor ?>">
                        <?= $isAI ? '🤖' : '👤' ?> 
                        <?= $row['result'] ?>
                    </div>
                    <div class="date">
                        <?= date('M d, Y h:i A', 
                            strtotime($row['created_at'])) ?>
                    </div>
                </div>
                <div class="activity-conf" 
                    style="color:<?= $confColor ?>">
                    <?= $row['confidence'] ?>%
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <div class="card">
        <h2>💡 Recommendations</h2>
        <div class="rec-list">
            <?php if ($risk_score >= 70): ?>
            <div class="rec-item high">
                <div class="rec-icon">🚨</div>
                <div class="rec-info">
                    <div class="title">Critical: Review All Content</div>
                    <div class="desc">Your content shows very high AI usage. 
                    Consider reviewing and rewriting content to ensure 
                    authenticity and originality.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($risk_score >= 50): ?>
            <div class="rec-item high">
                <div class="rec-icon">✍️</div>
                <div class="rec-info">
                    <div class="title">Use AI to Human Tool</div>
                    <div class="desc">Use the AI to Human writing tool to 
                    convert AI-generated content into more natural, 
                    human-like writing.</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($risk_score >= 30): ?>
            <div class="rec-item medium">
                <div class="rec-icon">🔍</div>
                <div class="rec-info">
                    <div class="title">Regular Content Auditing</div>
                    <div class="desc">Perform regular content audits using 
                    the Audit Log to track and monitor AI content 
                    patterns over time.</div>
                </div>
            </div>
            <?php endif; ?>

            <div class="rec-item low">
                <div class="rec-icon">📋</div>
                <div class="rec-info">
                    <div class="title">Set Up Brand Rules</div>
                    <div class="desc">Configure Brand Rules to automatically 
                    flag content that violates your writing standards 
                    and guidelines.</div>
                </div>
            </div>

            <div class="rec-item low">
                <div class="rec-icon">🛡️</div>
                <div class="rec-info">
                    <div class="title">Scan Prompts Before Use</div>
                    <div class="desc">Use the Prompt Scanner to check AI 
                    prompts for injection attacks and security 
                    vulnerabilities before processing.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Draw Gauge Chart
const canvas = document.getElementById('gaugeCanvas');
const ctx = canvas.getContext('2d');
const score = <?= $risk_score ?>;
const color = '<?= $risk_color ?>';

function drawGauge(score, color) {
    const cx = 110, cy = 110, r = 90;
    const startAngle = Math.PI;
    const endAngle = 2 * Math.PI;
    const scoreAngle = startAngle + (score / 100) * Math.PI;

    ctx.clearRect(0, 0, 220, 130);

    // Background arc
    ctx.beginPath();
    ctx.arc(cx, cy, r, startAngle, endAngle);
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 18;
    ctx.lineCap = 'round';
    ctx.stroke();

    // Score arc
    ctx.beginPath();
    ctx.arc(cx, cy, r, startAngle, scoreAngle);
    ctx.strokeStyle = color;
    ctx.lineWidth = 18;
    ctx.lineCap = 'round';
    ctx.stroke();

    // Score text
    ctx.fillStyle = color;
    ctx.font = 'bold 36px Segoe UI';
    ctx.textAlign = 'center';
    ctx.fillText(score + '%', cx, cy - 10);

    ctx.fillStyle = '#aaa';
    ctx.font = '13px Segoe UI';
    ctx.fillText('Risk Score', cx, cy + 15);

    // Labels
    ctx.fillStyle = '#555';
    ctx.font = '11px Segoe UI';
    ctx.fillText('0', cx - r - 5, cy + 15);
    ctx.fillText('100', cx + r - 10, cy + 15);
}

drawGauge(score, color);
</script>
</body>
</html>