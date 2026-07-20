<?php
/**
 * ai_training_assistant.php  (SIMPLE VERSION - Quiz + Summary only)
 * -------------------------------------------------------------------
 * No Composer / no libraries needed. Just paste your training text in,
 * and get an AI-generated Quiz or Summary using IBM Granite (watsonx.ai).
 *
 * SETUP: fill in WATSONX_API_KEY below with a FRESH, regenerated key.
 * Never paste your real API key into a screenshot or chat again.
 */

session_start();

$env = parse_ini_file(__DIR__ . '/.env');

define('WATSONX_API_KEY', $env['WATSONX_API_KEY']);
define('WATSONX_PROJECT_ID', $env['WATSONX_PROJECT_ID']);
define('WATSONX_URL', $env['WATSONX_URL']);
define('WATSONX_MODEL_ID', $env['WATSONX_MODEL_ID']);
define('IBM_IAM_URL', $env['IBM_IAM_URL']);

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

function watsonx_call_granite($prompt, $maxTokens = 700, $temperature = 0.6) {
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

    if ($err) throw new Exception("Granite API request failed: $err");

    $data = json_decode($response, true);

    if (!empty($data['results'][0]['generated_text'])) {
        return trim($data['results'][0]['generated_text']);
    }
    if (!empty($data['errors'][0]['message'])) {
        throw new Exception("Granite API error: " . $data['errors'][0]['message']);
    }
    throw new Exception("Unexpected Granite API response: " . $response);
}

function truncate_for_prompt($text, $maxChars = 6000) {
    if (strlen($text) <= $maxChars) return $text;
    return substr($text, 0, $maxChars) . "\n\n[...content truncated for length...]";
}

// ---------- State ----------
if (!isset($_SESSION['material_text'])) $_SESSION['material_text'] = '';

$error = '';
$aiResultTitle = '';
$aiResult = '';

// ---------- Handle actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Always save whatever text is in the box, so it's not lost between actions
    if (isset($_POST['material_text'])) {
        $_SESSION['material_text'] = trim($_POST['material_text']);
    }

    try {
        if (trim($_SESSION['material_text']) === '') {
            $error = "Please paste some training material text first.";
        } else {
            $context = truncate_for_prompt($_SESSION['material_text']);

            if ($action === 'quiz') {
                $numQuestions = max(1, min(15, (int)($_POST['num_questions'] ?? 5)));
                $prompt = "Based on the training material below, create $numQuestions multiple-choice quiz questions "
                        . "to test understanding. For each question, give 4 options labeled A-D, and clearly mark the "
                        . "correct answer at the end of each question as 'Correct Answer: X'.\n\n"
                        . "TRAINING MATERIAL:\n$context\n\nQUIZ:";
                $aiResult = watsonx_call_granite($prompt, 900, 0.6);
                $aiResultTitle = "Generated Quiz";
            }

            elseif ($action === 'summary') {
                $prompt = "Summarize the following training material into clear, well-organized key points a learner can quickly review.\n\nTRAINING MATERIAL:\n$context\n\nSUMMARY:";
                $aiResult = watsonx_call_granite($prompt, 500, 0.5);
                $aiResultTitle = "Summary";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
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
<title>AI Training Assistant - Guard AI</title>
<style>
    .ta-wrap { max-width: 900px; margin: 0 auto; padding: 20px; color: #e8ecf5; }
    .ta-card { background: #10182b; border: 1px solid #223055; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .ta-card h2 { margin-top: 0; font-size: 18px; color: #9db4ff; }
    .ta-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    textarea, input[type=number] {
        width: 100%; background: #0b1120; color: #e8ecf5; border: 1px solid #2a3a63;
        border-radius: 6px; padding: 10px; font-size: 14px; box-sizing: border-box; font-family: inherit;
    }
    button {
        background: #3b5bfd; color: #fff; border: none; border-radius: 6px;
        padding: 10px 18px; font-size: 14px; cursor: pointer;
    }
    button:hover { opacity: 0.9; }
    .ta-error { background: #3a1212; border: 1px solid #7a1f1f; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
    .ta-result { white-space: pre-wrap; background: #0b1120; border: 1px solid #2a3a63; border-radius: 6px; padding: 14px; margin-top: 12px; }
</style>
</head>
<body style="background:#0a0f1e; margin:0;">

<?php if (file_exists($sidebarPath)) include $sidebarPath; ?>

<div class="ta-wrap">
    <h1 style="color:#fff;">🤖 AI Training Assistant <span style="font-size:14px; color:#9db4ff;">(IBM Granite)</span></h1>

    <?php if ($error): ?><div class="ta-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="ta-card">
        <h2>📚 Paste Your Training Material</h2>
        <form method="POST" id="mainForm">
            <textarea name="material_text" rows="8" placeholder="Paste your training text here..."><?= htmlspecialchars($_SESSION['material_text']) ?></textarea>

            <div class="ta-row" style="margin-top:14px;">
                <input type="number" name="num_questions" value="5" min="1" max="15" style="max-width:100px;">
                <button type="submit" name="action" value="quiz">📝 Generate Quiz</button>
                <button type="submit" name="action" value="summary">📖 Summarize</button>
            </div>
        </form>
    </div>

    <?php if ($aiResultTitle): ?>
    <div class="ta-card">
        <h2><?= $aiResultTitle === 'Generated Quiz' ? '📝' : '📖' ?> <?= htmlspecialchars($aiResultTitle) ?></h2>
        <div class="ta-result"><?= htmlspecialchars($aiResult) ?></div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
