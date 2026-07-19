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
    <title>AI CV Generator - InkGuard</title>
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
        .navbar-right {
            display:flex; align-items:center; gap:8px;
        }
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
            max-width:1100px; margin:0 auto;
            padding:30px 20px;
        }
        .page-title {
            text-align:center; margin-bottom:30px;
        }
        .page-title h1 {
            font-size:32px; font-weight:800; margin-bottom:8px;
            background:linear-gradient(135deg,#7c7cff,#00cc66);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .page-title p { color:#888; font-size:14px; }
        .layout {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:24px;
        }
        .glass-card {
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:24px;
            backdrop-filter:blur(10px);
            margin-bottom:20px;
        }
        .glass-card h2 {
            font-size:16px; font-weight:700;
            margin-bottom:16px; color:#7c7cff;
            display:flex; align-items:center; gap:8px;
        }
        .form-group { margin-bottom:14px; }
        .form-group label {
            display:block; font-size:12px;
            color:#888; margin-bottom:5px;
        }
        .form-input {
            width:100%; padding:10px 14px;
            background:rgba(0,0,0,0.3);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; color:#fff;
            font-size:13px; font-family:inherit;
            outline:none; transition:border-color 0.3s;
        }
        .form-input:focus {
            border-color:rgba(124,124,255,0.5);
        }
        .form-input::placeholder { color:#555; }
        textarea.form-input {
            min-height:80px; resize:vertical; line-height:1.6;
        }
        .grid-2 {
            display:grid; grid-template-columns:1fr 1fr; gap:12px;
        }
        .btn-add {
            width:100%; padding:8px;
            background:rgba(124,124,255,0.1);
            border:1px dashed rgba(124,124,255,0.4);
            color:#7c7cff; border-radius:8px;
            cursor:pointer; font-size:13px;
            transition:all 0.2s; margin-top:8px;
        }
        .btn-add:hover {
            background:rgba(124,124,255,0.2);
        }
        .btn-remove {
            background:rgba(255,68,68,0.1);
            border:none; color:#ff6b6b;
            width:24px; height:24px;
            border-radius:50%; cursor:pointer;
            font-size:14px; flex-shrink:0;
            display:flex; align-items:center;
            justify-content:center;
        }
        .entry-block {
            background:rgba(0,0,0,0.2);
            border:1px solid rgba(255,255,255,0.06);
            border-radius:10px; padding:14px;
            margin-bottom:10px;
        }
        .entry-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:10px;
        }
        .entry-num {
            font-size:12px; color:#7c7cff; font-weight:700;
        }
        .btn-generate {
            width:100%; padding:14px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:16px; font-weight:700; cursor:pointer;
            transition:all 0.3s;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
            margin-top:10px;
        }
        .btn-generate:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(124,124,255,0.5);
        }

        /* CV Preview */
        .cv-preview-wrap {
            position:sticky; top:80px;
        }
        .cv-actions {
            display:flex; gap:10px; margin-bottom:16px;
        }
        .btn-print {
            flex:1; padding:12px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
        }
        .btn-print:hover { transform:translateY(-1px); }

        /* Professional White CV */
        #cvOutput {
            background:#fff; color:#1a1a1a;
            border-radius:12px; overflow:hidden;
            box-shadow:0 10px 40px rgba(0,0,0,0.5);
            font-family:'Segoe UI',sans-serif;
            min-height:400px;
        }
        .cv-empty {
            text-align:center; padding:60px 20px;
            color:#aaa;
        }
        .cv-empty .icon { font-size:50px; margin-bottom:12px; }
        .loading-wrap { text-align:center; padding:30px; }
        .spinner {
            width:40px; height:40px;
            border:3px solid rgba(124,124,255,0.2);
            border-top-color:#7c7cff;
            border-radius:50%;
            animation:spin 0.8s linear infinite;
            margin:0 auto 14px;
        }
        @keyframes spin { to{transform:rotate(360deg)} }

        @media(max-width:768px) {
            .layout { grid-template-columns:1fr; }
            .cv-preview-wrap { position:static; }
        }
        @media(max-width:600px) {
            .grid-2 { grid-template-columns:1fr; }
        }

        /* Print Styles */
        @media print {
            body * { visibility:hidden; }
            #cvOutput, #cvOutput * { visibility:visible; }
            #cvOutput {
                position:fixed; top:0; left:0;
                width:100%; height:100%;
                box-shadow:none; border-radius:0;
            }
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
        <h1>📄 AI CV Generator</h1>
        <p>Fill in your details — AI will generate a professional CV instantly</p>
    </div>

    <div class="layout">

        <!-- LEFT: Form -->
        <div class="form-side">

            <!-- Personal Info -->
            <div class="glass-card">
                <h2>👤 Personal Information</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" class="form-input"
                            id="fullName"
                            placeholder="e.g. Myat Noe Wai">
                    </div>
                    <div class="form-group">
                        <label>Job Title *</label>
                        <input type="text" class="form-input"
                            id="jobTitle"
                            placeholder="e.g. Web Developer">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-input"
                            id="email"
                            placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" class="form-input"
                            id="phone"
                            placeholder="+62 xxx xxx xxxx">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" class="form-input"
                            id="location"
                            placeholder="City, Country">
                    </div>
                    <div class="form-group">
                        <label>LinkedIn / GitHub</label>
                        <input type="text" class="form-input"
                            id="linkedin"
                            placeholder="linkedin.com/in/yourname">
                    </div>
                </div>
                <div class="form-group">
                    <label>Professional Summary
                        <span style="color:#7c7cff">
                            (AI will enhance this)
                        </span>
                    </label>
                    <textarea class="form-input" id="summary"
                        placeholder="Brief description of yourself, skills and goals...">
                    </textarea>
                </div>
            </div>

            <!-- Work Experience -->
            <div class="glass-card">
                <h2>💼 Work Experience</h2>
                <div id="experienceList"></div>
                <button class="btn-add" onclick="addExperience()">
                    ➕ Add Work Experience
                </button>
            </div>

            <!-- Education -->
            <div class="glass-card">
                <h2>🎓 Education</h2>
                <div id="educationList"></div>
                <button class="btn-add" onclick="addEducation()">
                    ➕ Add Education
                </button>
            </div>

            <!-- Skills -->
            <div class="glass-card">
                <h2>⚡ Skills</h2>
                <div class="form-group">
                    <label>Technical Skills
                        (comma separated)
                    </label>
                    <textarea class="form-input" id="techSkills"
                        placeholder="PHP, MySQL, JavaScript, Python, HTML/CSS..."
                        style="min-height:60px">
                    </textarea>
                </div>
                <div class="form-group">
                    <label>Soft Skills (comma separated)</label>
                    <textarea class="form-input" id="softSkills"
                        placeholder="Leadership, Communication, Problem Solving..."
                        style="min-height:60px">
                    </textarea>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Languages</label>
                        <input type="text" class="form-input"
                            id="languages"
                            placeholder="English, Burmese, Indonesian">
                    </div>
                    <div class="form-group">
                        <label>Certifications</label>
                        <input type="text" class="form-input"
                            id="certs"
                            placeholder="Google UX Design, IBM...">
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="glass-card">
                <h2>🚀 Projects</h2>
                <div id="projectList"></div>
                <button class="btn-add" onclick="addProject()">
                    ➕ Add Project
                </button>
            </div>

            <button class="btn-generate" onclick="generateCV()">
                ✨ Generate Professional CV
            </button>
        </div>

        <!-- RIGHT: Preview -->
        <div class="cv-preview-wrap">
            <div class="cv-actions">
                <button class="btn-print" onclick="printCV()">
                    🖨️ Print / Save PDF
                </button>
            </div>
            <div id="cvOutput">
                <div class="cv-empty">
                    <div class="icon">📄</div>
                    <p>Fill in your details and click</p>
                    <p style="color:#7c7cff; font-weight:700;
                        margin-top:4px">
                        "Generate Professional CV"
                    </p>
                    <p style="font-size:12px; margin-top:8px">
                        to see your CV preview here
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let expCount = 0;
let eduCount = 0;
let projCount = 0;

// Add Experience
function addExperience() {
    expCount++;
    const div = document.createElement('div');
    div.className = 'entry-block';
    div.id = `exp-${expCount}`;
    div.innerHTML = `
        <div class="entry-header">
            <span class="entry-num">
                💼 Experience #${expCount}
            </span>
            <button class="btn-remove"
                onclick="removeEntry('exp-${expCount}')">
                ✕
            </button>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Job Title</label>
                <input type="text" class="form-input exp-title"
                    placeholder="e.g. Web Developer">
            </div>
            <div class="form-group">
                <label>Company</label>
                <input type="text" class="form-input exp-company"
                    placeholder="e.g. Google Inc.">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Start Date</label>
                <input type="text" class="form-input exp-start"
                    placeholder="e.g. Jan 2023">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="text" class="form-input exp-end"
                    placeholder="e.g. Dec 2024 / Present">
            </div>
        </div>
        <div class="form-group">
            <label>Key Responsibilities / Achievements</label>
            <textarea class="form-input exp-desc"
                placeholder="Describe your main duties and achievements..."
                style="min-height:70px"></textarea>
        </div>`;
    document.getElementById('experienceList').appendChild(div);
}

// Add Education
function addEducation() {
    eduCount++;
    const div = document.createElement('div');
    div.className = 'entry-block';
    div.id = `edu-${eduCount}`;
    div.innerHTML = `
        <div class="entry-header">
            <span class="entry-num">
                🎓 Education #${eduCount}
            </span>
            <button class="btn-remove"
                onclick="removeEntry('edu-${eduCount}')">
                ✕
            </button>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Degree / Certificate</label>
                <input type="text" class="form-input edu-degree"
                    placeholder="e.g. Bachelor of Information Systems">
            </div>
            <div class="form-group">
                <label>Institution</label>
                <input type="text" class="form-input edu-school"
                    placeholder="e.g. Nusa Putra University">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Year</label>
                <input type="text" class="form-input edu-year"
                    placeholder="e.g. 2022 - 2026">
            </div>
            <div class="form-group">
                <label>GPA / Grade (optional)</label>
                <input type="text" class="form-input edu-gpa"
                    placeholder="e.g. 3.58 / 4.00">
            </div>
        </div>`;
    document.getElementById('educationList').appendChild(div);
}

// Add Project
function addProject() {
    projCount++;
    const div = document.createElement('div');
    div.className = 'entry-block';
    div.id = `proj-${projCount}`;
    div.innerHTML = `
        <div class="entry-header">
            <span class="entry-num">
                🚀 Project #${projCount}
            </span>
            <button class="btn-remove"
                onclick="removeEntry('proj-${projCount}')">
                ✕
            </button>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Project Name</label>
                <input type="text" class="form-input proj-name"
                    placeholder="e.g. InkGuard AI">
            </div>
            <div class="form-group">
                <label>Tech Stack</label>
                <input type="text" class="form-input proj-tech"
                    placeholder="e.g. PHP, MySQL, JavaScript">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-input proj-desc"
                placeholder="What does this project do?"
                style="min-height:60px"></textarea>
        </div>
        <div class="form-group">
            <label>Link (optional)</label>
            <input type="text" class="form-input proj-link"
                placeholder="github.com/username/project">
        </div>`;
    document.getElementById('projectList').appendChild(div);
}

function removeEntry(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

// Generate CV
function generateCV() {
    const name = document.getElementById('fullName').value.trim();
    const title = document.getElementById('jobTitle').value.trim();

    if (!name || !title) {
        alert('Please enter Full Name and Job Title!');
        return;
    }

    const cvOutput = document.getElementById('cvOutput');
    cvOutput.innerHTML = `
        <div class="loading-wrap" style="background:#fff;
            padding:60px; text-align:center">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600">
                ✨ Generating your professional CV...
            </p>
        </div>`;

    setTimeout(() => {
        // Collect data
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const location = document.getElementById('location').value;
        const linkedin = document.getElementById('linkedin').value;
        const summary = document.getElementById('summary').value;
        const techSkills = document.getElementById('techSkills').value;
        const softSkills = document.getElementById('softSkills').value;
        const languages = document.getElementById('languages').value;
        const certs = document.getElementById('certs').value;

        // AI-enhanced summary
        const aiSummary = enhanceSummary(summary, title, techSkills);

        // Collect experiences
        const experiences = [];
        document.querySelectorAll('#experienceList .entry-block')
            .forEach(block => {
            experiences.push({
                title: block.querySelector('.exp-title')?.value || '',
                company: block.querySelector('.exp-company')?.value || '',
                start: block.querySelector('.exp-start')?.value || '',
                end: block.querySelector('.exp-end')?.value || '',
                desc: block.querySelector('.exp-desc')?.value || ''
            });
        });

        // Collect education
        const education = [];
        document.querySelectorAll('#educationList .entry-block')
            .forEach(block => {
            education.push({
                degree: block.querySelector('.edu-degree')?.value || '',
                school: block.querySelector('.edu-school')?.value || '',
                year: block.querySelector('.edu-year')?.value || '',
                gpa: block.querySelector('.edu-gpa')?.value || ''
            });
        });

        // Collect projects
        const projects = [];
        document.querySelectorAll('#projectList .entry-block')
            .forEach(block => {
            projects.push({
                name: block.querySelector('.proj-name')?.value || '',
                tech: block.querySelector('.proj-tech')?.value || '',
                desc: block.querySelector('.proj-desc')?.value || '',
                link: block.querySelector('.proj-link')?.value || ''
            });
        });

        // Build tech skills tags
        const techTags = techSkills.split(',')
            .filter(s => s.trim())
            .map(s => `
                <span style="display:inline-block;
                    background:#1a1a4e; color:#7c7cff;
                    padding:3px 10px; border-radius:12px;
                    font-size:11px; margin:2px;
                    border:1px solid #7c7cff22">
                    ${s.trim()}
                </span>`).join('');

        const softTags = softSkills.split(',')
            .filter(s => s.trim())
            .map(s => `
                <span style="display:inline-block;
                    background:#f0f0f0; color:#333;
                    padding:3px 10px; border-radius:12px;
                    font-size:11px; margin:2px">
                    ${s.trim()}
                </span>`).join('');

        // Experience HTML
        const expHTML = experiences.filter(e => e.title)
            .map(e => `
                <div style="margin-bottom:16px; padding-bottom:16px;
                    border-bottom:1px solid #f0f0f0">
                    <div style="display:flex;
                        justify-content:space-between;
                        align-items:flex-start">
                        <div>
                            <div style="font-size:14px;
                                font-weight:700; color:#1a1a2e">
                                ${e.title}
                            </div>
                            <div style="font-size:13px;
                                color:#7c7cff; font-weight:600">
                                ${e.company}
                            </div>
                        </div>
                        <div style="font-size:12px; color:#888;
                            text-align:right; white-space:nowrap;
                            margin-left:12px">
                            ${e.start}${e.end ? ' — '+e.end : ''}
                        </div>
                    </div>
                    ${e.desc ? `
                    <ul style="margin-top:8px; padding-left:18px;
                        color:#555; font-size:12px; line-height:1.7">
                        ${e.desc.split('\n')
                            .filter(l => l.trim())
                            .map(l => `<li>${l.trim()}</li>`)
                            .join('')}
                    </ul>` : ''}
                </div>`).join('');

        // Education HTML
        const eduHTML = education.filter(e => e.degree)
            .map(e => `
                <div style="margin-bottom:14px;
                    padding-bottom:14px;
                    border-bottom:1px solid #f0f0f0">
                    <div style="display:flex;
                        justify-content:space-between;
                        align-items:flex-start">
                        <div>
                            <div style="font-size:14px;
                                font-weight:700; color:#1a1a2e">
                                ${e.degree}
                            </div>
                            <div style="font-size:13px; color:#555">
                                ${e.school}
                            </div>
                            ${e.gpa ? `
                            <div style="font-size:12px;
                                color:#7c7cff; margin-top:2px">
                                GPA: ${e.gpa}
                            </div>` : ''}
                        </div>
                        <div style="font-size:12px; color:#888;
                            white-space:nowrap; margin-left:12px">
                            ${e.year}
                        </div>
                    </div>
                </div>`).join('');

        // Projects HTML
        const projHTML = projects.filter(p => p.name)
            .map(p => `
                <div style="margin-bottom:14px;
                    padding-bottom:14px;
                    border-bottom:1px solid #f0f0f0">
                    <div style="display:flex;
                        justify-content:space-between;
                        align-items:flex-start">
                        <div style="font-size:14px;
                            font-weight:700; color:#1a1a2e">
                            ${p.name}
                        </div>
                        ${p.link ? `
                        <a href="https://${p.link}"
                            style="font-size:11px;
                            color:#7c7cff; text-decoration:none">
                            🔗 ${p.link}
                        </a>` : ''}
                    </div>
                    ${p.tech ? `
                    <div style="font-size:12px; color:#7c7cff;
                        margin:4px 0">
                        Tech: ${p.tech}
                    </div>` : ''}
                    ${p.desc ? `
                    <div style="font-size:12px; color:#555;
                        margin-top:4px; line-height:1.6">
                        ${p.desc}
                    </div>` : ''}
                </div>`).join('');

        // Build full CV
        cvOutput.innerHTML = `
<div style="background:#fff; color:#1a1a1a;
    font-family:'Segoe UI',sans-serif;
    max-width:800px; margin:0 auto;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1a1a4e,#2a2a7e);
        color:#fff; padding:32px 36px;">
        <div style="display:flex; justify-content:space-between;
            align-items:flex-start; gap:20px">
            <div>
                <h1 style="font-size:28px; font-weight:800;
                    margin-bottom:4px; letter-spacing:-0.5px">
                    ${name}
                </h1>
                <div style="font-size:14px; color:#a0a0ff;
                    font-weight:600; margin-bottom:14px">
                    ${title}
                </div>
                <div style="display:flex; flex-wrap:wrap;
                    gap:16px; font-size:12px; color:#ccc">
                    ${email ? `<span>📧 ${email}</span>` : ''}
                    ${phone ? `<span>📱 ${phone}</span>` : ''}
                    ${location ? `<span>📍 ${location}</span>` : ''}
                    ${linkedin ? `<span>🔗 ${linkedin}</span>` : ''}
                </div>
            </div>
            <div style="width:70px; height:70px;
                background:rgba(255,255,255,0.15);
                border-radius:50%; display:flex;
                align-items:center; justify-content:center;
                font-size:28px; font-weight:900; color:#fff;
                flex-shrink:0; border:3px solid rgba(255,255,255,0.3)">
                ${name.charAt(0).toUpperCase()}
            </div>
        </div>
    </div>

    <!-- Body -->
    <div style="display:grid; grid-template-columns:2fr 1fr;
        gap:0">

        <!-- Left Column -->
        <div style="padding:28px 28px 28px 36px;
            border-right:1px solid #f0f0f0">

            <!-- Summary -->
            ${aiSummary ? `
            <div style="margin-bottom:24px">
                <div style="font-size:13px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:10px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Professional Summary
                </div>
                <p style="font-size:13px; color:#444;
                    line-height:1.7">
                    ${aiSummary}
                </p>
            </div>` : ''}

            <!-- Experience -->
            ${expHTML ? `
            <div style="margin-bottom:24px">
                <div style="font-size:13px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:14px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Work Experience
                </div>
                ${expHTML}
            </div>` : ''}

            <!-- Projects -->
            ${projHTML ? `
            <div style="margin-bottom:24px">
                <div style="font-size:13px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:14px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Projects
                </div>
                ${projHTML}
            </div>` : ''}

        </div>

        <!-- Right Column -->
        <div style="padding:28px 24px; background:#fafafa">

            <!-- Education -->
            ${eduHTML ? `
            <div style="margin-bottom:24px">
                <div style="font-size:12px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:12px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Education
                </div>
                ${eduHTML}
            </div>` : ''}

            <!-- Technical Skills -->
            ${techSkills ? `
            <div style="margin-bottom:20px">
                <div style="font-size:12px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:10px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Technical Skills
                </div>
                <div>${techTags}</div>
            </div>` : ''}

            <!-- Soft Skills -->
            ${softSkills ? `
            <div style="margin-bottom:20px">
                <div style="font-size:12px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:10px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Soft Skills
                </div>
                <div>${softTags}</div>
            </div>` : ''}

            <!-- Languages -->
            ${languages ? `
            <div style="margin-bottom:20px">
                <div style="font-size:12px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:10px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Languages
                </div>
                ${languages.split(',').filter(l=>l.trim())
                    .map(l => `
                    <div style="font-size:12px; color:#555;
                        margin-bottom:4px; display:flex;
                        align-items:center; gap:6px">
                        🌐 ${l.trim()}
                    </div>`).join('')}
            </div>` : ''}

            <!-- Certifications -->
            ${certs ? `
            <div style="margin-bottom:20px">
                <div style="font-size:12px; font-weight:800;
                    color:#1a1a4e; text-transform:uppercase;
                    letter-spacing:1.5px; margin-bottom:10px;
                    padding-bottom:6px;
                    border-bottom:2px solid #7c7cff">
                    Certifications
                </div>
                ${certs.split(',').filter(c=>c.trim())
                    .map(c => `
                    <div style="font-size:12px; color:#555;
                        margin-bottom:4px; display:flex;
                        align-items:center; gap:6px">
                        🏆 ${c.trim()}
                    </div>`).join('')}
            </div>` : ''}

        </div>
    </div>

    <!-- Footer -->
    <div style="background:#1a1a4e; color:rgba(255,255,255,0.4);
        text-align:center; padding:10px; font-size:10px">
        Generated by InkGuard AI CV Generator
    </div>

</div>`;

    }, 1500);
}

// AI-enhanced summary
function enhanceSummary(summary, title, skills) {
    if (!summary && !title) return '';

    const skillList = skills.split(',')
        .filter(s => s.trim())
        .slice(0,3)
        .map(s => s.trim())
        .join(', ');

    if (summary.trim()) {
        // Enhance existing summary
        return summary.trim() +
            (skillList ? ` With expertise in ${skillList}, I am committed to delivering high-quality results and continuously growing as a professional.` : '');
    }

    // Generate from title
    const templates = {
        'developer': `Passionate and results-driven ${title} with hands-on experience in building scalable web applications. ${skillList ? `Proficient in ${skillList}.` : ''} Dedicated to writing clean, efficient code and staying current with emerging technologies.`,
        'designer': `Creative ${title} with a strong eye for aesthetics and user experience. ${skillList ? `Skilled in ${skillList}.` : ''} Committed to crafting intuitive and visually compelling designs that meet both user needs and business goals.`,
        'analyst': `Detail-oriented ${title} with strong analytical and problem-solving skills. ${skillList ? `Experienced with ${skillList}.` : ''} Adept at transforming complex data into actionable insights to drive business decisions.`,
        'manager': `Dynamic ${title} with proven leadership and organizational skills. ${skillList ? `Experienced with ${skillList}.` : ''} Skilled at motivating teams and delivering projects on time within budget.`,
        'default': `Motivated and dedicated ${title} with a passion for excellence and continuous learning. ${skillList ? `Skilled in ${skillList}.` : ''} Seeking to leverage my skills and experience to contribute meaningfully to a forward-thinking organization.`
    };

    const t = title.toLowerCase();
    if (t.includes('develop')||t.includes('engineer')||t.includes('programmer'))
        return templates.developer;
    if (t.includes('design')||t.includes('ui')||t.includes('ux'))
        return templates.designer;
    if (t.includes('analyst')||t.includes('data'))
        return templates.analyst;
    if (t.includes('manager')||t.includes('lead')||t.includes('director'))
        return templates.manager;
    return templates.default;
}

function printCV() {
    const cvContent = document.getElementById('cvOutput').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>CV - ${document.getElementById('fullName').value || 'Resume'}</title>
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { font-family:'Segoe UI',sans-serif; }
                @page { margin:0; size:A4; }
            </style>
        </head>
        <body>${cvContent}</body>
        </html>`);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Auto-add one entry on load
addExperience();
addEducation();
addProject();
</script>
</body>
</html>