<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Answer X</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Caveat:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    * { box-sizing: border-box; }
    html, body {
      margin: 0;
      min-height: 100%;
      background: #800020;
      color: #FDFBF7;
      font-family: 'Playfair Display', serif;
      overflow: hidden;
    }
    body { background: #800020; }
    .page-shell {
      position: relative;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      display: grid;
      place-items: center;
      padding: 1.5rem;
      background: #800020;
    }
    #starsCanvas {
      position: absolute;
      inset: 0;
      z-index: 0;
      background: #800020;
    }
    .card {
      position: relative;
      z-index: 1;
      width: min(100%, 760px);
      padding: 2.25rem 2.5rem;
      border-radius: 2rem;
      background: rgba(253,251,247,0.08);
      border: 1px solid rgba(253,251,247,0.18);
      box-shadow: 0 35px 100px rgba(0,0,0,0.24);
      text-align: center;
    }
    .emoji {
      font-size: 4.5rem;
      margin-bottom: 1rem;
    }
    .card h1 {
      margin: 0;
      font-size: clamp(2rem, 5vw, 3rem);
      line-height: 1.05;
      letter-spacing: 0.02em;
      color: #FDFBF7;
    }
    .back-button {
      margin-top: 2rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.95rem 1.8rem;
      border-radius: 9999px;
      background: #FDFBF7;
      color: #800020;
      border: 1px solid #FDFBF7;
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 1rem;
      text-decoration: none;
      box-shadow: 0 22px 54px rgba(0,0,0,0.22);
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <canvas id="starsCanvas" aria-hidden="true"></canvas>
    <div class="card">
      <div class="emoji">😢</div>
      <h1>OH NO! Trust me you'll like it!</h1>
      <a href="index.php" class="back-button">Back</a>
    </div>
  </div>
  <script>
    const canvas = document.getElementById('starsCanvas');
    const ctx = canvas.getContext('2d');
    const stars = Array.from({ length: 90 }, () => ({
      x: Math.random() * window.innerWidth,
      y: Math.random() * window.innerHeight,
      r: Math.random() * 1.4 + 0.3,
      alpha: Math.random() * 0.45 + 0.2,
      speed: Math.random() * 0.05 + 0.02,
      phase: Math.random() * Math.PI * 2
    }));
    function resize() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);
    function loop() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      stars.forEach(star => {
        star.y += star.speed;
        star.phase += 0.03;
        if (star.y > canvas.height + 10) star.y = -10;
        ctx.globalAlpha = star.alpha * (0.5 + 0.5 * Math.sin(star.phase));
        ctx.fillStyle = '#FDFBF7';
        ctx.beginPath();
        ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
        ctx.fill();
      });
      requestAnimationFrame(loop);
    }
    loop();
  </script>
</body>
</html>
