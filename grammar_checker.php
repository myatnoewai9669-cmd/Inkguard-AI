<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grammar Checker - InkGuard</title>
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
        .navbar-brand {
            display:flex; align-items:center;
            gap:10px; text-decoration:none;
        }
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
        .navbar-right {
            display:flex; align-items:center; gap:8px;
        }
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
        .page {
            position:relative; z-index:1;
            max-width:900px; margin:0 auto;
            padding:30px 20px;
        }
        .page-title {
            text-align:center; margin-bottom:30px;
        }
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
        .glass-card h2 {
            font-size:18px; font-weight:700;
            margin-bottom:16px; color:#fff;
        }
        .lang-select {
            display:flex; gap:8px; margin-bottom:16px;
            flex-wrap:wrap;
        }
        .lang-btn {
            padding:8px 16px;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:20px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:13px;
            transition:all 0.2s;
        }
        .lang-btn.selected,
        .lang-btn:hover {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
        }
        .text-input {
            width:100%; min-height:200px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:16px;
            color:#fff; font-size:14px;
            font-family:inherit; resize:vertical;
            line-height:1.8; outline:none;
            transition:border-color 0.3s;
        }
        .text-input:focus {
            border-color:rgba(124,124,255,0.5);
        }
        .text-input::placeholder { color:#555; }
        .input-footer {
            display:flex; justify-content:space-between;
            align-items:center; margin-top:12px;
        }
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
        .btn-check:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(124,124,255,0.5);
        }
        .btn-check:disabled {
            opacity:0.6; cursor:not-allowed; transform:none;
        }
        .btn-clear {
            padding:12px 20px;
            background:rgba(255,255,255,0.05);
            color:#888; border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; font-size:14px; cursor:pointer;
        }
        .btn-clear:hover {
            background:rgba(255,68,68,0.1); color:#ff6b6b;
        }
        .result-section { display:none; }
        .stats-bar {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:12px; margin-bottom:20px;
        }
        .stat-tile {
            background:rgba(0,0,0,0.3);
            border-radius:12px; padding:16px;
            text-align:center; border:1px solid rgba(255,255,255,0.06);
        }
        .stat-tile .num {
            font-size:28px; font-weight:900; line-height:1;
        }
        .stat-tile .lbl {
            font-size:11px; color:#888; margin-top:4px;
        }
        .corrected-box {
            background:rgba(0,204,102,0.05);
            border:1px solid rgba(0,204,102,0.2);
            border-radius:12px; padding:20px;
            margin-bottom:20px;
        }
        .corrected-box h3 {
            color:#00cc66; font-size:15px;
            margin-bottom:12px;
        }
        .corrected-text {
            color:#ccc; line-height:1.8;
            font-size:14px; white-space:pre-wrap;
        }
        .corrected-text .correction {
            background:rgba(0,204,102,0.2);
            border-bottom:2px solid #00cc66;
            color:#00ff88; padding:0 2px;
            border-radius:2px;
        }
        .errors-list { display:flex; flex-direction:column; gap:12px; }
        .error-item {
            background:rgba(0,0,0,0.3);
            border-radius:12px; padding:16px;
            border-left:4px solid;
        }
        .error-item.grammar { border-color:#ff4444; }
        .error-item.spelling { border-color:#ffd700; }
        .error-item.style { border-color:#7c7cff; }
        .error-item.punctuation { border-color:#00cc66; }
        .error-header {
            display:flex; align-items:center;
            gap:8px; margin-bottom:8px;
        }
        .error-type {
            font-size:11px; font-weight:700;
            padding:3px 8px; border-radius:10px;
            text-transform:uppercase;
        }
        .type-grammar {
            background:rgba(255,68,68,0.2);
            color:#ff4444;
        }
        .type-spelling {
            background:rgba(255,215,0,0.2);
            color:#ffd700;
        }
        .type-style {
            background:rgba(124,124,255,0.2);
            color:#7c7cff;
        }
        .type-punctuation {
            background:rgba(0,204,102,0.2);
            color:#00cc66;
        }
        .error-msg {
            font-size:14px; color:#ccc;
            margin-bottom:8px;
        }
        .error-context {
            font-size:13px; color:#888;
            background:rgba(255,255,255,0.03);
            padding:8px 12px; border-radius:6px;
            margin-bottom:8px; font-style:italic;
        }
        .error-context .wrong {
            color:#ff6b6b;
            text-decoration:line-through;
        }
        .error-context .right {
            color:#00ff88; font-weight:700;
        }
        .suggestions {
            display:flex; gap:6px; flex-wrap:wrap;
        }
        .suggestion-tag {
            padding:4px 10px;
            background:rgba(0,204,102,0.1);
            border:1px solid rgba(0,204,102,0.3);
            border-radius:6px; font-size:12px;
            color:#00cc66; cursor:pointer;
            transition:all 0.2s;
        }
        .suggestion-tag:hover {
            background:rgba(0,204,102,0.25);
        }
        .score-circle {
            text-align:center; margin-bottom:20px;
        }
        .score-ring {
            width:120px; height:120px;
            margin:0 auto 12px;
        }
        .no-errors {
            text-align:center; padding:30px;
        }
        .no-errors .icon { font-size:60px; margin-bottom:12px; }
        .no-errors h3 {
            font-size:20px; color:#00cc66;
            margin-bottom:8px;
        }
        .no-errors p { color:#888; font-size:14px; }
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
        .copy-btn {
            background:rgba(124,124,255,0.2);
            border:1px solid rgba(124,124,255,0.3);
            color:#7c7cff; padding:6px 14px;
            border-radius:6px; cursor:pointer;
            font-size:12px; font-weight:600;
            float:right;
        }
        @media(max-width:600px) {
            .stats-bar{grid-template-columns:repeat(2,1fr)}
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
        <h1>✏️ Grammar Checker</h1>
        <p>Check and correct grammar, spelling, punctuation and style errors</p>
    </div>

    <div class="glass-card">
        <h2>📝 Enter Your Text</h2>

        <!-- Language Selection -->
        <div style="margin-bottom:12px">
            <label style="font-size:13px; color:#888; display:block; margin-bottom:8px">
                🌐 Language:
            </label>
            <div class="lang-select">
                <div class="lang-btn selected" onclick="selectLang(this,'en-US')">
                    🇺🇸 English (US)
                </div>
                <div class="lang-btn" onclick="selectLang(this,'en-GB')">
                    🇬🇧 English (UK)
                </div>
                <div class="lang-btn" onclick="selectLang(this,'id')">
                    🇮🇩 Indonesian
                </div>
                <div class="lang-btn" onclick="selectLang(this,'de-DE')">
                    🇩🇪 German
                </div>
                <div class="lang-btn" onclick="selectLang(this,'fr')">
                    🇫🇷 French
                </div>
            </div>
        </div>

        <textarea class="text-input" id="grammarText"
            placeholder="Paste or type your text here to check grammar, spelling and punctuation...

Example:
I goes to school yesterday and I has a great time with my friends. We doesnt know that the teacher would gives us a test."
            oninput="updateCount()">
        </textarea>

        <div class="input-footer">
            <span class="char-info">
                <span id="wordCount">0</span> words |
                <span id="charCount">0</span> characters
            </span>
            <div class="btn-row">
                <button class="btn-clear" onclick="clearAll()">
                    🗑️ Clear
                </button>
                <button class="btn-check" id="checkBtn"
                    onclick="checkGrammar()">
                    ✏️ Check Grammar
                </button>
            </div>
        </div>
    </div>

    <!-- Result -->
    <div class="result-section" id="resultSection">
        <div class="glass-card">
            <h2>📊 Grammar Report</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
let selectedLang = 'en-US';

function selectLang(el, lang) {
    document.querySelectorAll('.lang-btn').forEach(b => {
        b.classList.remove('selected');
    });
    el.classList.add('selected');
    selectedLang = lang;
}

function updateCount() {
    const text = document.getElementById('grammarText').value;
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    document.getElementById('wordCount').textContent = words;
    document.getElementById('charCount').textContent = text.length;
}

function clearAll() {
    document.getElementById('grammarText').value = '';
    document.getElementById('wordCount').textContent = '0';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('resultSection').style.display = 'none';
}

async function checkGrammar() {
    const text = document.getElementById('grammarText').value.trim();
    if (!text) { alert('Please enter some text!'); return; }
    if (text.length < 10) {
        alert('Please enter at least 10 characters!');
        return;
    }

    const btn = document.getElementById('checkBtn');
    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');

    btn.disabled = true;
    btn.textContent = '⏳ Checking...';
    resultSection.style.display = 'block';
    resultContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">
                ✏️ Checking grammar...
            </p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    try {
        // LanguageTool Free API
        const formData = new URLSearchParams();
        formData.append('text', text);
        formData.append('language', selectedLang);

        const response = await fetch(
            'https://api.languagetool.org/v2/check',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            }
        );

        const data = await response.json();
        displayResults(text, data);

    } catch (error) {
        resultContent.innerHTML = `
            <div style="text-align:center; padding:20px;
                color:#ff6b6b">
                <p>❌ Connection failed.</p>
                <p style="color:#555; font-size:13px; margin-top:8px">
                    LanguageTool API requires internet connection.
                </p>
                <button onclick="checkOffline()"
                    style="background:#7c7cff; color:#fff;
                    border:none; padding:10px 20px;
                    border-radius:8px; cursor:pointer;
                    margin-top:12px; font-weight:600">
                    🔄 Try Offline Check
                </button>
            </div>`;
    }

    btn.disabled = false;
    btn.textContent = '✏️ Check Grammar';
}

function displayResults(originalText, data) {
    const matches = data.matches || [];
    const words = originalText.trim().split(/\s+/).length;

    // Score
    const errorCount = matches.length;
    const score = Math.max(0, Math.round(
        100 - (errorCount / words * 100)));

    const scoreColor = score >= 80 ? '#00cc66' :
        score >= 60 ? '#ffd700' : '#ff4444';

    // Categorize errors
    let grammar = 0, spelling = 0, style = 0, punctuation = 0;
    matches.forEach(m => {
        const cat = m.rule?.category?.id || '';
        if (cat.includes('SPELL')) spelling++;
        else if (cat.includes('PUNCT')) punctuation++;
        else if (cat.includes('STYLE')||cat.includes('TYPOGRAPHY')) style++;
        else grammar++;
    });

    // Build corrected text
    let correctedText = originalText;
    const sortedMatches = [...matches].sort((a,b) =>
        b.offset - a.offset);
    sortedMatches.forEach(m => {
        if (m.replacements && m.replacements.length > 0) {
            const replacement = m.replacements[0].value;
            correctedText =
                correctedText.substring(0, m.offset) +
                replacement +
                correctedText.substring(m.offset + m.length);
        }
    });

    const resultContent = document.getElementById('resultContent');

    if (matches.length === 0) {
        resultContent.innerHTML = `
            <div class="no-errors">
                <div class="icon">✅</div>
                <h3>Perfect! No errors found.</h3>
                <p>Your text has no grammar, spelling or punctuation errors.</p>
                <p style="color:#7c7cff; margin-top:8px; font-size:13px">
                    Score: 100/100 🎉
                </p>
            </div>`;
        return;
    }

    let errorsHTML = '';
    matches.forEach((m, idx) => {
        const cat = m.rule?.category?.id || 'GRAMMAR';
        let type, typeClass, typeLabel;

        if (cat.includes('SPELL')) {
            type='spelling'; typeClass='type-spelling';
            typeLabel='🔤 Spelling';
        } else if (cat.includes('PUNCT')) {
            type='punctuation'; typeClass='type-punctuation';
            typeLabel='📌 Punctuation';
        } else if (cat.includes('STYLE')||cat.includes('TYPO')) {
            type='style'; typeClass='type-style';
            typeLabel='✨ Style';
        } else {
            type='grammar'; typeClass='type-grammar';
            typeLabel='📝 Grammar';
        }

        const context = m.context?.text || '';
        const contextOffset = m.context?.offset || 0;
        const contextLen = m.context?.length || 0;
        const wrongPart = context.substring(
            contextOffset, contextOffset+contextLen);
        const beforeCtx = context.substring(0, contextOffset);
        const afterCtx = context.substring(contextOffset+contextLen);

        const suggestions = m.replacements
            ?.slice(0,4)
            .map(r => `
                <span class="suggestion-tag"
                    onclick="applySuggestion(${m.offset},
                    ${m.length},'${r.value.replace(/'/g,"\\'")}')">
                    ${r.value}
                </span>`)
            .join('') || '<span style="color:#555">No suggestions</span>';

        errorsHTML += `
            <div class="error-item ${type}">
                <div class="error-header">
                    <span class="error-type ${typeClass}">
                        ${typeLabel}
                    </span>
                    <span style="color:#555; font-size:12px">
                        Error #${idx+1}
                    </span>
                </div>
                <div class="error-msg">${m.message}</div>
                <div class="error-context">
                    ...${beforeCtx}<span class="wrong">${wrongPart}</span>${afterCtx}...
                </div>
                <div style="margin-bottom:6px; font-size:12px; color:#888">
                    💡 Suggestions:
                </div>
                <div class="suggestions">${suggestions}</div>
            </div>`;
    });

    resultContent.innerHTML = `
        <!-- Score -->
        <div style="text-align:center; margin-bottom:20px">
            <div style="font-size:60px; font-weight:900;
                color:${scoreColor}">${score}</div>
            <div style="color:#888; font-size:14px">
                Grammar Score / 100
            </div>
            <div style="height:8px; background:#333;
                border-radius:99px; overflow:hidden;
                margin:12px auto; max-width:300px">
                <div style="width:${score}%; height:100%;
                    background:${scoreColor};
                    border-radius:99px;
                    transition:width 1s ease">
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-tile">
                <div class="num" style="color:#ff4444">
                    ${errorCount}
                </div>
                <div class="lbl">Total Errors</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ff6b6b">
                    ${grammar}
                </div>
                <div class="lbl">Grammar</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ffd700">
                    ${spelling}
                </div>
                <div class="lbl">Spelling</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66">
                    ${punctuation + style}
                </div>
                <div class="lbl">Other</div>
            </div>
        </div>

        <!-- Corrected Text -->
        ${correctedText !== originalText ? `
        <div class="corrected-box">
            <div style="display:flex; justify-content:space-between;
                align-items:center; margin-bottom:12px">
                <h3 style="color:#00cc66; margin:0">
                    ✅ Corrected Text
                </h3>
                <button class="copy-btn"
                    onclick="copyText('${
                        correctedText.replace(/'/g,"\\'")
                        .replace(/\n/g,'\\n')}')">
                    📋 Copy
                </button>
            </div>
            <div class="corrected-text">${correctedText}</div>
        </div>` : ''}

        <!-- Errors List -->
        <div style="margin-bottom:12px">
            <h3 style="color:#fff; font-size:16px">
                ❌ Errors Found (${errorCount})
            </h3>
        </div>
        <div class="errors-list">${errorsHTML}</div>
    `;
}

function applySuggestion(offset, length, replacement) {
    const textarea = document.getElementById('grammarText');
    const text = textarea.value;
    textarea.value =
        text.substring(0, offset) +
        replacement +
        text.substring(offset + length);
    updateCount();
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Copied to clipboard!');
    });
}

// Offline basic check
function checkOffline() {
    const text = document.getElementById('grammarText').value;
    const errors = [];
    const words = text.split(/\s+/);

    // Common grammar rules
    const rules = [
        {
            pattern: /\bi goes\b/gi,
            msg: '"I goes" should be "I go"',
            fix: 'I go', type: 'grammar'
        },
        {
            pattern: /\bhe go\b/gi,
            msg: '"he go" should be "he goes"',
            fix: 'he goes', type: 'grammar'
        },
        {
            pattern: /\bshe go\b/gi,
            msg: '"she go" should be "she goes"',
            fix: 'she goes', type: 'grammar'
        },
        {
            pattern: /\bdoesnt\b/gi,
            msg: 'Missing apostrophe: "doesnt" → "doesn\'t"',
            fix: "doesn't", type: 'spelling'
        },
        {
            pattern: /\bdont\b/gi,
            msg: 'Missing apostrophe: "dont" → "don\'t"',
            fix: "don't", type: 'spelling'
        },
        {
            pattern: /\bcant\b/gi,
            msg: 'Missing apostrophe: "cant" → "can\'t"',
            fix: "can't", type: 'spelling'
        },
        {
            pattern: /\bwont\b/gi,
            msg: 'Missing apostrophe: "wont" → "won\'t"',
            fix: "won't", type: 'spelling'
        },
        {
            pattern: /\bi has\b/gi,
            msg: '"I has" should be "I have"',
            fix: 'I have', type: 'grammar'
        },
        {
            pattern: /\bthey was\b/gi,
            msg: '"they was" should be "they were"',
            fix: 'they were', type: 'grammar'
        },
        {
            pattern: /\bwe was\b/gi,
            msg: '"we was" should be "we were"',
            fix: 'we were', type: 'grammar'
        },
        {
            pattern: /\ba [aeiou]/gi,
            msg: 'Use "an" before vowel sounds',
            fix: 'an', type: 'grammar'
        },
        {
            pattern: /\s{2,}/g,
            msg: 'Extra spaces detected',
            fix: ' ', type: 'style'
        },
    ];

    let errorsHTML = '';
    let errorCount = 0;
    let corrected = text;

    rules.forEach(rule => {
        const matches = text.match(rule.pattern);
        if (matches) {
            matches.forEach(match => {
                errorCount++;
                errorsHTML += `
                    <div class="error-item ${rule.type}">
                        <div class="error-header">
                            <span class="error-type type-${rule.type}">
                                ${rule.type}
                            </span>
                        </div>
                        <div class="error-msg">${rule.msg}</div>
                        <div class="error-context">
                            Found: <span class="wrong">"${match}"</span>
                            → <span class="right">"${rule.fix}"</span>
                        </div>
                    </div>`;
            });
            corrected = corrected.replace(rule.pattern, rule.fix);
        }
    });

    const score = Math.max(0,
        Math.round(100-(errorCount/words.length*100)));
    const scoreColor = score>=80 ? '#00cc66' :
        score>=60 ? '#ffd700' : '#ff4444';

    const resultContent = document.getElementById('resultContent');

    if (errorCount === 0) {
        resultContent.innerHTML = `
            <div class="no-errors">
                <div class="icon">✅</div>
                <h3>No common errors found!</h3>
                <p style="color:#888">
                    Enable internet for full grammar check
                    using LanguageTool.
                </p>
            </div>`;
        return;
    }

    resultContent.innerHTML = `
        <div style="text-align:center; margin-bottom:20px">
            <div style="font-size:60px; font-weight:900;
                color:${scoreColor}">${score}</div>
            <div style="color:#888; font-size:14px">
                Grammar Score / 100 (Offline)
            </div>
        </div>
        ${corrected !== text ? `
        <div class="corrected-box">
            <h3 style="color:#00cc66">✅ Corrected Version</h3>
            <div class="corrected-text">${corrected}</div>
        </div>` : ''}
        <div class="errors-list">${errorsHTML}</div>`;
}
</script>
</body>
</html>