<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Content Detector - InkGuard</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .page-title h1 {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .page-title p {
            color: #aaa;
            font-size: 15px;
        }
        .shield-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 10px;
        }
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
            margin-bottom: 16px;
        }
        textarea {
            width: 100%;
            min-height: 200px;
            border: 2px solid #333;
            border-radius: 10px;
            padding: 14px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            color: #fff;
            background: #0a0a1a;
            line-height: 1.6;
        }
        textarea:focus {
            outline: none;
            border-color: #7c7cff;
        }
        .char-count {
            text-align: right;
            font-size: 12px;
            color: #aaa;
            margin-top: 6px;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            flex: 1;
        }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-secondary {
            background: #333;
            color: #aaa;
        }
        .btn-secondary:hover { background: #444; }

        /* Result */
        .result-wrap { display: none; }
        .result-card {
            border-radius: 14px;
            padding: 24px;
            border: 2px solid;
        }
        .result-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .source-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .score-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .score-item label {
            display: block;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .progress-bar {
            height: 12px;
            background: #0a0a1a;
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .progress-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.8s ease;
        }
        .score-num {
            font-size: 20px;
            font-weight: 800;
        }
        .source-breakdown {
            border-top: 1px solid #333;
            padding-top: 16px;
            margin-top: 4px;
        }
        .source-breakdown h3 {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .source-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .source-name {
            min-width: 80px;
            font-size: 13px;
            color: #ccc;
        }
        .source-bar {
            flex: 1;
            height: 8px;
            background: #0a0a1a;
            border-radius: 99px;
            overflow: hidden;
        }
        .source-fill {
            height: 100%;
            border-radius: 99px;
        }
        .source-pct {
            font-size: 13px;
            font-weight: 700;
            min-width: 35px;
            text-align: right;
        }
        .stats-row {
            display: flex;
            gap: 20px;
            padding-top: 16px;
            border-top: 1px solid #333;
            margin-top: 16px;
        }
        .stat-item {
            font-size: 13px;
            color: #aaa;
        }
        .stat-item strong {
            color: #fff;
        }
        .loading {
            text-align: center;
            padding: 30px;
            color: #7c7cff;
        }
        .loading-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7c7cff;
            margin: 0 3px;
            animation: bounce 1.2s infinite;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
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
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>🛡️ InkGuard</h1>
    <div class="user-info">
        <span>👤 <?= $username ?></span>
        <a href="history.php">📋 History</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="page-wrap">

    <!-- Nav Tabs -->
    <div class="nav-tabs">
        <a href="detector.php" class="nav-tab active">🔍 Detector</a>
        <a href="dashboard.php?tab=humanizer" class="nav-tab">✍️ Humanize</a>
        <a href="plagiarism.php" class="nav-tab">📄 Plagiarism</a>
        <a href="brand_rules.php" class="nav-tab">📋 Brand Rules</a>
    </div>

    <!-- Page Title -->
    <div class="page-title">
        <span class="shield-icon">🔍</span>
        <h1>AI Content Detector</h1>
        <p>Detect if text was written by AI or Human — 
        and identify the source</p>
    </div>

    <!-- Input Card -->
    <div class="card">
        <h2>📝 Paste Your Text</h2>
        <textarea id="userText"
            placeholder="Paste any text here to detect if it was written by ChatGPT, Gemini, Claude, or a Human..."
            oninput="updateCount()">
        </textarea>
        <div class="char-count">
            <span id="charCount">0</span> / 5000 characters
        </div>
        <div class="btn-row">
            <button class="btn btn-primary" onclick="analyzeText()">
                🔍 Analyze Content
            </button>
            <button class="btn btn-secondary" onclick="clearText()">
                🗑️ Clear
            </button>
        </div>
    </div>

    <!-- Result -->
    <div class="result-wrap" id="result">
        <div class="card">
            <h2>📊 Analysis Result</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
function updateCount() {
    const len = document.getElementById('userText').value.length;
    document.getElementById('charCount').textContent = len;
}

function clearText() {
    document.getElementById('userText').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('result').style.display = 'none';
}

function analyzeText() {
    const text = document.getElementById('userText').value;

    if (text.trim() === '') {
        alert('Please enter some text!');
        return;
    }
    if (text.length < 50) {
        alert('Please enter at least 50 characters!');
        return;
    }

    document.getElementById('result').style.display = 'block';
    document.getElementById('resultContent').innerHTML = `
        <div class="loading">
            <p style="font-size:18px; margin-bottom:15px">
                🔍 Analyzing content...
            </p>
            <div class="loading-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;

    fetch('analyze.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'text=' + encodeURIComponent(text)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        const isAI = data.result === 'AI Generated';
        const isMixed = data.result === 'Mixed Content';
        const color = isAI ? '#ff4444' : isMixed ? '#ffd700' : '#00cc66';
        const emoji = isAI ? '🤖' : isMixed ? '⚠️' : '👤';

        let sourceEmoji = '❓';
        if (data.ai_source === 'ChatGPT') sourceEmoji = '🟢';
        else if (data.ai_source === 'Gemini') sourceEmoji = '🔵';
        else if (data.ai_source === 'Claude') sourceEmoji = '🟠';
        else if (data.ai_source === 'Human') sourceEmoji = '👤';

        const s = data.source_scores || {};

        document.getElementById('resultContent').innerHTML = `
            <div class="result-card" style="border-color:${color}">

                <div class="result-title" style="color:${color}">
                    ${emoji} ${data.result}
                </div>

                ${isAI || isMixed ? `
                <div class="source-badge" 
                    style="background:${color}22; color:${color}; 
                    border:1px solid ${color}">
                    ${sourceEmoji} Likely: ${data.ai_source}
                </div>
                ` : ''}

                <div class="score-grid">
                    <div class="score-item">
                        <label>🤖 AI Score</label>
                        <div class="progress-bar">
                            <div class="progress-fill" 
                                style="width:${data.ai_score}%; 
                                background:#ff4444">
                            </div>
                        </div>
                        <div class="score-num" style="color:#ff4444">
                            ${data.ai_score}%
                        </div>
                    </div>
                    <div class="score-item">
                        <label>👤 Human Score</label>
                        <div class="progress-bar">
                            <div class="progress-fill"
                                style="width:${data.human_score}%; 
                                background:#00cc66">
                            </div>
                        </div>
                        <div class="score-num" style="color:#00cc66">
                            ${data.human_score}%
                        </div>
                    </div>
                </div>

                ${isAI || isMixed ? `
                <div class="source-breakdown">
                    <h3>🔎 Source Analysis:</h3>
                    <div class="source-item">
                        <span class="source-name">🟢 ChatGPT</span>
                        <div class="source-bar">
                            <div class="source-fill"
                                style="width:${Math.min(s.chatgpt||0,100)}%; 
                                background:#74aa9c">
                            </div>
                        </div>
                        <span class="source-pct" style="color:#74aa9c">
                            ${s.chatgpt||0}%
                        </span>
                    </div>
                    <div class="source-item">
                        <span class="source-name">🔵 Gemini</span>
                        <div class="source-bar">
                            <div class="source-fill"
                                style="width:${Math.min(s.gemini||0,100)}%; 
                                background:#4285f4">
                            </div>
                        </div>
                        <span class="source-pct" style="color:#4285f4">
                            ${s.gemini||0}%
                        </span>
                    </div>
                    <div class="source-item">
                        <span class="source-name">🟠 Claude</span>
                        <div class="source-bar">
                            <div class="source-fill"
                                style="width:${Math.min(s.claude||0,100)}%; 
                                background:#ff6b35">
                            </div>
                        </div>
                        <span class="source-pct" style="color:#ff6b35">
                            ${s.claude||0}%
                        </span>
                    </div>
                </div>
                ` : ''}

                <div class="stats-row">
                    <div class="stat-item">
                        📝 Words: <strong>${data.word_count}</strong>
                    </div>
                    <div class="stat-item">
                        📊 Confidence: <strong>${data.ai_score}%</strong>
                    </div>
                </div>

            </div>
        `;
    })
    .catch(() => {
        document.getElementById('resultContent').innerHTML = `
            <p style="color:red">Error analyzing. Please try again.</p>
        `;
    });
}
</script>
</body>
</html>