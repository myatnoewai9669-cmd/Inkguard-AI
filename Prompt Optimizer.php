<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

/* ---------- AJAX endpoint ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    ini_set('display_errors', '0');
    header('Content-Type: application/json');

    try {
        $prompt  = trim($_POST['prompt'] ?? '');
        $useCase = $_POST['use_case'] ?? 'general';

        if ($prompt === '') {
            echo json_encode(['ok' => false, 'error' => 'Please enter a prompt first.']);
            exit();
        }
        if (mb_strlen($prompt) > 4000) {
            echo json_encode(['ok' => false, 'error' => 'That prompt is quite long — try under 4000 characters.']);
            exit();
        }

        echo json_encode(optimizePrompt($prompt, $useCase));
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

/**
 * Real API path — set ANTHROPIC_API_KEY in your environment and this
 * is used automatically. Falls back to an honest offline heuristic
 * checklist (not a fabricated rewrite) if no key is set, the call
 * fails, or the output doesn't pass validation.
 */
function callClaudeApiOptimize($prompt, $useCase) {
    $apiKey = getenv('ANTHROPIC_API_KEY');
    if (!$apiKey || !function_exists('curl_init')) return null;

    $useCaseLabel = [
        'general'  => 'general-purpose assistant use',
        'coding'   => 'coding / software development',
        'creative' => 'creative writing',
        'analysis' => 'data analysis / research',
        'image'    => 'AI image generation',
    ][$useCase] ?? 'general-purpose assistant use';

    $metaPrompt = "You are a prompt engineering expert. Improve the following user prompt for {$useCaseLabel}.\n"
        . "Return ONLY raw JSON, nothing else — no markdown fences, no commentary.\n"
        . "Shape: {\"optimized\":\"the rewritten prompt\",\"changes\":[\"short phrase, max 12 words\",\"...\"],"
        . "\"score_before\":0-100,\"score_after\":0-100}.\n"
        . "Rules: \"optimized\" must be a complete, ready-to-use prompt in the user's own intent — never a template with "
        . "[placeholder] brackets. Each item in \"changes\" is a short label (max 12 words), not a sentence. "
        . "3-6 items in \"changes\". score_after should be realistically higher than score_before, not always 100.\n\n"
        . "USER PROMPT:\n" . $prompt;

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1536,
            'messages' => [['role' => 'user', 'content' => $metaPrompt]],
        ]),
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;
    $data = json_decode($raw, true);
    $text = $data['content'][0]['text'] ?? null;
    if (!$text) return null;

    $text = preg_replace('/^```json\s*|\s*```$/m', '', trim($text));
    $parsed = json_decode($text, true);
    if (!is_array($parsed)) return null;

    $clean = sanitizeOptimization($parsed);
    if ($clean === null) return null;

    return ['ok' => true, 'source' => 'live'] + $clean;
}

/** Same defensive validation lesson as the other tools: reject
 *  anything that looks like a broken/placeholder response instead
 *  of shipping it to the user. */
function sanitizeOptimization($p) {
    $optimized = trim((string) ($p['optimized'] ?? ''));
    if ($optimized === '') return null;
    if (strpos($optimized, '[') !== false && strpos($optimized, ']') !== false) return null;
    if (mb_strlen($optimized) > 3000) return null;

    $changesRaw = $p['changes'] ?? [];
    if (!is_array($changesRaw)) return null;
    $changes = [];
    foreach ($changesRaw as $c) {
        $c = trim(preg_replace('/\s+/', ' ', (string) $c));
        if ($c === '') continue;
        if (str_word_count($c) > 16) continue; // too long to be a "change" label
        $changes[] = mb_substr($c, 0, 90);
        if (count($changes) >= 6) break;
    }
    if (count($changes) === 0) return null;

    $before = (int) ($p['score_before'] ?? 40);
    $after  = (int) ($p['score_after'] ?? 75);
    $before = max(0, min(100, $before));
    $after  = max(0, min(100, $after));
    if ($after < $before) $after = min(100, $before + 15);

    return ['optimized' => $optimized, 'changes' => $changes, 'score_before' => $before, 'score_after' => $after];
}

/**
 * Honest offline mode: rather than fabricating a "rewritten" prompt
 * with guessed specifics (the mistake that broke the PPT tool
 * earlier), this scores the prompt and returns a checklist of what
 * to add — real, useful, and never misleading about what it invented.
 */
function offlineOptimize($prompt, $useCase) {
    $wordCount = str_word_count($prompt);
    $lower = mb_strtolower($prompt);

    $checks = [
        'has_format'    => (bool) preg_match('/\b(format|bullet|list|table|json|paragraph|word count|words|steps|outline)\b/', $lower),
        'has_audience'  => (bool) preg_match('/\b(audience|for a|beginner|expert|reader|customer|student)\b/', $lower),
        'has_length'    => (bool) preg_match('/\b(\d+\s*(word|sentence|paragraph|line|page)s?|short|long|brief|detailed)\b/', $lower),
        'has_tone'      => (bool) preg_match('/\b(tone|style|formal|casual|friendly|professional|funny|serious)\b/', $lower),
        'has_context'   => $wordCount >= 15,
        'has_example'   => (bool) preg_match('/\b(example|e\.g\.|such as|like this|for instance)\b/', $lower),
        'has_constraint'=> (bool) preg_match('/\b(don\'t|do not|avoid|must|should|never|always|only)\b/', $lower),
    ];

    $score = 30;
    foreach ($checks as $v) if ($v) $score += 10;
    $score = min(100, $score);

    $suggestions = [];
    if (!$checks['has_context'])   $suggestions[] = 'Add background: what this is for and why it matters';
    if (!$checks['has_format'])    $suggestions[] = 'Specify the output format (list, table, prose, code, etc.)';
    if (!$checks['has_length'])    $suggestions[] = 'State a target length (word count, number of points)';
    if (!$checks['has_audience'])  $suggestions[] = 'Name the intended audience or reader';
    if (!$checks['has_tone'])      $suggestions[] = 'Specify the tone or style you want';
    if (!$checks['has_example'])   $suggestions[] = 'Include an example of the kind of output you want';
    if (!$checks['has_constraint'])$suggestions[] = 'Add any hard constraints (what to include or avoid)';
    if ($wordCount < 8)            $suggestions[] = 'Expand the prompt — it may be too short to be specific';

    $useCaseTips = [
        'coding'   => 'Mention the language/framework and any error messages or constraints',
        'creative' => 'Describe the desired mood, point of view, and length',
        'analysis' => 'Specify the data source, question being answered, and desired output form',
        'image'    => 'Describe subject, style, lighting, and composition explicitly',
    ];
    if (isset($useCaseTips[$useCase])) $suggestions[] = $useCaseTips[$useCase];

    if (count($suggestions) === 0) {
        $suggestions[] = 'This prompt already covers the basics well — consider adding one concrete example for even better results';
    }

    return [
        'ok' => true, 'source' => 'offline',
        'score_before' => $score, 'score_after' => min(100, $score + count($suggestions) * 5),
        'suggestions' => array_slice($suggestions, 0, 6),
    ];
}

function optimizePrompt($prompt, $useCase) {
    $live = callClaudeApiOptimize($prompt, $useCase);
    if ($live) return $live;
    return offlineOptimize($prompt, $useCase);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt Optimizer - InkGuard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#050510; color:#fff; min-height:100vh; }
        body::before {
            content:''; position:fixed; top:-50%; left:-50%; width:200%; height:200%;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(124,124,255,0.15) 0%,transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0,204,102,0.1) 0%,transparent 50%);
            z-index:0; animation:bgMove 15s ease infinite alternate;
        }
        @keyframes bgMove { 0%{transform:translate(0,0)} 100%{transform:translate(30px,20px)} }
        .navbar {
            position:sticky; top:0; z-index:100; display:flex; align-items:center; justify-content:space-between;
            padding:0 30px; height:65px; background:rgba(10,10,26,0.85); backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(124,124,255,0.2);
        }
        .navbar-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .brand-icon {
            width:38px; height:38px; background:linear-gradient(135deg,#7c7cff,#5555dd);
            border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px;
            box-shadow:0 0 15px rgba(124,124,255,0.4);
        }
        .brand-name {
            font-size:20px; font-weight:800; background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .navbar-right { display:flex; align-items:center; gap:8px; }
        .nav-link {
            padding:7px 16px; border-radius:8px; font-size:13px; color:#aaa;
            text-decoration:none; transition:all 0.2s; border:1px solid transparent;
        }
        .nav-link:hover { background:rgba(124,124,255,0.1); color:#fff; border-color:rgba(124,124,255,0.3); }
        .nav-link.danger { color:#ff6b6b; }
        .page { position:relative; z-index:1; max-width:900px; margin:0 auto; padding:30px 20px; }
        .page-title { text-align:center; margin-bottom:30px; }
        .page-title h1 {
            font-size:32px; font-weight:800; margin-bottom:8px;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .page-title p { color:#888; font-size:14px; }
        .glass-card {
            background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:28px; backdrop-filter:blur(10px); margin-bottom:24px;
        }
        .glass-card h2 { font-size:18px; font-weight:700; margin-bottom:16px; color:#fff; }
        .opt-select { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .opt-btn {
            padding:8px 16px; border:1px solid rgba(255,255,255,0.1); border-radius:20px;
            background:rgba(0,0,0,0.2); color:#aaa; cursor:pointer; font-size:13px; transition:all 0.2s;
        }
        .opt-btn.selected, .opt-btn:hover { border-color:#7c7cff; background:rgba(124,124,255,0.15); color:#fff; }
        .text-input {
            width:100%; min-height:150px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:16px; color:#fff; font-size:14px; font-family:inherit;
            resize:vertical; line-height:1.8; outline:none; transition:border-color 0.3s;
        }
        .text-input:focus { border-color:rgba(124,124,255,0.5); }
        .text-input::placeholder { color:#555; }
        .input-footer { display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
        .char-info { font-size:12px; color:#555; }
        .btn-row { display:flex; gap:10px; }
        .btn-check {
            padding:12px 28px; background:linear-gradient(135deg,#7c7cff,#5555dd); color:#fff; border:none;
            border-radius:12px; font-size:15px; font-weight:700; cursor:pointer; transition:all 0.3s;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-check:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(124,124,255,0.5); }
        .btn-check:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
        .btn-clear {
            padding:12px 20px; background:rgba(255,255,255,0.05); color:#888;
            border:1px solid rgba(255,255,255,0.1); border-radius:12px; font-size:14px; cursor:pointer;
        }
        .btn-clear:hover { background:rgba(255,68,68,0.1); color:#ff6b6b; }
        .result-section { display:none; }
        .source-tag {
            display:inline-block; margin-bottom:14px; font-size:11px; font-weight:700; text-transform:uppercase;
            padding:3px 10px; border-radius:10px;
        }
        .source-tag.live { background:rgba(0,204,102,0.2); color:#00cc66; }
        .source-tag.offline { background:rgba(255,215,0,0.15); color:#ffd700; }
        .score-row { display:flex; gap:16px; align-items:center; margin-bottom:22px; flex-wrap:wrap; }
        .score-box { flex:1; min-width:130px; background:rgba(0,0,0,0.3); border-radius:12px; padding:16px; text-align:center; }
        .score-box .num { font-size:34px; font-weight:900; }
        .score-box .lbl { font-size:11px; color:#888; margin-top:4px; text-transform:uppercase; letter-spacing:0.05em; }
        .score-arrow { font-size:22px; color:#555; }
        .optimized-box {
            background:rgba(0,204,102,0.05); border:1px solid rgba(0,204,102,0.2);
            border-radius:12px; padding:20px; margin-bottom:20px;
        }
        .optimized-box h3 { color:#00cc66; font-size:15px; margin-bottom:12px; }
        .optimized-text { color:#ddd; line-height:1.8; font-size:14.5px; white-space:pre-wrap; }
        .copy-btn {
            background:rgba(124,124,255,0.2); border:1px solid rgba(124,124,255,0.3); color:#7c7cff;
            padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; float:right;
        }
        .changes-list, .suggestions-list { display:flex; flex-direction:column; gap:10px; }
        .change-item {
            background:rgba(0,0,0,0.3); border-radius:10px; padding:12px 16px;
            border-left:3px solid #7c7cff; font-size:13.5px; color:#ccc;
        }
        .suggestion-item {
            background:rgba(0,0,0,0.3); border-radius:10px; padding:12px 16px;
            border-left:3px solid #ffd700; font-size:13.5px; color:#ccc;
        }
        .loading-wrap { text-align:center; padding:30px; }
        .spinner {
            width:40px; height:40px; border:3px solid rgba(124,124,255,0.2); border-top-color:#7c7cff;
            border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto 14px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }
        @media(max-width:600px) { .navbar{padding:0 15px} }
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
        <h1>🧠 Prompt Optimizer</h1>
        <p>Paste a rough prompt — get a clearer, more effective version</p>
    </div>

    <div class="glass-card">
        <h2>📝 Your prompt</h2>

        <textarea class="text-input" id="promptText"
            placeholder="e.g. write me a blog post about productivity"
            oninput="updateCount()"></textarea>

        <div class="input-footer" style="margin-bottom:16px">
            <span class="char-info"><span id="charCount">0</span> / 4000 characters</span>
        </div>

        <div style="margin-bottom:16px">
            <label style="font-size:13px; color:#888; display:block; margin-bottom:8px">🎯 Optimize for:</label>
            <div class="opt-select" id="useCaseSelect">
                <div class="opt-btn selected" onclick="selectUseCase(this,'general')">💬 General</div>
                <div class="opt-btn" onclick="selectUseCase(this,'coding')">💻 Coding</div>
                <div class="opt-btn" onclick="selectUseCase(this,'creative')">✍️ Creative Writing</div>
                <div class="opt-btn" onclick="selectUseCase(this,'analysis')">📊 Data / Analysis</div>
                <div class="opt-btn" onclick="selectUseCase(this,'image')">🎨 Image Generation</div>
            </div>
        </div>

        <div class="input-footer">
            <span class="char-info">Optimized output is a starting point — review before using.</span>
            <div class="btn-row">
                <button class="btn-clear" onclick="clearAll()">🗑️ Clear</button>
                <button class="btn-check" id="optBtn" onclick="optimizePrompt()">🧠 Optimize</button>
            </div>
        </div>
    </div>

    <div class="result-section" id="resultSection">
        <div class="glass-card">
            <h2>📊 Result</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
let selectedUseCase = 'general';

function selectUseCase(el, value) {
    document.querySelectorAll('#useCaseSelect .opt-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    selectedUseCase = value;
}

function updateCount() {
    document.getElementById('charCount').textContent = document.getElementById('promptText').value.length;
}

function clearAll() {
    document.getElementById('promptText').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('resultSection').style.display = 'none';
}

function scoreColor(n) {
    return n >= 80 ? '#00cc66' : n >= 55 ? '#ffd700' : '#ff6b6b';
}

async function optimizePrompt() {
    const prompt = document.getElementById('promptText').value.trim();
    if (!prompt) { alert('Please enter a prompt first!'); return; }

    const btn = document.getElementById('optBtn');
    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');

    btn.disabled = true;
    btn.textContent = '⏳ Optimizing...';
    resultSection.style.display = 'block';
    resultContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">🧠 Analyzing your prompt...</p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    try {
        const formData = new URLSearchParams();
        formData.append('ajax', '1');
        formData.append('prompt', prompt);
        formData.append('use_case', selectedUseCase);

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
                <p>❌ Something went wrong optimizing your prompt.</p>
                <p style="color:#555; font-size:13px; margin-top:8px">Please try again.</p>
            </div>`;
    }

    btn.disabled = false;
    btn.textContent = '🧠 Optimize';
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function displayResult(data) {
    const resultContent = document.getElementById('resultContent');

    if (!data.ok) {
        resultContent.innerHTML = `<div style="text-align:center; padding:20px; color:#ff6b6b"><p>❌ ${escapeHtml(data.error)}</p></div>`;
        return;
    }

    const before = Number(data.score_before) || 0;
    const after = Number(data.score_after) || 0;

    if (data.source === 'live') {
        const changesHTML = (data.changes || []).map(c => `<div class="change-item">✓ ${escapeHtml(c)}</div>`).join('');
        resultContent.innerHTML = `
            <span class="source-tag live">🟢 Live AI rewrite</span>
            <div class="score-row">
                <div class="score-box"><div class="num" style="color:${scoreColor(before)}">${before}</div><div class="lbl">Before</div></div>
                <div class="score-arrow">→</div>
                <div class="score-box"><div class="num" style="color:${scoreColor(after)}">${after}</div><div class="lbl">After</div></div>
            </div>
            <div class="optimized-box">
                <button class="copy-btn" onclick="copyText(${JSON.stringify(data.optimized)})">📋 Copy</button>
                <h3>✅ Optimized Prompt</h3>
                <div class="optimized-text">${escapeHtml(data.optimized)}</div>
            </div>
            <div style="margin-bottom:10px; font-size:14px; font-weight:700; color:#fff;">What changed</div>
            <div class="changes-list">${changesHTML}</div>`;
    } else {
        const suggHTML = (data.suggestions || []).map(s => `<div class="suggestion-item">💡 ${escapeHtml(s)}</div>`).join('');
        resultContent.innerHTML = `
            <span class="source-tag offline">🟡 Offline analysis</span>
            <div class="score-row">
                <div class="score-box"><div class="num" style="color:${scoreColor(before)}">${before}</div><div class="lbl">Current score</div></div>
                <div class="score-arrow">→</div>
                <div class="score-box"><div class="num" style="color:${scoreColor(after)}">${after}</div><div class="lbl">If improved</div></div>
            </div>
            <div style="margin-bottom:6px; font-size:13px; color:#888;">
                No AI connected — here's an honest checklist instead of a guessed rewrite. Add these details yourself for best results:
            </div>
            <div class="suggestions-list" style="margin-top:14px;">${suggHTML}</div>`;
    }
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Copied to clipboard!');
    });
}
</script>
</body>
</html>