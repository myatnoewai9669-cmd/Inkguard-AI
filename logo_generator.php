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
    <title>Logo Generator - InkGuard</title>
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
            max-width:1000px; margin:0 auto;
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
        .layout {
            display:grid; grid-template-columns:1fr 1fr;
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
        .form-input:focus { border-color:rgba(124,124,255,0.5); }
        .form-input::placeholder { color:#555; }

        .type-tabs {
            display:flex; gap:8px; margin-bottom:16px;
        }
        .type-tab {
            flex:1; padding:10px; text-align:center;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:13px;
            transition:all 0.2s;
        }
        .type-tab.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
        }

        .icon-grid {
            display:grid; grid-template-columns:repeat(6,1fr);
            gap:8px; margin-bottom:16px;
        }
        .icon-btn {
            aspect-ratio:1; display:flex;
            align-items:center; justify-content:center;
            font-size:20px; border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; background:rgba(0,0,0,0.2);
            cursor:pointer; transition:all 0.2s;
        }
        .icon-btn:hover, .icon-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.2);
        }

        .color-grid {
            display:grid; grid-template-columns:repeat(8,1fr);
            gap:8px; margin-bottom:8px;
        }
        .color-swatch {
            aspect-ratio:1; border-radius:8px;
            cursor:pointer; border:2px solid transparent;
            transition:all 0.2s;
        }
        .color-swatch:hover, .color-swatch.selected {
            border-color:#fff; transform:scale(1.1);
        }

        .shape-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:8px; margin-bottom:16px;
        }
        .shape-btn {
            padding:12px 8px; text-align:center;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer; font-size:11px;
            transition:all 0.2s;
        }
        .shape-btn:hover, .shape-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
            color:#fff;
        }

        .font-select {
            display:flex; flex-direction:column; gap:8px;
            margin-bottom:16px;
        }
        .font-option {
            padding:12px; border:1px solid rgba(255,255,255,0.1);
            border-radius:8px; background:rgba(0,0,0,0.2);
            cursor:pointer; transition:all 0.2s;
            text-align:center; font-size:18px; color:#fff;
        }
        .font-option:hover, .font-option.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
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

        .preview-wrap { position:sticky; top:80px; }
        .canvas-container {
            background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:20px;
            text-align:center;
        }
        #logoCanvas {
            max-width:100%; border-radius:12px;
            border:1px solid rgba(255,255,255,0.1);
        }
        .checker-bg {
            background-image:
                linear-gradient(45deg,#222 25%,transparent 25%),
                linear-gradient(-45deg,#222 25%,transparent 25%),
                linear-gradient(45deg,transparent 75%,#222 75%),
                linear-gradient(-45deg,transparent 75%,#222 75%);
            background-size:20px 20px;
            background-position:0 0,0 10px,10px -10px,-10px 0px;
            border-radius:12px; padding:20px;
        }
        .download-row {
            display:flex; gap:10px; margin-top:16px;
        }
        .btn-download {
            flex:1; padding:12px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:13px; font-weight:700; cursor:pointer;
            text-decoration:none; text-align:center;
        }
        .size-select {
            display:flex; gap:8px; margin-top:12px;
            flex-wrap:wrap; justify-content:center;
        }
        .size-btn {
            padding:6px 14px; border-radius:20px;
            border:1px solid rgba(255,255,255,0.15);
            background:rgba(0,0,0,0.2); color:#aaa;
            cursor:pointer; font-size:12px;
        }
        .size-btn.selected {
            border-color:#00cc66;
            background:rgba(0,204,102,0.15);
            color:#00cc66;
        }
        @media(max-width:768px) {
            .layout { grid-template-columns:1fr; }
            .preview-wrap { position:static; }
        }
        @media(max-width:600px) {
            .icon-grid { grid-template-columns:repeat(4,1fr); }
            .shape-grid { grid-template-columns:repeat(2,1fr); }
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
        <h1>🎨 Logo & Banner Generator</h1>
        <p>Create custom logos and banners instantly</p>
    </div>

    <div class="layout">

        <!-- LEFT: Controls -->
        <div>

            <!-- Type -->
            <div class="glass-card">
                <h2>📐 Design Type</h2>
                <div class="type-tabs">
                    <div class="type-tab selected"
                        onclick="selectType(this,'logo')">
                        🔷 Logo (Square)
                    </div>
                    <div class="type-tab"
                        onclick="selectType(this,'banner')">
                        🖼️ Banner (Wide)
                    </div>
                </div>
            </div>

            <!-- Text -->
            <div class="glass-card">
                <h2>✍️ Text</h2>
                <div class="form-group">
                    <label>Brand / Company Name</label>
                    <input type="text" class="form-input"
                        id="brandName" placeholder="e.g. InkGuard"
                        oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>Tagline (optional)</label>
                    <input type="text" class="form-input"
                        id="tagline"
                        placeholder="e.g. AI Content Protection"
                        oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>Font Style</label>
                    <div class="font-select">
                        <div class="font-option selected"
                            style="font-weight:800"
                            onclick="selectFont(this,'800')">
                            Bold Sans
                        </div>
                        <div class="font-option"
                            style="font-style:italic; font-weight:600"
                            onclick="selectFont(this,'italic 600')">
                            Elegant Italic
                        </div>
                        <div class="font-option"
                            style="font-weight:300; letter-spacing:3px"
                            onclick="selectFont(this,'300', true)">
                            Minimal Light
                        </div>
                        <div class="font-option"
                            style="font-family:Georgia; font-weight:700"
                            onclick="selectFont(this,'700', false, 'Georgia')">
                            Classic Serif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Icon -->
            <div class="glass-card">
                <h2>🔣 Icon</h2>
                <div class="icon-grid">
                    <div class="icon-btn selected"
                        onclick="selectIcon(this,'🛡️')">🛡️</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'⚡')">⚡</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'🚀')">🚀</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'💎')">💎</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'🔥')">🔥</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'⭐')">⭐</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'🎯')">🎯</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'💡')">💡</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'🌟')">🌟</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'🔮')">🔮</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'📊')">📊</div>
                    <div class="icon-btn"
                        onclick="selectIcon(this,'')">🚫</div>
                </div>
            </div>

            <!-- Shape -->
            <div class="glass-card">
                <h2>🔷 Icon Background</h2>
                <div class="shape-grid">
                    <div class="shape-btn selected"
                        onclick="selectShape(this,'circle')">
                        ⭕ Circle
                    </div>
                    <div class="shape-btn"
                        onclick="selectShape(this,'rounded')">
                        ▢ Rounded
                    </div>
                    <div class="shape-btn"
                        onclick="selectShape(this,'square')">
                        ⬛ Square
                    </div>
                    <div class="shape-btn"
                        onclick="selectShape(this,'hexagon')">
                        ⬡ Hexagon
                    </div>
                </div>
            </div>

            <!-- Colors -->
            <div class="glass-card">
                <h2>🎨 Color Theme</h2>
                <div class="color-grid" id="colorGrid"></div>
            </div>

            <button class="btn-generate" onclick="updatePreview()">
                ✨ Update Preview
            </button>
        </div>

        <!-- RIGHT: Preview -->
        <div class="preview-wrap">
            <div class="canvas-container">
                <div class="checker-bg">
                    <canvas id="logoCanvas" width="500" height="500"></canvas>
                </div>

                <div class="size-select">
                    <div class="size-btn selected"
                        onclick="setSize(this,500,500)">
                        500×500
                    </div>
                    <div class="size-btn"
                        onclick="setSize(this,1000,1000)">
                        1000×1000
                    </div>
                    <div class="size-btn"
                        onclick="setSize(this,800,600)">
                        800×600
                    </div>
                    <div class="size-btn"
                        onclick="setSize(this,1200,300)">
                        1200×300
                    </div>
                </div>

                <div class="download-row">
                    <a href="#" id="downloadPng" download="logo.png"
                        class="btn-download">
                        ⬇️ Download PNG
                    </a>
                    <a href="#" id="downloadJpg" download="logo.jpg"
                        class="btn-download"
                        style="background:linear-gradient(135deg,#7c7cff,#5555dd)">
                        ⬇️ Download JPG
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentType = 'logo';
let currentIcon = '🛡️';
let currentShape = 'circle';
let currentColor = 0;
let currentFont = { weight: '800', style: '', family: 'Segoe UI' };

const colorThemes = [
    ['#7c7cff','#5555dd'],
    ['#00cc66','#009944'],
    ['#ff6b35','#ff4444'],
    ['#ffd700','#ff9800'],
    ['#ff2d78','#c2185b'],
    ['#00bcd4','#0097a7'],
    ['#9c27b0','#6a1b9a'],
    ['#1a1a2e','#0f3460'],
    ['#2ecc71','#27ae60'],
    ['#e74c3c','#c0392b'],
    ['#3498db','#2980b9'],
    ['#f39c12','#d68910'],
    ['#e91e63','#ad1457'],
    ['#607d8b','#37474f'],
    ['#795548','#4e342e'],
    ['#000000','#333333']
];

// Build color swatches
function buildColorGrid() {
    const grid = document.getElementById('colorGrid');
    colorThemes.forEach((theme, i) => {
        const div = document.createElement('div');
        div.className = 'color-swatch' + (i===0?' selected':'');
        div.style.background =
            `linear-gradient(135deg,${theme[0]},${theme[1]})`;
        div.onclick = () => selectColor(div, i);
        grid.appendChild(div);
    });
}

function selectType(el, type) {
    document.querySelectorAll('.type-tab').forEach(t =>
        t.classList.remove('selected'));
    el.classList.add('selected');
    currentType = type;
    if (type === 'banner') {
        document.getElementById('logoCanvas').width = 1200;
        document.getElementById('logoCanvas').height = 400;
    } else {
        document.getElementById('logoCanvas').width = 500;
        document.getElementById('logoCanvas').height = 500;
    }
    updatePreview();
}

function selectIcon(el, icon) {
    document.querySelectorAll('.icon-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    currentIcon = icon;
    updatePreview();
}

function selectShape(el, shape) {
    document.querySelectorAll('.shape-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    currentShape = shape;
    updatePreview();
}

function selectColor(el, idx) {
    document.querySelectorAll('.color-swatch').forEach(c =>
        c.classList.remove('selected'));
    el.classList.add('selected');
    currentColor = idx;
    updatePreview();
}

function selectFont(el, weight, spaced, family) {
    document.querySelectorAll('.font-option').forEach(f =>
        f.classList.remove('selected'));
    el.classList.add('selected');
    currentFont.weight = weight;
    currentFont.spaced = spaced || false;
    currentFont.family = family || 'Segoe UI';
    updatePreview();
}

function setSize(el, w, h) {
    document.querySelectorAll('.size-btn').forEach(b =>
        b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('logoCanvas').width = w;
    document.getElementById('logoCanvas').height = h;
    updatePreview();
}

function drawRoundedRect(ctx,x,y,w,h,r) {
    ctx.beginPath();
    ctx.moveTo(x+r,y);
    ctx.lineTo(x+w-r,y);
    ctx.arcTo(x+w,y,x+w,y+r,r);
    ctx.lineTo(x+w,y+h-r);
    ctx.arcTo(x+w,y+h,x+w-r,y+h,r);
    ctx.lineTo(x+r,y+h);
    ctx.arcTo(x,y+h,x,y+h-r,r);
    ctx.lineTo(x,y+r);
    ctx.arcTo(x,y,x+r,y,r);
    ctx.closePath();
}

function drawHexagon(ctx,cx,cy,r) {
    ctx.beginPath();
    for (let i=0; i<6; i++) {
        const angle = (Math.PI/3)*i - Math.PI/2;
        const x = cx + r*Math.cos(angle);
        const y = cy + r*Math.sin(angle);
        i===0 ? ctx.moveTo(x,y) : ctx.lineTo(x,y);
    }
    ctx.closePath();
}

function updatePreview() {
    const canvas = document.getElementById('logoCanvas');
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    const theme = colorThemes[currentColor];

    const brandName = document.getElementById('brandName')
        .value || 'YourBrand';
    const tagline = document.getElementById('tagline').value;

    ctx.clearRect(0,0,W,H);

    // Background
    const bgGrad = ctx.createLinearGradient(0,0,W,H);
    bgGrad.addColorStop(0,'#0a0a1a');
    bgGrad.addColorStop(1,'#1a1a2e');
    ctx.fillStyle = bgGrad;
    ctx.fillRect(0,0,W,H);

    // Subtle pattern
    ctx.globalAlpha = 0.05;
    for (let i=0; i<20; i++) {
        ctx.fillStyle = theme[0];
        ctx.beginPath();
        ctx.arc(Math.random()*W, Math.random()*H,
            Math.random()*40+10, 0, Math.PI*2);
        ctx.fill();
    }
    ctx.globalAlpha = 1;

    if (currentType === 'logo') {
        // LOGO layout - centered
        const iconSize = W*0.28;
        const iconY = H*0.32;

        // Icon background shape
        const iconGrad = ctx.createLinearGradient(
            W/2-iconSize/2,iconY-iconSize/2,
            W/2+iconSize/2,iconY+iconSize/2);
        iconGrad.addColorStop(0,theme[0]);
        iconGrad.addColorStop(1,theme[1]);
        ctx.fillStyle = iconGrad;
        ctx.shadowColor = theme[0];
        ctx.shadowBlur = 30;

        if (currentShape === 'circle') {
            ctx.beginPath();
            ctx.arc(W/2, iconY, iconSize/2, 0, Math.PI*2);
            ctx.fill();
        } else if (currentShape === 'rounded') {
            drawRoundedRect(ctx, W/2-iconSize/2,
                iconY-iconSize/2, iconSize, iconSize,
                iconSize*0.2);
            ctx.fill();
        } else if (currentShape === 'square') {
            ctx.fillRect(W/2-iconSize/2, iconY-iconSize/2,
                iconSize, iconSize);
        } else if (currentShape === 'hexagon') {
            drawHexagon(ctx, W/2, iconY, iconSize/2);
            ctx.fill();
        }
        ctx.shadowBlur = 0;

        // Icon emoji
        if (currentIcon) {
            ctx.font = `${iconSize*0.5}px Segoe UI Emoji`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#fff';
            ctx.fillText(currentIcon, W/2, iconY+iconSize*0.03);
        }

        // Brand name
        const fontStr =
            `${currentFont.style} ${currentFont.weight} ${
                W*0.09}px '${currentFont.family}'`;
        ctx.font = fontStr;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Text gradient
        const textGrad = ctx.createLinearGradient(
            W*0.1, H*0.62, W*0.9, H*0.62);
        textGrad.addColorStop(0, '#ffffff');
        textGrad.addColorStop(1, theme[0]);
        ctx.fillStyle = textGrad;

        let displayName = brandName;
        if (currentFont.spaced) {
            displayName = brandName.split('').join(String.fromCharCode(8202).repeat(3));
        }
        ctx.fillText(displayName, W/2, H*0.68);

        // Tagline
        if (tagline) {
            ctx.font = `400 ${W*0.032}px 'Segoe UI'`;
            ctx.fillStyle = 'rgba(255,255,255,0.6)';
            ctx.letterSpacing = '2px';
            ctx.fillText(tagline.toUpperCase(), W/2, H*0.78);
            ctx.letterSpacing = '0px';
        }

        // Bottom accent line
        ctx.strokeStyle = theme[0];
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(W*0.35, H*0.85);
        ctx.lineTo(W*0.65, H*0.85);
        ctx.stroke();

    } else {
        // BANNER layout - horizontal
        const iconSize = H*0.55;
        const iconX = W*0.1;
        const iconY = H/2;

        const iconGrad = ctx.createLinearGradient(
            iconX-iconSize/2,iconY-iconSize/2,
            iconX+iconSize/2,iconY+iconSize/2);
        iconGrad.addColorStop(0,theme[0]);
        iconGrad.addColorStop(1,theme[1]);
        ctx.fillStyle = iconGrad;
        ctx.shadowColor = theme[0];
        ctx.shadowBlur = 25;

        if (currentShape === 'circle') {
            ctx.beginPath();
            ctx.arc(iconX, iconY, iconSize/2, 0, Math.PI*2);
            ctx.fill();
        } else if (currentShape === 'rounded') {
            drawRoundedRect(ctx, iconX-iconSize/2,
                iconY-iconSize/2, iconSize, iconSize,
                iconSize*0.2);
            ctx.fill();
        } else if (currentShape === 'square') {
            ctx.fillRect(iconX-iconSize/2, iconY-iconSize/2,
                iconSize, iconSize);
        } else if (currentShape === 'hexagon') {
            drawHexagon(ctx, iconX, iconY, iconSize/2);
            ctx.fill();
        }
        ctx.shadowBlur = 0;

        if (currentIcon) {
            ctx.font = `${iconSize*0.5}px Segoe UI Emoji`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#fff';
            ctx.fillText(currentIcon, iconX, iconY+iconSize*0.03);
        }

        // Text (right side)
        const textX = W*0.22;
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';

        const fontStr =
            `${currentFont.style} ${currentFont.weight} ${
                H*0.16}px '${currentFont.family}'`;
        ctx.font = fontStr;

        const textGrad = ctx.createLinearGradient(
            textX, 0, textX+W*0.4, 0);
        textGrad.addColorStop(0, '#ffffff');
        textGrad.addColorStop(1, theme[0]);
        ctx.fillStyle = textGrad;

        const nameY = tagline ? H*0.4 : H/2;
        ctx.fillText(brandName, textX, nameY);

        if (tagline) {
            ctx.font = `400 ${H*0.07}px 'Segoe UI'`;
            ctx.fillStyle = 'rgba(255,255,255,0.6)';
            ctx.fillText(tagline, textX, H*0.65);
        }

        // Accent line
        ctx.strokeStyle = theme[0];
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(textX, H*0.78);
        ctx.lineTo(textX+W*0.35, H*0.78);
        ctx.stroke();
    }

    // Update download links
    canvas.toBlob(blob => {
        const url = URL.createObjectURL(blob);
        document.getElementById('downloadPng').href = url;
        document.getElementById('downloadPng').download =
            `inkguard_${currentType}_${Date.now()}.png`;
    }, 'image/png');

    const jpgUrl = canvas.toDataURL('image/jpeg', 0.95);
    document.getElementById('downloadJpg').href = jpgUrl;
    document.getElementById('downloadJpg').download =
        `inkguard_${currentType}_${Date.now()}.jpg`;
}

// Init
buildColorGrid();
document.getElementById('brandName').value = 'InkGuard';
document.getElementById('tagline').value = 'AI Content Protection';
updatePreview();
</script>
</body>
</html>