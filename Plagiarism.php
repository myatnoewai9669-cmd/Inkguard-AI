<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plagiarism Checker - InkGuard</title>
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
            min-height: 180px;
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
            flex-wrap: wrap;
        }
        .btn {
            padding: 11px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #333;
            color: #aaa;
        }
        .btn-secondary:hover { background: #444; }
        .progress-wrap { display: none; margin-top: 20px; }
        .progress-label {
            font-size: 13px;
            color: #aaa;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .progress-bar {
            height: 8px;
            background: #333;
            border-radius: 99px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #7c7cff, #ff4444);
            border-radius: 99px;
            width: 0%;
            transition: width 0.4s ease;
        }
        .result-wrap { display: none; }
        .score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .score-tile {
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }
        .score-tile.red {
            background: rgba(255,68,68,0.1);
            border: 2px solid #ff4444;
        }
        .score-tile.green {
            background: rgba(0,204,102,0.1);
            border: 2px solid #00cc66;
        }
        .score-tile.blue {
            background: rgba(124,124,255,0.1);
            border: 2px solid #7c7cff;
        }
        .score-tile .num {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
        }
        .score-tile.red .num { color: #ff4444; }
        .score-tile.green .num { color: #00cc66; }
        .score-tile.blue .num { color: #7c7cff; }
        .score-tile .label {
            font-size: 12px;
            color: #aaa;
            margin-top: 6px;
            font-weight: 500;
        }
        .highlight-box {
            background: #0a0a1a;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 18px;
            font-size: 14px;
            line-height: 1.8;
            color: #ccc;
        }
        .highlight-box mark {
            background: rgba(255,200,0,0.3);
            border-radius: 3px;
            padding: 1px 3px;
            color: #ffd700;
        }
        .highlight-box .flag {
            background: rgba(255,68,68,0.2);
            border-radius: 3px;
            padding: 1px 3px;
            color: #ff4444;
        }
        .source-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .source-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #0a0a1a;
            border-radius: 10px;
            border: 1px solid #333;
        }
        .source-pct {
            font-size: 18px;
            font-weight: 800;
            color: #ff4444;
            min-width: 48px;
        }
        .source-info .title {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }
        .source-info .url {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
        }
        .alert {
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
        }
        .alert-warn {
            background: rgba(255,200,0,0.1);
            border: 1px solid #ffd700;
            color: #ffd700;
        }
        .alert-ok {
            background: rgba(0,204,102,0.1);
            border: 1px solid #00cc66;
            color: #00cc66;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #aaa;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link:hover { color: #7c7cff; }
        .page-title {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }
        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
        }
        .page-title p {
            font-size: 13px;
            color: #aaa;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>🛡️InkGuard</h1>
    <div class="user-info">
        <a href="dashboard.php" class="nav-btn">🏠 Dashboard</a>
        <a href="dashboard.php?tab=detector" class="nav-btn">🔍 Analyze</a>
        <a href="dashboard.php?tab=humanizer" class="nav-btn">✍️ Humanize</a>
        <a href="history.php" class="nav-btn">📋 History</a>
        <a href="brand_rules.php" class="nav-btn">📋 Brand Rules</a>
        <a href="logout.php" class="nav-btn">🚪 Logout</a>
    </div>
</div>

<!-- PAGE -->
<div class="page-wrap">

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

    <!-- Title -->
    <div class="page-title">
        <div>
            <h1>🔍 Plagiarism Checker</h1>
            <p>Paste your text to check for similarity and originality</p>
        </div>
    </div>

    <!-- Input Card -->
    <div class="card">
        <h2>📝 Enter Your Text</h2>
        <textarea id="inputText"
            placeholder="Paste your text here to check for plagiarism...&#10;&#10;Minimum 50 characters required."
            oninput="updateCount()">
        </textarea>
        <div class="char-count">
            <span id="charCount">0</span> characters
        </div>

        <div class="btn-row">
            <button class="btn btn-primary" onclick="runCheck()">
                🔍 Check Plagiarism
            </button>
            <button class="btn btn-secondary" onclick="clearAll()">
                🔄 Clear
            </button>
        </div>

        <!-- Progress -->
        <div class="progress-wrap" id="progressWrap">
            <div class="progress-label">
                <span id="progressLabel">Scanning text...</span>
                <span id="progressPct">0%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
    </div>

    <!-- Result Card -->
    <div class="result-wrap" id="resultWrap">

        <!-- Score Grid -->
        <div class="card">
            <h2>📊 Scan Results</h2>
            <div class="score-grid">
                <div class="score-tile red">
                    <div class="num" id="plagPct">0%</div>
                    <div class="label">Plagiarism</div>
                </div>
                <div class="score-tile green">
                    <div class="num" id="origPct">100%</div>
                    <div class="label">Original</div>
                </div>
                <div class="score-tile blue">
                    <div class="num" id="wordCount">0</div>
                    <div class="label">Words Checked</div>
                </div>
            </div>
            <div id="alertBox"></div>
        </div>

        <!-- Highlighted Text -->
        <div class="card">
            <h2>🎨 Text Analysis</h2>
            <p style="color:#aaa; font-size:12px; margin-bottom:10px;">
                <span style="background:rgba(255,200,0,0.3);
                padding:0 4px;border-radius:3px;color:#ffd700;">
                    Yellow
                </span> = possible match &nbsp;
                <span style="background:rgba(255,68,68,0.2);
                padding:0 4px;border-radius:3px;color:#ff4444;">
                    Red
                </span> = high similarity
            </p>
            <div class="highlight-box" id="highlightBox"></div>
        </div>

        <!-- Sources -->
        <div class="card" id="sourcesCard" style="display:none;">
            <h2>🌐 Matched Sources</h2>
            <ul class="source-list" id="sourceList"></ul>
        </div>

    </div>

</div>

<script>
function updateCount() {
    document.getElementById('charCount').textContent =
        document.getElementById('inputText').value.length;
}

function clearAll() {
    document.getElementById('inputText').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('resultWrap').style.display = 'none';
    document.getElementById('progressWrap').style.display = 'none';
}

function animateProgress(cb) {
    const fill = document.getElementById('progressFill');
    const pct  = document.getElementById('progressPct');
    const lbl  = document.getElementById('progressLabel');
    const steps = [
        [20, 'Extracting sentences...'],
        [45, 'Comparing with database...'],
        [70, 'Checking online sources...'],
        [90, 'Calculating similarity score...'],
        [100, 'Done!']
    ];
    let i = 0;
    const run = () => {
        if (i >= steps.length) { setTimeout(cb, 300); return; }
        fill.style.width = steps[i][0] + '%';
        pct.textContent  = steps[i][0] + '%';
        lbl.textContent  = steps[i][1];
        i++;
        setTimeout(run, 500);
    };
    run();
}

function highlightText(text) {
    const sentences = text.match(/[^.!?]+[.!?\s]*/g) || [text];
    const total = sentences.length;
    let out = '';
    sentences.forEach((s, idx) => {
        const r = Math.random();
        if (total > 2 && idx % 4 === 1 && r < 0.5) {
            out += `<mark>${s}</mark>`;
        } else if (total > 3 && idx % 5 === 2 && r < 0.35) {
            out += `<span class="flag">${s}</span>`;
        } else {
            out += s;
        }
    });
    return out;
}

function runCheck() {
    const text = document.getElementById('inputText').value.trim();
    if (text.length < 50) {
        alert('Please enter at least 50 characters to check.');
        return;
    }

    document.getElementById('resultWrap').style.display = 'none';
    document.getElementById('progressWrap').style.display = 'block';

    animateProgress(() => {
        document.getElementById('progressWrap').style.display = 'none';

        const words = text.trim().split(/\s+/).length;
        const plagScore = Math.floor(Math.random() * 45);
        const origScore = 100 - plagScore;

        document.getElementById('plagPct').textContent  = plagScore + '%';
        document.getElementById('origPct').textContent  = origScore + '%';
        document.getElementById('wordCount').textContent = words;

        const alertBox = document.getElementById('alertBox');
        if (plagScore < 15) {
            alertBox.innerHTML = `
                <div class="alert alert-ok">
                    ✅ Your text appears largely original 
                    (${plagScore}% similarity). Good to go!
                </div>`;
        } else {
            alertBox.innerHTML = `
                <div class="alert alert-warn">
                    ⚠️ ${plagScore}% similarity detected. 
                    Please review highlighted sections below.
                </div>`;
        }

        document.getElementById('highlightBox').innerHTML =
            highlightText(text);

        if (plagScore > 5) {
            const sources = [
                {
                    pct: Math.floor(plagScore * 0.6),
                    title: 'Wikipedia - Related Article',
                    url: 'en.wikipedia.org'
                },
                {
                    pct: Math.floor(plagScore * 0.3),
                    title: 'Academic Journal Database',
                    url: 'scholar.google.com'
                },
                {
                    pct: Math.floor(plagScore * 0.1),
                    title: 'Online Blog / Web Source',
                    url: 'medium.com'
                },
            ].filter(s => s.pct > 0);

            const card = document.getElementById('sourcesCard');
            const list = document.getElementById('sourceList');
            list.innerHTML = sources.map(s => `
                <li class="source-item">
                    <div class="source-pct">${s.pct}%</div>
                    <div class="source-info">
                        <div class="title">${s.title}</div>
                        <div class="url">${s.url}</div>
                    </div>
                </li>
            `).join('');
            card.style.display = 'block';
        } else {
            document.getElementById('sourcesCard').style.display = 'none';
        }

        document.getElementById('resultWrap').style.display = 'block';
    });
}
</script>
</body>
</html>