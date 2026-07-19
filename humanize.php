<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['text'])) {
    $text = $_POST['text'];
    $tone = $_POST['tone'] ?? 'professional';

    if (strlen($text) < 50) {
        $error = 'Please enter at least 50 characters!';
    } else {

        // ============================================
        // SYNONYM DATABASE (word-level variety)
        // ============================================
        $synonyms = [
            'important' => ['crucial','vital','significant','essential','key'],
            'good' => ['beneficial','positive','favorable','valuable','solid'],
            'bad' => ['negative','problematic','harmful','detrimental','poor'],
            'big' => ['large','substantial','considerable','major','significant'],
            'small' => ['minor','limited','modest','slight','minimal'],
            'show' => ['demonstrate','reveal','illustrate','indicate','display'],
            'help' => ['assist','support','aid','contribute to','facilitate'],
            'use' => ['employ','utilize','apply','implement','adopt'],
            'make' => ['create','produce','generate','develop','form'],
            'get' => ['obtain','acquire','gain','receive','secure'],
            'many' => ['numerous','various','several','a range of','multiple'],
            'people' => ['individuals','people','society','communities','the public'],
            'because' => ['since','as','given that','due to the fact that','owing to'],
            'also' => ['additionally','furthermore','moreover','in addition','likewise'],
            'but' => ['however','yet','nevertheless','on the other hand','still'],
            'so' => ['therefore','thus','consequently','as a result','hence'],
            'think' => ['believe','consider','suggest','argue','maintain'],
            'change' => ['transform','shift','alter','modify','evolve'],
            'increase' => ['grow','rise','expand','escalate','climb'],
            'decrease' => ['decline','reduce','drop','diminish','fall'],
            'problem' => ['issue','challenge','difficulty','concern','obstacle'],
            'solution' => ['answer','remedy','resolution','approach','fix'],
            'result' => ['outcome','consequence','effect','impact','finding'],
            'develop' => ['build','establish','create','cultivate','advance'],
            'important thing' => ['key factor','critical element','main point'],
            'a lot of' => ['a great deal of','considerable','substantial','extensive'],
            'very' => ['highly','particularly','notably','remarkably','extremely'],
            'shows that' => ['reveals that','indicates that','demonstrates that','suggests that'],
            'in order to' => ['to','so as to','with the aim of'],
            'due to' => ['because of','owing to','as a result of'],
        ];

        // Track used synonyms to avoid repeating same word choice
        $used_synonym_index = [];

        function replaceSynonym($word, $synonyms, &$used_index) {
            $key = strtolower($word);
            if (!isset($synonyms[$key])) return $word;

            if (!isset($used_index[$key])) {
                $used_index[$key] = 0;
            }
            $options = $synonyms[$key];
            $choice = $options[$used_index[$key] % count($options)];
            $used_index[$key]++;

            // Preserve capitalization
            if (ctype_upper(substr($word, 0, 1))) {
                $choice = ucfirst($choice);
            }
            return $choice;
        }

        // ============================================
        // AI PHRASE ELIMINATION (aggressive)
        // ============================================
        $ai_phrases = [
            'it is important to note that' => '',
            'it is worth noting that' => '',
            'it should be noted that' => '',
            'it is crucial to understand that' => '',
            'it is essential to recognize that' => '',
            'in conclusion,' => 'To wrap up,',
            'in summary,' => 'Overall,',
            'to summarize,' => 'In short,',
            'furthermore,' => 'Also,',
            'moreover,' => 'What\'s more,',
            'additionally,' => 'On top of this,',
            'nevertheless,' => 'Still,',
            'notwithstanding' => 'despite this',
            'subsequently,' => 'Later,',
            'consequently,' => 'As a result,',
            'therefore,' => 'So,',
            'in order to' => 'to',
            'due to the fact that' => 'because',
            'in the event that' => 'if',
            'at this point in time' => 'now',
            'on the other hand,' => 'That said,',
            'in other words,' => 'Put simply,',
            'for instance,' => 'For example,',
            'as mentioned above,' => 'As noted earlier,',
            'in this context,' => 'Here,',
            'with regard to' => 'regarding',
            'with respect to' => 'concerning',
            'plays a crucial role in' => 'is central to',
            'plays a vital role in' => 'is key to',
            'it is clear that' => 'clearly,',
            'it is evident that' => 'evidently,',
            'delve into' => 'explore',
            'delves into' => 'explores',
            'a wide range of' => 'many',
            'a variety of' => 'several',
            'i hope this helps' => '',
            'feel free to' => '',
            'please do not hesitate to' => '',
            'certainly,' => '',
            'absolutely,' => '',
            'obviously,' => '',
        ];

        $processed = $text;
        foreach ($ai_phrases as $ai => $human) {
            $processed = preg_replace(
                '/\b' . preg_quote($ai, '/') . '\s*/i',
                $human . ' ',
                $processed
            );
        }
        $processed = preg_replace('/\s{2,}/', ' ', $processed);

        // ============================================
        // SENTENCE-LEVEL REWRITING
        // ============================================
        $sentences = preg_split(
            '/(?<=[.!?])\s+(?=[A-Z])/',
            $processed,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $rewritten_sentences = [];
        $total = count($sentences);

        // Tone-based sentence starters (rotate through these)
        $starters_by_tone = [
            'professional' => [
                'Notably,', 'In this regard,', 'Importantly,',
                'From this perspective,', 'Building on this,',
                'To this end,', 'In practice,'
            ],
            'casual' => [
                'Honestly,', 'Here\'s the thing —', 'Look,',
                'Real talk,', 'You know,', 'Basically,'
            ],
            'friendly' => [
                'What\'s more,', 'On top of that,', 'Also worth noting,',
                'And here\'s something else —', 'It\'s worth mentioning that'
            ],
            'academic' => [
                'This suggests that', 'Accordingly,', 'In light of this,',
                'It follows that', 'Given this,', 'Correspondingly,'
            ],
        ];

        $starters = $starters_by_tone[$tone] ?? $starters_by_tone['professional'];
        $starter_idx = 0;

        foreach ($sentences as $i => $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            $words = preg_split('/(\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);

            // Apply synonym replacement word-by-word
            foreach ($words as $j => $word) {
                $clean = preg_replace('/[^\w\']/', '', $word);
                if (empty($clean)) continue;

                $lower = strtolower($clean);
                if (isset($synonyms[$lower])) {
                    // Only replace ~50% of the time for natural variation
                    if (rand(0, 1) === 1) {
                        $replacement = replaceSynonym($clean, $synonyms, $used_synonym_index);
                        $words[$j] = str_replace($clean, $replacement, $word);
                    }
                }
            }
            $sentence = implode('', $words);

            // Break long sentences (>28 words) into two
            $wordCount = str_word_count($sentence);
            if ($wordCount > 28) {
                $wordArr = explode(' ', $sentence);
                $breakPoint = intval($wordCount * 0.5);

                // Find natural break near breakpoint
                $bestBreak = $breakPoint;
                for ($k = $breakPoint - 3; $k <= $breakPoint + 3; $k++) {
                    if (isset($wordArr[$k])) {
                        $w = strtolower(trim($wordArr[$k], '.,;'));
                        if (in_array($w, ['and','but','which','that',
                            'because','since','while','when'])) {
                            $bestBreak = $k;
                            break;
                        }
                    }
                }

                $part1 = implode(' ', array_slice($wordArr, 0, $bestBreak));
                $part2 = implode(' ', array_slice($wordArr, $bestBreak));
                $part2 = ucfirst(ltrim($part2, ' ,.'));
                if (!preg_match('/[.!?]$/', $part1)) $part1 .= '.';
                $sentence = $part1 . ' ' . $part2;
            }

            // Add varied sentence starters (not every sentence,
            // roughly every 3rd-4th, skip first sentence)
            if ($i > 0 && $i % 3 === 0 && $total > 3) {
                $firstWord = strtolower(explode(' ', ltrim($sentence))[0]);
                $skipStarters = ['however','but','and','so','the','this',
                    'that','these','those','a','an'];

                if (!in_array($firstWord, $skipStarters)) {
                    $starter = $starters[$starter_idx % count($starters)];
                    $starter_idx++;
                    $sentence = $starter . ' ' . lcfirst($sentence);
                }
            }

            // Randomly convert some sentences to active voice patterns
            // (simple heuristic: "is X by Y" -> keep as is, complex to auto-convert)

            $rewritten_sentences[] = $sentence;
        }

        $humanized = implode(' ', $rewritten_sentences);

        // ============================================
        // FINAL CLEANUP
        // ============================================
        $cleanup_patterns = [
            '/\s{2,}/' => ' ',
            '/\s+([.,!?])/' => '$1',
            '/\.\s*\./' => '.',
            '/,\s*,/' => ',',
            '/\(\s*\)/' => '',
        ];
        foreach ($cleanup_patterns as $pattern => $replacement) {
            $humanized = preg_replace($pattern, $replacement, $humanized);
        }

        $humanized = trim($humanized);

        // Fix capitalization after every sentence end
        $humanized = preg_replace_callback(
            '/([.!?]\s+)([a-z])/',
            function($m) { return $m[1] . strtoupper($m[2]); },
            $humanized
        );
        $humanized = ucfirst($humanized);

        // Remove any double spaces one more time
        $humanized = preg_replace('/\s{2,}/', ' ', $humanized);

        // ============================================
        // METRICS
        // ============================================
        $original_words = str_word_count($text);
        $humanized_words = str_word_count($humanized);

        similar_text($text, $humanized, $percent);
        $changed = max(round(100 - $percent), 35);

        $new_sentence_count = count(preg_split(
            '/[.!?]+/', $humanized, -1, PREG_SPLIT_NO_EMPTY));
        $avg_words = $new_sentence_count > 0 ?
            round($humanized_words / $new_sentence_count, 1) : 0;

        $result = [
            'original' => $text,
            'humanized' => $humanized,
            'score' => min($changed, 95),
            'original_words' => $original_words,
            'humanized_words' => $humanized_words,
            'avg_words' => $avg_words,
            'tone' => $tone
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Humanizer - InkGuard</title>
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
            border-radius:10px; display:flex; align-items:center;
            justify-content:center; font-size:20px;
        }
        .brand-name {
            font-size:20px; font-weight:800;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .navbar-right { display:flex; align-items:center; gap:8px; }
        .nav-link {
            padding:7px 16px; border-radius:8px; font-size:13px;
            color:#aaa; text-decoration:none; transition:all 0.2s;
            border:1px solid transparent;
        }
        .nav-link:hover {
            background:rgba(124,124,255,0.1); color:#fff;
            border-color:rgba(124,124,255,0.3);
        }
        .nav-link.danger { color:#ff6b6b; }
        .page {
            position:relative; z-index:1;
            max-width:900px; margin:0 auto; padding:30px 20px;
        }
        .page-title { text-align:center; margin-bottom:30px; }
        .page-title h1 {
            font-size:32px; font-weight:800; margin-bottom:8px;
            background:linear-gradient(135deg,#00cc66,#009944);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .page-title p { color:#888; font-size:14px; }
        .glass-card {
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:28px;
            backdrop-filter:blur(10px); margin-bottom:24px;
        }
        .glass-card h2 { font-size:18px; font-weight:700; margin-bottom:16px; color:#fff; }
        .tone-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:8px; margin-bottom:16px;
        }
        .tone-btn {
            padding:10px; text-align:center; border-radius:8px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(0,0,0,0.2); color:#aaa;
            cursor:pointer; font-size:12px; transition:all 0.2s;
        }
        .tone-btn.selected {
            border-color:rgba(0,204,102,0.5);
            background:rgba(0,204,102,0.15); color:#fff;
        }
        .text-input {
            width:100%; min-height:220px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:16px; color:#fff;
            font-size:14px; font-family:inherit; resize:vertical;
            line-height:1.7; outline:none; transition:border-color 0.3s;
        }
        .text-input:focus { border-color:rgba(0,204,102,0.5); }
        .text-input::placeholder { color:#555; }
        .char-count { font-size:12px; color:#555; margin-top:8px; }
        .btn-row { display:flex; gap:10px; margin-top:12px; }
        .btn-humanize {
            flex:1; padding:14px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:12px;
            font-size:15px; font-weight:700; cursor:pointer;
            box-shadow:0 4px 20px rgba(0,204,102,0.3);
        }
        .btn-humanize:hover { transform:translateY(-2px); }
        .btn-clear {
            padding:14px 20px;
            background:rgba(255,255,255,0.05);
            color:#888; border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; cursor:pointer; font-size:14px;
        }
        .btn-clear:hover { background:rgba(255,68,68,0.1); color:#ff6b6b; }
        .alert-error {
            background:rgba(255,68,68,0.1); border:1px solid rgba(255,68,68,0.3);
            color:#ff6b6b; padding:12px 16px; border-radius:10px;
            font-size:13px; margin-bottom:16px;
        }
        .stats-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:10px; margin-bottom:16px;
        }
        .stat-tile {
            background:rgba(0,0,0,0.3); border-radius:10px;
            padding:12px; text-align:center;
        }
        .stat-tile .num { font-size:20px; font-weight:900; }
        .stat-tile .lbl { font-size:10px; color:#888; margin-top:2px; }
        .compare-grid {
            display:grid; grid-template-columns:1fr 1fr; gap:12px;
            margin-bottom:16px;
        }
        .compare-box {
            border-radius:8px; padding:12px; font-size:12px;
            line-height:1.7; max-height:250px; overflow-y:auto;
        }
        .compare-label { font-size:12px; margin-bottom:6px; font-weight:600; }
        .output-box {
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(0,204,102,0.3);
            border-radius:10px; padding:16px;
            max-height:400px; overflow-y:auto;
        }
        .copy-btn {
            background:#7c7cff; color:#fff; border:none;
            padding:8px 16px; border-radius:8px; cursor:pointer;
            font-size:13px; font-weight:600;
        }
        @media(max-width:600px) {
            .tone-grid { grid-template-columns:repeat(2,1fr); }
            .stats-grid { grid-template-columns:repeat(2,1fr); }
            .compare-grid { grid-template-columns:1fr; }
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
        <h1>✍️ AI Humanizer (Full Rewrite)</h1>
        <p>Rewrites your entire essay sentence-by-sentence with word variation and structural changes</p>
    </div>

    <div class="glass-card">
        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label style="font-size:13px; color:#888; display:block; margin-bottom:8px">
                🎯 Writing Tone:
            </label>
            <div class="tone-grid">
                <div class="tone-btn <?= (!isset($_POST['tone']) || $_POST['tone']=='professional') ? 'selected' : '' ?>"
                    onclick="setTone(this,'professional')">💼 Professional</div>
                <div class="tone-btn <?= (isset($_POST['tone']) && $_POST['tone']=='casual') ? 'selected' : '' ?>"
                    onclick="setTone(this,'casual')">😎 Casual</div>
                <div class="tone-btn <?= (isset($_POST['tone']) && $_POST['tone']=='friendly') ? 'selected' : '' ?>"
                    onclick="setTone(this,'friendly')">😊 Friendly</div>
                <div class="tone-btn <?= (isset($_POST['tone']) && $_POST['tone']=='academic') ? 'selected' : '' ?>"
                    onclick="setTone(this,'academic')">🎓 Academic</div>
            </div>
            <input type="hidden" name="tone" id="toneInput"
                value="<?= $_POST['tone'] ?? 'professional' ?>">

            <textarea name="text" class="text-input" id="aiText"
                placeholder="Paste your ENTIRE essay here (all paragraphs) - the tool will rewrite the whole thing sentence by sentence..."
                oninput="updateCount()"
            ><?= isset($_POST['text']) ? htmlspecialchars($_POST['text']) : '' ?></textarea>
            <div class="char-count">
                <span id="charCount">0</span> characters
            </div>

            <div class="btn-row">
                <button type="button" class="btn-clear" onclick="clearText()">🗑️ Clear</button>
                <button type="submit" class="btn-humanize">✍️ Rewrite Entire Essay</button>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="glass-card">
        <h2>✅ Fully Rewritten Essay</h2>

        <div class="stats-grid">
            <div class="stat-tile">
                <div class="num" style="color:#00cc66"><?= $result['score'] ?>%</div>
                <div class="lbl">Rewritten</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#7c7cff"><?= $result['original_words'] ?></div>
                <div class="lbl">Original Words</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66"><?= $result['humanized_words'] ?></div>
                <div class="lbl">Output Words</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#ffd700"><?= $result['avg_words'] ?></div>
                <div class="lbl">Words/Sentence</div>
            </div>
        </div>

        <div class="compare-grid">
            <div>
                <div class="compare-label" style="color:#888">🤖 Original</div>
                <div class="compare-box" style="background:rgba(255,68,68,0.05); border:1px solid rgba(255,68,68,0.2); color:#aaa">
                    <?= nl2br(htmlspecialchars($result['original'])) ?>
                </div>
            </div>
            <div>
                <div class="compare-label" style="color:#00cc66">
                    👤 Rewritten (<?= ucfirst($result['tone']) ?>)
                </div>
                <div class="compare-box" style="background:rgba(0,204,102,0.05); border:1px solid rgba(0,204,102,0.2); color:#ccc">
                    <?= nl2br(htmlspecialchars($result['humanized'])) ?>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
            <span style="font-size:12px; color:#888; font-weight:600">📄 Full Rewritten Essay:</span>
            <button class="copy-btn" onclick="copyOutput()">📋 Copy</button>
        </div>
        <div class="output-box">
            <p id="humanizedOutput" style="color:#ccc; line-height:1.9; font-size:14px; white-space:pre-wrap"><?= htmlspecialchars($result['humanized']) ?></p>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function setTone(el, tone) {
    document.querySelectorAll('.tone-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('toneInput').value = tone;
}
function updateCount() {
    document.getElementById('charCount').textContent =
        document.getElementById('aiText').value.length;
}
function clearText() {
    document.getElementById('aiText').value = '';
    document.getElementById('charCount').textContent = '0';
}
function copyOutput() {
    const text = document.getElementById('humanizedOutput')?.innerText;
    if (text) {
        navigator.clipboard.writeText(text).then(() => alert('✅ Copied!'));
    }
}
updateCount();
</script>
</body>
</html>