<?php
// ai_writer.php
session_start();
require_once 'env.php';
require_once 'db_connect.php'
// ----- Template definitions -----
$templates = [
    'blog'     => ['label' => '📝 Blog',            'icon' => '📝'],
    'email'    => ['label' => '📧 Email',           'icon' => '📧'],
    'social'   => ['label' => '📱 Social Media Post','icon' => '📱'],
    'article'  => ['label' => '📄 Article',         'icon' => '📄'],
    'ad'       => ['label' => '📢 Advertisement',   'icon' => '📢'],
    'youtube'  => ['label' => '📺 YouTube Script',  'icon' => '📺'],
];

$generatedContent = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $type      = $_POST['content_type'] ?? 'blog';
    $topic     = trim($_POST['topic'] ?? '');
    $tone      = $_POST['tone'] ?? 'Professional';
    $language  = $_POST['language'] ?? 'English';
    $wordCount = (int)($_POST['word_count'] ?? 500);

    if (empty($topic)) {
        $error = "Topic ထည့်ဖို့ လိုအပ်ပါတယ်။";
    } else {
        $prompt = buildPrompt($type, $topic, $tone, $language, $wordCount);
        $generatedContent = callClaudeAPI($prompt);

        // Optional: save to audit log
        if ($generatedContent && !str_starts_with($generatedContent, 'Error:')) {
            $stmt = $conn->prepare("INSERT INTO audit_log (feature, topic, content_type, tone, word_count, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $feature = 'AI Writer';
            $stmt->bind_param("sssss", $feature, $topic, $type, $tone, $wordCount);
            $stmt->execute();
        }
    }
}

// ----- Build prompt based on template type -----
function buildPrompt($type, $topic, $tone, $language, $wordCount) {
    $base = "Write in $language with a $tone tone. Target length: approximately $wordCount words.\n\n";

    switch ($type) {
        case 'blog':
            return $base . "Write a complete blog article about \"$topic\".
Structure the output exactly like this:
📝 Title
📖 Introduction
📌 Main Content (with subheadings)
✅ Conclusion
🔍 SEO Keywords (5-8 relevant keywords)";

        case 'email':
            return $base . "Write a professional email about \"$topic\".
Include:
📧 Subject Line
Greeting
Body (clear and concise)
Call to action
Sign-off";

        case 'social':
            return $base . "Write a social media post about \"$topic\".
Include:
📱 Caption (short, catchy)
Relevant hashtags (5-10)
Suggested emoji placement";

        case 'article':
            return $base . "Write an in-depth article about \"$topic\".
Structure:
📄 Title
Introduction
Body (well-organized with subheadings)
Conclusion
References/sources to consider (if applicable)";

        case 'ad':
            return $base . "Write an advertisement copy about \"$topic\".
Include:
📢 Headline
Body copy (persuasive)
Call to action
Tagline";

        case 'youtube':
            return $base . "Write a YouTube video script about \"$topic\".
Include:
📺 Hook (first 10 seconds)
Intro
Main content (with timestamps as [00:XX])
Outro + Call to action (subscribe/like)";

        default:
            return $base . "Write content about \"$topic\".";
    }
}

// ----- Call Anthropic API -----
function callClaudeAPI($prompt) {
$apiKey = ANTHROPIC_API_KEY;
    $url = "https://api.anthropic.com/v1/messages";

    $data = [
        "model" => "claude-sonnet-4-6",
        "max_tokens" => 2000,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: $apiKey",
        "anthropic-version: 2023-06-01"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return "Error: API request failed (HTTP $httpCode)";
    }

    $result = json_decode($response, true);
    return $result['content'][0]['text'] ?? "Error: No content returned";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Writer - InkGuard</title>
<style>
    body { font-family: Arial, sans-serif; background:#0d1424; color:#eee; padding:30px; }
    .container { max-width:700px; margin:auto; }
    label { display:block; margin-top:15px; margin-bottom:5px; }
    select, input, textarea { width:100%; padding:10px; border-radius:8px; border:none; }
    button { margin-top:20px; padding:12px 20px; background:#3b82f6; color:#fff; border:none; border-radius:8px; cursor:pointer; }
    .output { background:#141b2f; padding:20px; border-radius:10px; margin-top:25px; white-space:pre-wrap; line-height:1.6; }
    .error { color:#f87171; }
</style>
</head>
<body>
<div class="container">
    <h2>🖋️ AI Writer</h2>

    <form method="POST">
        <label>Content Type</label>
        <select name="content_type">
            <?php foreach ($templates as $key => $t): ?>
                <option value="<?= $key ?>"><?= $t['label'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Topic</label>
        <input type="text" name="topic" placeholder="e.g. Benefits of Artificial Intelligence" required>

        <label>Tone</label>
        <select name="tone">
            <option>Professional</option>
            <option>Friendly</option>
            <option>Casual</option>
            <option>Academic</option>
        </select>

        <label>Language</label>
        <select name="language">
            <option>English</option>
            <option>Burmese</option>
            <option>Indonesian</option>
        </select>

        <label>Word Count</label>
        <select name="word_count">
            <option value="300">300</option>
            <option value="500" selected>500</option>
            <option value="800">800</option>
            <option value="1000">1000</option>
        </select>

        <button type="submit" name="generate">Generate</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($generatedContent): ?>
        <div class="output"><?= htmlspecialchars($generatedContent) ?></div>
    <?php endif; ?>
</div>
</body>
</html>
