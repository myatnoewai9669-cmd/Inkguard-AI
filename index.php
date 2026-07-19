<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkGuard - Content Detector</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1>🛡️InkGuard</h1>
        <p>Detect AI-generated vs Human-created content</p>
    </div>

    <!-- Input Section -->
    <div class="input-section">
        <h2>Paste your text below</h2>
        <textarea id="userText" rows="8" 
            placeholder="Paste any text here to detect if it was written by AI or a human...">
        </textarea>
        <button onclick="analyzeText()">🔍 Analyze</button>
    </div>

    <!-- Result Section -->
    <div class="result-section" id="result" style="display:none;">
        <h2>Analysis Result</h2>
        <div id="resultContent"></div>
    </div>

    <!-- History Link -->
    <div class="history-link">
        <a href="history.php">📋 View History</a>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>