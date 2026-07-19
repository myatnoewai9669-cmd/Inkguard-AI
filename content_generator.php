<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

/* ---------- AJAX endpoint: same file handles the generation call ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Never let a PHP warning/notice/fatal leak HTML into the JSON response.
    ini_set('display_errors', '0');
    header('Content-Type: application/json');

    try {
        $topic  = trim($_POST['topic'] ?? '');
        $tone   = $_POST['tone'] ?? 'friendly';
        $format = $_POST['format'] ?? 'paragraph';
        $length = (int) ($_POST['length'] ?? 400);

        if ($topic === '') {
            echo json_encode(['ok' => false, 'error' => 'Please enter a topic first.']);
            exit();
        }

        echo json_encode(generateAIContent($topic, $tone, $format, $length));
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

/**
 * Real API path — set ANTHROPIC_API_KEY in your environment and this
 * is used automatically. Falls back to an offline template generator
 * so the page always works without a key.
 */
function callClaudeApi($topic, $tone, $format, $length) {
    $apiKey = getenv('ANTHROPIC_API_KEY');
    if (!$apiKey || !function_exists('curl_init')) return null;

    if ($format === 'headline') {
        $formatHint = 'Start with a short headline, then the body.';
    } elseif ($format === 'social') {
        $formatHint = 'Write it as a short social media post.';
    } else {
        $formatHint = 'Write it as flowing paragraphs.';
    }
    $prompt = "Write about: \"{$topic}\". Tone: {$tone}. Target length: about {$length} words. {$formatHint} Return only the content, no preamble.";

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]),
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    $data = json_decode($raw, true);
    return $data['content'][0]['text'] ?? null;
}

function generateAIContent($topic, $tone, $format, $length) {
    $liveText = callClaudeApi($topic, $tone, $format, $length);
    if ($liveText) {
        $words = str_word_count($liveText);
        return [
            'ok' => true, 'source' => 'live', 'text' => $liveText,
            'words' => $words, 'chars' => mb_strlen($liveText),
            'read_time' => max(1, (int) round($words / 200)),
        ];
    }

    $openers = [
        'formal'   => ["In considering %s, it is worth noting that", "A closer examination of %s reveals that"],
        'friendly' => ["Let's talk about %s for a second —", "Okay, so %s is actually pretty interesting."],
        'bold'     => ["%s isn't just a topic. It's a turning point.", "Here's the truth about %s that no one says out loud:"],
        'playful'  => ["So, %s, huh? Buckle up.", "Fun fact about %s: it's weirder than you think."],
    ];
    $bodies = [
        "the underlying patterns matter more than the surface details, and understanding them changes how you approach everything downstream.",
        "most people overestimate the complexity and underestimate the discipline it takes to get right.",
        "the difference between good and great usually comes down to a handful of small, repeatable decisions.",
        "context is everything — the same idea lands completely differently depending on who's reading it.",
        "the first step is usually the hardest, but momentum builds faster than most people expect once you start.",
        "small, consistent effort tends to beat big, sporadic bursts over the long run.",
        "it's easy to focus on the wrong metric and miss what actually drives the outcome.",
        "the people who get this right usually started by asking better questions, not by having better answers.",
        "there's a real cost to waiting for perfect conditions that may never arrive.",
        "what looks simple from the outside often hides a surprising amount of nuance.",
        "getting the fundamentals right early on saves a lot of trouble later.",
        "the gap between knowing and doing is where most progress actually happens.",
    ];
    $closers = [
        'formal'   => "In summary, this warrants continued attention and careful execution.",
        'friendly' => "Anyway, that's the gist of it — hope that helps!",
        'bold'     => "Don't wait for permission. Act on it.",
        'playful'  => "And that's the tea.",
    ];

    $tone = array_key_exists($tone, $openers) ? $tone : 'friendly';
    $opener = sprintf($openers[$tone][array_rand($openers[$tone])], $topic);
    $closer = $closers[$tone];

    // Keep adding sentences until we're close to the requested word count.
    $usedIdx = -1;
    $sentences = [];
    $wordsSoFar = str_word_count($opener) + str_word_count($closer);

    while ($wordsSoFar < $length) {
        $idx = array_rand($bodies);
        if ($idx === $usedIdx && count($bodies) > 1) continue; // avoid immediate repeats
        $usedIdx = $idx;
        $sentence = ucfirst($bodies[$idx]);
        $sentences[] = $sentence;
        $wordsSoFar += str_word_count($sentence);
    }

    // Group sentences into paragraphs of ~4 sentences each.
    $paragraphs = [$opener . ' ' . ($sentences[0] ?? '')];
    $chunk = [];
    foreach (array_slice($sentences, 1) as $s) {
        $chunk[] = $s;
        if (count($chunk) >= 4) {
            $paragraphs[] = implode(' ', $chunk);
            $chunk = [];
        }
    }
    if ($chunk) $paragraphs[] = implode(' ', $chunk);
    $paragraphs[] = $closer;

    $prefix = ($format === 'headline')
        ? ucfirst($topic) . ": " . ucfirst(explode('.', $bodies[0])[0]) . "\n\n"
        : "";
    $text = $prefix . implode("\n\n", $paragraphs);
    $words = str_word_count($text);

    return [
        'ok' => true, 'source' => 'offline', 'text' => $text,
        'words' => $words, 'chars' => mb_strlen($text),
        'read_time' => max(1, (int) round($words / 200)),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Content Generator - InkGuard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #050510;
            color: #fff;
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top:-50%; left:-50%;
            width:200%; height:200%;
            background:
                radial-gradient(ellipse at 20% 50%,
                    rgba(124,124,255,0.15) 0%,transparent 50%),
                radial-gradient(ellipse at 80% 20%,
                    rgba(0,204,102,0.1) 0%,transparent 50%);
            z-index:0;
            animation:bgMove 15s ease infinite alternate;
        }
        @keyframes bgMove {
            0%{transform:translate(0,0)}
            100%{transform:translate(30px,20px)}
        }
        .navbar {
            position:sticky; top:0; z-index:100;
            display:flex; align-items:center;
            justify-content:space-between;
            padding:0 30px; height:65px;
            background:rgba(10,10,26,0.85);
            backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(124,124,255,0.2);
        }
        .navbar-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .brand-icon {
            width:38px; height:38px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            border-radius:10px;
            display:flex; align-items:center;
            justify-content:center; font-size:20px;
            box-shadow:0 0 15px rgba(124,124,255,0.4);
        }
        .brand-name {
            font-size:20px; font-weight:800;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .navbar-right { display:flex; align-items:center; gap:8px; }
        .nav-link {
            padding:7px 16px; border-radius:8px;
            font-size:13px; color:#aaa;
            text-decoration:none; transition:all 0.2s;
            border:1px solid transparent;
        }
        .nav-link:hover {
            background:rgba(124,124,255,0.1);
            color:#fff; border-color:rgba(124,124,255,0.3);
        }
        .nav-link.danger { color:#ff6b6b; }
        .page { position:relative; z-index:1; max-width:900px; margin:0 auto; padding:30px 20px; }
        .page-title { text-align:center; margin-bottom:30px; }
        .page-title h1 {
            font-size:32px; font-weight:800; margin-bottom:8px;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .page-title p { color:#888; font-size:14px; }
        .glass-card {
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:28px;
            backdrop-filter:blur(10px);
            margin-bottom:24px;
        }
        .glass-card h2 { font-size:18px; font-weight:700; margin-bottom:16px; color:#fff; }
        .opt-select { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .opt-btn {
            padding:8px 16px;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:20px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:13px;
            transition:all 0.2s;
        }
        .opt-btn.selected, .opt-btn:hover {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
        }
        .text-input {
            width:100%; min-height:140px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:16px;
            color:#fff; font-size:14px;
            font-family:inherit; resize:vertical;
            line-height:1.8; outline:none;
            transition:border-color 0.3s;
        }
        .text-input:focus { border-color:rgba(124,124,255,0.5); }
        .text-input::placeholder { color:#555; }
        .range-row { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
        .range-row input[type=range] { flex:1; accent-color:#7c7cff; }
        .range-val { font-size:13px; color:#7c7cff; font-weight:700; min-width:80px; text-align:right; }
        .input-footer { display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
        .char-info { font-size:12px; color:#555; }
        .btn-row { display:flex; gap:10px; }
        .btn-check {
            padding:12px 28px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:15px; font-weight:700; cursor:pointer;
            transition:all 0.3s;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-check:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(124,124,255,0.5); }
        .btn-check:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
        .btn-clear {
            padding:12px 20px;
            background:rgba(255,255,255,0.05);
            color:#888; border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; font-size:14px; cursor:pointer;
        }
        .btn-clear:hover { background:rgba(255,68,68,0.1); color:#ff6b6b; }
        .result-section { display:none; }
        .stats-bar { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
        .stat-tile {
            background:rgba(0,0,0,0.3);
            border-radius:12px; padding:16px;
            text-align:center; border:1px solid rgba(255,255,255,0.06);
        }
        .stat-tile .num { font-size:28px; font-weight:900; line-height:1; color:#7c7cff; }
        .stat-tile .lbl { font-size:11px; color:#888; margin-top:4px; }
        .output-box {
            background:rgba(0,204,102,0.05);
            border:1px solid rgba(0,204,102,0.2);
            border-radius:12px; padding:20px;
        }
        .output-box h3 { color:#00cc66; font-size:15px; margin-bottom:12px; }
        .output-text { color:#ddd; line-height:1.9; font-size:15px; white-space:pre-wrap; }
        .source-tag {
            display:inline-block; margin-bottom:10px;
            font-size:11px; font-weight:700; text-transform:uppercase;
            padding:3px 10px; border-radius:10px;
        }
        .source-tag.live { background:rgba(0,204,102,0.2); color:#00cc66; }
        .source-tag.offline { background:rgba(255,215,0,0.15); color:#ffd700; }
        .copy-btn {
            background:rgba(124,124,255,0.2);
            border:1px solid rgba(124,124,255,0.3);
            color:#7c7cff; padding:6px 14px;
            border-radius:6px; cursor:pointer;
            font-size:12px; font-weight:600;
            float:right;
        }
        .loading-wrap { text-align:center; padding:30px; }
        .spinner {
            width:40px; height:40px;
            border:3px solid rgba(124,124,255,0.2);
            border-top-color:#7c7cff;
            border-radius:50%;
            animation:spin 0.8s linear infinite;
            margin:0 auto 14px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }
        @media(max-width:600px) {
            .stats-bar{grid-template-columns:repeat(1,1fr)}
            .navbar{padding:0 15px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">
        <div class="brand-icon">🛡️</div>
        <span class="brand-name">InkGuard</span>
    </a>
    <div class="navbar-right">
        <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
        <a href="logout.php" class="nav-link danger">🚪 Logout</a>
    </div>
</nav>

<div class="page">

    <div class="page-title">
        <h1>🪄 AI Content Generator</h1>
        <p>Describe a topic, pick a tone and shape, and generate a first draft</p>
    </div>

    <div class="glass-card">
        <h2>📝 What should it be about?</h2>

        <textarea class="text-input" id="topicText"
            placeholder="e.g. why small teams ship faster than large ones"
            oninput="updateCount()"></textarea>

        <div class="input-footer" style="margin-bottom:16px">
            <span class="char-info">
                <span id="wordCount">0</span> words | <span id="charCount">0</span> characters
            </span>
        </div>

        <div style="margin-bottom:12px">
            <label style="font-size:13px; color:#888; display:block; margin-bottom:8px">🎭 Tone:</label>
            <div class="opt-select" id="toneSelect">
                <div class="opt-btn selected" onclick="selectOpt(this,'toneSelect','friendly')">😊 Friendly</div>
                <div class="opt-btn" onclick="selectOpt(this,'toneSelect','formal')">🎩 Formal</div>
                <div class="opt-btn" onclick="selectOpt(this,'toneSelect','bold')">🔥 Bold</div>
                <div class="opt-btn" onclick="selectOpt(this,'toneSelect','playful')">🎈 Playful</div>
            </div>
        </div>

        <div style="margin-bottom:16px">
            <label style="font-size:13px; color:#888; display:block; margin-bottom:8px">📐 Format:</label>
            <div class="opt-select" id="formatSelect">
                <div class="opt-btn selected" onclick="selectOpt(this,'formatSelect','paragraph')">📄 Paragraph</div>
                <div class="opt-btn" onclick="selectOpt(this,'formatSelect','headline')">📰 Headline + Body</div>
                <div class="opt-btn" onclick="selectOpt(this,'formatSelect','social')">📱 Social Post</div>
            </div>
        </div>

        <div class="range-row">
            <label style="font-size:13px; color:#888; white-space:nowrap">📏 Length</label>
            <input type="range" id="lengthRange" min="80" max="900" step="20" value="400"
                oninput="document.getElementById('lengthVal').textContent = this.value + ' words'">
            <span class="range-val" id="lengthVal">400 words</span>
        </div>

        <div class="input-footer">
            <span class="char-info">Draft quality — always review before you publish.</span>
            <div class="btn-row">
                <button class="btn-clear" onclick="clearAll()">🗑️ Clear</button>
                <button class="btn-check" id="genBtn" onclick="generateContent()">🪄 Generate</button>
            </div>
        </div>
    </div>

    <div class="result-section" id="resultSection">
        <div class="glass-card">
            <h2>📊 Generated Draft</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
let selectedTone = 'friendly';
let selectedFormat = 'paragraph';

function selectOpt(el, groupId, value) {
    document.querySelectorAll('#' + groupId + ' .opt-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    if (groupId === 'toneSelect') selectedTone = value;
    if (groupId === 'formatSelect') selectedFormat = value;
}

function updateCount() {
    const text = document.getElementById('topicText').value;
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    document.getElementById('wordCount').textContent = words;
    document.getElementById('charCount').textContent = text.length;
}

function clearAll() {
    document.getElementById('topicText').value = '';
    document.getElementById('wordCount').textContent = '0';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('resultSection').style.display = 'none';
}

async function generateContent() {
    const topic = document.getElementById('topicText').value.trim();
    if (!topic) { alert('Please enter a topic first!'); return; }

    const btn = document.getElementById('genBtn');
    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');
    const length = document.getElementById('lengthRange').value;

    btn.disabled = true;
    btn.textContent = '⏳ Generating...';
    resultSection.style.display = 'block';
    resultContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">🪄 Writing your draft...</p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('topic', topic);
        formData.append('tone', selectedTone);
        formData.append('format', selectedFormat);
        formData.append('length', length);

        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();
        displayResult(data);

    } catch (error) {
        resultContent.innerHTML = `
            <div style="text-align:center; padding:20px; color:#ff6b6b">
                <p>❌ Something went wrong generating your draft.</p>
                <p style="color:#555; font-size:13px; margin-top:8px">Please try again.</p>
            </div>`;
    }

    btn.disabled = false;
    btn.textContent = '🪄 Generate';
}

function displayResult(data) {
    const resultContent = document.getElementById('resultContent');

    if (!data.ok) {
        resultContent.innerHTML = `
            <div style="text-align:center; padding:20px; color:#ff6b6b">
                <p>❌ ${data.error}</p>
            </div>`;
        return;
    }

    const sourceLabel = data.source === 'live'
        ? '<span class="source-tag live">🟢 Live AI</span>'
        : '<span class="source-tag offline">🟡 Offline draft</span>';

    resultContent.innerHTML = `
        <div class="stats-bar">
            <div class="stat-tile">
                <div class="num">${data.words}</div>
                <div class="lbl">Words</div>
            </div>
            <div class="stat-tile">
                <div class="num">${data.chars}</div>
                <div class="lbl">Characters</div>
            </div>
            <div class="stat-tile">
                <div class="num">${data.read_time}</div>
                <div class="lbl">Min Read</div>
            </div>
        </div>
        <div class="output-box">
            ${sourceLabel}
            <button class="copy-btn" onclick="copyText('${data.text.replace(/'/g,"\\'").replace(/\n/g,'\\n')}')">📋 Copy</button>
            <h3>✅ Your Draft</h3>
            <div class="output-text">${data.text}</div>
        </div>`;
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Copied to clipboard!');
    });
}
</script>
</body>
</html>