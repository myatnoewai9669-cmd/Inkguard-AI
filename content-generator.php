<?php
/**
 * Inkwell — AI Content Generator
 * Single-file PHP page: form handling + a lightweight rule-based
 * generator so the page works end-to-end without any external API key.
 * Swap generateContent() for a real API call whenever you're ready
 * (see the comment inside the function).
 */

session_start();

function generateContent(string $topic, string $tone, string $format, int $length): array {
    if (trim($topic) === '') {
        return ['ok' => false, 'error' => 'Give me a subject to write about first.'];
    }

    // --- Replace this block with a real API call when ready, e.g.:
    // $response = callYourAiApi($topic, $tone, $format, $length);
    // -----------------------------------------------------------------

    $openers = [
        'formal'    => ["In considering %s, it is worth noting that", "A closer examination of %s reveals that"],
        'friendly'  => ["Let's talk about %s for a second —", "Okay, so %s is actually pretty interesting."],
        'bold'      => ["%s isn't just a topic. It's a turning point.", "Here's the truth about %s that no one says out loud:"],
        'playful'   => ["So, %s, huh? Buckle up.", "Fun fact about %s: it's weirder than you think."],
    ];
    $bodies = [
        "the underlying patterns matter more than the surface details, and understanding them changes how you approach everything downstream.",
        "most people overestimate the complexity and underestimate the discipline it takes to get right.",
        "the difference between good and great usually comes down to a handful of small, repeatable decisions.",
        "context is everything — the same idea lands completely differently depending on who's reading it.",
    ];
    $closers = [
        'formal'   => "In summary, this warrants continued attention and careful execution.",
        'friendly' => "Anyway, that's the gist of it — hope that helps!",
        'bold'     => "Don't wait for permission. Act on it.",
        'playful'  => "And that's the tea. ✨",
    ];

    $tone = array_key_exists($tone, $openers) ? $tone : 'friendly';
    $opener = sprintf($openers[$tone][array_rand($openers[$tone])], $topic);
    $paraCount = max(1, (int) round($length / 320));

    $paragraphs = [];
    for ($i = 0; $i < $paraCount; $i++) {
        $paragraphs[] = ($i === 0 ? $opener . ' ' : '') . ucfirst($bodies[array_rand($bodies)]);
    }
    $paragraphs[] = $closers[$tone];

    $prefix = match ($format) {
        'headline' => "🖋 " . ucfirst($topic) . ": " . ucfirst(explode('.', $bodies[0])[0]) . "\n\n",
        'social'   => "",
        default    => "",
    };

    $text = $prefix . implode("\n\n", $paragraphs);
    $words = str_word_count($text);

    return [
        'ok' => true,
        'text' => $text,
        'meta' => [
            'words' => $words,
            'chars' => mb_strlen($text),
            'read_time' => max(1, (int) round($words / 200)),
        ],
    ];
}

$result = null;
$topic = $_POST['topic'] ?? '';
$tone = $_POST['tone'] ?? 'friendly';
$format = $_POST['format'] ?? 'paragraph';
$length = (int) ($_POST['length'] ?? 400);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = generateContent($topic, $tone, $format, $length);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inkwell — AI Content Generator</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --bg: #0F1A17;
    --surface: #16241F;
    --surface-2: #1D2E27;
    --line: #2B3F37;
    --gold: #C9A227;
    --teal: #4A9184;
    --ink: #F2EFE6;
    --ink-dim: #A7B6AE;
    --radius: 4px;
  }
  *{box-sizing:border-box;}
  html{scroll-behavior:smooth;}
  body{
    margin:0;
    background:
      radial-gradient(ellipse at top left, rgba(201,162,39,0.06), transparent 45%),
      var(--bg);
    color:var(--ink);
    font-family:'Inter', sans-serif;
    line-height:1.6;
  }
  @media (prefers-reduced-motion: reduce){
    *{animation-duration:0.01ms !important; transition-duration:0.01ms !important;}
  }

  a{color:inherit;}

  header.top{
    display:flex; align-items:center; justify-content:space-between;
    padding:28px 48px;
    border-bottom:1px solid var(--line);
  }
  .brand{
    display:flex; align-items:center; gap:12px;
    font-family:'Fraunces', serif; font-size:22px; letter-spacing:0.01em;
  }
  .brand .nib{
    width:22px; height:22px; position:relative; flex-shrink:0;
  }
  .brand .nib svg{width:100%; height:100%; display:block;}
  nav.top-links{display:flex; gap:32px; font-size:14px; color:var(--ink-dim);}
  nav.top-links a{text-decoration:none; transition:color .2s;}
  nav.top-links a:hover{color:var(--gold);}

  main{max-width:920px; margin:0 auto; padding:64px 32px 96px;}

  .eyebrow{
    font-family:'JetBrains Mono', monospace; font-size:12px; letter-spacing:0.14em;
    text-transform:uppercase; color:var(--teal); margin-bottom:18px;
    display:flex; align-items:center; gap:10px;
  }
  .eyebrow::before{content:''; width:28px; height:1px; background:var(--teal);}

  h1{
    font-family:'Fraunces', serif; font-weight:400; font-size:clamp(34px, 5vw, 54px);
    line-height:1.08; margin:0 0 16px; letter-spacing:-0.01em;
  }
  h1 em{font-style:italic; color:var(--gold);}
  p.lede{color:var(--ink-dim); font-size:17px; max-width:56ch; margin:0 0 48px;}

  form{
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius);
    padding:0;
    position:relative;
  }
  .manuscript{
    position:relative;
    border-bottom:1px solid var(--line);
  }
  .manuscript::before{
    /* ruled margin, like a page */
    content:'';
    position:absolute; left:56px; top:0; bottom:0; width:1px;
    background:linear-gradient(var(--gold), transparent 90%);
    opacity:0.35;
  }
  textarea{
    width:100%;
    background:transparent;
    border:none;
    color:var(--ink);
    font-family:'Fraunces', serif;
    font-size:19px;
    line-height:1.9;
    padding:32px 32px 32px 76px;
    resize:vertical;
    min-height:130px;
    outline:none;
  }
  textarea::placeholder{color:#5B6B63;}

  .controls{
    display:flex; flex-wrap:wrap; gap:0;
    border-bottom:1px solid var(--line);
  }
  .field{
    flex:1; min-width:150px;
    padding:18px 24px;
    border-right:1px solid var(--line);
  }
  .field:last-child{border-right:none;}
  .field label{
    display:block; font-family:'JetBrains Mono', monospace; font-size:11px;
    letter-spacing:0.1em; text-transform:uppercase; color:var(--ink-dim); margin-bottom:8px;
  }
  select, input[type=range]{
    width:100%; background:transparent; border:none; color:var(--ink);
    font-family:'Inter'; font-size:14px; outline:none; appearance:none;
  }
  select option{background:var(--surface-2); color:var(--ink);}
  .lenval{font-family:'JetBrains Mono', monospace; font-size:13px; color:var(--gold);}

  .submit-row{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 32px;
  }
  .hint{font-size:13px; color:var(--ink-dim);}
  button.seal{
    font-family:'Fraunces', serif; font-size:16px; font-weight:600;
    background:var(--gold); color:#16130A; border:none;
    padding:14px 30px; border-radius:999px; cursor:pointer;
    display:inline-flex; align-items:center; gap:10px;
    transition:transform .15s ease, box-shadow .15s ease;
    box-shadow:0 0 0 0 rgba(201,162,39,0.4);
  }
  button.seal:hover{transform:translateY(-1px); box-shadow:0 6px 20px rgba(201,162,39,0.25);}
  button.seal:active{transform:translateY(0);}
  button.seal svg{width:16px; height:16px;}

  .output{
    margin-top:36px;
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius);
    padding:36px 40px;
    animation:reveal .5s ease both;
  }
  @keyframes reveal{
    from{opacity:0; transform:translateY(6px);}
    to{opacity:1; transform:translateY(0);}
  }
  .output-head{
    display:flex; justify-content:space-between; align-items:baseline;
    border-bottom:1px solid var(--line); padding-bottom:16px; margin-bottom:20px;
  }
  .output-head h2{
    font-family:'Fraunces', serif; font-weight:400; font-size:20px; margin:0;
  }
  .output-meta{
    font-family:'JetBrains Mono', monospace; font-size:12px; color:var(--ink-dim); display:flex; gap:16px;
  }
  .generated{
    font-family:'Fraunces', serif; font-size:18px; line-height:1.85; white-space:pre-wrap;
  }
  .error-box{
    margin-top:24px; padding:18px 22px; border:1px solid #6E3B2E;
    background:rgba(198,84,52,0.08); border-radius:var(--radius); color:#E4A48E; font-size:14px;
  }

  .ledger{margin-top:96px;}
  .ledger .eyebrow{margin-bottom:8px;}
  .ledger h2{font-family:'Fraunces', serif; font-weight:400; font-size:28px; margin:0 0 36px;}
  .ledger-row{
    display:flex; gap:24px; align-items:flex-start;
    padding:22px 0; border-top:1px solid var(--line);
  }
  .ledger-row:last-child{border-bottom:1px solid var(--line);}
  .ledger-num{
    font-family:'JetBrains Mono', monospace; color:var(--gold); font-size:13px; width:32px; flex-shrink:0; padding-top:3px;
  }
  .ledger-body h3{margin:0 0 4px; font-size:16px; font-weight:600;}
  .ledger-body p{margin:0; color:var(--ink-dim); font-size:14px;}

  footer{
    text-align:center; padding:48px; color:var(--ink-dim); font-size:13px;
    border-top:1px solid var(--line); margin-top:96px;
  }

  @media (max-width:640px){
    header.top{padding:20px 24px;}
    nav.top-links{display:none;}
    main{padding:44px 20px 72px;}
    .manuscript::before{left:24px;}
    textarea{padding:24px 20px 24px 44px;}
    .controls{flex-direction:column;}
    .field{border-right:none; border-bottom:1px solid var(--line);}
    .submit-row{flex-direction:column; align-items:flex-start; gap:14px;}
  }
</style>
</head>
<body>

<header class="top">
  <div class="brand">
    <span class="nib">
      <svg viewBox="0 0 24 24" fill="none"><path d="M12 2 L20 10 L12 22 L4 10 Z" stroke="#C9A227" stroke-width="1.4"/><line x1="12" y1="2" x2="12" y2="22" stroke="#C9A227" stroke-width="1"/></svg>
    </span>
    Inkwell
  </div>
  <nav class="top-links">
    <a href="#generate">Generate</a>
    <a href="#tools">Tools</a>
    <a href="#">History</a>
  </nav>
</header>

<main>
  <section id="generate">
    <div class="eyebrow">Content Generator</div>
    <h1>Give it a subject.<br>Get a <em>first draft</em> in seconds.</h1>
    <p class="lede">Describe what you want written, pick a tone and a shape, and Inkwell drafts it for you. Nothing here leaves your machine — edit the generator logic to plug in your own model.</p>

    <form method="POST" action="#generate">
      <div class="manuscript">
        <textarea name="topic" placeholder="e.g. why small teams ship faster than large ones" required><?= htmlspecialchars($topic) ?></textarea>
      </div>

      <div class="controls">
        <div class="field">
          <label for="tone">Tone</label>
          <select name="tone" id="tone">
            <?php foreach (['friendly'=>'Friendly','formal'=>'Formal','bold'=>'Bold','playful'=>'Playful'] as $val=>$label): ?>
              <option value="<?= $val ?>" <?= $tone === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="format">Format</label>
          <select name="format" id="format">
            <?php foreach (['paragraph'=>'Paragraph','headline'=>'Headline + body','social'=>'Social post'] as $val=>$label): ?>
              <option value="<?= $val ?>" <?= $format === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="length">Length — <span class="lenval" id="lenval"><?= $length ?> words</span></label>
          <input type="range" name="length" id="length" min="80" max="900" step="20" value="<?= $length ?>"
                 oninput="document.getElementById('lenval').textContent = this.value + ' words'">
        </div>
      </div>

      <div class="submit-row">
        <span class="hint">Draft quality — always review before you publish.</span>
        <button class="seal" type="submit">
          Generate
          <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="#16130A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </form>

    <?php if ($result && !$result['ok']): ?>
      <div class="error-box"><?= htmlspecialchars($result['error']) ?></div>
    <?php elseif ($result && $result['ok']): ?>
      <div class="output">
        <div class="output-head">
          <h2>Draft</h2>
          <div class="output-meta">
            <span><?= $result['meta']['words'] ?> words</span>
            <span><?= $result['meta']['chars'] ?> chars</span>
            <span><?= $result['meta']['read_time'] ?> min read</span>
          </div>
        </div>
        <div class="generated"><?= htmlspecialchars($result['text']) ?></div>
      </div>
    <?php endif; ?>
  </section>

  <section class="ledger" id="tools">
    <div class="eyebrow">The rest of the desk</div>
    <h2>Everything else in Inkwell</h2>

    <div class="ledger-row">
      <div class="ledger-num">01</div>
      <div class="ledger-body">
        <h3>Content Detector</h3>
        <p>Estimate whether a passage reads as AI-written or human-written.</p>
      </div>
    </div>
    <div class="ledger-row">
      <div class="ledger-num">02</div>
      <div class="ledger-body">
        <h3>Plagiarism Checker</h3>
        <p>Scan a draft against public sources to confirm it's original.</p>
      </div>
    </div>
    <div class="ledger-row">
      <div class="ledger-num">03</div>
      <div class="ledger-body">
        <h3>Brand Voice Rules</h3>
        <p>Save tone and vocabulary guidelines so every draft matches your brand.</p>
      </div>
    </div>
    <div class="ledger-row">
      <div class="ledger-num">04</div>
      <div class="ledger-body">
        <h3>Prompt Scanner</h3>
        <p>Flag prompt-injection attempts before they reach the model.</p>
      </div>
    </div>
    <div class="ledger-row">
      <div class="ledger-num">05</div>
      <div class="ledger-body">
        <h3>Audit Log</h3>
        <p>Review every prompt and draft generated, in order.</p>
      </div>
    </div>
    <div class="ledger-row">
      <div class="ledger-num">06</div>
      <div class="ledger-body">
        <h3>Risk Score</h3>
        <p>A single number summarizing how safe a piece of content is to publish.</p>
      </div>
    </div>
  </section>
</main>

<footer>Inkwell — draft freely, publish deliberately.</footer>

</body>
</html>