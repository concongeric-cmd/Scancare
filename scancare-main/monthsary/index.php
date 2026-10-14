<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Monthsary Celebration</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Caveat:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    :root { color-scheme: dark; }
    *, *::before, *::after { box-sizing: border-box; }
    html, body {
      margin: 0;
      min-height: 100%;
      background: #800020;
      color: #FDFBF7;
      font-family: 'Playfair Display', serif;
      overflow-x: hidden;
    }
    body { background: #800020; }
    button, a { font: inherit; }
    #landingState, #galleryState { width: 100%; position: relative; background: #800020; }
    #landingState {
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 0 1.5rem;
    }
    #landingStars { position: absolute; inset: 0; z-index: 0; }
    .landing-inner {
      position: relative;
      z-index: 10;
      width: min(100%, 900px);
      padding: 1rem 1.5rem;
      text-align: center;
    }
    .landing-title {
      margin: 0;
      font-size: clamp(2.5rem, 5vw, 3.6rem);
      line-height: 1.02;
      color: #FDFBF7;
      font-family: 'Playfair Display', serif;
    }
    .heart-card {
      margin: 2rem auto 0;
      width: 8rem;
      height: 8rem;
      display: grid;
      place-items: center;
      transform-origin: center;
      transition: transform 800ms ease;
    }
    .heart-card.burst { transform: scale(30); }
    .heart-card svg {
      width: 8rem;
      height: 8rem;
      fill: currentColor;
      color: #DC3545;
      filter: drop-shadow(0 0 15px rgba(220,38,38,0.7));
      animation: pulse-heart 2.4s ease-in-out infinite;
    }
    @keyframes pulse-heart { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.06); } }
    .question-card {
      margin: 2rem auto 0;
      padding: 1.5rem 1.5rem;
      background: rgba(253,251,247,0.08);
      border: 1px solid rgba(253,251,247,0.18);
      border-radius: 2rem;
      max-width: 28rem;
    }
    .question-card p {
      margin: 0;
      font-size: 1.35rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: #FDFBF7;
    }
    .selection-row {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }
    .choice-heart {
      position: relative;
      width: 5rem;
      height: 5rem;
      border: none;
      border-radius: 999px;
      background: rgba(253,251,247,0.1);
      color: #FDFBF7;
      cursor: pointer;
      display: grid;
      place-items: center;
      transition: transform 0.2s ease, background 0.2s ease;
    }
    .choice-heart:hover { transform: translateY(-4px); background: rgba(253,251,247,0.18); }
    .choice-heart svg { width: 100%; height: 100%; }
    .choice-label {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 1.8rem;
      font-weight: 800;
      pointer-events: none;
    }
    #galleryState {
      display: none;
      min-height: 100vh;
      width: 100%;
      overflow-y: auto;
      background: #800020;
      padding-bottom: 5rem;
    }
    #galleryCanvas {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      background: #800020;
    }
    #galleryStateInner {
      position: relative;
      z-index: 1;
      max-width: 1200px;
      margin: 0 auto;
      padding: 5rem 1.25rem 4rem;
    }
    .back-button {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 20;
      background: #FDFBF7;
      color: #800020;
      border: none;
      border-radius: 9999px;
      padding: 0.95rem 1.2rem;
      font-family: 'Playfair Display', serif;
      font-size: 0.95rem;
      font-weight: 700;
      box-shadow: 0 18px 45px rgba(0,0,0,0.3);
      cursor: pointer;
    }
    .vinyl-wrapper {
      margin: 0 auto 3.5rem;
      width: min(100%, 44rem);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.25rem;
      position: relative;
      z-index: 1;
    }
    .vinyl-top {
      position: relative;
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 1rem;
      align-items: flex-end;
    }
    .vinyl-disc {
      width: 12rem;
      min-height: 14rem;
      border: 1px solid rgba(253,251,247,0.14);
      border-radius: 2rem;
      background: rgba(253,251,247,0.06);
      padding: 1rem;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.9rem;
      transition: transform 220ms ease, border-color 220ms ease, background 220ms ease;
    }
    .vinyl-disc:hover { transform: translateY(-4px); background: rgba(253,251,247,0.14); }
    .vinyl-disc.selected {
      border-color: rgba(253,251,247,0.35);
      background: rgba(253,251,247,0.12);
    }
    .record {
      width: 12.5rem;
      height: 12.5rem;
      border-radius: 9999px;
      background: radial-gradient(circle, #111111 0%, #050505 58%, #000 100%);
      border: 4px solid #111111;
      box-shadow: inset 0 0 0 3px rgba(255,255,255,0.04), 0 24px 70px rgba(0,0,0,0.5);
      animation: spin 4s linear infinite;
      animation-play-state: paused;
      position: relative;
    }
    .record::before {
      content: '';
      position: absolute;
      width: 4.8rem;
      height: 4.8rem;
      border-radius: 9999px;
      background: radial-gradient(circle, #FDFBF7 0%, #F1E5DA 48%, #C11B3A 100%);
      z-index: 1;
      top: calc(50% - 2.4rem);
      left: calc(50% - 2.4rem);
    }
    .record.playing { animation-play-state: running; }
    .vinyl-title {
      margin: 0;
      font-size: 0.92rem;
      text-align: center;
      letter-spacing: 0.08em;
      color: #FDFBF7;
      line-height: 1.3;
      text-transform: uppercase;
    }
    .vinyl-controls {
      width: 100%;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      padding: 0.9rem 1rem;
      border-radius: 1.5rem;
      background: rgba(253,251,247,0.06);
      border: 1px solid rgba(253,251,247,0.15);
    }
    .play-pause-button {
      border: none;
      border-radius: 999px;
      background: #FDFBF7;
      color: #800020;
      font-family: 'Playfair Display', serif;
      font-size: 0.95rem;
      font-weight: 700;
      padding: 0.9rem 1.6rem;
      cursor: pointer;
      box-shadow: 0 18px 45px rgba(0,0,0,0.28);
      transition: transform 0.2s ease;
    }
    .play-pause-button:hover { transform: translateY(-2px); }
    .track-info {
      color: #FDFBF7;
      font-family: 'Playfair Display', serif;
      font-size: 0.95rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }
    .needle {
      position: absolute;
      width: 11rem;
      height: 0.95rem;
      background: linear-gradient(90deg, #c8ab99, #4f2626);
      border-radius: 0.5rem;
      top: -1.4rem;
      left: 11.5%;
      transform-origin: left center;
      transform: rotate(-42deg);
      transition: transform 700ms ease, left 700ms ease;
      box-shadow: 0 8px 24px rgba(0,0,0,0.35);
      z-index: 2;
      pointer-events: none;
    }
    .needle.right { left: calc(50% + 3.5rem); transform: rotate(-18deg); }
    .track-label {
      color: #FDFBF7;
      font-family: 'Playfair Display', serif;
      font-size: 1rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      opacity: 0.92;
    }
    .visualizer {
      display: flex;
      align-items: flex-end;
      gap: 0.55rem;
      margin-top: 0.85rem;
    }
    .visualizer span {
      width: 6px;
      border-radius: 999px;
      background: #FDFBF7;
      opacity: 0.85;
      animation: pulse 1.1s ease-in-out infinite;
      animation-play-state: paused;
    }
    .visualizer.active span { animation-play-state: running; }
    .visualizer span:nth-child(1) { animation-delay: 0ms; height: 10px; }
    .visualizer span:nth-child(2) { animation-delay: 120ms; height: 14px; }
    .visualizer span:nth-child(3) { animation-delay: 240ms; height: 12px; }
    .visualizer span:nth-child(4) { animation-delay: 360ms; height: 16px; }
    .visualizer span:nth-child(5) { animation-delay: 480ms; height: 11px; }
    @keyframes pulse { 0%, 100% { transform: scaleY(0.5); } 50% { transform: scaleY(1); } }
    @keyframes spin { to { transform: rotate(360deg); } }
    .scrapbook-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1.1rem;
      width: min(100%, 1100px);
      margin: 0 auto 3rem;
      padding: 0 0.25rem;
      position: relative;
    }
    .photo-card {
      position: relative;
      width: 100%;
      background: #FDFBF7;
      border-radius: 1.75rem;
      overflow: hidden;
      box-shadow: 0 26px 80px rgba(0,0,0,0.24);
      border: 1px solid rgba(0,0,0,0.08);
      transition: transform 220ms ease, box-shadow 220ms ease;
    }
    .photo-card:hover {
      transform: translateY(-6px) rotate(0deg);
      box-shadow: 0 38px 105px rgba(0,0,0,0.34);
      z-index: 5;
    }
    .photo-card img {
      width: 100%;
      display: block;
      aspect-ratio: 4 / 5;
      object-fit: cover;
    }
    .photo-button { width: 100%; border: none; background: transparent; padding: 0; cursor: pointer; }
    .postcard-tilt-0 { transform: rotate(1deg); }
    .postcard-tilt-1 { transform: rotate(-1.5deg); }
    .postcard-tilt-2 { transform: rotate(2deg); }
    .postcard-tilt-3 { transform: rotate(-2deg); }
    .postcard-tilt-4 { transform: rotate(1.5deg); }
    .postcard-tilt-5 { transform: rotate(-1deg); }
    .scrapbook-grid article:nth-child(1)  { transform: translate(0px, 0px) rotate(1deg); }
    .scrapbook-grid article:nth-child(2)  { transform: translate(0px, 12px) rotate(-2deg); }
    .scrapbook-grid article:nth-child(3)  { transform: translate(0px, 0px) rotate(2deg); }
    .scrapbook-grid article:nth-child(4)  { transform: translate(0px, 10px) rotate(-1deg); }
    .scrapbook-grid article:nth-child(5)  { transform: translate(0px, 8px) rotate(1.5deg); }
    .scrapbook-grid article:nth-child(6)  { transform: translate(0px, 20px) rotate(-2.5deg); }
    .scrapbook-grid article:nth-child(7)  { transform: translate(0px, 10px) rotate(1deg); }
    .scrapbook-grid article:nth-child(8)  { transform: translate(0px, 18px) rotate(-1deg); }
    .scrapbook-grid article:nth-child(9)  { transform: translate(0px, 4px) rotate(2deg); }
    .scrapbook-grid article:nth-child(10) { transform: translate(0px, 18px) rotate(-1.5deg); }
    .scrapbook-grid article:nth-child(11) { transform: translate(0px, 8px) rotate(1deg); }
    .scrapbook-grid article:nth-child(12) { transform: translate(0px, 22px) rotate(-2deg); }
    .scrapbook-grid article:nth-child(13) { transform: translate(0px, 12px) rotate(2deg); }
    .scrapbook-grid article:nth-child(14) { transform: translate(0px, 6px) rotate(-1deg); }
    .scrapbook-grid article:nth-child(15) { transform: translate(0px, 14px) rotate(1.5deg); }
    .scrapbook-grid article:nth-child(16) { transform: translate(0px, 10px) rotate(-2deg); }
    .scrapbook-grid article:nth-child(17) { transform: translate(0px, 18px) rotate(1deg); }
    @media (max-width: 1024px) {
      .scrapbook-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .photo-card { width: 100%; }
    }
    @media (max-width: 860px) {
      .scrapbook-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      .photo-card {
        transform: none;
      }
    }
    @media (max-width: 640px) {
      .photo-card { width: 100%; }
    }
    .photo-prompt {
      padding: 1rem;
      text-align: center;
      font-family: 'Caveat', cursive;
      font-size: 1.15rem;
      color: #800020;
      letter-spacing: 0.01em;
    }
    .postcard-tilt-0 { transform: rotate(1deg); }
    .postcard-tilt-1 { transform: rotate(-1.5deg); }
    .postcard-tilt-2 { transform: rotate(2deg); }
    .postcard-tilt-3 { transform: rotate(-2deg); }
    .postcard-tilt-4 { transform: rotate(1.5deg); }
    .postcard-tilt-5 { transform: rotate(-1deg); }
    .photo-modal {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0.78);
      backdrop-filter: blur(8px);
      padding: 1.5rem;
    }
    .photo-modal.open { display: flex; }
    .flip-card { width: 100%; max-width: 840px; perspective: 1400px; }
    .flip-inner {
      width: 100%;
      min-height: 420px;
      position: relative;
      transform-style: preserve-3d;
      transition: transform 700ms ease;
    }
    .flip-inner.flipped { transform: rotateY(180deg); }
    .flip-face {
      position: absolute;
      inset: 0;
      border-radius: 2rem;
      backface-visibility: hidden;
      box-shadow: 0 35px 90px rgba(0,0,0,0.32);
    }
    .flip-front {
      background: #FDFBF7;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      overflow: hidden;
    }
    .flip-front img { width: 100%; height: 100%; object-fit: contain; border-radius: 1.5rem; }
    .flip-back {
      background: linear-gradient(180deg, #fff8ee, #f7e1d9);
      transform: rotateY(180deg);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .flip-back p {
      margin: 0;
      font-family: 'Caveat', cursive;
      font-size: clamp(2rem, 3vw, 3.2rem);
      line-height: 1.05;
      color: #5a1f2f;
      text-align: center;
      padding: 0 1rem;
    }
    .flip-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 3rem;
      height: 3rem;
      border: none;
      border-radius: 9999px;
      background: #FDFBF7;
      color: #800020;
      font-size: 1.4rem;
      cursor: pointer;
      box-shadow: 0 14px 34px rgba(0,0,0,0.24);
      z-index: 10;
    }
    .videobox {
      width: 100%;
      max-width: 42rem;
      margin: 3rem auto 0;
      padding: 0 1rem;
      clear: both;
    }
    .videobox video {
      width: 100%;
      border-radius: 1.25rem;
      border: 4px solid #FDFBF7;
      box-shadow: 0 32px 100px rgba(0,0,0,0.28);
      background: #000;
    }
    .letter-scroll {
      width: 100%;
      max-width: 900px;
      margin: 4rem auto 0;
      border-radius: 2rem;
      background: #2e0014;
      border: 4px solid rgba(253,251,247,0.18);
      box-shadow: 0 35px 90px rgba(0,0,0,0.28);
      overflow: hidden;
      position: relative;
      max-height: 8rem;
      transition: max-height 1200ms ease;
      cursor: pointer;
    }
    .letter-scroll.open { max-height: 1200px; }
    .letter-deco {
      position: absolute;
      inset: 1.5rem 50%;
      width: 4px;
      background: rgba(253,251,247,0.25);
      transform: translateX(-50%);
      z-index: 10;
    }
    .letter-deco svg { width: 100%; height: 100%; display: block; }
    .letter-badge {
      position: absolute;
      top: 1.2rem;
      right: 1.5rem;
      z-index: 15;
      background: rgba(253,251,247,0.95);
      color: #800020;
      font-family: 'Caveat', cursive;
      font-size: 1rem;
      padding: 0.65rem 1rem;
      border-radius: 9999px;
      box-shadow: 0 18px 40px rgba(0,0,0,0.14);
      animation: blink 2.4s ease-in-out infinite;
    }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .letter-dove {
      position: absolute;
      top: 1.2rem;
      left: 1.5rem;
      width: 3rem;
      height: 3rem;
      fill: rgba(253,251,247,0.85);
    }
    .letter-dove.right { left: auto; right: 1.5rem; transform: scaleX(-1); }
    .letter-content {
      position: relative;
      padding: 4.5rem 2rem 2.5rem;
      opacity: 0;
      transform: translateY(-1rem);
      transition: opacity 0.8s ease 0.25s, transform 0.8s ease 0.25s;
      color: #FDFBF7;
      font-family: 'Caveat', cursive;
      font-size: 1.05rem;
      line-height: 1.9;
      white-space: pre-wrap;
      max-width: 780px;
      margin: 0 auto;
      text-align: left;
    }
    .letter-scroll.open .letter-content { opacity: 1; transform: translateY(0); }
  </style>
</head>
<body>
<section id="landingState" class="relative flex flex-col justify-center items-center h-screen w-screen overflow-hidden">
  <canvas id="landingStars" class="absolute inset-0 w-full h-full"></canvas>
  <div class="landing-inner">
    <h1 class="landing-title">Happy Monthsary, My Love! | June 2, 2026</h1>
    <div id="centralHeart" class="heart-card">
      <svg viewBox="0 0 32 29.6" class="w-32 h-32 fill-current text-red-600 drop-shadow-[0_0_15px_rgba(220,38,38,0.7)] animate-pulse" xmlns="http://www.w3.org/2000/svg">
        <path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/>
      </svg>
    </div>
    <div class="question-card"><p>SEE THE GIFT FOR YOU?</p></div>
    <div class="selection-row" role="group" aria-label="Select gift reveal option">
      <button id="noButton" class="choice-heart" aria-label="No, go to answer X">
        <svg viewBox="0 0 32 29.6" class="w-full h-full fill-current text-red-600" xmlns="http://www.w3.org/2000/svg">
          <path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/>
        </svg>
        <span class="choice-label">×</span>
      </button>
      <button id="yesButton" class="choice-heart" aria-label="Yes, reveal the gift">
        <svg viewBox="0 0 32 29.6" class="w-full h-full fill-current text-red-600" xmlns="http://www.w3.org/2000/svg">
          <path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/>
        </svg>
        <span class="choice-label">✓</span>
      </button>
    </div>
  </div>
</section>

<section id="galleryState">
  <canvas id="galleryCanvas"></canvas>
  <button id="backButton" class="back-button" type="button">Back</button>
  <div id="galleryStateInner">
    <div class="vinyl-wrapper">
      <div class="vinyl-player">
        <div id="needle" class="needle"></div>
        <div class="vinyl-top">
          <button class="vinyl-disc selected" type="button" data-video-id="sdF6Dn5ahbM" data-title="FOREVERMORE - JURIS" data-side="left">
            <div class="record"></div>
            <p class="vinyl-title">Forevermore</p>
            <p class="track-info">Juris</p>
          </button>
          <button class="vinyl-disc" type="button" data-video-id="gxHHwoaZvyQ" data-title="ON THE WINGS OF LOVE - Kyla" data-side="right">
            <div class="record"></div>
            <p class="vinyl-title">OTWOL</p>
            <p class="track-info">Kyla</p>
          </button>
        </div>
      </div>
      <div class="vinyl-controls">
        <button id="playPauseButton" class="play-pause-button" type="button">Play</button>
        <div class="track-label" id="currentTrackLabel">Selected: FOREVERMORE - JURIS</div>
      </div>
      <div id="visualizer" class="visualizer">
        <span></span><span></span><span></span><span></span><span></span>
      </div>
    </div>

    <div class="scrapbook-grid">
      <?php
      $captions = [
        "Starting our collection with my absolute favorite view in the world. You and me against the night lights. 💜",
        "Food tastes a million times better when I get to look across the table and see your beautiful face smiling back at me. 🍕",
        "Look at us being an absolute power couple! Teamwork makes the dream work, and you're my favorite teammate forever. 💪✨",
        "Getting lost in nature with you is my favorite adventure. I'd walk any path in life as long as your hand is holding mine. 🌿",
        "Those spontaneous late-night drives where we talk about everything and nothing at all. Safe and warm right next to you. 🚗🌙",
        "Proof that you're stuck with my goofy side forever! Thank you for always matching my energy and laughing at my dumb jokes. 🤪",
        "The way you look at me in moments like this makes my heart completely melt. I am so incredibly lucky. 🕯️❤️",
        "Just a reminder that you are absolutely breathtaking. I still get butterflies every single time you walk into the room. ✨",
        "A beautiful day in the middle of nowhere, but my eyes were completely locked on you the entire time. 🌾",
        "Chasing sunsets and building a beautiful life together, one precious memory at a time. 🌅",
        "Sun-kissed and absolutely perfect. You radiate so much warmth, love, and happiness into my life. ☀️",
        "Flowers for the prettiest flower in the garden. You deserve all the beauty and sweetness this world has to offer. 💐",
        "Caught you being completely adorable when you thought I wasn't looking! I love every little detail about you. 📸",
        "From sweet little kids to the crazy couple we are today. Our paths were always meant to cross. 💖",
        "Those gorgeous eyes and that perfect smile. You look absolutely adorable here, and I can't look away. 🤓✨",
        "A quiet, gentle moment, but your beauty speaks volumes. You are my peace in a chaotic world. 🕊️",
        "Ending the scrapbook with this absolute masterpiece of a face! I love you and all your adorable expressions. 🥰"
      ];
      $rotations = ['postcard-tilt-0', 'postcard-tilt-1', 'postcard-tilt-2', 'postcard-tilt-3', 'postcard-tilt-4', 'postcard-tilt-5'];
      for ($i = 1; $i <= 17; $i++):
        $photoPath = '../DINDIN MONTHSARY/pic' . $i . '.jpg';
        $rotation = $rotations[($i - 1) % count($rotations)];
        $caption = htmlspecialchars($captions[$i - 1], ENT_QUOTES);
      ?>
        <article class="photo-card <?php echo $rotation; ?>">
          <button type="button" class="photo-button" data-photo="<?php echo $photoPath; ?>" data-caption="<?php echo $caption; ?>">
            <img src="<?php echo $photoPath; ?>" alt="Monthsary photo <?php echo $i; ?>" loading="lazy">
          </button>
          <div class="photo-prompt">Click Me ✨</div>
        </article>
      <?php endfor; ?>
    </div>

    <div class="w-full max-w-2xl mx-auto my-12 px-4 block clear-both">
      <video id="scrapbookVideo" controls playsinline preload="metadata" class="w-full rounded-2xl border-4 border-[#FDFBF7] shadow-2xl bg-black" src="../DINDIN VIDEO/vid1.mp4">
        <source src="../DINDIN VIDEO/vid1.mp4" type="video/mp4">
        <source src="../DINDIN VIDEO/vid1.MP4" type="video/mp4">
        <source src="../DINDIN VIDEO/vid1.mov" type="video/quicktime">

        <source src="../DINDIN%20VIDEO/vid1.mp4" type="video/mp4">
        <source src="../DINDIN%20VIDEO/vid1.MP4" type="video/mp4">
        <source src="../DINDIN%20VIDEO/vid1.mov" type="video/quicktime">

        <source src="/itelec4/DINDIN VIDEO/vid1.mp4" type="video/mp4">
        <source src="/itelec4/DINDIN VIDEO/vid1.MP4" type="video/mp4">
        <source src="/itelec4/DINDIN VIDEO/vid1.mov" type="video/quicktime">

        <source src="/itelec4/DINDIN%20VIDEO/vid1.mp4" type="video/mp4">
        <source src="/itelec4/DINDIN%20VIDEO/vid1.MP4" type="video/mp4">
        <source src="/itelec4/DINDIN%20VIDEO/vid1.mov" type="video/quicktime">

        <p class="text-center text-[#FDFBF7] font-serif p-4">
            Video cannot load. Please make sure the video file exists inside the 'DINDIN VIDEO' folder as 'vid1.mp4' or 'vid1.mov'.
        </p>
      </video>
    </div>

    <script>
      const vPlayer = document.getElementById('scrapbookVideo');
      if (vPlayer) {
        vPlayer.addEventListener('error', function (e) {
          console.log('Local Server Video Loading Error Detected. Code:', vPlayer.error ? vPlayer.error.code : 'Unknown');
        }, true);
      }
    </script>

    <div id="letterScroll" class="letter-scroll" role="button" aria-expanded="false" tabindex="0">
      <div class="letter-deco" aria-hidden="true">
        <svg viewBox="0 0 20 280" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M10 0 l4 20 l-8 20 l8 20 l-8 20 l8 20 l-8 20 l8 20 l-8 20 l8 20 l-8 20 l8 20 l-8 20 l4 20" fill="none" stroke="rgba(253,251,247,0.75)" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <svg class="letter-dove" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M7 38c13-9 21-14 30-12 4 1 9 5 15 8 3-3 7-7 9-11-5 2-12 2-19-1-12-5-17-15-23-12-2 1-4 7-2 16 1 6 1 9 0 12z"/>
        <path d="M46 26c5 4 10 9 14 15 1-6 0-14-3-20-4 2-8 3-11 5z"/>
      </svg>
      <svg class="letter-dove right" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M7 38c13-9 21-14 30-12 4 1 9 5 15 8 3-3 7-7 9-11-5 2-12 2-19-1-12-5-17-15-23-12-2 1-4 7-2 16 1 6 1 9 0 12z"/>
        <path d="M46 26c5 4 10 9 14 15 1-6 0-14-3-20-4 2-8 3-11 5z"/>
      </svg>
      <div class="letter-badge">Unlace my heart... ✨</div>
      <div class="letter-content" id="letterContent">
Hi LULUVS ,
I know things aren’t always perfect between us. We have our moments, we disagree, and honestly, we fight a lot. But even when things are heavy and we’re driving each other crazy, there is still nowhere else I’d rather be.
I don't expect us to be perfect, but I do promise to keep showing up. I hope God gives both of us the strength and the courage to always choose each other, to patient with one another, and to always fight for us, rather than against each other.
You are my person, even in the messy chapters. I love you, and I’m not going anywhere.
Forever yours,
      </div>
    </div>
  </div>
</section>

<div id="photoModal" class="photo-modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="flip-card">
    <div class="flip-inner" id="flipInner">
      <div class="flip-face flip-front">
        <button type="button" class="flip-close" id="closeModal" aria-label="Close modal">×</button>
        <img id="modalImage" src="" alt="Expanded monthsary photo">
      </div>
      <div class="flip-face flip-back">
        <p id="modalText"></p>
      </div>
    </div>
  </div>
</div>

<div id="youtubePlayer" class="hidden"></div>
<script src="https://www.youtube.com/iframe_api"></script>
<script>
const noButton = document.getElementById('noButton');
const yesButton = document.getElementById('yesButton');
const landingState = document.getElementById('landingState');
const galleryState = document.getElementById('galleryState');
const centralHeart = document.getElementById('centralHeart');
const backButton = document.getElementById('backButton');
const galleryCanvas = document.getElementById('galleryCanvas');
const landingStars = document.getElementById('landingStars');
const needle = document.getElementById('needle');
const visualizer = document.getElementById('visualizer');
const letterScroll = document.getElementById('letterScroll');
const vinylDiscs = document.querySelectorAll('.vinyl-disc');
const playPauseButton = document.getElementById('playPauseButton');
const currentTrackLabel = document.getElementById('currentTrackLabel');
const photoButtons = document.querySelectorAll('.photo-button');
const photoModal = document.getElementById('photoModal');
const flipInner = document.getElementById('flipInner');
const closeModal = document.getElementById('closeModal');
const modalImage = document.getElementById('modalImage');
const modalText = document.getElementById('modalText');
let ytPlayer = null;
let pendingPlay = false;
let galleryCanvasInitialized = false;
let playerReady = false;
let isPlaying = false;
let selectedTrack = {
  id: 'sdF6Dn5ahbM',
  title: 'FOREVERMORE - JURIS',
  side: 'left'
};

function initCanvas(canvas, particleCount) {
  const ctx = canvas.getContext('2d');
  let width = window.innerWidth;
  let height = window.innerHeight;
  canvas.width = width;
  canvas.height = height;
  const items = Array.from({ length: particleCount }, () => createGalleryParticle(width, height));
  window.addEventListener('resize', () => {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  });
  function animate() {
    ctx.clearRect(0, 0, width, height);
    items.forEach(item => {
      item.y += item.speed;
      item.x += item.drift;
      item.rotation += item.rotationSpeed;
      if (item.y > height + 40) { resetGalleryParticle(item, width); }
      ctx.globalAlpha = item.alpha;
      ctx.save();
      ctx.translate(item.x, item.y);
      ctx.rotate((item.rotation * Math.PI) / 180);
      if (item.type === 'heart') { drawHeart(ctx, item.size); }
      else if (item.type === 'flower') { drawFlower(ctx, item.size); }
      else { drawPhotoOutline(ctx, item.size); }
      ctx.restore();
    });
    requestAnimationFrame(animate);
  }
  animate();
}

function createGalleryParticle(width, height) {
  const type = Math.random() < 0.4 ? 'heart' : Math.random() < 0.65 ? 'flower' : 'photo';
  return {
    x: Math.random() * width,
    y: Math.random() * height - height,
    size: Math.random() * 24 + 14,
    speed: Math.random() * 0.7 + 0.5,
    drift: (Math.random() - 0.5) * 0.3,
    alpha: Math.random() * 0.3 + 0.12,
    rotation: Math.random() * 360,
    rotationSpeed: (Math.random() - 0.5) * 0.5,
    type
  };
}

function resetGalleryParticle(particle, width) {
  Object.assign(particle, createGalleryParticle(width, window.innerHeight), { y: -40 });
  particle.x = Math.random() * width;
}

function drawHeart(ctx, size) {
  const s = size / 32;
  ctx.fillStyle = 'rgba(253,251,247,0.75)';
  ctx.beginPath();
  ctx.moveTo(16 * s, 4 * s);
  ctx.bezierCurveTo(16 * s, -4 * s, 6 * s, -4 * s, 6 * s, 6 * s);
  ctx.bezierCurveTo(6 * s, 12 * s, 16 * s, 20 * s, 16 * s, 24 * s);
  ctx.bezierCurveTo(16 * s, 20 * s, 26 * s, 12 * s, 26 * s, 6 * s);
  ctx.bezierCurveTo(26 * s, -4 * s, 16 * s, -4 * s, 16 * s, 4 * s);
  ctx.closePath();
  ctx.fill();
}

function drawFlower(ctx, size) {
  ctx.fillStyle = 'rgba(253,251,247,0.72)';
  for (let i = 0; i < 5; i++) {
    ctx.save();
    ctx.rotate((Math.PI * 2 / 5) * i);
    ctx.beginPath();
    ctx.ellipse(0, -size * 0.24, size * 0.22, size * 0.42, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  }
  ctx.beginPath();
  ctx.arc(0, 0, size * 0.15, 0, Math.PI * 2);
  ctx.fillStyle = 'rgba(220,38,57,0.85)';
  ctx.fill();
}

function drawPhotoOutline(ctx, size) {
  ctx.strokeStyle = 'rgba(253,251,247,0.55)';
  ctx.lineWidth = 2;
  ctx.strokeRect(-size * 0.55, -size * 0.45, size * 1.1, size * 0.9);
  ctx.beginPath();
  ctx.moveTo(-size * 0.45, -size * 0.15);
  ctx.lineTo(-size * 0.2, size * 0.3);
  ctx.lineTo(size * 0.45, -size * 0.25);
  ctx.stroke();
}

function initLandingStars() {
  const canvas = landingStars;
  const ctx = canvas.getContext('2d');
  let width = window.innerWidth;
  let height = window.innerHeight;
  canvas.width = width;
  canvas.height = height;
  const stars = Array.from({ length: 120 }, () => ({
    x: Math.random() * width,
    y: Math.random() * height,
    r: Math.random() * 1.4 + 0.3,
    alpha: Math.random() * 0.4 + 0.2,
    phase: Math.random() * Math.PI * 2,
    speed: Math.random() * 0.03 + 0.01
  }));
  window.addEventListener('resize', () => {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  });
  function animate() {
    ctx.clearRect(0, 0, width, height);
    stars.forEach(star => {
      star.y += star.speed;
      star.phase += 0.03;
      if (star.y > height + 10) star.y = -10;
      ctx.globalAlpha = star.alpha * (0.5 + 0.5 * Math.sin(star.phase));
      ctx.fillStyle = '#FDFBF7';
      ctx.beginPath();
      ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
      ctx.fill();
    });
    requestAnimationFrame(animate);
  }
  animate();
}

function onYouTubeIframeAPIReady() {
  ytPlayer = new YT.Player('youtubePlayer', {
    height: '0',
    width: '0',
    videoId: selectedTrack.id,
    playerVars: {
      autoplay: 0,
      controls: 0,
      rel: 0,
      modestbranding: 1,
      loop: 1,
      playlist: selectedTrack.id,
      iv_load_policy: 3,
      playsinline: 1
    },
    events: {
      onReady: event => {
        playerReady = true;
        event.target.cueVideoById(selectedTrack.id);
        if (pendingPlay) {
          event.target.playVideo();
          pendingPlay = false;
        }
      },
      onStateChange: event => {
        if (event.data === YT.PlayerState.ENDED && isPlaying) {
          event.target.playVideo();
        }
      }
    }
  });
}

function updateSelectedTrackUI() {
  currentTrackLabel.textContent = `Selected: ${selectedTrack.title}`;
  vinylDiscs.forEach(disc => {
    const record = disc.querySelector('.record');
    const isActive = disc.dataset.videoId === selectedTrack.id;
    disc.classList.toggle('selected', isActive);
    record.classList.toggle('playing', isPlaying && isActive);
  });
  needle.classList.toggle('right', selectedTrack.side === 'right');
  needle.classList.toggle('left', selectedTrack.side === 'left');
  visualizer.classList.toggle('active', isPlaying);
  playPauseButton.textContent = isPlaying ? 'Pause' : 'Play';
}

function selectTrack(trackId, trackTitle, side) {
  selectedTrack = { id: trackId, title: trackTitle, side };
  if (playerReady && ytPlayer) {
    if (isPlaying) {
      ytPlayer.loadVideoById(selectedTrack.id);
    } else {
      ytPlayer.cueVideoById(selectedTrack.id);
    }
  }
  updateSelectedTrackUI();
}

function setPlayingState(play) {
  isPlaying = play;
  if (playerReady && ytPlayer) {
    if (play) {
      const currentId = ytPlayer.getVideoData().video_id;
      if (currentId !== selectedTrack.id) {
        ytPlayer.loadVideoById(selectedTrack.id);
      }
      ytPlayer.playVideo();
    } else {
      ytPlayer.pauseVideo();
    }
  } else if (play) {
    pendingPlay = true;
  }
  updateSelectedTrackUI();
}

function revealGallery() {
  centralHeart.classList.add('burst');
  setTimeout(() => {
    landingState.style.display = 'none';
    galleryState.style.display = 'block';
    if (!galleryCanvasInitialized) {
      initCanvas(galleryCanvas, 18);
      galleryCanvasInitialized = true;
    }
    updateSelectedTrackUI();
  }, 100);
}

function returnToLanding() {
  galleryState.style.display = 'none';
  landingState.style.display = 'flex';
  centralHeart.classList.remove('burst');
  if (ytPlayer && typeof ytPlayer.pauseVideo === 'function') { ytPlayer.pauseVideo(); }
  isPlaying = false;
  updateSelectedTrackUI();
}

function openPhotoModal(src, caption) {
  modalImage.src = src;
  modalText.textContent = caption;
  photoModal.classList.add('open');
  flipInner.classList.remove('flipped');
  requestAnimationFrame(() => flipInner.classList.add('flipped'));
}

function closePhotoModal() {
  flipInner.classList.remove('flipped');
  setTimeout(() => photoModal.classList.remove('open'), 520);
}

noButton.addEventListener('click', () => { window.location.href = 'answer_X.php'; });
yesButton.addEventListener('click', revealGallery);
backButton.addEventListener('click', returnToLanding);
letterScroll.addEventListener('click', () => {
  const expanded = letterScroll.classList.toggle('open');
  letterScroll.setAttribute('aria-expanded', expanded ? 'true' : 'false');
});
vinylDiscs.forEach(disc => {
  disc.addEventListener('click', () => {
    const id = disc.dataset.videoId;
    const title = disc.dataset.title;
    const side = disc.dataset.side || 'left';
    selectTrack(id, title, side);
  });
});
playPauseButton.addEventListener('click', () => setPlayingState(!isPlaying));
photoButtons.forEach(button => { button.addEventListener('click', () => openPhotoModal(button.dataset.photo, button.dataset.caption)); });
closeModal.addEventListener('click', closePhotoModal);
photoModal.addEventListener('click', event => { if (event.target === photoModal) closePhotoModal(); });
window.addEventListener('DOMContentLoaded', () => {
  initLandingStars();
  updateSelectedTrackUI();
});
</script>
</body>
</html>
