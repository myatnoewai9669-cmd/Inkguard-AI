<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $text = $_POST['text'];
    $user_id = $_SESSION['user_id'];

    $words = explode(' ', $text);
    $word_count = count($words);
    $text_lower = strtolower($text);

    // ChatGPT Patterns
    $chatgpt_patterns = [
        'it is important to note',
        'in conclusion',
        'furthermore',
        'it is worth noting',
        'as an ai',
        'i cannot',
        'delve',
        'certainly',
        'absolutely',
        'straightforward',
        'in summary',
        'to summarize',
        'i hope this helps',
        'feel free to ask',
        'let me know if',
        'happy to help',
        'great question',
        'firstly', 'secondly', 'thirdly', 'lastly',
        'moreover', 'however', 'therefore', 'thus', 'hence',
        'play a crucial role', 'plays a vital role',
        'it is crucial', 'it is essential',
        'on the other hand', 'in other words',
        'as a result', 'it should be noted',
        'in this context', 'of course', 'sure, here',
        'here\'s a', 'here are some',
        'in addition', 'additionally',
        'it is clear', 'it is evident',
        'with regard to', 'with respect to',
        'in terms of', 'for instance',
        'as mentioned', 'as discussed'
    ];

    // Gemini Patterns
    $gemini_patterns = [
        'as gemini', 'i\'m gemini',
        'google deepmind', 'bard',
        'i was trained by google',
        'as a large language model',
        'i am a large language model',
        'here\'s a breakdown',
        'here\'s what i found',
        'let me know if you\'d like',
        'great question', 'good question',
        'i can help with that',
        'i\'d be glad to',
        'let\'s dive in', 'let\'s explore',
        'key points', 'key facts',
        'pros and cons', 'in a nutshell',
        'simply put', 'i hope this helps',
        'feel free to ask',
        'don\'t hesitate to ask',
        'is there anything else',
        'happy to answer',
        'that\'s a great question'
    ];

    // Claude Patterns
    $claude_patterns = [
        'as claude', 'i\'m claude', 'anthropic',
        'i aim to', 'i want to be direct',
        'i should note', 'i strive to',
        'to be direct', 'to be clear',
        'to be honest', 'that said',
        'with that said', 'having said that',
        'worth noting', 'worth mentioning',
        'let me explain', 'let me clarify',
        'here\'s the thing', 'nuanced', 'nuance',
        'i\'m not sure', 'i could be wrong',
        'ethical', 'responsible', 'thoughtful',
        'i genuinely', 'i actually',
        'i want to be clear',
        'i should mention'
    ];

    // Count matches
    $chatgpt_score = 0;
    $gemini_score = 0;
    $claude_score = 0;

    foreach ($chatgpt_patterns as $pattern) {
        if (strpos($text_lower, $pattern) !== false) {
            $chatgpt_score += 8;
        }
    }
    foreach ($gemini_patterns as $pattern) {
        if (strpos($text_lower, $pattern) !== false) {
            $gemini_score += 8;
        }
    }
    foreach ($claude_patterns as $pattern) {
        if (strpos($text_lower, $pattern) !== false) {
            $claude_score += 8;
        }
    }

    // Sentence structure analysis
    $sentences = preg_split('/[.!?]+/', $text);
    $sentence_count = count(array_filter($sentences));
    $avg_sentence_length = $word_count / max($sentence_count, 1);

    $structure_score = 0;
    if ($avg_sentence_length > 20) $structure_score += 20;
    if ($avg_sentence_length > 15) $structure_score += 10;
    if ($word_count > 150) $structure_score += 10;

    // Vocabulary richness
    $unique_words = count(array_unique($words));
    $vocab_ratio = $unique_words / max($word_count, 1);
    if ($vocab_ratio > 0.7) $structure_score += 15;

    // Cap pattern scores
    $chatgpt_score = min($chatgpt_score, 100);
    $gemini_score = min($gemini_score, 100);
    $claude_score = min($claude_score, 100);

    // Calculate total AI score
    $max_pattern_score = max($chatgpt_score, $gemini_score, $claude_score);

    if ($max_pattern_score > 0) {
        // Pattern found = definitely AI
        $total_ai_score = min($max_pattern_score + $structure_score + 20, 100);
    } else {
        // No pattern = check structure only
        $total_ai_score = min($structure_score, 40);
    }

    $human_score = 100 - $total_ai_score;

    // Determine result
    if ($total_ai_score < 40) {
        $result = "Human Written";
        $ai_source = "Human";
    } elseif ($total_ai_score >= 40 && $total_ai_score < 55) {
        $result = "Mixed Content";
        $ai_source = "Human + AI";
    } else {
        $result = "AI Generated";
        if ($chatgpt_score >= $gemini_score && 
            $chatgpt_score >= $claude_score) {
            $ai_source = "ChatGPT";
        } elseif ($gemini_score >= $chatgpt_score && 
                  $gemini_score >= $claude_score) {
            $ai_source = "Gemini";
        } elseif ($claude_score > 0) {
            $ai_source = "Claude";
        } else {
            $ai_source = "Unknown AI Tool";
        }
    }

    // Save to database
    $text_escaped = mysqli_real_escape_string($conn, $text);
    $sql = "INSERT INTO analyses (user_id, text_input, result, confidence) 
            VALUES ('$user_id', '$text_escaped', '$result - $ai_source', '$total_ai_score')";
    mysqli_query($conn, $sql);

    echo json_encode([
        'result' => $result,
        'ai_source' => $ai_source,
        'ai_score' => $total_ai_score,
        'human_score' => $human_score,
        'word_count' => $word_count,
        'source_scores' => [
            'chatgpt' => $chatgpt_score,
            'gemini' => $gemini_score,
            'claude' => $claude_score
        ]
    ]);
}
?>