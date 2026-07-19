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
    <title>Prompt Scanner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0a0a1a;
            min-height: 100vh;
            color: #fff;
        }
        .page-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .page-title h1 {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .page-title p { color: #aaa; font-size: 14px; }
        .card {
            background: #1a1a2e;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #333;
            margin-bottom: 24px;
        }
        .card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #7c7cff;
            margin-bottom: 16px;
        }
        textarea {
            width: 100%;
            min-height: 180px;
            border: 2px solid #333;
            border-radius: 10px;
            padding: 14px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            color: #fff;
            background: #0a0a1a;
            line-height: 1.6;
        }
        textarea:focus {
            outline: none;
            border-color: #7c7cff;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            flex: 1;
        }
        .btn-secondary {
            background: #333;
            color: #aaa;
        }
        .result-wrap { display: none; }
        .risk-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .risk-high {
            background: rgba(255,68,68,0.2);
            color: #ff4444;
            border: 2px solid #ff4444;
        }
        .risk-medium {
            background: rgba(255,200,0,0.2);
            color: #ffd700;
            border: 2px solid #ffd700;
        }
        .risk-low {
            background: rgba(0,204,102,0.2);
            color: #00cc66;
            border: 2px solid #00cc66;
        }
        .threat-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        .threat-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: #0a0a1a;
            border-radius: 10px;
            border-left: 4px solid;
        }
        .threat-item.high { border-color: #ff4444; }
        .threat-item.medium { border-color: #ffd700; }
        .threat-item.low { border-color: #00cc66; }
        .threat-icon { font-size: 20px; }
        .threat-info .title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
        }
        .threat-info .desc {
            font-size: 12px;
            color: #aaa;
            margin-top: 3px;
        }
        .threat-info .matched {
            font-size: 12px;
            color: #ff4444;
            margin-top: 4px;
            font-style: italic;
        }
        .score-bar-wrap {
            margin: 20px 0;
        }
        .score-bar-wrap label {
            display: flex;
            justify-content: space-between;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .score-bar {
            height: 12px;
            background: #0a0a1a;
            border-radius: 99px;
            overflow: hidden;
        }
        .score-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.8s ease;
        }
        .safe-box {
            text-align: center;
            padding: 30px;
            color: #00cc66;
        }
        .safe-box .safe-icon { font-size: 50px; margin-bottom: 10px; }
        .safe-box h3 { font-size: 22px; font-weight: 800; }
        .safe-box p { color: #aaa; margin-top: 8px; }
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .nav-tab {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: #aaa;
            background: #1a1a2e;
            border: 1px solid #333;
            transition: all 0.2s;
        }
        .nav-tab.active,
        .nav-tab:hover {
            background: linear-gradient(135deg, #7c7cff, #5555dd);
            color: #fff;
            border-color: #7c7cff;
        }
        .loading {
            text-align: center;
            padding: 30px;
            color: #7c7cff;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>🛡️ Guard AI</h1>
    <div class="user-info">
        <span>👤 <?= $username ?></span>
        <a href="history.php">📋 History</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="page-wrap">

    <!-- Nav Tabs -->
    <div class="nav-tabs">
        <a href="dashboard.php" class="nav-tab">🏠 Dashboard</a>
        <a href="detector.php" class="nav-tab">🔍 Detector</a>
        <a href="plagiarism.php" class="nav-tab">📄 Plagiarism</a>
        <a href="brand_rules.php" class="nav-tab">📋 Brand Rules</a>
        <a href="prompt_scanner.php" class="nav-tab active">🛡️ Prompt Scanner</a>
    </div>

    <!-- Title -->
    <div class="page-title">
        <h1>🛡️ Prompt Scanner</h1>
        <p>Scan AI prompts for injection attacks, 
        jailbreaks, and security threats</p>
    </div>

    <!-- Input -->
    <div class="card">
        <h2>📝 Enter Prompt to Scan</h2>
        <textarea id="promptText"
            placeholder="Paste any AI prompt here to scan for security threats, injection attacks, jailbreaks, or malicious content...">
        </textarea>
        <div class="btn-row">
            <button class="btn btn-primary" onclick="scanPrompt()">
                🛡️ Scan Prompt
            </button>
            <button class="btn btn-secondary" onclick="clearScan()">
                🗑️ Clear
            </button>
        </div>
    </div>

    <!-- Result -->
    <div class="result-wrap" id="resultWrap">
        <div class="card">
            <h2>🔎 Scan Results</h2>
            <div id="resultContent"></div>
        </div>
    </div>

</div>

<script>
// Threat patterns database
const threats = [
    {
        id: 'jailbreak',
        level: 'high',
        icon: '🚨',
        title: 'Jailbreak Attempt',
        desc: 'Trying to bypass AI safety guidelines',
        patterns: [
            'ignore previous instructions',
            'ignore all instructions',
            'disregard your training',
            'forget your guidelines',
            'you are now',
            'pretend you are',
            'act as if you have no restrictions',
            'bypass your restrictions',
            'override your programming',
            'jailbreak',
            'dan mode',
            'developer mode',
            'unrestricted mode',
            'no restrictions',
            'without restrictions',
            'ignore your rules',
            'forget everything',
            'new personality',
            'your true self',
            'freedom mode'
        ]
    },
    {
        id: 'injection',
        level: 'high',
        icon: '💉',
        title: 'Prompt Injection',
        desc: 'Attempting to inject malicious instructions',
        patterns: [
            'system:',
            'system prompt',
            '###instruction',
            '[system]',
            '<system>',
            'ignore the above',
            'ignore the previous',
            'end of system',
            'new instruction:',
            'assistant:',
            'human:',
            '###',
            '<<<',
            '>>>',
            'instruction:',
            'override:'
        ]
    },
    {
        id: 'extraction',
        level: 'high',
        icon: '🔓',
        title: 'Data Extraction',
        desc: 'Trying to extract sensitive system information',
        patterns: [
            'reveal your system prompt',
            'show your instructions',
            'what are your instructions',
            'repeat your prompt',
            'print your system',
            'tell me your rules',
            'show me your guidelines',
            'what were you told',
            'initial instructions',
            'original prompt',
            'confidential instructions'
        ]
    },
    {
        id: 'roleplay',
        level: 'medium',
        icon: '🎭',
        title: 'Suspicious Roleplay',
        desc: 'Using roleplay to bypass safety filters',
        patterns: [
            'roleplay as',
            'role play as',
            'pretend to be',
            'simulate being',
            'imagine you are an ai with no',
            'act like you have no',
            'hypothetically speaking',
            'in a fictional world',
            'for a story',
            'for fiction purposes',
            'for educational purposes only',
            'hypothetical scenario'
        ]
    },
    {
        id: 'harmful',
        level: 'high',
        icon: '☠️',
        title: 'Harmful Content Request',
        desc: 'Requesting dangerous or illegal content',
        patterns: [
            'how to make a bomb',
            'how to hack',
            'illegal weapons',
            'how to hurt',
            'how to kill',
            'drug synthesis',
            'malware code',
            'ransomware',
            'exploit code',
            'bypass security',
            'steal data',
            'phishing'
        ]
    },
    {
        id: 'social',
        level: 'medium',
        icon: '🎣',
        title: 'Social Engineering',
        desc: 'Manipulative language to influence AI behavior',
        patterns: [
            'my life depends on it',
            'i will reward you',
            'please just this once',
            'no one will know',
            'this is urgent',
            'emergency situation',
            'trust me',
            'between us',
            'off the record',
            'confidentially'
        ]
    },
    {
        id: 'encoding',
        level: 'medium',
        icon: '🔢',
        title: 'Encoded Instructions',
        desc: 'Using encoding to hide malicious content',
        patterns: [
            'base64',
            'hex decode',
            'rot13',
            'caesar cipher',
            'decode this',
            'translate from binary',
            'unicode escape'
        ]
    }
];

function scanPrompt() {
    const text = document.getElementById('promptText').value.trim();

    if (!text) {
        alert('Please enter a prompt to scan!');
        return;
    }

    document.getElementById('resultWrap').style.display = 'block';
    document.getElementById('resultContent').innerHTML = `
        <div class="loading">
            <p>🛡️ Scanning for threats...</p>
        </div>
    `;

    // Simulate scan delay
    setTimeout(() => {
        const textLower = text.toLowerCase();
        const foundThreats = [];

        threats.forEach(threat => {
            const matched = [];
            threat.patterns.forEach(pattern => {
                if (textLower.includes(pattern.toLowerCase())) {
                    matched.push(pattern);
                }
            });
            if (matched.length > 0) {
                foundThreats.push({ ...threat, matched });
            }
        });

        // Calculate risk score
        let riskScore = 0;
        foundThreats.forEach(t => {
            if (t.level === 'high') riskScore += 35;
            else if (t.level === 'medium') riskScore += 20;
            else riskScore += 10;
        });
        riskScore = Math.min(riskScore, 100);

        // Determine overall risk
        let riskLevel = 'LOW';
        let riskColor = '#00cc66';
        let riskEmoji = '✅';

        if (riskScore >= 60) {
            riskLevel = 'HIGH';
            riskColor = '#ff4444';
            riskEmoji = '🚨';
        } else if (riskScore >= 30) {
            riskLevel = 'MEDIUM';
            riskColor = '#ffd700';
            riskEmoji = '⚠️';
        }

        if (foundThreats.length === 0) {
            document.getElementById('resultContent').innerHTML = `
                <div class="safe-box">
                    <div class="safe-icon">✅</div>
                    <h3>Prompt is Safe!</h3>
                    <p>No security threats detected in this prompt.</p>
                </div>
            `;
            return;
        }

        const threatHTML = foundThreats.map(t => `
            <li class="threat-item ${t.level}">
                <div class="threat-icon">${t.icon}</div>
                <div class="threat-info">
                    <div class="title">${t.title}
                        <span style="font-size:11px; 
                            padding:2px 8px; border-radius:10px;
                            margin-left:8px;
                            background:${t.level === 'high' ? 
                                'rgba(255,68,68,0.2)' : 
                                'rgba(255,200,0,0.2)'};
                            color:${t.level === 'high' ? 
                                '#ff4444' : '#ffd700'}">
                            ${t.level.toUpperCase()}
                        </span>
                    </div>
                    <div class="desc">${t.desc}</div>
                    <div class="matched">
                        Matched: "${t.matched.join('", "')}"
                    </div>
                </div>
            </li>
        `).join('');

        document.getElementById('resultContent').innerHTML = `

            <!-- Risk Level -->
            <div style="text-align:center; margin-bottom:20px">
                <div class="risk-badge risk-${riskLevel.toLowerCase()}">
                    ${riskEmoji} ${riskLevel} RISK
                </div>
            </div>

            <!-- Risk Score Bar -->
            <div class="score-bar-wrap">
                <label>
                    <span>Risk Score</span>
                    <span style="color:${riskColor}; 
                        font-weight:800">
                        ${riskScore}/100
                    </span>
                </label>
                <div class="score-bar">
                    <div class="score-fill"
                        style="width:${riskScore}%; 
                        background:${riskColor}">
                    </div>
                </div>
            </div>

            <!-- Threats Found -->
            <div style="margin-bottom:10px">
                <p style="color:#aaa; font-size:13px">
                    ⚠️ ${foundThreats.length} threat(s) detected:
                </p>
            </div>
            <ul class="threat-list">
                ${threatHTML}
            </ul>

            <!-- Recommendation -->
            <div style="margin-top:20px; padding:15px; 
                background:rgba(255,68,68,0.1); 
                border:1px solid #ff4444; 
                border-radius:10px;">
                <p style="color:#ff4444; font-weight:700; 
                    margin-bottom:5px">
                    🛡️ Recommendation:
                </p>
                <p style="color:#ccc; font-size:13px">
                    ${riskLevel === 'HIGH' ? 
                        'This prompt contains high-risk content. Do NOT process this prompt. It may be attempting to manipulate or jailbreak an AI system.' :
                        'This prompt contains suspicious patterns. Review carefully before processing.'}
                </p>
            </div>
        `;

    }, 1500);
}

function clearScan() {
    document.getElementById('promptText').value = '';
    document.getElementById('resultWrap').style.display = 'none';
}
</script>
</body>
</html>