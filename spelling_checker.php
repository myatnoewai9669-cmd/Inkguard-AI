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
    <title>Spelling Checker - InkGuard</title>
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
            background:linear-gradient(135deg,#ffd700,#ff6b35);
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
            border-color:rgba(255,215,0,0.5);
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
            background:linear-gradient(135deg,#ffd700,#ff9800);
            color:#000; border:none; border-radius:12px;
            font-size:15px; font-weight:700; cursor:pointer;
            transition:all 0.3s;
            box-shadow:0 4px 20px rgba(255,215,0,0.3);
        }
        .btn-check:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(255,215,0,0.5);
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
            text-align:center;
            border:1px solid rgba(255,255,255,0.06);
        }
        .stat-tile .num {
            font-size:28px; font-weight:900; line-height:1;
        }
        .stat-tile .lbl {
            font-size:11px; color:#888; margin-top:4px;
        }
        .highlighted-text {
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:20px;
            line-height:2; font-size:14px;
            color:#ccc; margin-bottom:20px;
        }
        .misspelled {
            background:rgba(255,68,68,0.25);
            border-bottom:2px solid #ff4444;
            color:#ff6b6b; padding:1px 3px;
            border-radius:3px; cursor:pointer;
            position:relative;
        }
        .misspelled:hover {
            background:rgba(255,68,68,0.4);
        }
        .correct-word {
            background:rgba(0,204,102,0.15);
            border-bottom:2px solid #00cc66;
            color:#00ff88; padding:1px 3px;
            border-radius:3px;
        }
        .word-list {
            display:flex; flex-direction:column; gap:12px;
        }
        .word-item {
            background:rgba(0,0,0,0.3);
            border-radius:12px; padding:16px;
            border-left:4px solid #ffd700;
        }
        .word-header {
            display:flex; align-items:center;
            gap:10px; margin-bottom:10px;
        }
        .wrong-word {
            font-size:16px; font-weight:700;
            color:#ff6b6b;
            text-decoration:line-through;
        }
        .arrow { color:#888; font-size:14px; }
        .word-msg {
            font-size:13px; color:#aaa; margin-bottom:10px;
        }
        .suggestions {
            display:flex; gap:6px; flex-wrap:wrap;
        }
        .suggestion-tag {
            padding:5px 12px;
            background:rgba(255,215,0,0.1);
            border:1px solid rgba(255,215,0,0.3);
            border-radius:6px; font-size:13px;
            color:#ffd700; cursor:pointer;
            transition:all 0.2s; font-weight:600;
        }
        .suggestion-tag:hover {
            background:rgba(255,215,0,0.25);
            color:#fff;
        }
        .corrected-box {
            background:rgba(0,204,102,0.05);
            border:1px solid rgba(0,204,102,0.2);
            border-radius:12px; padding:20px;
            margin-bottom:20px;
        }
        .no-errors {
            text-align:center; padding:40px;
        }
        .no-errors .icon { font-size:70px; margin-bottom:12px; }
        .no-errors h3 {
            font-size:22px; color:#00cc66; margin-bottom:8px;
        }
        .no-errors p { color:#888; font-size:14px; }
        .loading-wrap { text-align:center; padding:30px; }
        .spinner {
            width:40px; height:40px;
            border:3px solid rgba(255,215,0,0.2);
            border-top-color:#ffd700;
            border-radius:50%;
            animation:spin 0.8s linear infinite;
            margin:0 auto 14px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }
        .copy-btn {
            background:rgba(0,204,102,0.2);
            border:1px solid rgba(0,204,102,0.3);
            color:#00cc66; padding:6px 14px;
            border-radius:6px; cursor:pointer;
            font-size:12px; font-weight:600;
        }
        @media(max-width:600px) {
            .stats-bar{grid-template-columns:repeat(2,1fr)}
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
        <a href="grammar_checker.php" class="nav-link">✏️ Grammar</a>
        <a href="logout.php" class="nav-link danger">🚪 Logout</a>
    </div>
</nav>

<div class="page">

    <div class="page-title">
        <h1>🔤 Spelling Checker</h1>
        <p>Find and fix spelling mistakes in your text</p>
    </div>

    <div class="glass-card">
        <h2>📝 Enter Your Text</h2>

        <textarea class="text-input" id="spellText"
            placeholder="Type or paste your text here to check spelling...

Example:
I recieved a leter from my freind yesterday. He writed that he woud visted us next weak."
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
                    onclick="checkSpelling()">
                    🔤 Check Spelling
                </button>
            </div>
        </div>
    </div>

    <div class="result-section" id="resultSection">
        <div class="glass-card">
            <h2>📊 Spelling Report</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
// Common misspellings dictionary
const commonMisspellings = {
    'recieve': 'receive',
    'beleive': 'believe',
    'freind': 'friend',
    'wierd': 'weird',
    'thier': 'their',
    'occured': 'occurred',
    'seperate': 'separate',
    'definately': 'definitely',
    'goverment': 'government',
    'occassion': 'occasion',
    'accomodate': 'accommodate',
    'becuase': 'because',
    'untill': 'until',
    'begining': 'beginning',
    'occuring': 'occurring',
    'persue': 'pursue',
    'grammer': 'grammar',
    'writting': 'writing',
    'writed': 'wrote',
    'woud': 'would',
    'visted': 'visited',
    'leter': 'letter',
    'recieved': 'received',
    'aswell': 'as well',
    'alot': 'a lot',
    'alright': 'all right',
    'enviroment': 'environment',
    'knowlege': 'knowledge',
    'responsibilty': 'responsibility',
    'succesful': 'successful',
    'successfull': 'successful',
    'tommorrow': 'tomorrow',
    'tommorow': 'tomorrow',
    'yesterday': 'yesterday',
    'calender': 'calendar',
    'catagory': 'category',
    'commitee': 'committee',
    'concious': 'conscious',
    'embarass': 'embarrass',
    'existance': 'existence',
    'foriegn': 'foreign',
    'gaurd': 'guard',
    'harrass': 'harass',
    'imediately': 'immediately',
    'innoculate': 'inoculate',
    'liason': 'liaison',
    'maintainance': 'maintenance',
    'millenium': 'millennium',
    'miniature': 'miniature',
    'mischievious': 'mischievous',
    'neccessary': 'necessary',
    'noticable': 'noticeable',
    'occassionally': 'occasionally',
    'paralell': 'parallel',
    'passtime': 'pastime',
    'peice': 'piece',
    'percieve': 'perceive',
    'privalege': 'privilege',
    'pronounciation': 'pronunciation',
    'publically': 'publicly',
    'questionaire': 'questionnaire',
    'reccommend': 'recommend',
    'refered': 'referred',
    'relevent': 'relevant',
    'restaraunt': 'restaurant',
    'rythm': 'rhythm',
    'sargent': 'sergeant',
    'sieze': 'seize',
    'similer': 'similar',
    'speach': 'speech',
    'strenght': 'strength',
    'supercede': 'supersede',
    'tendancy': 'tendency',
    'threshhold': 'threshold',
    'truely': 'truly',
    'tyrany': 'tyranny',
    'underate': 'underrate',
    'unfortunatly': 'unfortunately',
    'usefull': 'useful',
    'vacum': 'vacuum',
    'visious': 'vicious',
    'weak': 'week',
    'whether': 'whether',
    'wich': 'which',
    'writen': 'written',
    'yeild': 'yield',
    'acomodate': 'accommodate',
    'adress': 'address',
    'arguement': 'argument',
    'athiest': 'atheist',
    'atheletic': 'athletic',
    'beautifull': 'beautiful',
    'buisness': 'business',
    'cemetary': 'cemetery',
    'changable': 'changeable',
    'collegue': 'colleague',
    'completly': 'completely',
    'congradulations': 'congratulations',
    'continous': 'continuous',
    'decieve': 'deceive',
    'definate': 'definite',
    'desparate': 'desperate',
    'diferent': 'different',
    'dissapear': 'disappear',
    'dissapoint': 'disappoint',
    'doesnt': "doesn't",
    'dont': "don't",
    'cant': "can't",
    'wont': "won't",
    'isnt': "isn't",
    'wasnt': "wasn't",
    'werent': "weren't",
    'havent': "haven't",
    'hasnt': "hasn't",
    'didnt': "didn't",
    'wouldnt': "wouldn't",
    'couldnt': "couldn't",
    'shouldnt': "shouldn't",
    'ive': "I've",
    'im': "I'm",
    'id': "I'd",
    'ill': "I'll",
};

function updateCount() {
    const text = document.getElementById('spellText').value;
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    document.getElementById('wordCount').textContent = words;
    document.getElementById('charCount').textContent = text.length;
}

function clearAll() {
    document.getElementById('spellText').value = '';
    document.getElementById('wordCount').textContent = '0';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('resultSection').style.display = 'none';
}

async function checkSpelling() {
    const text = document.getElementById('spellText').value.trim();
    if (!text) { alert('Please enter some text!'); return; }

    const btn = document.getElementById('checkBtn');
    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');

    btn.disabled = true;
    btn.textContent = '⏳ Checking...';
    resultSection.style.display = 'block';
    resultContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#ffd700; font-weight:600">
                🔤 Checking spelling...
            </p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    // Try LanguageTool API first
    try {
        const formData = new URLSearchParams();
        formData.append('text', text);
        formData.append('language', 'en-US');
        formData.append('enabledRules', 'MORFOLOGIK_RULE_EN_US');

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

        // Filter spelling only
        const spellingErrors = data.matches.filter(m =>
            m.rule?.category?.id?.includes('SPELL') ||
            m.rule?.issueType === 'misspelling'
        );

        if (spellingErrors.length > 0) {
            displayAPIResults(text, spellingErrors);
        } else {
            // Also check local dictionary
            const localErrors = checkLocalDictionary(text);
            if (localErrors.length > 0) {
                displayLocalResults(text, localErrors);
            } else {
                displayNoErrors();
            }
        }

    } catch (error) {
        // Fallback to local dictionary
        const localErrors = checkLocalDictionary(text);
        if (localErrors.length > 0) {
            displayLocalResults(text, localErrors);
        } else {
            displayNoErrors();
        }
    }

    btn.disabled = false;
    btn.textContent = '🔤 Check Spelling';
}

function checkLocalDictionary(text) {
    const errors = [];
    const words = text.split(/\b/);

    words.forEach((word, idx) => {
        const clean = word.toLowerCase().trim();
        if (commonMisspellings[clean]) {
            // Find position in original text
            let pos = 0;
            for (let i = 0; i < idx; i++) pos += words[i].length;

            errors.push({
                word: word,
                suggestion: commonMisspellings[clean],
                offset: pos,
                length: word.length
            });
        }
    });

    return errors;
}

function displayAPIResults(text, errors) {
    const wordCount = text.trim().split(/\s+/).length;
    const score = Math.max(0,
        Math.round(100 - (errors.length / wordCount * 150)));
    const scoreColor = score >= 80 ? '#00cc66' :
        score >= 60 ? '#ffd700' : '#ff4444';

    // Build highlighted text
    let highlighted = text;
    const sorted = [...errors].sort((a,b) => b.offset - a.offset);
    sorted.forEach(m => {
        const wrong = text.substring(m.offset, m.offset+m.length);
        const suggestion = m.replacements?.[0]?.value || '';
        highlighted =
            highlighted.substring(0, m.offset) +
            `<span class="misspelled" title="${suggestion}">
                ${wrong}
            </span>` +
            highlighted.substring(m.offset + m.length);
    });

    // Corrected text
    let corrected = text;
    sorted.forEach(m => {
        if (m.replacements?.[0]) {
            corrected =
                corrected.substring(0, m.offset) +
                m.replacements[0].value +
                corrected.substring(m.offset + m.length);
        }
    });

    // Errors HTML
    let errorsHTML = '';
    errors.forEach((m, idx) => {
        const wrong = text.substring(m.offset, m.offset+m.length);
        const suggestions = m.replacements?.slice(0,5)
            .map(r => `
                <span class="suggestion-tag"
                    onclick="applyFix(${m.offset},${m.length},
                    '${r.value.replace(/'/g,"\\'")}')">
                    ${r.value}
                </span>`).join('') ||
            '<span style="color:#555">No suggestions</span>';

        errorsHTML += `
            <div class="word-item">
                <div class="word-header">
                    <span class="wrong-word">${wrong}</span>
                    <span class="arrow">→</span>
                    <span style="color:#00ff88; font-weight:700">
                        ${m.replacements?.[0]?.value || '?'}
                    </span>
                </div>
                <div class="word-msg">${m.message}</div>
                <div class="suggestions">${suggestions}</div>
            </div>`;
    });

    document.getElementById('resultContent').innerHTML = `
        <!-- Score -->
        <div style="text-align:center; margin-bottom:20px">
            <div style="font-size:60px; font-weight:900;
                color:${scoreColor}">${score}</div>
            <div style="color:#888; font-size:14px">
                Spelling Score / 100
            </div>
            <div style="height:8px; background:#333;
                border-radius:99px; overflow:hidden;
                margin:12px auto; max-width:300px">
                <div style="width:${score}%; height:100%;
                    background:${scoreColor}; border-radius:99px;
                    transition:width 1s ease"></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-tile">
                <div class="num" style="color:#ffd700">
                    ${wordCount}
                </div>
                <div class="lbl">Total Words</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ff4444">
                    ${errors.length}
                </div>
                <div class="lbl">Misspelled</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66">
                    ${wordCount - errors.length}
                </div>
                <div class="lbl">Correct</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:${scoreColor}">
                    ${score}%
                </div>
                <div class="lbl">Accuracy</div>
            </div>
        </div>

        <!-- Highlighted -->
        <div class="glass-card" style="margin-bottom:20px">
            <h3 style="color:#ffd700; margin-bottom:12px">
                🔍 Text with Errors Highlighted
            </h3>
            <div class="highlighted-text">${highlighted}</div>
        </div>

        <!-- Corrected -->
        ${corrected !== text ? `
        <div class="corrected-box">
            <div style="display:flex; justify-content:space-between;
                align-items:center; margin-bottom:12px">
                <h3 style="color:#00cc66; margin:0">
                    ✅ Corrected Text
                </h3>
                <button class="copy-btn"
                    onclick="copyText()">
                    📋 Copy
                </button>
            </div>
            <div id="correctedOutput"
                style="color:#ccc; line-height:1.8; font-size:14px">
                ${corrected}
            </div>
        </div>` : ''}

        <!-- Word List -->
        <h3 style="color:#fff; font-size:16px; margin-bottom:12px">
            ❌ Misspelled Words (${errors.length})
        </h3>
        <div class="word-list">${errorsHTML}</div>
    `;
}

function displayLocalResults(text, errors) {
    const wordCount = text.trim().split(/\s+/).length;
    const score = Math.max(0,
        Math.round(100 - (errors.length / wordCount * 150)));
    const scoreColor = score >= 80 ? '#00cc66' :
        score >= 60 ? '#ffd700' : '#ff4444';

    let corrected = text;
    let errorsHTML = '';

    errors.forEach(e => {
        corrected = corrected.replace(
            new RegExp('\\b' + e.word + '\\b', 'gi'),
            e.suggestion);

        errorsHTML += `
            <div class="word-item">
                <div class="word-header">
                    <span class="wrong-word">${e.word}</span>
                    <span class="arrow">→</span>
                    <span style="color:#00ff88; font-weight:700">
                        ${e.suggestion}
                    </span>
                </div>
                <div class="word-msg">
                    Possible misspelling detected
                </div>
                <div class="suggestions">
                    <span class="suggestion-tag"
                        onclick="replaceWord('${e.word}',
                        '${e.suggestion}')">
                        ${e.suggestion}
                    </span>
                </div>
            </div>`;
    });

    // Highlight errors in text
    let highlighted = text;
    errors.forEach(e => {
        highlighted = highlighted.replace(
            new RegExp('\\b' + e.word + '\\b', 'gi'),
            `<span class="misspelled">${e.word}</span>`);
    });

    document.getElementById('resultContent').innerHTML = `
        <div style="text-align:center; margin-bottom:20px">
            <div style="font-size:60px; font-weight:900;
                color:${scoreColor}">${score}</div>
            <div style="color:#888; font-size:14px">
                Spelling Score / 100
            </div>
            <div style="height:8px; background:#333;
                border-radius:99px; overflow:hidden;
                margin:12px auto; max-width:300px">
                <div style="width:${score}%; height:100%;
                    background:${scoreColor}; border-radius:99px">
                </div>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-tile">
                <div class="num" style="color:#ffd700">
                    ${wordCount}
                </div>
                <div class="lbl">Total Words</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ff4444">
                    ${errors.length}
                </div>
                <div class="lbl">Misspelled</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66">
                    ${wordCount - errors.length}
                </div>
                <div class="lbl">Correct</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:${scoreColor}">
                    ${score}%
                </div>
                <div class="lbl">Accuracy</div>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:20px">
            <h3 style="color:#ffd700; margin-bottom:12px">
                🔍 Highlighted Errors
            </h3>
            <div class="highlighted-text">${highlighted}</div>
        </div>

        ${corrected !== text ? `
        <div class="corrected-box">
            <div style="display:flex; justify-content:space-between;
                align-items:center; margin-bottom:12px">
                <h3 style="color:#00cc66; margin:0">
                    ✅ Corrected Text
                </h3>
                <button class="copy-btn" onclick="copyText()">
                    📋 Copy
                </button>
            </div>
            <div id="correctedOutput"
                style="color:#ccc; line-height:1.8; font-size:14px">
                ${corrected}
            </div>
        </div>` : ''}

        <h3 style="color:#fff; font-size:16px; margin-bottom:12px">
            ❌ Misspelled Words (${errors.length})
        </h3>
        <div class="word-list">${errorsHTML}</div>
    `;
}

function displayNoErrors() {
    document.getElementById('resultContent').innerHTML = `
        <div class="no-errors">
            <div class="icon">✅</div>
            <h3>Perfect Spelling!</h3>
            <p>No spelling mistakes found in your text.</p>
            <p style="color:#ffd700; margin-top:8px; font-size:13px">
                Score: 100/100 🎉
            </p>
        </div>`;
}

function applyFix(offset, length, replacement) {
    const textarea = document.getElementById('spellText');
    const text = textarea.value;
    textarea.value =
        text.substring(0, offset) +
        replacement +
        text.substring(offset + length);
    updateCount();
}

function replaceWord(wrong, right) {
    const textarea = document.getElementById('spellText');
    textarea.value = textarea.value.replace(
        new RegExp('\\b' + wrong + '\\b', 'gi'), right);
    updateCount();
}

function copyText() {
    const text = document.getElementById('correctedOutput')?.innerText;
    if (text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✅ Copied!');
        });
    }
}
</script>
</body>
</html>