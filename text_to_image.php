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
    <title>Text to Image - InkGuard</title>
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
            box-shadow:0 0 15px rgba(124,124,255,0.4);
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
            max-width:900px; margin:0 auto;
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
        .form-input:focus {
            border-color:rgba(124,124,255,0.6);
        }
        .form-input::placeholder { color:#555; }
        textarea.form-input {
            min-height:120px; resize:vertical; line-height:1.6;
        }
        .style-grid {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:10px; margin-bottom:16px;
        }
        .style-btn {
            padding:10px;
            border:1px solid rgba(255,255,255,0.1);
            border-radius:8px;
            background:rgba(0,0,0,0.2);
            color:#aaa; cursor:pointer;
            font-size:13px; text-align:center;
            transition:all 0.2s;
        }
        .style-btn:hover,
        .style-btn.selected {
            border-color:#7c7cff;
            background:rgba(124,124,255,0.15);
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
        .btn-generate:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(124,124,255,0.5);
        }
        .btn-generate:disabled {
            opacity:0.6; cursor:not-allowed; transform:none;
        }
        .result-section { display:none; }
        .image-wrap { text-align:center; padding:20px 0; }
        .generated-img {
            width:100%; max-width:512px;
            border-radius:16px;
            border:2px solid rgba(124,124,255,0.3);
            box-shadow:0 0 30px rgba(124,124,255,0.2);
        }
        .img-actions {
            display:flex; gap:10px;
            justify-content:center; margin-top:16px;
        }
        .btn-download {
            padding:10px 24px;
            background:linear-gradient(135deg,#00cc66,#009944);
            color:#fff; border:none; border-radius:10px;
            font-size:14px; font-weight:600; cursor:pointer;
            text-decoration:none; display:inline-block;
        }
        .btn-retry {
            padding:10px 24px;
            background:rgba(255,255,255,0.05);
            color:#aaa; border:1px solid rgba(255,255,255,0.1);
            border-radius:10px; font-size:14px; cursor:pointer;
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
        .examples {
            display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;
        }
        .example-tag {
            padding:6px 12px;
            background:rgba(124,124,255,0.1);
            border:1px solid rgba(124,124,255,0.2);
            border-radius:20px; font-size:12px;
            color:#7c7cff; cursor:pointer; transition:all 0.2s;
        }
        .example-tag:hover {
            background:rgba(124,124,255,0.2); color:#fff;
        }
        .info-box {
            background:rgba(124,124,255,0.1);
            border:1px solid rgba(124,124,255,0.3);
            border-radius:10px; padding:14px;
            color:#aaa; font-size:13px; margin-bottom:16px;
            display:flex; align-items:center; gap:10px;
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
        <h1>🎨 Text to Image</h1>
        <p>Generate AI-style images from your text description</p>
    </div>

    <div class="info-box">
        ℹ️ Generated locally using Canvas — no internet required.
        Each generation is unique based on your prompt!
    </div>

    <div class="glass-card">
        <h2>✍️ Describe Your Image</h2>

        <div class="form-group">
            <label>Image Description (Prompt)</label>
            <textarea class="form-input" id="promptText"
                placeholder="e.g. A beautiful sunset over mountains with purple sky...">
            </textarea>
        </div>

        <div class="form-group">
            <label>💡 Quick Examples (click to use):</label>
            <div class="examples">
                <span class="example-tag" onclick="useExample(this)">
                    A futuristic city at night with neon lights
                </span>
                <span class="example-tag" onclick="useExample(this)">
                    Beautiful tropical beach at sunset
                </span>
                <span class="example-tag" onclick="useExample(this)">
                    Abstract digital art with blue and purple colors
                </span>
                <span class="example-tag" onclick="useExample(this)">
                    Cute cartoon robot holding a shield
                </span>
                <span class="example-tag" onclick="useExample(this)">
                    Professional logo design minimalist style
                </span>
                <span class="example-tag" onclick="useExample(this)">
                    Galaxy and stars in deep space
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>🎨 Image Style:</label>
            <div class="style-grid">
                <div class="style-btn selected"
                    onclick="selectStyle(this,'')">
                    🖼️ Default
                </div>
                <div class="style-btn"
                    onclick="selectStyle(this,'digital art')">
                    💻 Digital Art
                </div>
                <div class="style-btn"
                    onclick="selectStyle(this,'realistic')">
                    📷 Realistic
                </div>
                <div class="style-btn"
                    onclick="selectStyle(this,'anime')">
                    🎌 Anime
                </div>
                <div class="style-btn"
                    onclick="selectStyle(this,'oil painting')">
                    🎨 Oil Painting
                </div>
                <div class="style-btn"
                    onclick="selectStyle(this,'minimalist')">
                    ✨ Minimalist
                </div>
            </div>
        </div>

        <button class="btn-generate" id="generateBtn"
            onclick="generateImage()">
            🎨 Generate Image
        </button>
    </div>

    <div class="glass-card result-section" id="resultSection">
        <h2>🖼️ Generated Image</h2>
        <div id="imageContent"></div>
    </div>

</div>

<script>
let selectedStyle = '';

function selectStyle(el, style) {
    document.querySelectorAll('.style-btn').forEach(b => {
        b.classList.remove('selected');
    });
    el.classList.add('selected');
    selectedStyle = style;
}

function useExample(el) {
    document.getElementById('promptText').value =
        el.textContent.trim();
}

function getColorsFromPrompt(prompt) {
    const p = prompt.toLowerCase();
    if (p.includes('sunset')||p.includes('orange')||p.includes('fire'))
        return ['#ff6b35','#ff4500','#1a0500'];
    if (p.includes('ocean')||p.includes('sea')||p.includes('beach'))
        return ['#006994','#00a8cc','#001a33'];
    if (p.includes('forest')||p.includes('nature')||p.includes('green'))
        return ['#1a4a1a','#2d7a2d','#0a1a0a'];
    if (p.includes('night')||p.includes('dark')||p.includes('space')||p.includes('galaxy'))
        return ['#0a0a2e','#1a1a6e','#2a0a4e'];
    if (p.includes('neon')||p.includes('cyber')||p.includes('futur'))
        return ['#0a0a1a','#7c7cff','#00ff88'];
    if (p.includes('pink')||p.includes('rose')||p.includes('flower'))
        return ['#4a0a2e','#cc2266','#ff66aa'];
    if (p.includes('gold')||p.includes('royal')||p.includes('luxury'))
        return ['#1a1000','#7a5500','#ffd700'];
    if (p.includes('logo')||p.includes('minimalist')||p.includes('brand'))
        return ['#050510','#1a1a3e','#7c7cff'];
    if (p.includes('red')||p.includes('fire')||p.includes('hot'))
        return ['#2a0000','#880000','#ff2200'];
    if (p.includes('tropical')||p.includes('paradise'))
        return ['#004433','#00aa66','#00ffaa'];
    return ['#0a0a2e','#1a0a4e','#2a1a6e'];
}
function drawArtElements(ctx, prompt, colors, style) {
    const p = prompt.toLowerCase();
    const words = p.split(' ');
    ctx.save();

    // Always draw style base first
    if (style === 'anime') {
        for (let i = 0; i < 5; i++) {
            ctx.strokeStyle = `rgba(255,255,255,${0.05+i*0.04})`;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(256,256,70+i*35,0,Math.PI*2);
            ctx.stroke();
        }
        for (let i = 0; i < 30; i++) {
            ctx.fillStyle = 'rgba(255,255,255,0.8)';
            ctx.beginPath();
            ctx.arc(Math.random()*512,
                Math.random()*380,
                Math.random()*2.5,0,Math.PI*2);
            ctx.fill();
        }
    } else if (style === 'minimalist') {
        ctx.globalAlpha = 0.6;
        ctx.fillStyle = colors[1]+'88';
        ctx.fillRect(128,128,256,256);
        ctx.fillStyle = colors[2]+'aa';
        ctx.beginPath();
        ctx.arc(256,256,90,0,Math.PI*2);
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.4)';
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.globalAlpha = 1;
    } else if (style === 'oil painting') {
        for (let i = 0; i < 400; i++) {
            const x = Math.random()*512;
            const y = Math.random()*512;
            const r = Math.random()*20+3;
            ctx.globalAlpha = 0.15+Math.random()*0.25;
            ctx.fillStyle = `hsl(${
                Math.random()*60-30},60%,${
                40+Math.random()*30}%)`;
            ctx.beginPath();
            ctx.ellipse(x,y,r,r/3,
                Math.random()*Math.PI,0,Math.PI*2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    } else if (style === 'digital art') {
        for (let i = 0; i < 10; i++) {
            const x = Math.random()*512;
            const y = Math.random()*400;
            const r = Math.random()*80+20;
            const g = ctx.createRadialGradient(x,y,0,x,y,r);
            g.addColorStop(0,'rgba(124,124,255,0.5)');
            g.addColorStop(0.5,'rgba(0,204,102,0.2)');
            g.addColorStop(1,'rgba(0,0,0,0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(x,y,r,0,Math.PI*2);
            ctx.fill();
        }
        ctx.strokeStyle = 'rgba(124,124,255,0.1)';
        ctx.lineWidth = 1;
        for (let i = 0; i < 512; i+=32) {
            ctx.beginPath();
            ctx.moveTo(i,0); ctx.lineTo(i,512); ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(0,i); ctx.lineTo(512,i); ctx.stroke();
        }
    } else {
        // Default abstract base
        ctx.globalAlpha = 0.4;
        for (let i = 0; i < 20; i++) {
            ctx.fillStyle = i%2===0 ?
                'rgba(255,255,255,0.15)' : colors[1]+'44';
            ctx.beginPath();
            ctx.arc(Math.random()*512,
                Math.random()*400,
                Math.random()*80+20,0,Math.PI*2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    // ============================================
    // SMART ELEMENT DRAWING based on ANY prompt
    // ============================================
    ctx.globalAlpha = 0.85;

    // Person / Woman / Man / Human
    if (p.includes('woman')||p.includes('man')||
        p.includes('person')||p.includes('girl')||
        p.includes('boy')||p.includes('people')||
        p.includes('human')||p.includes('worker')||
        p.includes('student')) {
        // Head
        ctx.fillStyle = '#F5CBA7';
        ctx.beginPath();
        ctx.arc(256,160,45,0,Math.PI*2);
        ctx.fill();
        // Hair
        ctx.fillStyle = '#5D4037';
        ctx.beginPath();
        ctx.arc(256,140,45,Math.PI,0);
        ctx.fill();
        ctx.fillRect(211,140,90,20);
        // Body
        ctx.fillStyle = '#3498DB';
        ctx.beginPath();
        ctx.roundRect(206,205,100,110,8);
        ctx.fill();
        // Arms
        ctx.strokeStyle = '#F5CBA7';
        ctx.lineWidth = 18;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(210,215); ctx.lineTo(165,285); ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(302,215); ctx.lineTo(347,285); ctx.stroke();
        // Legs
        ctx.strokeStyle = '#2C3E50';
        ctx.lineWidth = 20;
        ctx.beginPath();
        ctx.moveTo(235,315); ctx.lineTo(220,390); ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(277,315); ctx.lineTo(292,390); ctx.stroke();
    }

    // Laptop / Computer / Office
    if (p.includes('laptop')||p.includes('computer')||
        p.includes('office')||p.includes('work')||
        p.includes('desk')||p.includes('coding')) {
        // Desk
        ctx.fillStyle = '#795548';
        ctx.fillRect(100,370,312,15);
        ctx.fillRect(100,385,20,60);
        ctx.fillRect(392,385,20,60);
        // Laptop base
        ctx.fillStyle = '#607D8B';
        ctx.beginPath();
        ctx.roundRect(160,300,192,15,3);
        ctx.fill();
        // Laptop screen
        ctx.fillStyle = '#455A64';
        ctx.beginPath();
        ctx.roundRect(168,200,176,100,5);
        ctx.fill();
        // Screen content
        ctx.fillStyle = '#00BCD4';
        ctx.fillRect(176,210,160,80);
        // Screen glow
        ctx.fillStyle = 'rgba(0,188,212,0.3)';
        ctx.fillRect(170,205,172,90);
        // Keyboard lines
        ctx.strokeStyle = 'rgba(255,255,255,0.3)';
        ctx.lineWidth = 1;
        for (let i = 0; i < 5; i++) {
            ctx.beginPath();
            ctx.moveTo(165,305+i*2);
            ctx.lineTo(347,305+i*2);
            ctx.stroke();
        }
    }

    // Nature / Tree / Forest
    if (p.includes('tree')||p.includes('forest')||
        p.includes('nature')||p.includes('park')||
        p.includes('garden')) {
        // Ground
        ctx.fillStyle = '#4CAF50';
        ctx.fillRect(0,380,512,132);
        // Trees
        const trees = [[150,200],[256,170],[362,210],[80,220],[430,190]];
        trees.forEach(([x,y]) => {
            // Trunk
            ctx.fillStyle = '#5D4037';
            ctx.fillRect(x-8,y+80,16,60);
            // Leaves
            ctx.fillStyle = '#2E7D32';
            ctx.beginPath();
            ctx.arc(x,y+50,45,0,Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#388E3C';
            ctx.beginPath();
            ctx.arc(x,y+25,35,0,Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#43A047';
            ctx.beginPath();
            ctx.arc(x,y,25,0,Math.PI*2);
            ctx.fill();
        });
    }

    // Car / Vehicle / Road
    if (p.includes('car')||p.includes('vehicle')||
        p.includes('road')||p.includes('drive')||
        p.includes('truck')) {
        // Road
        ctx.fillStyle = '#424242';
        ctx.fillRect(0,360,512,152);
        // Road lines
        ctx.strokeStyle = '#FFEB3B';
        ctx.lineWidth = 4;
        ctx.setLineDash([30,20]);
        ctx.beginPath();
        ctx.moveTo(0,435); ctx.lineTo(512,435);
        ctx.stroke();
        ctx.setLineDash([]);
        // Car body
        ctx.fillStyle = '#E53935';
        ctx.beginPath();
        ctx.roundRect(120,290,272,80,10);
        ctx.fill();
        // Car top
        ctx.fillStyle = '#C62828';
        ctx.beginPath();
        ctx.roundRect(170,240,172,55,8);
        ctx.fill();
        // Windows
        ctx.fillStyle = '#90CAF9';
        ctx.beginPath();
        ctx.roundRect(178,248,70,40,4);
        ctx.fill();
        ctx.beginPath();
        ctx.roundRect(256,248,78,40,4);
        ctx.fill();
        // Wheels
        ctx.fillStyle = '#212121';
        [170,342].forEach(wx => {
            ctx.beginPath();
            ctx.arc(wx,372,30,0,Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#9E9E9E';
            ctx.beginPath();
            ctx.arc(wx,372,15,0,Math.PI*2);
            ctx.fill();
            ctx.fillStyle = '#212121';
        });
    }

    // House / Home / Building
    if (p.includes('house')||p.includes('home')||
        p.includes('building')||p.includes('architecture')) {
        // Ground
        ctx.fillStyle = '#81C784';
        ctx.fillRect(0,400,512,112);
        // House body
        ctx.fillStyle = '#F5F5F5';
        ctx.fillRect(156,250,200,155);
        // Roof
        ctx.fillStyle = '#B71C1C';
        ctx.beginPath();
        ctx.moveTo(136,255);
        ctx.lineTo(256,150);
        ctx.lineTo(376,255);
        ctx.closePath();
        ctx.fill();
        // Door
        ctx.fillStyle = '#5D4037';
        ctx.beginPath();
        ctx.roundRect(230,330,52,75,5);
        ctx.fill();
        ctx.fillStyle = '#FFD54F';
        ctx.beginPath();
        ctx.arc(274,367,4,0,Math.PI*2);
        ctx.fill();
        // Windows
        ctx.fillStyle = '#90CAF9';
        [[170,270],[310,270]].forEach(([wx,wy]) => {
            ctx.fillRect(wx,wy,50,45);
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(wx+25,wy);
            ctx.lineTo(wx+25,wy+45);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(wx,wy+22);
            ctx.lineTo(wx+50,wy+22);
            ctx.stroke();
        });
    }

    // Food / Cake / Restaurant
    if (p.includes('food')||p.includes('cake')||
        p.includes('restaurant')||p.includes('eat')||
        p.includes('coffee')||p.includes('pizza')) {
        if (p.includes('coffee')) {
            // Coffee cup
            ctx.fillStyle = '#fff';
            ctx.beginPath();
            ctx.roundRect(186,220,140,140,10);
            ctx.fill();
            // Coffee
            ctx.fillStyle = '#5D4037';
            ctx.beginPath();
            ctx.arc(256,280,55,0,Math.PI*2);
            ctx.fill();
            // Steam
            ctx.strokeStyle = 'rgba(255,255,255,0.7)';
            ctx.lineWidth = 3;
            for (let i = 0; i < 3; i++) {
                ctx.beginPath();
                ctx.moveTo(220+i*18,215);
                ctx.bezierCurveTo(
                    215+i*18,195,
                    225+i*18,185,
                    220+i*18,165);
                ctx.stroke();
            }
            // Handle
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 8;
            ctx.beginPath();
            ctx.arc(326,280,30,
                -Math.PI/2,Math.PI/2);
            ctx.stroke();
        } else {
            // Generic food plate
            ctx.fillStyle = '#F5F5F5';
            ctx.beginPath();
            ctx.arc(256,280,100,0,Math.PI*2);
            ctx.fill();
            ctx.strokeStyle = '#E0E0E0';
            ctx.lineWidth = 3;
            ctx.stroke();
            // Food items
            const foodColors = ['#FF7043','#66BB6A','#FFA726'];
            foodColors.forEach((fc,i) => {
                ctx.fillStyle = fc;
                ctx.beginPath();
                ctx.arc(230+i*26,260+
                    (i===1?30:0),25,0,Math.PI*2);
                ctx.fill();
            });
        }
    }

    // Flower / Rose / Bloom
    if (p.includes('flower')||p.includes('rose')||
        p.includes('bloom')||p.includes('petal')) {
        const flowerColors = ['#E91E63','#FF5722',
            '#9C27B0','#FF9800'];
        for (let f = 0; f < 4; f++) {
            const fx = 130+f*90;
            const fy = 280;
            // Stem
            ctx.strokeStyle = '#4CAF50';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(fx,fy+60);
            ctx.lineTo(fx,fy+130);
            ctx.stroke();
            // Petals
            ctx.fillStyle = flowerColors[f%4];
            for (let p2 = 0; p2 < 8; p2++) {
                ctx.save();
                ctx.translate(fx,fy);
                ctx.rotate(p2*Math.PI/4);
                ctx.beginPath();
                ctx.ellipse(0,-25,12,25,0,0,Math.PI*2);
                ctx.fill();
                ctx.restore();
            }
            // Center
            ctx.fillStyle = '#FDD835';
            ctx.beginPath();
            ctx.arc(fx,fy,12,0,Math.PI*2);
            ctx.fill();
        }
        // Grass
        ctx.fillStyle = '#4CAF50';
        ctx.fillRect(0,400,512,112);
    }

    // Animal / Cat / Dog
    if (p.includes('cat')||p.includes('dog')||
        p.includes('animal')||p.includes('pet')) {
        if (p.includes('cat')) {
            // Cat body
            ctx.fillStyle = '#FF8F00';
            ctx.beginPath();
            ctx.ellipse(256,300,70,55,0,0,Math.PI*2);
            ctx.fill();
            // Head
            ctx.beginPath();
            ctx.arc(256,210,50,0,Math.PI*2);
            ctx.fill();
            // Ears
            ctx.beginPath();
            ctx.moveTo(215,175);
            ctx.lineTo(200,140);
            ctx.lineTo(235,165);
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(297,175);
            ctx.lineTo(312,140);
            ctx.lineTo(277,165);
            ctx.fill();
            // Eyes
            ctx.fillStyle = '#00E676';
            ctx.beginPath();
            ctx.ellipse(238,207,10,12,0,0,Math.PI*2);
            ctx.fill();
            ctx.beginPath();
            ctx.ellipse(274,207,10,12,0,0,Math.PI*2);
            ctx.fill();
            // Pupils
            ctx.fillStyle = '#000';
            ctx.beginPath();
            ctx.ellipse(238,207,4,10,0,0,Math.PI*2);
            ctx.fill();
            ctx.beginPath();
            ctx.ellipse(274,207,4,10,0,0,Math.PI*2);
            ctx.fill();
            // Nose
            ctx.fillStyle = '#F48FB1';
            ctx.beginPath();
            ctx.moveTo(256,220);
            ctx.lineTo(249,228);
            ctx.lineTo(263,228);
            ctx.fill();
            // Tail
            ctx.strokeStyle = '#FF8F00';
            ctx.lineWidth = 12;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(326,340);
            ctx.bezierCurveTo(380,320,400,260,360,220);
            ctx.stroke();
        }
    }

    // Abstract / Art / Creative (generic)
    if (p.includes('abstract')||p.includes('art')||
        p.includes('creative')||p.includes('design')||
        p.includes('pattern')||p.includes('color')) {
        for (let i = 0; i < 12; i++) {
            ctx.globalAlpha = 0.6;
            const hue = i*30;
            ctx.fillStyle = `hsla(${hue},80%,60%,0.6)`;
            const shapes = ['circle','rect','triangle'];
            const s = shapes[i%3];
            const x = 50+Math.random()*400;
            const y = 50+Math.random()*350;
            const r = 20+Math.random()*60;
            if (s==='circle') {
                ctx.beginPath();
                ctx.arc(x,y,r,0,Math.PI*2);
                ctx.fill();
            } else if (s==='rect') {
                ctx.fillRect(x-r/2,y-r/2,r,r);
            } else {
                ctx.beginPath();
                ctx.moveTo(x,y-r);
                ctx.lineTo(x+r,y+r);
                ctx.lineTo(x-r,y+r);
                ctx.closePath();
                ctx.fill();
            }
        }
        ctx.globalAlpha = 1;
    }

    // Stars / Space / Galaxy / Night
    if (p.includes('star')||p.includes('space')||
        p.includes('galaxy')||p.includes('night')||
        p.includes('sky')||p.includes('universe')) {
        for (let i = 0; i < 80; i++) {
            const sz = Math.random()*2.5;
            ctx.fillStyle =
                `rgba(255,255,255,${0.5+Math.random()*0.5})`;
            ctx.beginPath();
            ctx.arc(Math.random()*512,
                Math.random()*350,sz,0,Math.PI*2);
            ctx.fill();
        }
        // Moon
        if (p.includes('moon')||p.includes('night')) {
            ctx.fillStyle = '#FFF9C4';
            ctx.beginPath();
            ctx.arc(380,80,40,0,Math.PI*2);
            ctx.fill();
            ctx.fillStyle = colors[0];
            ctx.beginPath();
            ctx.arc(398,72,34,0,Math.PI*2);
            ctx.fill();
        }
        // Planet
        if (p.includes('planet')||p.includes('galaxy')) {
            const pg = ctx.createRadialGradient(
                256,200,0,256,200,70);
            pg.addColorStop(0,'#CE93D8');
            pg.addColorStop(1,'#7B1FA2');
            ctx.fillStyle = pg;
            ctx.beginPath();
            ctx.arc(256,200,70,0,Math.PI*2);
            ctx.fill();
            // Ring
            ctx.strokeStyle = 'rgba(255,255,255,0.4)';
            ctx.lineWidth = 8;
            ctx.beginPath();
            ctx.ellipse(256,200,120,20,
                -0.3,0,Math.PI*2);
            ctx.stroke();
        }
    }

    // Sun / Sunset / Sunrise / Sky
    if (p.includes('sun')||p.includes('sunset')||
        p.includes('sunrise')||p.includes('sunny')) {
        const sg = ctx.createRadialGradient(
            256,180,0,256,180,90);
        sg.addColorStop(0,'rgba(255,235,59,1)');
        sg.addColorStop(0.5,'rgba(255,152,0,0.6)');
        sg.addColorStop(1,'rgba(255,87,34,0)');
        ctx.fillStyle = sg;
        ctx.beginPath();
        ctx.arc(256,180,90,0,Math.PI*2);
        ctx.fill();
        // Sun rays
        ctx.strokeStyle = 'rgba(255,235,59,0.4)';
        ctx.lineWidth = 3;
        for (let i = 0; i < 12; i++) {
            ctx.save();
            ctx.translate(256,180);
            ctx.rotate(i*Math.PI/6);
            ctx.beginPath();
            ctx.moveTo(0,95);
            ctx.lineTo(0,130);
            ctx.stroke();
            ctx.restore();
        }
    }

    // Mountain / Hill / Snow
    if (p.includes('mountain')||p.includes('hill')||
        p.includes('volcano')) {
        ctx.fillStyle = '#78909C';
        ctx.beginPath();
        ctx.moveTo(0,512);
        ctx.lineTo(0,400);
        ctx.lineTo(120,220);
        ctx.lineTo(256,120);
        ctx.lineTo(392,220);
        ctx.lineTo(512,400);
        ctx.lineTo(512,512);
        ctx.closePath();
        ctx.fill();
        // Snow cap
        ctx.fillStyle = '#ECEFF1';
        ctx.beginPath();
        ctx.moveTo(256,120);
        ctx.lineTo(218,210);
        ctx.lineTo(294,210);
        ctx.closePath();
        ctx.fill();
        // Sky
        const skyG = ctx.createLinearGradient(0,0,0,120);
        skyG.addColorStop(0,'rgba(33,150,243,0.5)');
        skyG.addColorStop(1,'transparent');
        ctx.fillStyle = skyG;
        ctx.fillRect(0,0,512,120);
    }

    // Ocean / Beach / Sea / Water
    if (p.includes('ocean')||p.includes('beach')||
        p.includes('sea')||p.includes('wave')||
        p.includes('water')) {
        // Sky
        const skyG = ctx.createLinearGradient(0,0,0,250);
        skyG.addColorStop(0,'rgba(135,206,235,0.6)');
        skyG.addColorStop(1,'rgba(0,150,200,0.3)');
        ctx.fillStyle = skyG;
        ctx.fillRect(0,0,512,250);
        // Water
        const waterG = ctx.createLinearGradient(0,250,0,512);
        waterG.addColorStop(0,'rgba(0,150,200,0.7)');
        waterG.addColorStop(1,'rgba(0,80,120,0.9)');
        ctx.fillStyle = waterG;
        ctx.fillRect(0,250,512,262);
        // Waves
        for (let i = 0; i < 5; i++) {
            ctx.strokeStyle =
                `rgba(255,255,255,${0.2+i*0.05})`;
            ctx.lineWidth = 2;
            ctx.beginPath();
            for (let x = 0; x <= 512; x+=20) {
                const y = 270+i*25+
                    Math.sin((x/40)+i)*12;
                x===0 ? ctx.moveTo(x,y) :
                    ctx.lineTo(x,y);
            }
            ctx.stroke();
        }
        // Beach sand
        if (p.includes('beach')) {
            ctx.fillStyle = '#F5DEB3';
            ctx.beginPath();
            ctx.ellipse(256,460,200,60,0,0,Math.PI*2);
            ctx.fill();
        }
    }

    // City / Urban / Skyline
    if (p.includes('city')||p.includes('urban')||
        p.includes('skyline')||p.includes('downtown')) {
        // Sky gradient
        const cityG = ctx.createLinearGradient(0,0,0,300);
        cityG.addColorStop(0,'rgba(10,10,40,0.8)');
        cityG.addColorStop(1,'rgba(30,30,80,0.4)');
        ctx.fillStyle = cityG;
        ctx.fillRect(0,0,512,400);
        // Buildings
        const bldgs = [
            [20,200,55,200],[90,250,50,150],
            [155,180,65,220],[235,150,60,250],
            [310,210,55,190],[378,170,65,230],
            [455,230,50,170]
        ];
        bldgs.forEach(([x,y,w,h]) => {
            ctx.fillStyle = `rgba(${20+Math.random()*20},
                ${20+Math.random()*20},
                ${60+Math.random()*40},0.9)`;
            ctx.fillRect(x,y,w,h);
            // Windows
            for (let wy=y+10; wy<y+h-10; wy+=18) {
                for (let wx=x+5; wx<x+w-5; wx+=12) {
                    if (Math.random()>0.35) {
                        ctx.fillStyle =
                            `rgba(255,${200+Math.random()*55},
                            100,${0.4+Math.random()*0.5})`;
                        ctx.fillRect(wx,wy,8,10);
                    }
                }
            }
        });
        // Neon lights
        if (p.includes('neon')) {
            ['#ff0066','#00ffff','#ffff00'].forEach((c,i) => {
                ctx.strokeStyle = c;
                ctx.lineWidth = 2;
                ctx.shadowColor = c;
                ctx.shadowBlur = 15;
                ctx.beginPath();
                ctx.moveTo(i*180+20,380);
                ctx.lineTo(i*180+140,380);
                ctx.stroke();
            });
            ctx.shadowBlur = 0;
        }
    }

    // Shield / Guard / Security
    if (p.includes('shield')||p.includes('guard')||
        p.includes('security')||p.includes('protect')) {
        ctx.fillStyle = 'rgba(124,124,255,0.7)';
        ctx.beginPath();
        ctx.moveTo(256,140);
        ctx.lineTo(340,175);
        ctx.lineTo(340,285);
        ctx.lineTo(256,340);
        ctx.lineTo(172,285);
        ctx.lineTo(172,175);
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.8)';
        ctx.lineWidth = 4;
        ctx.stroke();
        // Checkmark
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 6;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(220,240);
        ctx.lineTo(248,270);
        ctx.lineTo(295,215);
        ctx.stroke();
    }

    // If NO specific keyword matched — draw creative abstract
    const knownWords = ['woman','man','person','girl','boy',
        'laptop','computer','office','tree','forest','car',
        'house','food','flower','cat','dog','star','space',
        'sun','mountain','ocean','city','shield','abstract',
        'art','creative','night','beach','galaxy','robot',
        'water','building','nature','work'];

    const hasMatch = knownWords.some(w => p.includes(w));

    if (!hasMatch) {
        // Generic creative scene based on word count
        const wordList = prompt.split(' ');
        const seed = wordList.length;

        // Colorful shapes representing the concept
        ctx.globalAlpha = 0.7;
        for (let i = 0; i < 15; i++) {
            const hue = (i*24+seed*10)%360;
            ctx.fillStyle = `hsla(${hue},75%,55%,0.65)`;
            const x = 40+i*30;
            const y = 150+(Math.sin(i*0.8+seed)*80);
            const r = 20+Math.sin(i*1.2)*20;
            ctx.beginPath();
            if (i%3===0) {
                ctx.arc(x,y,r,0,Math.PI*2);
            } else if (i%3===1) {
                ctx.fillRect(x-r/2,y-r/2,r,r);
            } else {
                ctx.moveTo(x,y-r);
                ctx.lineTo(x+r,y+r);
                ctx.lineTo(x-r,y+r);
                ctx.closePath();
            }
            ctx.fill();
        }

        // Text visualization in center
        ctx.globalAlpha = 0.9;
        ctx.fillStyle = 'rgba(255,255,255,0.15)';
        ctx.beginPath();
        ctx.roundRect(100,180,312,150,20);
        ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.8)';
        ctx.font = 'bold 15px Segoe UI';
        ctx.textAlign = 'center';

        // Show first few words as visual elements
        wordList.slice(0,6).forEach((word,i) => {
            const angle = (i/6)*Math.PI*2;
            const radius = 70;
            const wx = 256+Math.cos(angle)*radius;
            const wy = 255+Math.sin(angle)*radius;
            ctx.fillStyle =
                `hsla(${i*60},80%,70%,0.9)`;
            ctx.font = `bold ${12+i*2}px Segoe UI`;
            ctx.fillText(word, wx, wy);
        });

        ctx.globalAlpha = 1;
    }

    ctx.globalAlpha = 1;
    ctx.restore();
}
function generateImage() {
    const prompt = document.getElementById('promptText').value.trim();
    if (!prompt) { alert('Please enter a description!'); return; }

    const btn = document.getElementById('generateBtn');
    const resultSection = document.getElementById('resultSection');
    const imageContent = document.getElementById('imageContent');

    btn.disabled = true;
    btn.textContent = '⏳ Generating...';
    resultSection.style.display = 'block';
    imageContent.innerHTML = `
        <div class="loading-wrap">
            <div class="spinner"></div>
            <p style="color:#7c7cff; font-weight:600; font-size:16px">
                🎨 Generating your image...
            </p>
            <p style="color:#555; font-size:13px; margin-top:8px">
                Creating unique artwork from your prompt
            </p>
        </div>`;
    resultSection.scrollIntoView({behavior:'smooth'});

    setTimeout(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');
        const colors = getColorsFromPrompt(prompt);

        // Background gradient
        const grad = ctx.createLinearGradient(0,0,512,512);
        grad.addColorStop(0, colors[0]);
        grad.addColorStop(0.5, colors[1]);
        grad.addColorStop(1, colors[2]);
        ctx.fillStyle = grad;
        ctx.fillRect(0,0,512,512);

        // Draw art elements
        drawArtElements(ctx, prompt, colors, selectedStyle);

        // Bottom overlay
        ctx.fillStyle = 'rgba(0,0,0,0.55)';
        ctx.fillRect(0,420,512,92);

        // Prompt text
        ctx.fillStyle = 'rgba(255,255,255,0.75)';
        ctx.font = '12px Segoe UI';
        ctx.textAlign = 'center';
        const short = prompt.length>65 ?
            prompt.substring(0,65)+'...' : prompt;
        ctx.fillText(short, 256, 450);

        // Style label
        if (selectedStyle) {
            ctx.fillStyle = 'rgba(124,124,255,0.9)';
            ctx.font = 'bold 11px Segoe UI';
            ctx.fillText(selectedStyle.toUpperCase(), 256, 470);
        }

        // Watermark
        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.font = '11px Segoe UI';
        ctx.fillText('✨ InkGuard AI Generator', 256, 500);

        const imageUrl = canvas.toDataURL('image/png');

        imageContent.innerHTML = `
            <div class="image-wrap">
                <img src="${imageUrl}"
                    class="generated-img"
                    alt="Generated Image">
                <div class="img-actions">
                    <a href="${imageUrl}"
                        download="inkguard_${Date.now()}.png"
                        class="btn-download">
                        ⬇️ Download PNG
                    </a>
                    <button onclick="generateImage()"
                        class="btn-retry">
                        🔄 Generate Again
                    </button>
                </div>
                <p style="color:#555; font-size:12px; margin-top:12px">
                    Prompt: "${prompt}"
                    ${selectedStyle ? '| Style: '+selectedStyle : ''}
                </p>
            </div>`;

        btn.disabled = false;
        btn.textContent = '🎨 Generate Image';
    }, 1800);
}
</script>
</body>
</html>