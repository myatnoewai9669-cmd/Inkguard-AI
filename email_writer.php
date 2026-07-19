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
    <title>AI Email Writer - InkGuard</title>
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
        .navbar-brand {
            display:flex; align-items:center;
            gap:10px; text-decoration:none;
        }
        .brand-icon {
            width:38px; height:38px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            border-radius:10px;
            display:flex; align-items:center;
            justify-content:center; font-size:20px;
        }
        .brand-name {
            font-size:20px; font-weight:800;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .navbar-right { display:flex; align-items:center; gap:8px; }
        .nav-link {
            padding:7px 16px; border-radius:8px;
            font-size:13px; color:#aaa;
            text-decoration:none; transition:all 0.2s;
            border:1px solid transparent;
        }
        .nav-link:hover {
            background:rgba(124,124,255,0.1);
            color:#fff; border-color:rgba(124,124,255,0.3);
        }
        .nav-link.danger { color:#ff6b6b; }
        .page {
            position:relative; z-index:1;
            max-width:900px; margin:0 auto;
            padding:30px 20px;
        }
        .page-title { text-align:center; margin-bottom:30px; }
        .page-title h1 {
            font-size:32px; font-weight:800; margin-bottom:8px;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .page-title p { color:#888; font-size:14px; }
        .glass-card {
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:28px;
            backdrop-filter:blur(10px);
            margin-bottom:24px;
        }
        .glass-card h2 {
            font-size:18px; font-weight:700;
            margin-bottom:16px; color:#fff;
        }
        .type-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:10px; margin-bottom:16px;
        }
        .type-btn {
            padding:12px 8px; text-align:center;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:10px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:12px;
            transition:all 0.2s;
        }
        .type-btn:hover, .type-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
        }
        .type-icon { font-size:20px; display:block; margin-bottom:4px; }
        .form-group { margin-bottom:16px; }
        .form-group label {
            display:block; font-size:13px;
            color:#888; margin-bottom:6px;
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
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        textarea.form-input {
            min-height:90px; resize:vertical; line-height:1.6;
        }

        .tone-row { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .tone-chip {
            padding:8px 16px; border-radius:20px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(0,0,0,0.2); color:#aaa;
            cursor:pointer; font-size:12px; transition:all 0.2s;
        }
        .tone-chip.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.2);
            color:#fff;
        }

        .btn-generate {
            width:100%; padding:14px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:16px; font-weight:700; cursor:pointer;
            transition:all 0.3s;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-generate:hover { transform:translateY(-2px); }

        .result-section { display:none; }
        .email-preview {
            background:#fff; color:#1a1a1a;
            border-radius:12px; overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,0.4);
        }
        .email-header {
            background:#f5f5f5; padding:16px 20px;
            border-bottom:1px solid #e0e0e0;
        }
        .email-field {
            display:flex; gap:8px; margin-bottom:6px;
            font-size:13px;
        }
        .email-field-label {
            color:#888; min-width:50px; font-weight:600;
        }
        .email-body {
            padding:24px; font-size:14px; line-height:1.8;
            white-space:pre-wrap; color:#333;
            min-height:200px;
        }
        .result-actions {
            display:flex; gap:10px; margin-top:16px;
        }
        .btn-copy {
            flex:1; padding:12px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
        }
        .btn-copy:hover { transform:translateY(-1px); }
        .btn-regen {
            padding:12px 20px;
            background:rgba(255,255,255,0.05);
            color:#aaa; border:1px solid rgba(255,255,255,0.1);
            border-radius:10px; cursor:pointer; font-size:14px;
        }
        .loading-wrap { text-align:center; padding:40px; }
        .spinner {
            width:50px; height:50px;
            border:3px solid rgba(124,124,255,0.2);
            border-top-color:#7c7cff;
            border-radius:50%;
            animation:spin 0.8s linear infinite;
            margin:0 auto 16px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }
        @media(max-width:600px) {
            .type-grid { grid-template-columns:repeat(2,1fr); }
            .grid-2 { grid-template-columns:1fr; }
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
        <h1>📧 AI Email Writer</h1>
        <p>Generate professional emails instantly for any purpose</p>
    </div>

    <div class="glass-card">
        <h2>📋 Email Type</h2>
        <div class="type-grid">
            <div class="type-btn selected" onclick="selectType(this,'job_application')">
                <span class="type-icon">💼</span>
                Job Application
            </div>
            <div class="type-btn" onclick="selectType(this,'follow_up')">
                <span class="type-icon">🔄</span>
                Follow Up
            </div>
            <div class="type-btn" onclick="selectType(this,'meeting_request')">
                <span class="type-icon">📅</span>
                Meeting Request
            </div>
            <div class="type-btn" onclick="selectType(this,'thank_you')">
                <span class="type-icon">🙏</span>
                Thank You
            </div>
            <div class="type-btn" onclick="selectType(this,'complaint')">
                <span class="type-icon">⚠️</span>
                Complaint
            </div>
            <div class="type-btn" onclick="selectType(this,'introduction')">
                <span class="type-icon">👋</span>
                Introduction
            </div>
            <div class="type-btn" onclick="selectType(this,'resignation')">
                <span class="type-icon">📝</span>
                Resignation
            </div>
            <div class="type-btn" onclick="selectType(this,'apology')">
                <span class="type-icon">😔</span>
                Apology
            </div>
            <div class="type-btn" onclick="selectType(this,'custom')">
                <span class="type-icon">✨</span>
                Custom
            </div>
        </div>
    </div>

    <div class="glass-card">
        <h2>✍️ Email Details</h2>

        <div class="grid-2">
            <div class="form-group">
                <label>Recipient Name</label>
                <input type="text" class="form-input" id="recipientName"
                    placeholder="e.g. Mr. Smith / Hiring Manager">
            </div>
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" class="form-input" id="senderName"
                    placeholder="e.g. Myat Noe Wai">
            </div>
        </div>

        <div class="form-group">
            <label>Subject / Purpose *</label>
            <input type="text" class="form-input" id="emailSubject"
                placeholder="e.g. Application for Web Developer Position">
        </div>

        <div class="form-group">
            <label>Key Points / Context *</label>
            <textarea class="form-input" id="emailContext"
                placeholder="e.g. I am applying for the Web Developer role, I have 2 years experience in PHP/MySQL, graduated from Nusa Putra University, available to start immediately...">
            </textarea>
        </div>

        <div class="form-group">
            <label>Tone:</label>
            <div class="tone-row">
                <div class="tone-chip selected" onclick="selectTone(this,'formal')">
                    🎩 Formal
                </div>
                <div class="tone-chip" onclick="selectTone(this,'professional')">
                    💼 Professional
                </div>
                <div class="tone-chip" onclick="selectTone(this,'friendly')">
                    😊 Friendly
                </div>
                <div class="tone-chip" onclick="selectTone(this,'concise')">
                    ⚡ Concise
                </div>
            </div>
        </div>

        <button class="btn-generate" onclick="generateEmail()">
            ✨ Generate Email
        </button>
    </div>

    <div class="glass-card result-section" id="resultSection">
        <h2>📧 Generated Email</h2>
        <div id="emailContent"></div>
        <div class="result-actions">
            <button class="btn-copy" onclick="copyEmail()">
                📋 Copy Email
            </button>
            <button class="btn-regen" onclick="generateEmail()">
                🔄 Regenerate
            </button>
        </div>
    </div>

</div>

<script>
let selectedType = 'job_application';
let selectedTone = 'formal';
let generatedSubject = '';
let generatedBody = '';

function selectType(el, type) {
    document.querySelectorAll('.type-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    selectedType = type;
}

function selectTone(el, tone) {
    document.querySelectorAll('.tone-chip').forEach(t =>
        t.classList.remove('selected'));
    el.classList.add('selected');
    selectedTone = tone;
}

const greetings = {
    formal: (name) => name ? `Dear ${name},` : 'Dear Sir/Madam,',
    professional: (name) => name ? `Dear ${name},` : 'Dear Hiring Manager,',
    friendly: (name) => name ? `Hi ${name},` : 'Hello,',
    concise: (name) => name ? `Hi ${name},` : 'Hello,',
};

const closings = {
    formal: 'Yours sincerely,',
    professional: 'Best regards,',
    friendly: 'Best,',
    concise: 'Thanks,',
};

const templates = {
    job_application: {
        subject: (ctx) => `Application for ${extractRole(ctx)} Position`,
        body: (context, sender, tone) => {
            const intro = tone === 'concise' ?
                `I'm writing to apply for the position mentioned.` :
                `I am writing to express my interest in the position at your organization.`;
            return `${intro}\n\n${context}\n\n${
                tone === 'concise' ?
                'I would appreciate the opportunity to discuss this further.' :
                'I would welcome the opportunity to discuss how my skills and experience align with your needs. Thank you for considering my application, and I look forward to hearing from you.'
            }`;
        }
    },
    follow_up: {
        subject: () => `Following Up on Our Previous Conversation`,
        body: (context, sender, tone) => {
            return `I hope this email finds you well. I wanted to follow up regarding ${context}\n\n${
                tone === 'concise' ?
                'Please let me know if you need any additional information.' :
                'I would greatly appreciate an update on this matter at your earliest convenience. Please don\'t hesitate to reach out if you have any questions.'
            }`;
        }
    },
    meeting_request: {
        subject: () => `Meeting Request`,
        body: (context, sender, tone) => {
            return `I would like to schedule a meeting to discuss ${context}\n\n${
                tone === 'concise' ?
                'Please let me know your availability.' :
                'Could you please let me know your availability in the coming days? I am flexible and can accommodate your schedule. Looking forward to our discussion.'
            }`;
        }
    },
    thank_you: {
        subject: () => `Thank You`,
        body: (context, sender, tone) => {
            return `I wanted to take a moment to express my sincere gratitude regarding ${context}\n\n${
                tone === 'concise' ?
                'Thank you again for everything.' :
                'Your support and guidance have meant a great deal to me, and I truly appreciate the time and effort you have invested. Thank you once again.'
            }`;
        }
    },
    complaint: {
        subject: () => `Regarding an Issue That Requires Your Attention`,
        body: (context, sender, tone) => {
            return `I am writing to bring to your attention an issue regarding ${context}\n\n${
                tone === 'concise' ?
                'I would appreciate a prompt resolution to this matter.' :
                'I would greatly appreciate it if this matter could be addressed as soon as possible. I trust that you will take the necessary steps to resolve this issue, and I look forward to your prompt response.'
            }`;
        }
    },
    introduction: {
        subject: () => `Introduction and Interest in Connecting`,
        body: (context, sender, tone) => {
            return `I hope this message finds you well. I am reaching out to introduce myself. ${context}\n\n${
                tone === 'concise' ?
                'I would love to connect further.' :
                'I would be delighted to learn more about your work and explore potential opportunities to collaborate. Please let me know if you would be available for a brief conversation.'
            }`;
        }
    },
    resignation: {
        subject: () => `Notice of Resignation`,
        body: (context, sender, tone) => {
            return `I am writing to formally notify you of my resignation. ${context}\n\n${
                tone === 'concise' ?
                'Thank you for the opportunities during my time here.' :
                'I want to express my sincere gratitude for the opportunities I have been given during my time here. I am committed to ensuring a smooth transition and am happy to assist in any way during this period.'
            }`;
        }
    },
    apology: {
        subject: () => `My Sincere Apologies`,
        body: (context, sender, tone) => {
            return `I would like to sincerely apologize for ${context}\n\n${
                tone === 'concise' ?
                'It won\'t happen again.' :
                'I take full responsibility for this situation and want to assure you that I am taking steps to prevent this from happening again. Please accept my sincere apologies for any inconvenience caused.'
            }`;
        }
    },
    custom: {
        subject: (ctx) => extractRole(ctx) || 'Regarding Our Correspondence',
        body: (context, sender, tone) => {
            return context;
        }
    }
};

function extractRole(context) {
    const match = context.match(/(?:for|as)\s+(?:a|an|the)?\s*([\w\s]+?)(?:position|role|job)/i);
    if (match) return match[1].trim();
    return '';
}

function generateEmail() {
    const recipient = document.getElementById('recipientName').value.trim();
    const sender = document.getElementById('senderName').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();
    const context = document.getElementById('emailContext').value.trim();

    if (!subject || !context) {
        alert('Please fill in Subject and Key Points!');
        return;
    }

    const resultSection = document.getElementById('resultSection');
    const emailContent = document.getElementById('emailContent');

    resultSection.style.display = 'block';
    emailContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">
                ✨ Writing your email...
            </p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    setTimeout(() => {
        const template = templates[selectedType];
        const greeting = greetings[selectedTone](recipient);
        const closing = closings[selectedTone];

        const bodyText = template.body(context, sender, selectedTone);

        generatedSubject = subject;
        generatedBody = `${greeting}\n\n${bodyText}\n\n${closing}\n${sender || '[Your Name]'}`;

        emailContent.innerHTML = `
            <div class="email-preview">
                <div class="email-header">
                    <div class="email-field">
                        <span class="email-field-label">To:</span>
                        <span>${recipient || '[Recipient]'}</span>
                    </div>
                    <div class="email-field">
                        <span class="email-field-label">Subject:</span>
                        <span style="font-weight:600">${subject}</span>
                    </div>
                </div>
                <div class="email-body">${generatedBody}</div>
            </div>`;

    }, 1200);
}

function copyEmail() {
    const fullEmail = `Subject: ${generatedSubject}\n\n${generatedBody}`;
    navigator.clipboard.writeText(fullEmail).then(() => {
        alert('✅ Email copied to clipboard!');
    });
}
</script>
</body>
</html>