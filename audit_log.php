<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get all analyses for audit log
$sql = "SELECT * FROM analyses WHERE user_id='$user_id' 
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Stats
$total = mysqli_num_rows($result);
$ai_count = 0;
$human_count = 0;
$mixed_count = 0;

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
    if (strpos($row['result'], 'AI Generated') !== false) $ai_count++;
    elseif (strpos($row['result'], 'Human Written') !== false) $human_count++;
    else $mixed_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - InkGuard</title>
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
            max-width: 1000px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-tile {
            background: #1a1a2e;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            border: 1px solid #333;
        }
        .stat-tile .num {
            font-size: 36px;
            font-weight: 800;
            line-height: 1;
        }
        .stat-tile .label {
            font-size: 12px;
            color: #aaa;
            margin-top: 6px;
        }
        .stat-tile.total .num { color: #7c7cff; }
        .stat-tile.ai .num { color: #ff4444; }
        .stat-tile.human .num { color: #00cc66; }
        .stat-tile.mixed .num { color: #ffd700; }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #333;
            background: #0a0a1a;
            color: #aaa;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn.active,
        .filter-btn:hover {
            background: #7c7cff;
            color: #fff;
            border-color: #7c7cff;
        }
        .search-box {
            flex: 1;
            padding: 8px 14px;
            background: #0a0a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
        }
        .search-box:focus {
            outline: none;
            border-color: #7c7cff;
        }

        /* Table */
        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }
        .audit-table th {
            background: #0a0a1a;
            color: #7c7cff;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
            font-size: 13px;
        }
        .audit-table td {
            padding: 12px;
            border-bottom: 1px solid #1a1a2e;
            font-size: 13px;
            color: #ccc;
            vertical-align: middle;
        }
        .audit-table tr:hover td {
            background: rgba(124,124,255,0.05);
        }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-ai {
            background: rgba(255,68,68,0.2);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        .badge-human {
            background: rgba(0,204,102,0.2);
            color: #00cc66;
            border: 1px solid #00cc66;
        }
        .badge-mixed {
            background: rgba(255,215,0,0.2);
            color: #ffd700;
            border: 1px solid #ffd700;
        }
        .confidence-bar {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mini-bar {
            width: 60px;
            height: 6px;
            background: #333;
            border-radius: 99px;
            overflow: hidden;
        }
        .mini-fill {
            height: 100%;
            border-radius: 99px;
        }
        .expand-btn {
            background: none;
            border: 1px solid #333;
            color: #7c7cff;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
        }
        .expand-btn:hover {
            background: #7c7cff;
            color: #fff;
        }
        .text-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal {
            background: #1a1a2e;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 28px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal h3 {
            color: #7c7cff;
            margin-bottom: 15px;
        }
        .modal p {
            color: #ccc;
            line-height: 1.6;
            font-size: 14px;
        }
        .modal-close {
            float: right;
            background: #ff4444;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #aaa;
        }
        .empty-state .icon { font-size: 50px; margin-bottom: 10px; }
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
        .export-btn {
            background: linear-gradient(135deg, #00cc66, #009944);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
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
        <a href="prompt_scanner.php" class="nav-tab">🛡️ Prompt Scanner</a>
        <a href="audit_log.php" class="nav-tab active">📊 Audit Log</a>
    </div>

    <!-- Title -->
    <div class="page-title">
        <h1>📊 Audit Log</h1>
        <p>Complete history of all content analyses performed</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-tile total">
            <div class="num"><?= $total ?></div>
            <div class="label">Total Analyses</div>
        </div>
        <div class="stat-tile ai">
            <div class="num"><?= $ai_count ?></div>
            <div class="label">🤖 AI Generated</div>
        </div>
        <div class="stat-tile human">
            <div class="num"><?= $human_count ?></div>
            <div class="label">👤 Human Written</div>
        </div>
        <div class="stat-tile mixed">
            <div class="num"><?= $mixed_count ?></div>
            <div class="label">⚠️ Mixed Content</div>
        </div>
    </div>

    <!-- Log Table -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; 
            align-items:center; margin-bottom:16px">
            <h2 style="margin:0">📋 Analysis Log</h2>
            <button class="export-btn" onclick="exportCSV()">
                📥 Export CSV
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <button class="filter-btn active" 
                onclick="filterLog('all', this)">
                All (<?= $total ?>)
            </button>
            <button class="filter-btn" 
                onclick="filterLog('ai', this)">
                🤖 AI (<?= $ai_count ?>)
            </button>
            <button class="filter-btn" 
                onclick="filterLog('human', this)">
                👤 Human (<?= $human_count ?>)
            </button>
            <button class="filter-btn" 
                onclick="filterLog('mixed', this)">
                ⚠️ Mixed (<?= $mixed_count ?>)
            </button>
            <input type="text" class="search-box" 
                id="searchBox"
                placeholder="🔍 Search text..."
                oninput="searchLog()">
        </div>

        <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No analyses yet. Start analyzing content!</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="audit-table" id="auditTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date & Time</th>
                    <th>Text Preview</th>
                    <th>Result</th>
                    <th>Confidence</th>
                    <th>Words</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($rows as $i => $row):
                    $isAI = strpos($row['result'], 'AI') !== false;
                    $isHuman = strpos($row['result'], 'Human') !== false;
                    $badgeClass = $isAI ? 'badge-ai' : 
                        ($isHuman ? 'badge-human' : 'badge-mixed');
                    $badgeEmoji = $isAI ? '🤖' : 
                        ($isHuman ? '👤' : '⚠️');
                    $confColor = $isAI ? '#ff4444' : 
                        ($isHuman ? '#00cc66' : '#ffd700');
                    $wordCount = str_word_count($row['text_input']);
                    $dataType = $isAI ? 'ai' : 
                        ($isHuman ? 'human' : 'mixed');
                ?>
                <tr data-type="<?= $dataType ?>"
                    data-text="<?= strtolower(
                        htmlspecialchars($row['text_input'])) ?>">
                    <td style="color:#aaa"><?= $i + 1 ?></td>
                    <td style="white-space:nowrap">
                        <?= date('M d, Y', 
                            strtotime($row['created_at'])) ?>
                        <br>
                        <span style="color:#aaa; font-size:11px">
                            <?= date('h:i A', 
                                strtotime($row['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="text-preview">
                            <?= htmlspecialchars(
                                substr($row['text_input'], 0, 60)) ?>...
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= $badgeEmoji ?> <?= $row['result'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="confidence-bar">
                            <div class="mini-bar">
                                <div class="mini-fill"
                                    style="width:<?= $row['confidence'] ?>%;
                                    background:<?= $confColor ?>">
                                </div>
                            </div>
                            <span style="color:<?= $confColor ?>; 
                                font-weight:700">
                                <?= $row['confidence'] ?>%
                            </span>
                        </div>
                    </td>
                    <td><?= $wordCount ?></td>
                    <td>
                        <button class="expand-btn"
                            onclick="showModal(
                                '<?= addslashes(htmlspecialchars(
                                    $row['text_input'])) ?>',
                                '<?= $row['result'] ?>',
                                '<?= $row['confidence'] ?>',
                                '<?= date('M d Y h:i A', 
                                    strtotime($row['created_at'])) ?>'
                            )">
                            👁️ View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay" 
    onclick="closeModal(event)">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()">
            ✕ Close
        </button>
        <h3>📄 Full Text Analysis</h3>
        <div id="modalContent"></div>
    </div>
</div>

<script>
function filterLog(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('active');
    });
    btn.classList.add('active');

    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function searchLog() {
    const query = document.getElementById('searchBox')
        .value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(row => {
        const text = row.dataset.text || '';
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

function showModal(text, result, confidence, date) {
    const isAI = result.includes('AI');
    const isHuman = result.includes('Human');
    const color = isAI ? '#ff4444' : 
        isHuman ? '#00cc66' : '#ffd700';
    const emoji = isAI ? '🤖' : isHuman ? '👤' : '⚠️';

    document.getElementById('modalContent').innerHTML = `
        <div style="margin-bottom:15px">
            <span style="background:${color}22; color:${color};
                padding:5px 12px; border-radius:20px; 
                font-weight:700; border:1px solid ${color}">
                ${emoji} ${result}
            </span>
            <span style="color:#aaa; font-size:12px; 
                margin-left:10px">
                ${confidence}% confidence
            </span>
        </div>
        <div style="color:#aaa; font-size:12px; 
            margin-bottom:12px">
            📅 ${date}
        </div>
        <div style="background:#0a0a1a; padding:15px; 
            border-radius:10px; border:1px solid #333;
            color:#ccc; font-size:13px; line-height:1.7;
            max-height:300px; overflow-y:auto">
            ${text}
        </div>
    `;
    document.getElementById('modalOverlay')
        .classList.add('show');
}

function closeModal(e) {
    if (!e || e.target.id === 'modalOverlay') {
        document.getElementById('modalOverlay')
            .classList.remove('show');
    }
}

function exportCSV() {
    const rows = document.querySelectorAll('#tableBody tr');
    let csv = 'No,Date,Result,Confidence,Words\n';
    let i = 1;
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            const date = cells[1].innerText
                .replace('\n', ' ').trim();
            const result = cells[3].innerText.trim();
            const conf = cells[4].innerText.trim();
            const words = cells[5].innerText.trim();
            csv += `${i++},"${date}","${result}",${conf},${words}\n`;
        }
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inkguard_audit_log.csv';
    a.click();
}
</script>
</body>
</html>