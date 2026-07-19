<?php
session_start();
require 'config.php';
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get stats
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM analyses WHERE user_id='$user_id'"))['c'];
$ai_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM analyses WHERE user_id='$user_id' AND result LIKE '%AI%'"))['c'];
$human_count = $total - $ai_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inkguard AI</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #050510;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 20% 50%, 
                rgba(124,124,255,0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, 
                rgba(0,204,102,0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, 
                rgba(255,68,68,0.08) 0%, transparent 50%);
            z-index: 0;
            animation: bgMove 15s ease infinite alternate;
        }

        @keyframes bgMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 20px); }
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            height: 65px;
            background: rgba(10,10,26,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(124,124,255,0.2);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 0 15px rgba(124,124,255,0.4);
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #7c7cff, #00cc66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(124,124,255,0.1);
            border: 1px solid rgba(124,124,255,0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: #ccc;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .nav-link {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #aaa;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: rgba(124,124,255,0.1);
            color: #fff;
            border-color: rgba(124,124,255,0.3);
        }

        .nav-link.danger {
            color: #ff6b6b;
        }

        .nav-link.danger:hover {
            background: rgba(255,68,68,0.1);
            border-color: rgba(255,68,68,0.3);
        }

        /* Page */
        .page {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Welcome */
        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .welcome-section h2 span {
            background: linear-gradient(135deg, #7c7cff, #00cc66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-section p {
            color: #888;
            font-size: 14px;
        }

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: rgba(124,124,255,0.3);
            transform: translateY(-2px);
        }

        .stat-card .num {
            font-size: 32px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-card .label {
            font-size: 12px;
            color: #888;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 28px;
            backdrop-filter: blur(10px);
            margin-bottom: 24px;
            transition: border-color 0.3s;
        }

        .glass-card:focus-within {
            border-color: rgba(124,124,255,0.4);
        }

        .glass-card h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #fff;
        }

        .glass-card p {
            font-size: 13px;
            color: #888;
            margin-bottom: 18px;
        }

        /* Textarea */
        .text-input {
            width: 100%;
            min-height: 180px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            line-height: 1.7;
            transition: border-color 0.3s;
        }

        .text-input:focus {
            outline: none;
            border-color: rgba(124,124,255,0.5);
        }

        .text-input::placeholder { color: #555; }

        .input-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .char-count { font-size: 12px; color: #555; }

        /* Buttons */
        .btn-analyze {
            padding: 13px 30px;
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(124,124,255,0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-analyze:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124,124,255,0.5);
        }

        .btn-analyze:active { transform: scale(0.98); }

        .btn-clear {
            padding: 13px 20px;
            background: rgba(255,255,255,0.05);
            color: #888;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-clear:hover {
            background: rgba(255,68,68,0.1);
            color: #ff6b6b;
            border-color: rgba(255,68,68,0.3);
        }

        /* Result */
        .result-wrap { display: none; }

        .result-inner {
            border-radius: 16px;
            padding: 24px;
            border: 2px solid;
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .result-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .result-title {
            font-size: 22px;
            font-weight: 800;
        }

        .result-subtitle {
            font-size: 13px;
            color: #aaa;
            margin-top: 2px;
        }

        .scores-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .score-item label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #aaa;
            margin-bottom: 6px;
        }

        .score-bar {
            height: 10px;
            background: rgba(0,0,0,0.4);
            border-radius: 99px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .score-num {
            font-size: 24px;
            font-weight: 900;
            margin-top: 4px;
        }

        /* Source breakdown */
        .source-section {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 18px;
            margin-top: 4px;
        }

        .source-title {
            font-size: 13px;
            color: #888;
            margin-bottom: 12px;
        }

        .source-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .source-name {
            min-width: 85px;
            font-size: 13px;
            color: #ccc;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .source-bar-wrap {
            flex: 1;
            height: 7px;
            background: rgba(0,0,0,0.4);
            border-radius: 99px;
            overflow: hidden;
        }

        .source-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 1s ease;
        }

        .source-pct {
            font-size: 13px;
            font-weight: 700;
            min-width: 38px;
            text-align: right;
        }

        /* Loading */
        .loading-wrap {
            text-align: center;
            padding: 30px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(124,124,255,0.2);
            border-top-color: #7c7cff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 14px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Tools Grid */
        .tools-section h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #ccc;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .tool-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 22px 18px;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s;
            display: block;
        }

        .tool-card:hover {
            background: rgba(124,124,255,0.08);
            border-color: rgba(124,124,255,0.35);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(124,124,255,0.15);
        }

        .tool-emoji {
            font-size: 28px;
            margin-bottom: 10px;
            display: block;
        }

        .tool-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .tool-desc {
            font-size: 11px;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr; }
            .tools-grid { grid-template-columns: repeat(2, 1fr); }
            .scores-grid { grid-template-columns: 1fr; }
            .navbar { padding: 0 15px; }
            .navbar-right { gap: 4px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">
        <div class="brand-icon">🛡️</div>
        <span class="brand-name">Inkguard AI</span>
    </a>
    <div class="navbar-right">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($username, 0, 1)) ?>
            </div>
            <?= htmlspecialchars($username) ?>
        </div>
        <a href="history.php" class="nav-link">📋 History</a>
        <a href="audit_log.php" class="nav-link">📊 Audit</a>
        <a href="logout.php" class="nav-link danger">🚪 Logout</a>
    </div>
</nav>

<div class="page">

    <!-- Welcome -->
    <div class="welcome-section">
        <h2>Welcome back, <span><?= htmlspecialchars($username) ?></span> 👋</h2>
        <p>Protect your content with AI-powered detection and analysis tools</p>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="num" style="color:#7c7cff"><?= $total ?></div>
            <div class="label">📊 Total Analyses</div>
        </div>
        <div class="stat-card">
            <div class="num" style="color:#ff4444"><?= $ai_count ?></div>
            <div class="label">🤖 AI Detected</div>
        </div>
        <div class="stat-card">
            <div class="num" style="color:#00cc66"><?= $human_count ?></div>
            <div class="label">👤 Human Written</div>
        </div>
    </div>

    <!-- Analyzer -->
    <div class="glass-card">
        <h2>🔍 Content Detector</h2>
        <p>Paste any text to detect if it was written by AI or a Human</p>

        <textarea class="text-input" id="userText"
            placeholder="Paste your text here to analyze..."
            oninput="updateCount()">
        </textarea>

        <div class="input-footer">
            <span class="char-count">
                <span id="charCount">0</span> / 5000 characters
            </span>
            <div style="display:flex; gap:10px;">
                <button class="btn-clear" onclick="clearText()">
                    🗑️ Clear
                </button>
                <button class="btn-analyze" onclick="analyzeText()">
                    🔍 Analyze Content
                </button>
            </div>
        </div>
    </div>

    <!-- Result -->
    <div class="result-wrap glass-card" id="result">
        <h2>📊 Analysis Result</h2>
        <div id="resultContent"></div>
    </div>

    <!-- Tools -->
    <div class="tools-section">
        <h2>🛠️ Safety Tools</h2>
        <div class="tools-grid">
            <div class="tool-card">
    <a href="ai_training_assistant.php">
        <div class="icon">🤖</div>
        <h3>AI Training Assistant</h3>
        <p> Quiz + Summary only using IBM Granite</p>
    </a>
</div>
<div class="tool-card">
<a href="ai_chat_assistant.php" class="tool-tile">
    <div class="tile-icon">💬</div>
    <div class="tile-title">AI Chat Assistant</div>
    <div class="tile-desc">Ask questions about lesson material</div>
    </div>
            <a href="spelling_checker.php" class="tool-card">
    <span class="tool-emoji">🔤</span>
    <div class="tool-name">Spelling Checker</div>
    <div class="tool-desc">Find and fix spelling mistakes</div>
</a>
<a href="grammar_checker.php" class="tool-card">
    <span class="tool-emoji">✏️</span>
    <div class="tool-name">Grammar Checker</div>
    <div class="tool-desc">Check grammar, spelling and punctuation</div>


</a>
    <a href="plagiarism.php" class="tool-card">
                <span class="tool-emoji">📄</span>
                <div class="tool-name">Plagiarism Checker</div>
                <div class="tool-desc">Check content originality</div>
            </a>
            <a href="brand_rules.php" class="tool-card">
                <span class="tool-emoji">📋</span>
                <div class="tool-name">Brand Rules</div>
                <div class="tool-desc">Protect your brand voice</div>
            </a>
            <a href="brand_voice_checker.php" class="tool-card">
    <span class="tool-emoji">🎙️</span>
    <div class="tool-name">Brand Voice Checker</div>
    <div class="tool-desc">Check if content matches your brand voice</div>
</a>
            <a href="Prompt Optimizer.php" class="tool-card">
    <span class="tool-emoji">🧠</span>
    <div class="tool-name">Prompt Optimizer</div>
    <div class="tool-desc">Improve and optimize AI prompts for better results</div>
</a>
            <a href="prompt_scanner.php" class="tool-card">
                <span class="tool-emoji">🛡️</span>
                <div class="tool-name">Prompt Scanner</div>
                <div class="tool-desc">Detect injection attacks</div>
            </a>
            <a href="detector.php" class="tool-card">
                <span class="tool-emoji">🔍</span>
                <div class="tool-name">Content Detector</div>
                <div class="tool-desc">Detect AI vs Human writing</div>
            </a>

            <a href="audit_log.php" class="tool-card">
                <span class="tool-emoji">📊</span>
                <div class="tool-name">Audit Log</div>
                <div class="tool-desc">Full analysis history</div>
            </a>
            <a href="risk_score.php" class="tool-card">
                <span class="tool-emoji">⚠️</span>
                <div class="tool-name">Risk Score</div>
                <div class="tool-desc">Overall risk assessment</div>
            </a>
            <a href="text_to_image.php" class="tool-card">
    <span class="tool-emoji">🎨</span>
    <div class="tool-name">Text to Image</div>
    <div class="tool-desc">Generate AI images from text</div>
</a>

<a href="content_generator.php" class="tool-card">
    <span class="tool-emoji">✍️</span>
    <div class="tool-name"> AI Writer</div>
    <div class="tool-desc">Create AI-generated content</div>
</a>
<a href="cv_generator.php" class="tool-card">
    <span class="tool-emoji">📄</span>
    <div class="tool-name">AI CV Generator</div>
    <div class="tool-desc">Generate professional CV instantly</div>
</a>
<a href="ppt_generator.php" class="tool-card">
    <span class="tool-emoji">📊</span>
    <div class="tool-name">PPT Generator</div>
    <div class="tool-desc">Create AI-powered presentations</div>
</a>
<a href="humanize.php" class="tool-card">
    <span class="tool-emoji">👤</span>
    <div class="tool-name">AI Humanizer</div>
    <div class="tool-desc">Convert AI-generated text into natural human writing</div>
</a>
<a href="logo_generator.php" class="tool-card">
    <span class="tool-emoji">🎨</span>
    <div class="tool-name">Logo Generator</div>
    <div class="tool-desc">Create logos & banners instantly</div>
</a>
<a href="email_writer.php" class="tool-card">
    <span class="tool-emoji">📧</span>
    <div class="tool-name">AI Email Writer</div>
    <div class="tool-desc">Generate professional emails instantly</div>
</a>
     <a href="blog_writer.php" class="tool-card">
    <span class="tool-emoji">📝</span>
    <div class="tool-name">AI Blog Writer</div>
    <div class="tool-desc">Generate blog posts from any topic</div>
</a>
</div>
    </div>

</div>
<script src="script.js"></script>
<script>
function updateCount() {
    document.getElementById('charCount').textContent =
        document.getElementById('userText').value.length;
}

function clearText() {
    document.getElementById('userText').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('result').style.display = 'none';
}

function analyzeText() {
    const text = document.getElementById('userText').value;
    if (!text.trim()) {
        alert('Please enter some text!');
        return;
    }
    if (text.length < 50) {
        alert('Please enter at least 50 characters!');
        return;
    }

    const resultBox = document.getElementById('result');
    const resultContent = document.getElementById('resultContent');
    resultBox.style.display = 'block';
    resultContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">
                🔍 Analyzing content...
            </p>
            <p style="color:#555; font-size:13px; margin-top:6px">
                Detecting AI patterns and source...
            </p>
        </div>
    `;

    resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('analyze.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'text=' + encodeURIComponent(text)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { alert(data.error); return; }

        const isAI = data.result === 'AI Generated';
        const isMixed = data.result === 'Mixed Content';
        const color = isAI ? '#ff4444' :
            isMixed ? '#ffd700' : '#00cc66';
        const emoji = isAI ? '🤖' : isMixed ? '⚠️' : '👤';
        const bgColor = isAI ?
            'rgba(255,68,68,0.08)' :
            isMixed ? 'rgba(255,215,0,0.08)' :
            'rgba(0,204,102,0.08)';

        let sourceEmoji = '❓';
        if (data.ai_source === 'ChatGPT') sourceEmoji = '🟢';
        else if (data.ai_source === 'Gemini') sourceEmoji = '🔵';
        else if (data.ai_source === 'Claude') sourceEmoji = '🟠';
        else if (data.ai_source === 'Human') sourceEmoji = '👤';

        const s = data.source_scores || {};

        resultContent.innerHTML = `
            <div class="result-inner"
                style="border-color:${color};
                background:${bgColor}">

                <div class="result-header">
                    <div class="result-icon"
                        style="background:${color}22">
                        ${emoji}
                    </div>
                    <div>
                        <div class="result-title"
                            style="color:${color}">
                            ${data.result}
                        </div>
                        <div class="result-subtitle">
                            ${isAI || isMixed ?
                                sourceEmoji + ' Likely: ' +
                                data.ai_source : 
                                '✅ No AI patterns detected'}
                        </div>
                    </div>
                </div>

                <div class="scores-grid">
                    <div class="score-item">
                        <label>
                            <span>🤖 AI Score</span>
                            <span style="color:#ff4444;
                                font-weight:700">
                                ${data.ai_score}%
                            </span>
                        </label>
                        <div class="score-bar">
                            <div class="score-fill"
                                style="width:${data.ai_score}%;
                                background:linear-gradient(
                                    90deg,#ff4444,#ff6b6b)">
                            </div>
                        </div>
                        <div class="score-num"
                            style="color:#ff4444">
                            ${data.ai_score}%
                        </div>
                    </div>
                    <div class="score-item">
                        <label>
                            <span>👤 Human Score</span>
                            <span style="color:#00cc66;
                                font-weight:700">
                                ${data.human_score}%
                            </span>
                        </label>
                        <div class="score-bar">
                            <div class="score-fill"
                                style="width:${data.human_score}%;
                                background:linear-gradient(
                                    90deg,#00cc66,#00ff88)">
                            </div>
                        </div>
                        <div class="score-num"
                            style="color:#00cc66">
                            ${data.human_score}%
                        </div>
                    </div>
                </div>

                ${isAI || isMixed ? `
                <div class="source-section">
                    <div class="source-title">
                        🔎 Source Analysis:
                    </div>
                    <div class="source-row">
                        <div class="source-name">
                            🟢 ChatGPT
                        </div>
                        <div class="source-bar-wrap">
                            <div class="source-bar-fill"
                                style="width:${Math.min(
                                    s.chatgpt||0,100)}%;
                                background:#74aa9c">
                            </div>
                        </div>
                        <div class="source-pct"
                            style="color:#74aa9c">
                            ${s.chatgpt||0}%
                        </div>
                    </div>
                    <div class="source-row">
                        <div class="source-name">
                            🔵 Gemini
                        </div>
                        <div class="source-bar-wrap">
                            <div class="source-bar-fill"
                                style="width:${Math.min(
                                    s.gemini||0,100)}%;
                                background:#4285f4">
                            </div>
                        </div>
                        <div class="source-pct"
                            style="color:#4285f4">
                            ${s.gemini||0}%
                        </div>
                    </div>
                    <div class="source-row">
                        <div class="source-name">
                            🟠 Claude
                        </div>
                        <div class="source-bar-wrap">
                            <div class="source-bar-fill"
                                style="width:${Math.min(
                                    s.claude||0,100)}%;
                                background:#ff6b35">
                            </div>
                        </div>
                        <div class="source-pct"
                            style="color:#ff6b35">
                            ${s.claude||0}%
                        </div>
                    </div>
                </div>
                ` : ''}

                <div style="display:flex; gap:20px;
                    padding-top:16px;
                    border-top:1px solid rgba(255,255,255,0.08);
                    margin-top:16px;">
                    <span style="color:#555; font-size:13px">
                        📝 Words: 
                        <strong style="color:#ccc">
                            ${data.word_count}
                        </strong>
                    </span>
                    <span style="color:#555; font-size:13px">
                        📊 Confidence: 
                        <strong style="color:${color}">
                            ${data.ai_score}%
                        </strong>
                    </span>
                </div>

            </div>
        `;
    })
    .catch(() => {
        resultContent.innerHTML = `
            <p style="color:#ff4444; text-align:center;">
                ❌ Error analyzing. Please try again.
            </p>
        `;
    });
}

history.pushState(null, null, location.href);
window.onpopstate = function() {
    window.location.replace('dashboard.php');
};
</script>
</body>
</html>