<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['text'])) {
    $text = $_POST['text'];
    $voice = $_POST['voice'] ?? 'professional';

    if (strlen($text) < 20) {
        $error = 'Please enter at least 20 characters!';
    } else {
        $text_lower = strtolower($text);

        // ============================================
        // BRAND VOICE PROFILES
        // ============================================
        $voice_profiles = [
            'professional' => [
                'name' => 'Professional & Corporate',
                'good_words' => ['ensure','provide','deliver','solution',
                    'expertise','professional','quality','reliable',
                    'efficient','strategic','committed','excellence',
                    'partnership','value','trusted','proven'],
                'bad_words' => ['awesome','lol','omg','gonna','wanna',
                    'super duper','crazy','insane','literally','totally',
                    'kinda','sorta','yeah','nah','dude','yo'],
                'ideal_sentence_length' => [12, 22],
                'tone_desc' => 'Formal, authoritative, and trustworthy'
            ],
            'friendly' => [
                'name' => 'Friendly & Approachable',
                'good_words' => ['love','happy','excited','together',
                    'community','help','support','enjoy','wonderful',
                    'great','awesome','amazing','share','connect'],
                'bad_words' => ['mandatory','shall','henceforth',
                    'aforementioned','pursuant','herein','whereby',
                    'notwithstanding','utilize','facilitate'],
                'ideal_sentence_length' => [8, 15],
                'tone_desc' => 'Warm, casual, and inviting'
            ],
            'playful' => [
                'name' => 'Playful & Fun',
                'good_words' => ['fun','exciting','awesome','wow',
                    'amazing','love','yay','woohoo','cool','epic',
                    'fantastic','wild','adventure','magic'],
                'bad_words' => ['formal','procedure','compliance',
                    'regulation','mandatory','pursuant','stipulate',
                    'aforementioned','henceforth','whereas'],
                'ideal_sentence_length' => [6, 14],
                'tone_desc' => 'Energetic, bold, and entertaining'
            ],
            'luxury' => [
                'name' => 'Luxury & Premium',
                'good_words' => ['exquisite','refined','curated',
                    'exceptional','bespoke','elegant','timeless',
                    'craftsmanship','prestige','exclusive','finest',
                    'sophisticated','impeccable','distinguished'],
                'bad_words' => ['cheap','discount','deal','bargain',
                    'sale','budget','basic','ordinary','average',
                    'lol','awesome','cool','crazy'],
                'ideal_sentence_length' => [10, 20],
                'tone_desc' => 'Elegant, aspirational, and exclusive'
            ],
            'technical' => [
                'name' => 'Technical & Precise',
                'good_words' => ['optimize','implement','system',
                    'framework','architecture','scalable','robust',
                    'integrate','data','performance','efficient',
                    'protocol','infrastructure','configure'],
                'bad_words' => ['awesome','super','cool','amazing',
                    'love','lol','yay','fantastic','wow'],
                'ideal_sentence_length' => [12, 25],
                'tone_desc' => 'Precise, data-driven, and analytical'
            ],
        ];

        $profile = $voice_profiles[$voice];

        // ============================================
        // ANALYSIS
        // ============================================

        // 1. Word match analysis
        $good_matches = [];
        $bad_matches = [];

        foreach ($profile['good_words'] as $word) {
            if (strpos($text_lower, $word) !== false) {
                $good_matches[] = $word;
            }
        }
        foreach ($profile['bad_words'] as $word) {
            if (strpos($text_lower, $word) !== false) {
                $bad_matches[] = $word;
            }
        }

        // 2. Sentence length analysis
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_filter($sentences, fn($s) => trim($s) !== '');
        $total_words = str_word_count($text);
        $sentence_count = count($sentences);
        $avg_sentence_length = $sentence_count > 0 ?
            round($total_words / $sentence_count, 1) : 0;

        $ideal_min = $profile['ideal_sentence_length'][0];
        $ideal_max = $profile['ideal_sentence_length'][1];
        $length_ok = $avg_sentence_length >= $ideal_min &&
            $avg_sentence_length <= $ideal_max;

        // 3. Exclamation/punctuation check
        $exclamations = substr_count($text, '!');
        $questions = substr_count($text, '?');

        // 4. Calculate score
        $score = 50; // baseline

        // Good words boost score
        $score += min(count($good_matches) * 8, 30);

        // Bad words penalty
        $score -= count($bad_matches) * 10;

        // Sentence length
        if ($length_ok) {
            $score += 15;
        } else {
            $score -= 10;
        }

        // Voice-specific adjustments
        if ($voice === 'playful' && $exclamations > 0) {
            $score += min($exclamations * 3, 10);
        }
        if ($voice === 'professional' && $exclamations > 2) {
            $score -= 10;
        }
        if ($voice === 'luxury' && $exclamations > 1) {
            $score -= 8;
        }

        $score = max(0, min(100, round($score)));

        // 5. Determine grade
        if ($score >= 85) {
            $grade = 'A';
            $grade_label = 'Excellent Match';
            $grade_color = '#00cc66';
        } elseif ($score >= 70) {
            $grade = 'B';
            $grade_label = 'Good Match';
            $grade_color = '#7c7cff';
        } elseif ($score >= 50) {
            $grade = 'C';
            $grade_label = 'Needs Improvement';
            $grade_color = '#ffd700';
        } else {
            $grade = 'D';
            $grade_label = 'Poor Match';
            $grade_color = '#ff4444';
        }

        // 6. Generate recommendations
        $recommendations = [];

        if (count($bad_matches) > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'text' => 'Avoid these words that don\'t match your brand voice: ' .
                    implode(', ', array_slice($bad_matches, 0, 5))
            ];
        }

        if (count($good_matches) < 2) {
            $recommendations[] = [
                'type' => 'suggestion',
                'text' => 'Try incorporating more voice-aligned words like: ' .
                    implode(', ', array_slice($profile['good_words'], 0, 5))
            ];
        }

        if (!$length_ok) {
            if ($avg_sentence_length > $ideal_max) {
                $recommendations[] = [
                    'type' => 'warning',
                    'text' => "Your sentences average {$avg_sentence_length} words - consider shortening them to {$ideal_min}-{$ideal_max} words for this voice"
                ];
            } else {
                $recommendations[] = [
                    'type' => 'suggestion',
                    'text' => "Your sentences average {$avg_sentence_length} words - you could add more detail to reach {$ideal_min}-{$ideal_max} words"
                ];
            }
        }

        if ($voice === 'playful' && $exclamations === 0) {
            $recommendations[] = [
                'type' => 'suggestion',
                'text' => 'Add some energy with exclamation points to match your playful voice!'
            ];
        }

        if ($voice === 'professional' && $exclamations > 2) {
            $recommendations[] = [
                'type' => 'warning',
                'text' => 'Too many exclamation points for a professional tone - consider reducing them'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'text' => 'Great job! Your content aligns well with your brand voice.'
            ];
        }

        $result = [
            'text' => $text,
            'voice' => $voice,
            'profile_name' => $profile['name'],
            'tone_desc' => $profile['tone_desc'],
            'score' => $score,
            'grade' => $grade,
            'grade_label' => $grade_label,
            'grade_color' => $grade_color,
            'good_matches' => $good_matches,
            'bad_matches' => $bad_matches,
            'avg_sentence_length' => $avg_sentence_length,
            'ideal_range' => "{$ideal_min}-{$ideal_max}",
            'length_ok' => $length_ok,
            'word_count' => $total_words,
            'sentence_count' => $sentence_count,
            'recommendations' => $recommendations
        ];

        // Save to database
        $text_escaped = mysqli_real_escape_string($conn, $text);
        $sql = "INSERT INTO analyses (user_id, text_input, result, confidence)
                VALUES ('$user_id', '$text_escaped', 'Brand Voice - {$profile['name']}', '$score')";
        mysqli_query($conn, $sql);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Voice Checker - InkGuard</title>
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
            background:linear-gradient(135deg,#7c7cff,#00cc66);
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

        .voice-grid {
            display:grid; grid-template-columns:repeat(5,1fr);
            gap:8px; margin-bottom:20px;
        }
        .voice-btn {
            padding:14px 6px; text-align:center; border-radius:10px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(0,0,0,0.2); color:#aaa;
            cursor:pointer; font-size:11px; transition:all 0.2s;
        }
        .voice-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15); color:#fff;
        }
        .voice-icon { font-size:22px; display:block; margin-bottom:4px; }

        .text-input {
            width:100%; min-height:180px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:12px; padding:16px; color:#fff;
            font-size:14px; font-family:inherit; resize:vertical;
            line-height:1.7; outline:none; transition:border-color 0.3s;
        }
        .text-input:focus { border-color:rgba(124,124,255,0.5); }
        .text-input::placeholder { color:#555; }
        .char-count { font-size:12px; color:#555; margin-top:8px; }

        .btn-row { display:flex; gap:10px; margin-top:12px; }
        .btn-check {
            flex:1; padding:14px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:15px; font-weight:700; cursor:pointer;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-check:hover { transform:translateY(-2px); }
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

        /* Result */
        .grade-display {
            text-align:center; padding:20px 0; margin-bottom:20px;
        }
        .grade-circle {
            width:100px; height:100px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:40px; font-weight:900; margin:0 auto 12px;
            border:4px solid;
        }
        .grade-label {
            font-size:16px; font-weight:700; margin-bottom:4px;
        }
        .grade-score { font-size:13px; color:#888; }

        .stats-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:10px; margin-bottom:20px;
        }
        .stat-tile {
            background:rgba(0,0,0,0.3); border-radius:10px;
            padding:14px; text-align:center;
        }
        .stat-tile .num { font-size:20px; font-weight:900; }
        .stat-tile .lbl { font-size:10px; color:#888; margin-top:2px; }

        .words-section {
            margin-bottom:16px;
        }
        .words-title {
            font-size:13px; color:#888; margin-bottom:8px; font-weight:600;
        }
        .word-tags { display:flex; flex-wrap:wrap; gap:6px; }
        .word-tag {
            padding:4px 10px; border-radius:14px; font-size:12px;
        }
        .word-tag.good {
            background:rgba(0,204,102,0.15); color:#00cc66;
            border:1px solid rgba(0,204,102,0.3);
        }
        .word-tag.bad {
            background:rgba(255,68,68,0.15); color:#ff6b6b;
            border:1px solid rgba(255,68,68,0.3);
        }

        .rec-list { display:flex; flex-direction:column; gap:10px; }
        .rec-item {
            display:flex; gap:10px; padding:12px;
            border-radius:10px; font-size:13px; line-height:1.5;
            border-left:4px solid;
        }
        .rec-item.warning {
            background:rgba(255,215,0,0.08);
            border-color:#ffd700; color:#e0c840;
        }
        .rec-item.suggestion {
            background:rgba(124,124,255,0.08);
            border-color:#7c7cff; color:#a0a0ff;
        }
        .rec-item.success {
            background:rgba(0,204,102,0.08);
            border-color:#00cc66; color:#00cc66;
        }
        @media(max-width:600px) {
            .voice-grid { grid-template-columns:repeat(3,1fr); }
            .stats-grid { grid-template-columns:1fr; }
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
        <a href="brand_rules.php" class="nav-link">📋 Brand Rules</a>
        <a href="logout.php" class="nav-link danger">🚪 Logout</a>
    </div>
</nav>

<div class="page">
    <div class="page-title">
        <h1>🎙️ Brand Voice Checker</h1>
        <p>Analyze whether your content matches your target brand voice</p>
    </div>

    <div class="glass-card">
        <h2>🎯 Select Target Brand Voice</h2>

        <form method="POST">
            <div class="voice-grid">
                <div class="voice-btn <?= (!isset($_POST['voice']) || $_POST['voice']=='professional') ? 'selected' : '' ?>"
                    onclick="setVoice(this,'professional')">
                    <span class="voice-icon">💼</span>
                    Professional
                </div>
                <div class="voice-btn <?= (isset($_POST['voice']) && $_POST['voice']=='friendly') ? 'selected' : '' ?>"
                    onclick="setVoice(this,'friendly')">
                    <span class="voice-icon">😊</span>
                    Friendly
                </div>
                <div class="voice-btn <?= (isset($_POST['voice']) && $_POST['voice']=='playful') ? 'selected' : '' ?>"
                    onclick="setVoice(this,'playful')">
                    <span class="voice-icon">🎉</span>
                    Playful
                </div>
                <div class="voice-btn <?= (isset($_POST['voice']) && $_POST['voice']=='luxury') ? 'selected' : '' ?>"
                    onclick="setVoice(this,'luxury')">
                    <span class="voice-icon">💎</span>
                    Luxury
                </div>
                <div class="voice-btn <?= (isset($_POST['voice']) && $_POST['voice']=='technical') ? 'selected' : '' ?>"
                    onclick="setVoice(this,'technical')">
                    <span class="voice-icon">⚙️</span>
                    Technical
                </div>
            </div>
            <input type="hidden" name="voice" id="voiceInput"
                value="<?= $_POST['voice'] ?? 'professional' ?>">

            <?php if ($error): ?>
                <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <textarea name="text" class="text-input" id="voiceText"
                placeholder="Paste your marketing copy, social media post, or any brand content here to check if it matches your target voice..."
                oninput="updateCount()"
            ><?= isset($_POST['text']) ? htmlspecialchars($_POST['text']) : '' ?></textarea>
            <div class="char-count">
                <span id="charCount">0</span> characters
            </div>

            <div class="btn-row">
                <button type="button" class="btn-clear" onclick="clearText()">
                    🗑️ Clear
                </button>
                <button type="submit" class="btn-check">
                    🎙️ Check Brand Voice
                </button>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="glass-card">
        <h2>📊 Voice Analysis: <?= $result['profile_name'] ?></h2>
        <p style="color:#888; font-size:13px; margin-top:-10px; margin-bottom:20px">
            <?= $result['tone_desc'] ?>
        </p>

        <!-- Grade -->
        <div class="grade-display">
            <div class="grade-circle" style="border-color:<?= $result['grade_color'] ?>; color:<?= $result['grade_color'] ?>">
                <?= $result['grade'] ?>
            </div>
            <div class="grade-label" style="color:<?= $result['grade_color'] ?>">
                <?= $result['grade_label'] ?>
            </div>
            <div class="grade-score">Voice Match Score: <?= $result['score'] ?>/100</div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-tile">
                <div class="num" style="color:#7c7cff"><?= $result['word_count'] ?></div>
                <div class="lbl">Total Words</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:<?= $result['length_ok'] ? '#00cc66' : '#ffd700' ?>">
                    <?= $result['avg_sentence_length'] ?>
                </div>
                <div class="lbl">Avg Words/Sentence (ideal: <?= $result['ideal_range'] ?>)</div>
            </div>
            <div class="stat-tile">
                <div class="num" style="color:#00cc66"><?= $result['sentence_count'] ?></div>
                <div class="lbl">Sentences</div>
            </div>
        </div>

        <!-- Word matches -->
        <?php if (!empty($result['good_matches'])): ?>
        <div class="words-section">
            <div class="words-title">✅ Voice-Matching Words Found:</div>
            <div class="word-tags">
                <?php foreach ($result['good_matches'] as $word): ?>
                    <span class="word-tag good"><?= htmlspecialchars($word) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($result['bad_matches'])): ?>
        <div class="words-section">
            <div class="words-title">⚠️ Off-Brand Words Found:</div>
            <div class="word-tags">
                <?php foreach ($result['bad_matches'] as $word): ?>
                    <span class="word-tag bad"><?= htmlspecialchars($word) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <div class="words-title" style="margin-top:20px">💡 Recommendations:</div>
        <div class="rec-list">
            <?php foreach ($result['recommendations'] as $rec): ?>
            <div class="rec-item <?= $rec['type'] ?>">
                <?= $rec['type'] === 'warning' ? '⚠️' : ($rec['type'] === 'success' ? '✅' : '💡') ?>
                <span><?= htmlspecialchars($rec['text']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function setVoice(el, voice) {
    document.querySelectorAll('.voice-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('voiceInput').value = voice;
}

function updateCount() {
    document.getElementById('charCount').textContent =
        document.getElementById('voiceText').value.length;
}

function clearText() {
    document.getElementById('voiceText').value = '';
    document.getElementById('charCount').textContent = '0';
}

updateCount();
</script>
</body>
</html>