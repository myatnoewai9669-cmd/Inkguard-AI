<?php
/**
 * ai_chat_assistant.php  (CHAT Q&A ONLY)
 * -------------------------------------------------------------------
 * Paste training material, then ask questions about it in a chat.
 * Uses watsonx.ai (Meta Llama-3.3-70b-instruct, since Granite isn't
 * available in the Tokyo project region).
 *
 * No Composer needed - pure PHP + cURL.
 *
 * SETUP: fill in WATSONX_API_KEY below with a FRESH, regenerated key.
 * Never paste your real API key into a screenshot or chat again.
 */

session_start();

// ============ CONFIG (fill these in on YOUR computer only) =======\\\\\\\\\\\====
define('WATSONX_API_KEY', 'your_real -api _key');
define('WATSONX_PROJECT_ID', 'your_real-ID');
define('WATSONX_URL', 'https:.......'); // Tokyo region
define('WATSONX_MODEL_ID', 'meta-llama/llama-3-3-70b-instruct');
define('IBM_IAM_URL', 'https://iam.cloud.ibm.com/identity/token');
// ========================================================================

// Optional: hook into your existing shared layout, if present
$sidebarPath = __DIR__ . '/includes/sidebar.php';
$stylePath   = __DIR__ . '/includes/style.php';

// ---------- Core functions ----------

function watsonx_get_iam_token() {
    if (!empty($_SESSION['iam_token']) && !empty($_SESSION['iam_token_expires']) &&
        $_SESSION['iam_token_expires'] > time() + 30) {
        return $_SESSION['iam_token'];
    }

    $ch = curl_init(IBM_IAM_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ibm:params:oauth:grant-type:apikey',
            'apikey'     => WATSONX_API_KEY,
        ]),
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception("IAM token request failed: $err");

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        throw new Exception("IAM token request returned no token. Check your API key. Raw response: " . $response);
    }

    $_SESSION['iam_token'] = $data['access_token'];
    $_SESSION['iam_token_expires'] = time() + ($data['expires_in'] ?? 3300);

    return $_SESSION['iam_token'];
}

function watsonx_call_model($prompt, $maxTokens = 500, $temperature = 0.5) {
    $token = watsonx_get_iam_token();
    $url = rtrim(WATSONX_URL, '/') . '/ml/v1/text/generation?version=2023-05-29';

    $payload = [
        'model_id'   => WATSONX_MODEL_ID,
        'project_id' => WATSONX_PROJECT_ID,
        'input'      => $prompt,
        'parameters' => [
            'decoding_method'    => 'greedy',
            'max_new_tokens'     => $maxTokens,
            'temperature'        => $temperature,
            'repetition_penalty' => 1.05,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) throw new Exception("Model API request failed: $err");

    $data = json_decode($response, true);

    if (!empty($data['results'][0]['generated_text'])) {
        return trim($data['results'][0]['generated_text']);
    }
    if (!empty($data['errors'][0]['message'])) {
        throw new Exception("Model API error: " . $data['errors'][0]['message']);
    }
    throw new Exception("Unexpected API response: " . $response);
}

function truncate_for_prompt($text, $maxChars = 6000) {
    if (strlen($text) <= $maxChars) return $text;
    return substr($text, 0, $maxChars) . "\n\n[...content truncated for length...]";
}

// ---------- State ----------
if (!isset($_SESSION['chat_material'])) $_SESSION['chat_material'] = '';
if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];

$error = '';

// ---------- Handle actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'set_material') {
            $_SESSION['chat_material'] = trim($_POST['material_text'] ?? '');
            $_SESSION['chat_history'] = []; // reset chat when material changes
        }

        elseif ($action === 'ask' && !empty($_POST['question'])) {
            $question = trim($_POST['question']);
            $context = truncate_for_prompt($_SESSION['chat_material']);

            if (trim($context) === '') {
                $error = "Please paste some lesson/training material first.";
            } else {
                $prompt = "You are a helpful training assistant. Use ONLY the lesson material below to answer "
                        . "the learner's question clearly and concisely. If the answer isn't in the material, say so.\n\n"
                        . "LESSON MATERIAL:\n$context\n\nQUESTION: $question\n\nANSWER:";

                $answer = watsonx_call_model($prompt, 500, 0.4);

                $_SESSION['chat_history'][] = ['role' => 'user', 'text' => $question];
                $_SESSION['chat_history'][] = ['role' => 'assistant', 'text' => $answer];
            }
        }

        elseif ($action === 'reset_chat') {
            $_SESSION['chat_history'] = [];
        }

        elseif ($action === 'reset_all') {
            $_SESSION['chat_history'] = [];
            $_SESSION['chat_material'] = '';
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
$hasMaterial = trim($_SESSION['chat_material']) !== '';
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
<title>AI Chat Assistant - inkguard AI</title>
<style>
    .ta-wrap { max-width: 900px; margin: 0 auto; padding: 20px; color: #e8ecf5; }
    .ta-card { background: #10182b; border: 1px solid #223055; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .ta-card h2 { margin-top: 0; font-size: 18px; color: #9db4ff; }
    .ta-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    textarea, input[type=text] {
        width: 100%; background: #0b1120; color: #e8ecf5; border: 1px solid #2a3a63;
        border-radius: 6px; padding: 10px; font-size: 14px; box-sizing: border-box; font-family: inherit;
    }
    button {
        background: #3b5bfd; color: #fff; border: none; border-radius: 6px;
        padding: 10px 18px; font-size: 14px; cursor: pointer;
    }
    button.secondary { background: #223055; }
    button:hover { opacity: 0.9; }
    .ta-error { background: #3a1212; border: 1px solid #7a1f1f; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
    .chat-box { max-height: 400px; overflow-y: auto; margin-bottom: 12px; padding-right: 4px; }
    .chat-bubble { padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; max-width: 85%; white-space: pre-wrap; }
    .chat-user { background: #223055; margin-left: auto; }
    .chat-assistant { background: #0b1120; border: 1px solid #2a3a63; }
    .material-tag { display: inline-block; background: #223055; padding: 4px 10px; border-radius: 20px; font-size: 12px; margin-bottom: 10px; }
</style>
</head>
<body style="background:#0a0f1e; margin:0;">

<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>

<div class="ta-wrap">
    <h1 style="color:#fff;">💬 AI Chat Assistant</h1>

    <?php if ($error): ?><div class="ta-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="ta-card">
        <h2>📚 Lesson / Training Material</h2>
        <?php if ($hasMaterial): ?>
            <span class="material-tag">Material loaded (<?= strlen($_SESSION['chat_material']) ?> characters)</span>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="set_material">
            <textarea name="material_text" rows="6" placeholder="Paste your lesson text here..."><?= htmlspecialchars($_SESSION['chat_material']) ?></textarea>
            <div class="ta-row" style="margin-top:10px;">
                <button type="submit">Save Material</button>
            </div>
        </form>
    </div>
<div class="navbar-right">
        <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
        <a href="logout.php" class="nav-link danger">🚪 Logout</a>
    </div>

    <?php if ($hasMaterial): ?>
    <div class="ta-card">
        <h2>💬 Ask Questions About This Lesson</h2>

        <div class="chat-box">
            <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                <div class="chat-bubble <?= $msg['role'] === 'user' ? 'chat-user' : 'chat-assistant' ?>">
                    <?= htmlspecialchars($msg['text']) ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($_SESSION['chat_history'])): ?>
                <p style="color:#8ea0c9; font-size:14px;">No questions yet — ask something about the material above.</p>
            <?php endif; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="ask">
            <div class="ta-row">
                <input type="text" name="question" placeholder="Ask a question about the lesson..." required style="flex:1;">
                <button type="submit">Ask</button>
            </div>
        </form>

        <form method="POST" style="margin-top:10px; text-align:right;">
            <input type="hidden" name="action" value="reset_chat">
            <button type="submit" class="secondary">Clear Chat</button>
        </form>
    </div>
    <?php endif; ?>

    <form method="POST" style="text-align:right;">
        <input type="hidden" name="action" value="reset_all">
        <button type="submit" class="secondary">Clear Everything</button>
    </form>
</div>
</body>
</html>
