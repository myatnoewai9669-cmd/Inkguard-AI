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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['topic'])) {
    $topic = trim($_POST['topic']);
    $tone = $_POST['tone'] ?? 'professional';
    $length = $_POST['length'] ?? 'medium';
    $keywords = trim($_POST['keywords'] ?? '');

    if (empty($topic)) {
        $error = 'Please enter a blog topic!';
    } else {

        // ============================================
        // TONE VOCABULARY
        // ============================================
        $tone_intros = [
            'professional' => [
                "In today's competitive landscape, understanding {$topic} has become essential for success.",
                "As industries continue to evolve, {$topic} remains a critical topic worth exploring.",
                "Organizations across sectors are increasingly recognizing the importance of {$topic}."
            ],
            'casual' => [
                "So, let's talk about {$topic} — it's actually pretty interesting once you dig in.",
                "Ever wondered about {$topic}? Yeah, me too. Let's break it down together.",
                "Okay, real talk — {$topic} is something everyone should know a bit more about."
            ],
            'friendly' => [
                "Hey there! Today we're diving into {$topic}, and I think you'll find it fascinating.",
                "Welcome! Let's explore {$topic} together and see what makes it so important.",
                "I'm excited to share some thoughts on {$topic} with you today."
            ],
            'academic' => [
                "This article examines {$topic}, analyzing its significance within the broader context.",
                "The following discussion explores {$topic} through multiple perspectives and evidence.",
                "A comprehensive understanding of {$topic} requires careful examination of its core principles."
            ],
        ];

        $tone_transitions = [
            'professional' => ['Furthermore,', 'Additionally,', 'Moreover,', 'In this context,', 'Building on this,'],
            'casual' => ['Also,', 'Plus,', 'On top of that,', 'And here\'s the thing —', 'Not to mention,'],
            'friendly' => ['What\'s more,', 'I also want to mention,', 'Here\'s something else,', 'Another cool thing is,'],
            'academic' => ['Consequently,', 'Notably,', 'In addition,', 'It follows that,', 'This suggests that'],
        ];

        $tone_conclusions = [
            'professional' => "In conclusion, {$topic} represents a significant opportunity for growth and innovation. Organizations that prioritize this area will be well-positioned for long-term success.",
            'casual' => "So yeah, that's the deal with {$topic}. Pretty cool stuff, right? Hopefully this gave you a solid overview to work with.",
            'friendly' => "I hope this gave you a good introduction to {$topic}! Feel free to explore further and see how it applies to your own journey.",
            'academic' => "In summary, the analysis presented demonstrates the multifaceted nature of {$topic}. Further research may yield additional insights into its practical applications.",
        ];

        // Section structures based on length
        $section_counts = [
            'short' => 3,
            'medium' => 5,
            'long' => 7
        ];

        $num_sections = $section_counts[$length] ?? 5;

        $section_templates = [
            "Understanding the Basics of {$topic}",
            "Why {$topic} Matters Today",
            "Key Benefits and Applications",
            "Common Challenges to Consider",
            "Best Practices for Success",
            "Real-World Examples",
            "Future Trends and Predictions",
            "Practical Tips to Get Started",
            "Comparing Different Approaches",
            "Expert Insights and Recommendations"
        ];

        $section_content_templates = [
            "At its core, {$topic} involves understanding key principles that shape how we approach related challenges. This foundation is essential before diving into more advanced concepts.",
            "The relevance of {$topic} has grown significantly, driven by changing needs and increasing awareness across various industries and communities.",
            "There are numerous advantages associated with {$topic}, ranging from improved efficiency to enhanced outcomes for individuals and organizations alike.",
            "While {$topic} offers many benefits, it's important to acknowledge potential obstacles that may arise during implementation or adoption.",
            "Successful implementation of {$topic} typically involves careful planning, clear communication, and a willingness to adapt based on feedback and results.",
            "Looking at practical examples helps illustrate how {$topic} has been successfully applied in different contexts, offering valuable lessons for others.",
            "As we look ahead, {$topic} is likely to continue evolving, shaped by technological advances and shifting priorities within the field.",
        ];

        // Keyword integration
        $keyword_list = !empty($keywords) ?
            array_map('trim', explode(',', $keywords)) : [];

        // Generate title
        $title_templates = [
            'professional' => "The Complete Guide to {$topic}",
            'casual' => "Everything You Need to Know About {$topic}",
            'friendly' => "Let's Talk About {$topic}: A Friendly Guide",
            'academic' => "An Analysis of {$topic}: Key Concepts and Implications",
        ];

        $blog_title = $title_templates[$tone] ?? "The Complete Guide to {$topic}";

        // Build intro
        $intro = $tone_intros[$tone][array_rand($tone_intros[$tone])];
        if (!empty($keyword_list)) {
            $intro .= " Key areas we'll cover include " .
                implode(', ', array_slice($keyword_list, 0, 3)) . ".";
        }

        // Build sections
        $sections = [];
        $transitions = $tone_transitions[$tone];

        for ($i = 0; $i < $num_sections; $i++) {
            $section_title = str_replace('{$topic}', $topic,
                $section_templates[$i % count($section_templates)]);
            $section_title = str_replace('{topic}', $topic, $section_title);

            $content = str_replace('{$topic}', $topic,
                $section_content_templates[$i % count($section_content_templates)]);
            $content = str_replace('{topic}', $topic, $content);

            // Add transition sentence
            if ($i > 0) {
                $transition = $transitions[$i % count($transitions)];
                $content = $transition . ' ' . lcfirst($content);
            }

            // Add keyword mention occasionally
            if (!empty($keyword_list) && $i < count($keyword_list)) {
                $content .= " This is particularly relevant when considering " .
                    $keyword_list[$i] . ".";
            }

            $sections[] = [
                'title' => $section_title,
                'content' => $content
            ];
        }

        $conclusion = $tone_conclusions[$tone];

        // Word count estimate
        $full_text = $intro . ' ' . implode(' ', array_map(fn($s) =>
            $s['title'] . ' ' . $s['content'], $sections)) . ' ' . $conclusion;
        $word_count = str_word_count($full_text);
        $read_time = max(1, round($word_count / 200));

        $result = [
            'title' => $blog_title,
            'intro' => $intro,
            'sections' => $sections,
            'conclusion' => $conclusion,
            'word_count' => $word_count,
            'read_time' => $read_time,
            'tone' => $tone,
            'topic' => $topic
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Blog Writer - InkGuard</title>
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
        .form-group { margin-bottom:16px; }
        .form-group label {
            display:block; font-size:13px; color:#888; margin-bottom:6px;
        }
        .form-input {
            width:100%; padding:12px 16px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:10px; color:#fff;
            font-size:14px; font-family:inherit;
            outline:none; transition:border-color 0.3s;
        }
        .form-input:focus { border-color:rgba(124,124,255,0.6); }
        .form-input::placeholder { color:#555; }

        .option-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:16px; }
        .option-btn {
            padding:10px; text-align:center; border-radius:8px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(0,0,0,0.2); color:#aaa;
            cursor:pointer; font-size:12px; transition:all 0.2s;
        }
        .option-btn.selected {
            border-color:#7c7cff; background:rgba(124,124,255,0.15); color:#fff;
        }

        .btn-generate {
            width:100%; padding:14px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:16px; font-weight:700; cursor:pointer;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-generate:hover { transform:translateY(-2px); }

        .alert-error {
            background:rgba(255,68,68,0.1); border:1px solid rgba(255,68,68,0.3);
            color:#ff6b6b; padding:12px 16px; border-radius:10px;
            font-size:13px; margin-bottom:16px;
        }

        /* Blog Output */
        .blog-meta {
            display:flex; gap:16px; margin-bottom:20px;
            padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.08);
        }
        .meta-item { font-size:12px; color:#888; }
        .meta-item strong { color:#7c7cff; }

        .blog-output {
            background:#fff; color:#1a1a1a; border-radius:12px;
            padding:32px; box-shadow:0 10px 30px rgba(0,0,0,0.4);
        }
        .blog-title {
            font-size:26px; font-weight:800; color:#1a1a2e;
            margin-bottom:16px; line-height:1.3;
        }
        .blog-intro {
            font-size:15px; color:#444; line-height:1.8;
            margin-bottom:24px; font-style:italic;
            border-left:3px solid #7c7cff; padding-left:16px;
        }
        .blog-section { margin-bottom:20px; }
        .blog-section h3 {
            font-size:18px; color:#1a1a2e; margin-bottom:10px;
            padding-bottom:6px; border-bottom:2px solid #f0f0f0;
        }
        .blog-section p {
            font-size:14px; color:#444; line-height:1.8;
        }
        .blog-conclusion {
            margin-top:24px; padding-top:20px;
            border-top:1px solid #f0f0f0;
            font-size:14px; color:#444; line-height:1.8;
        }
        .copy-btn {
            width:100%; margin-top:16px; padding:12px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
        }
        .copy-btn:hover { transform:translateY(-1px); }
        @media(max-width:600px) {
            .option-grid { grid-template-columns:repeat(2,1fr); }
            .blog-meta { flex-wrap:wrap; gap:8px; }
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
        <h1>📝 AI Blog Writer</h1>
        <p>Generate complete blog posts instantly from any topic</p>
    </div>

    <div class="glass-card">
        <h2>✍️ Blog Details</h2>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Blog Topic *</label>
                <input type="text" name="topic" class="form-input"
                    placeholder="e.g. Sustainable Fashion, Remote Work Productivity..."
                    value="<?= isset($_POST['topic']) ? htmlspecialchars($_POST['topic']) : '' ?>">
            </div>

            <div class="form-group">
                <label>Keywords to Include (optional, comma separated)</label>
                <input type="text" name="keywords" class="form-input"
                    placeholder="e.g. eco-friendly, budget-friendly, innovative"
                    value="<?= isset($_POST['keywords']) ? htmlspecialchars($_POST['keywords']) : '' ?>">
            </div>

            <div class="form-group">
                <label>Writing Tone</label>
                <div class="option-grid">
                    <div class="option-btn <?= (!isset($_POST['tone']) || $_POST['tone']=='professional') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'tone','professional')">💼 Professional</div>
                    <div class="option-btn <?= (isset($_POST['tone']) && $_POST['tone']=='casual') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'tone','casual')">😎 Casual</div>
                    <div class="option-btn <?= (isset($_POST['tone']) && $_POST['tone']=='friendly') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'tone','friendly')">😊 Friendly</div>
                    <div class="option-btn <?= (isset($_POST['tone']) && $_POST['tone']=='academic') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'tone','academic')">🎓 Academic</div>
                </div>
                <input type="hidden" name="tone" id="toneInput" value="<?= $_POST['tone'] ?? 'professional' ?>">
            </div>

            <div class="form-group">
                <label>Blog Length</label>
                <div class="option-grid">
                    <div class="option-btn <?= (isset($_POST['length']) && $_POST['length']=='short') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'length','short')">📄 Short (3 sections)</div>
                    <div class="option-btn <?= (!isset($_POST['length']) || $_POST['length']=='medium') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'length','medium')">📃 Medium (5 sections)</div>
                    <div class="option-btn <?= (isset($_POST['length']) && $_POST['length']=='long') ? 'selected' : '' ?>"
                        onclick="setOpt(this,'length','long')">📚 Long (7 sections)</div>
                </div>
                <input type="hidden" name="length" id="lengthInput" value="<?= $_POST['length'] ?? 'medium' ?>">
            </div>

            <button type="submit" class="btn-generate">
                ✨ Generate Blog Post
            </button>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="glass-card">
        <h2>📝 Generated Blog Post</h2>

        <div class="blog-meta">
            <span class="meta-item">📊 <strong><?= $result['word_count'] ?></strong> words</span>
            <span class="meta-item">⏱️ <strong><?= $result['read_time'] ?> min</strong> read</span>
            <span class="meta-item">🎯 <strong><?= ucfirst($result['tone']) ?></strong> tone</span>
            <span class="meta-item">📑 <strong><?= count($result['sections']) ?></strong> sections</span>
        </div>

        <div class="blog-output" id="blogOutput">
            <h1 class="blog-title"><?= htmlspecialchars($result['title']) ?></h1>
            <p class="blog-intro"><?= htmlspecialchars($result['intro']) ?></p>

            <?php foreach ($result['sections'] as $i => $section): ?>
            <div class="blog-section">
                <h3><?= ($i+1) . '. ' . htmlspecialchars($section['title']) ?></h3>
                <p><?= htmlspecialchars($section['content']) ?></p>
            </div>
            <?php endforeach; ?>

            <div class="blog-conclusion">
                <strong>Conclusion:</strong><br>
                <?= htmlspecialchars($result['conclusion']) ?>
            </div>
        </div>

        <button class="copy-btn" onclick="copyBlog()">
            📋 Copy Full Blog Post
        </button>
    </div>
    <?php endif; ?>

</div>

<script>
function setOpt(el, group, value) {
    const parent = el.parentElement;
    parent.querySelectorAll('.option-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById(group + 'Input').value = value;
}

function copyBlog() {
    const text = document.getElementById('blogOutput').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Blog post copied to clipboard!');
    });
}
</script>
</body>
</html>