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
    <title>AI PPT Generator - InkGuard</title>
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

        .theme-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:10px; margin-bottom:16px;
        }
        .theme-btn {
            padding:16px 8px; text-align:center;
            border:2px solid rgba(255,255,255,0.1);
            border-radius:10px; cursor:pointer;
            transition:all 0.2s; font-size:11px;
        }
        .theme-btn.selected { border-color:#fff; }
        .theme-preview {
            width:100%; height:30px; border-radius:6px;
            margin-bottom:6px;
        }

        .btn-generate {
            width:100%; padding:15px;
            background:linear-gradient(135deg,#7c7cff,#5555dd);
            color:#fff; border:none; border-radius:12px;
            font-size:16px; font-weight:700; cursor:pointer;
            transition:all 0.3s;
            box-shadow:0 4px 20px rgba(124,124,255,0.3);
        }
        .btn-generate:hover { transform:translateY(-2px); }
        .btn-generate:disabled {
            opacity:0.6; cursor:not-allowed; transform:none;
        }

        .slides-count {
            display:flex; gap:8px; margin-bottom:16px;
        }
        .count-btn {
            flex:1; padding:10px; text-align:center;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:13px;
        }
        .count-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
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

        .result-section { display:none; }
        .presentation-actions {
            display:flex; gap:10px; margin-bottom:20px;
        }
        .btn-export {
            flex:1; padding:12px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
        }
        .btn-export:hover { transform:translateY(-1px); }

        .slides-container {
            display:flex; flex-direction:column; gap:20px;
        }
        .slide-wrapper {
            position:relative;
        }
        .slide-number {
            position:absolute; top:-10px; left:16px;
            background:#7c7cff; color:#fff;
            padding:4px 12px; border-radius:20px;
            font-size:11px; font-weight:700; z-index:2;
        }
        .slide-canvas-container {
            aspect-ratio:16/9; border-radius:12px;
            overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.5);
        }
        .slide-canvas {
            width:100%; height:100%; display:block;
        }
        .slide-edit-btn {
            position:absolute; top:8px; right:8px;
            background:rgba(0,0,0,0.6);
            border:1px solid rgba(255,255,255,0.2);
            color:#fff; padding:6px 12px;
            border-radius:6px; cursor:pointer;
            font-size:11px; z-index:2;
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
        <h1>📊 AI PPT Generator</h1>
        <p>Create professional presentation slides instantly from your topic</p>
    </div>

    <!-- Input -->
    <div class="glass-card">
        <h2>✍️ Presentation Details</h2>

        <div class="form-group">
            <label>Presentation Topic *</label>
            <input type="text" class="form-input" id="pptTopic"
                placeholder="e.g. Introduction to Artificial Intelligence">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Your Name / Presenter</label>
                <input type="text" class="form-input" id="presenterName"
                    placeholder="e.g. Myat Noe Wai">
            </div>
            <div class="form-group">
                <label>Subtitle (optional)</label>
                <input type="text" class="form-input" id="pptSubtitle"
                    placeholder="e.g. IBM SkillsBuild Challenge">
            </div>
        </div>

        <div class="form-group">
            <label>Number of Slides</label>
            <div class="slides-count">
                <div class="count-btn selected" onclick="selectCount(this,5)">5 Slides</div>
                <div class="count-btn" onclick="selectCount(this,7)">7 Slides</div>
                <div class="count-btn" onclick="selectCount(this,10)">10 Slides</div>
            </div>
        </div>

        <div class="form-group">
            <label>Color Theme</label>
            <div class="theme-grid">
                <div class="theme-btn selected" onclick="selectTheme(this,0)">
                    <div class="theme-preview" style="background:linear-gradient(135deg,#7c7cff,#5555dd)"></div>
                    Purple
                </div>
                <div class="theme-btn" onclick="selectTheme(this,1)">
                    <div class="theme-preview" style="background:linear-gradient(135deg,#00cc66,#009944)"></div>
                    Green
                </div>
                <div class="theme-btn" onclick="selectTheme(this,2)">
                    <div class="theme-preview" style="background:linear-gradient(135deg,#ff6b35,#ff4444)"></div>
                    Orange
                </div>
                <div class="theme-btn" onclick="selectTheme(this,3)">
                    <div class="theme-preview" style="background:linear-gradient(135deg,#1a1a2e,#0f3460)"></div>
                    Dark Navy
                </div>
            </div>
        </div>

        <button class="btn-generate" id="genBtn" onclick="generatePPT()">
            ✨ Generate Presentation
        </button>
    </div>

    <!-- Result -->
    <div class="result-section" id="resultSection">
        <div class="presentation-actions">
            <button class="btn-export" onclick="downloadAllSlides()">
                📥 Download All Slides (ZIP-like)
            </button>
            <button class="btn-export" onclick="generatePPT()"
                style="background:linear-gradient(135deg,#7c7cff,#5555dd)">
                🔄 Regenerate
            </button>
        </div>
        <div class="slides-container" id="slidesContainer"></div>
    </div>

</div>

<script>
let slideCount = 5;
let themeIndex = 0;

const themes = [
    { bg:['#1a1a4e','#2a2a7e'], accent:'#7c7cff', text:'#fff' },
    { bg:['#0a2a1a','#0d4d2e'], accent:'#00cc66', text:'#fff' },
    { bg:['#3d1a0a','#5d2a0a'], accent:'#ff6b35', text:'#fff' },
    { bg:['#0a0a1a','#0f1a30'], accent:'#4285f4', text:'#fff' },
];

function selectCount(el, count) {
    document.querySelectorAll('.count-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    slideCount = count;
}

function selectTheme(el, idx) {
    document.querySelectorAll('.theme-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    themeIndex = idx;
}

// Content templates based on keywords
function generateSlideContent(topic, slideCount) {
    const t = topic.toLowerCase();
    const slides = [];

    // Slide 1: Title (handled separately)

    // Generate content slides based on topic type
    const genericStructure = [
        { title: 'Introduction', points: [
            `Understanding the core concept of ${topic}`,
            `Why ${topic} matters in today's context`,
            `Overview of what we will cover`
        ]},
        { title: 'Background & Context', points: [
            `Historical development and evolution`,
            `Key drivers and motivations`,
            `Current state of the field`
        ]},
        { title: 'Key Concepts', points: [
            `Fundamental principles and definitions`,
            `Core components and how they work`,
            `Important terminology to understand`
        ]},
        { title: 'Benefits & Applications', points: [
            `Real-world use cases and examples`,
            `Advantages and positive impact`,
            `Industries and areas of application`
        ]},
        { title: 'Challenges & Considerations', points: [
            `Common obstacles and limitations`,
            `Risks that need to be managed`,
            `Ethical and practical considerations`
        ]},
        { title: 'Case Studies', points: [
            `Notable examples and success stories`,
            `Lessons learned from implementation`,
            `Measurable outcomes and results`
        ]},
        { title: 'Future Trends', points: [
            `Emerging developments to watch`,
            `Predictions for the coming years`,
            `Opportunities for innovation`
        ]},
        { title: 'Implementation Strategy', points: [
            `Step-by-step approach to getting started`,
            `Resources and tools needed`,
            `Best practices to follow`
        ]},
        { title: 'Comparison & Analysis', points: [
            `Comparing different approaches`,
            `Pros and cons of each method`,
            `Which option suits which scenario`
        ]},
        { title: 'Conclusion', points: [
            `Summary of key takeaways`,
            `Final thoughts and recommendations`,
            `Next steps moving forward`
        ]}
    ];

    // Select slides based on count (always include intro & conclusion)
    const middleCount = slideCount - 2;
    const middleSlides = genericStructure.slice(1, -1)
        .slice(0, middleCount);

    slides.push(genericStructure[0]); // Intro
    slides.push(...middleSlides);
    slides.push(genericStructure[genericStructure.length-1]); // Conclusion

    return slides.slice(0, slideCount);
}

function drawRoundedRect(ctx,x,y,w,h,r) {
    ctx.beginPath();
    ctx.moveTo(x+r,y);
    ctx.arcTo(x+w,y,x+w,y+h,r);
    ctx.arcTo(x+w,y+h,x,y+h,r);
    ctx.arcTo(x,y+h,x,y,r);
    ctx.arcTo(x,y,x+w,y,r);
    ctx.closePath();
}

function drawTitleSlide(canvas, topic, presenter, subtitle, theme) {
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;

    // Background gradient
    const bg = ctx.createLinearGradient(0,0,W,H);
    bg.addColorStop(0, theme.bg[0]);
    bg.addColorStop(1, theme.bg[1]);
    ctx.fillStyle = bg;
    ctx.fillRect(0,0,W,H);

    // Decorative circles
    ctx.globalAlpha = 0.1;
    for (let i=0; i<8; i++) {
        ctx.fillStyle = theme.accent;
        ctx.beginPath();
        ctx.arc(Math.random()*W, Math.random()*H,
            Math.random()*100+30, 0, Math.PI*2);
        ctx.fill();
    }
    ctx.globalAlpha = 1;

    // Accent bar
    ctx.fillStyle = theme.accent;
    ctx.fillRect(0, H*0.42, W*0.08, 8);

    // Title
    ctx.fillStyle = '#fff';
    ctx.font = `800 ${W*0.055}px Segoe UI`;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';

    // Word wrap title
    const words = topic.split(' ');
    let lines = [];
    let currentLine = '';
    const maxWidth = W*0.75;

    words.forEach(word => {
        const test = currentLine + word + ' ';
        if (ctx.measureText(test).width > maxWidth && currentLine) {
            lines.push(currentLine.trim());
            currentLine = word + ' ';
        } else {
            currentLine = test;
        }
    });
    lines.push(currentLine.trim());

    const lineHeight = W*0.07;
    const startY = H*0.5 - (lines.length-1)*lineHeight/2;
    lines.forEach((line,i) => {
        ctx.fillText(line, W*0.08, startY + i*lineHeight);
    });

    // Subtitle
    if (subtitle) {
        ctx.font = `400 ${W*0.022}px Segoe UI`;
        ctx.fillStyle = 'rgba(255,255,255,0.7)';
        ctx.fillText(subtitle, W*0.08,
            startY + lines.length*lineHeight + 10);
    }

    // Presenter
    if (presenter) {
        ctx.font = `600 ${W*0.02}px Segoe UI`;
        ctx.fillStyle = theme.accent;
        ctx.fillText('👤 ' + presenter, W*0.08, H*0.88);
    }

    // Logo watermark
    ctx.font = `700 ${W*0.018}px Segoe UI`;
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.textAlign = 'right';
    ctx.fillText('🛡️ InkGuard AI', W*0.94, H*0.06);
}

function drawContentSlide(canvas, slideNum, total, title, points, theme) {
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;

    // Background
    const bg = ctx.createLinearGradient(0,0,W,H);
    bg.addColorStop(0, '#0a0a1a');
    bg.addColorStop(1, '#151525');
    ctx.fillStyle = bg;
    ctx.fillRect(0,0,W,H);

    // Header bar
    ctx.fillStyle = theme.accent;
    ctx.fillRect(0,0,W,H*0.15);

    // Header gradient overlay
    const headerGrad = ctx.createLinearGradient(0,0,W,0);
    headerGrad.addColorStop(0, theme.bg[0]);
    headerGrad.addColorStop(1, theme.bg[1]);
    ctx.fillStyle = headerGrad;
    ctx.fillRect(0,0,W,H*0.15);

    // Slide title
    ctx.fillStyle = '#fff';
    ctx.font = `800 ${W*0.038}px Segoe UI`;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.fillText(title, W*0.06, H*0.075);

    // Slide number
    ctx.font = `600 ${W*0.02}px Segoe UI`;
    ctx.fillStyle = 'rgba(255,255,255,0.7)';
    ctx.textAlign = 'right';
    ctx.fillText(`${slideNum} / ${total}`, W*0.94, H*0.075);

    // Content points
    const startY = H*0.28;
    const spacing = H*0.16;

    points.forEach((point, i) => {
        const y = startY + i*spacing;

        // Number badge
        ctx.fillStyle = theme.accent;
        ctx.beginPath();
        ctx.arc(W*0.09, y, W*0.018, 0, Math.PI*2);
        ctx.fill();

        ctx.fillStyle = '#fff';
        ctx.font = `700 ${W*0.018}px Segoe UI`;
        ctx.textAlign = 'center';
        ctx.fillText(i+1, W*0.09, y);

        // Point text
        ctx.textAlign = 'left';
        ctx.font = `500 ${W*0.024}px Segoe UI`;
        ctx.fillStyle = '#e0e0e0';

        // Word wrap point text
        const words = point.split(' ');
        let line = '';
        let lineY = y - H*0.02;
        const maxWidth = W*0.78;
        const lineH = H*0.045;
        let lineCount = 0;

        words.forEach(word => {
            const test = line + word + ' ';
            if (ctx.measureText(test).width > maxWidth && line) {
                ctx.fillText(line.trim(), W*0.14, lineY + lineCount*lineH);
                line = word + ' ';
                lineCount++;
            } else {
                line = test;
            }
        });
        ctx.fillText(line.trim(), W*0.14, lineY + lineCount*lineH);
    });

    // Bottom accent
    ctx.fillStyle = theme.accent;
    ctx.globalAlpha = 0.3;
    ctx.fillRect(0, H-6, W, 6);
    ctx.globalAlpha = 1;

    // Watermark
    ctx.font = `600 ${W*0.014}px Segoe UI`;
    ctx.fillStyle = 'rgba(255,255,255,0.2)';
    ctx.textAlign = 'right';
    ctx.fillText('InkGuard AI', W*0.94, H*0.96);
}

function generatePPT() {
    const topic = document.getElementById('pptTopic').value.trim();
    if (!topic) {
        alert('Please enter a presentation topic!');
        return;
    }

    const presenter = document.getElementById('presenterName').value;
    const subtitle = document.getElementById('pptSubtitle').value;
    const theme = themes[themeIndex];

    const btn = document.getElementById('genBtn');
    const resultSection = document.getElementById('resultSection');
    const container = document.getElementById('slidesContainer');

    btn.disabled = true;
    btn.textContent = '⏳ Generating...';
    resultSection.style.display = 'block';
    container.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600; font-size:16px">
                ✨ Creating your presentation...
            </p>
            <p style="color:#555; font-size:13px; margin-top:8px">
                Generating ${slideCount} slides
            </p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    setTimeout(() => {
        container.innerHTML = '';

        const slideContents = generateSlideContent(topic, slideCount);

        // Title slide
        const titleWrapper = document.createElement('div');
        titleWrapper.className = 'slide-wrapper';
        titleWrapper.innerHTML = `
            <span class="slide-number">Slide 1 - Title</span>
            <div class="slide-canvas-container">
                <canvas class="slide-canvas" id="slide-0"
                    width="1280" height="720"></canvas>
            </div>`;
        container.appendChild(titleWrapper);

        setTimeout(() => {
            const titleCanvas = document.getElementById('slide-0');
            drawTitleSlide(titleCanvas, topic, presenter, subtitle, theme);
        }, 50);

        // Content slides
        slideContents.forEach((slide, idx) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'slide-wrapper';
            wrapper.innerHTML = `
                <span class="slide-number">
                    Slide ${idx+2} - ${slide.title}
                </span>
                <div class="slide-canvas-container">
                    <canvas class="slide-canvas" id="slide-${idx+1}"
                        width="1280" height="720"></canvas>
                </div>
                <button class="slide-edit-btn"
                    onclick="downloadSlide(${idx+1})">
                    ⬇️ Download
                </button>`;
            container.appendChild(wrapper);

            setTimeout(() => {
                const canvas = document.getElementById(`slide-${idx+1}`);
                drawContentSlide(canvas, idx+2, slideCount,
                    slide.title, slide.points, theme);
            }, 100 + idx*50);
        });

        // Add download button to title slide too
        setTimeout(() => {
            titleWrapper.innerHTML += `
                <button class="slide-edit-btn"
                    onclick="downloadSlide(0)">
                    ⬇️ Download
                </button>`;
        }, 60);

        btn.disabled = false;
        btn.textContent = '✨ Generate Presentation';

    }, 1500);
}

function downloadSlide(idx) {
    const canvas = document.getElementById(`slide-${idx}`);
    const link = document.createElement('a');
    link.download = `slide_${idx+1}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function downloadAllSlides() {
    const canvases = document.querySelectorAll('.slide-canvas');
    canvases.forEach((canvas, idx) => {
        setTimeout(() => {
            const link = document.createElement('a');
            link.download = `inkguard_slide_${idx+1}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }, idx*300);
    });
    alert(`📥 Downloading ${canvases.length} slides...\nCheck your Downloads folder.`);
}
</script>
</body>
</html>