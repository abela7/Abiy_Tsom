{{-- Resurrection intro — one long scrollable page: Bible verses → tomb scene → animation --}}
<div id="ri-overlay" style="position:fixed;inset:0;z-index:9999;overflow-y:auto;overflow-x:hidden;background:#03030a;">

  {{-- ════════════════════════════════════════
       SECTION 1 — BIBLE VERSES
       Same dark night sky as the tomb scene
       ════════════════════════════════════════ --}}
  <div id="ri-galaxy" style="
    position:relative;
    min-height:100vh;
    width:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    overflow:hidden;
    background:linear-gradient(180deg,#03030a 0%,#0a0a18 40%,#12101e 70%,#1a1220 100%);
  ">
    {{-- Stars (same as tomb, built by JS below) --}}
    <div id="ri-bible-stars" style="position:absolute;inset:0;z-index:0;pointer-events:none;"></div>

    {{-- Bible content --}}
    <div style="
      position:relative;z-index:2;
      width:100%;max-width:560px;
      padding: max(3rem, env(safe-area-inset-top, 0px)) max(1.5rem, env(safe-area-inset-right, 0px)) 4rem max(1.5rem, env(safe-area-inset-left, 0px));
      box-sizing:border-box;
    ">

      {{-- Reference pill --}}
      <div id="ri-ref" style="
        text-align:center;
        margin-bottom:2.5rem;
        opacity:0;
        animation: riFadeUp 1.2s ease 0.4s forwards;
      ">
        <span style="
          display:inline-block;
          font-family:'Noto Serif Ethiopic',serif;font-weight:600;
          font-size:clamp(0.72rem,2vw,0.88rem);
          color:rgba(184,150,62,0.8);
          letter-spacing:.22em;text-transform:uppercase;
          border:1px solid rgba(184,150,62,0.22);
          border-radius:999px;
          padding:0.35rem 1.2rem;
          background:rgba(184,150,62,0.06);
        ">ማቴዎስ ወንጌል 28፡1–8</span>
      </div>

      {{-- Verses --}}
      <div style="display:flex;flex-direction:column;gap:0;">

        <p class="ri-bv" style="--d:0.7s">
          <span class="ri-vn">፩</span>
          በሰንበትም መጨረሻ መጀመሪያው ቀን ሲነጋ መግደላዊት ማርያምና ሁለተኛይቱ ማርያም መቃብሩን ሊያዩ መጡ።
        </p>

        <p class="ri-bv" style="--d:1.1s">
          <span class="ri-vn">፪</span>
          እነሆም፥ የጌታ መልአክ ከሰማይ ስለ ወረደ ታላቅ የምድር መናወጥ ሆነ፤ ቀርቦም ድንጋዩን አንከባሎ በላዩ ተቀመጠ።
        </p>

        <p class="ri-bv" style="--d:1.5s">
          <span class="ri-vn">፫</span>
          መልኩም እንደ መብረቅ ልብሱም እንደ በረዶ ነጭ ነበረ።
        </p>

        <p class="ri-bv" style="--d:1.9s">
          <span class="ri-vn">፬</span>
          ጠባቆቹም እርሱን ከመፍራት የተነሣ ተናወጡ እንደ ሞቱም ሆኑ።
        </p>

        <p class="ri-bv" style="--d:2.3s">
          <span class="ri-vn">፭</span>
          መልአኩም መልሶ ሴቶቹን አላቸው፦ &ldquo;እናንተስ አትፍሩ የተሰቀለውን ኢየሱስን እንድትሹ አውቃለሁና፤
        </p>

        <p class="ri-bv ri-bv-key" style="--d:2.7s">
          <span class="ri-vn ri-vn-key">፮</span>
          እንደ ተናገረ ተነሥቶአልና በዚህ የለም፤ የተኛበትን ስፍራ ኑና እዩ።
        </p>

        <p class="ri-bv ri-bv-key" style="--d:3.1s">
          <span class="ri-vn ri-vn-key">፯</span>
          ፈጥናችሁም ሂዱና፦ &lsquo;ከሙታን ተነሣ፥ እነሆም፥ ወደ ገሊላ ይቀድማችኋል በዚያም ታዩታላችሁ&rsquo; ብላችሁ ለደቀ መዛሙርቱ ንገሯቸው። እነሆም፥ ነገርኳችሁ።&rdquo;
        </p>

        <p class="ri-bv" style="--d:3.5s">
          <span class="ri-vn">፰</span>
          እነርሱም በፍርሃትና በታላቅ ደስታ ፈጥነው መቃብሩን ትተው ሮጡ ደቀ መዛሙርቱንም ሊነግሩ ሄዱ።
        </p>

      </div>

      {{-- Scroll hint --}}
      <div id="ri-scroll-hint" style="
        text-align:center;
        margin-top:3.5rem;
        opacity:0;
        animation: riFadeUp 1.4s ease 4.2s forwards;
        display:flex;flex-direction:column;align-items:center;gap:0.6rem;
      ">
        <span style="
          font-family:'Noto Serif Ethiopic',serif;
          font-size:clamp(0.78rem,2vw,0.9rem);
          color:rgba(184,150,62,0.6);
          letter-spacing:.12em;
        ">ቀጥሉ ↓</span>
        <div class="ri-scroll-arrow">
          <span></span><span></span>
        </div>
      </div>

    </div>
  </div>

  {{-- ════════════════════════════════════════
       SECTION 2 — TOMB SCENE
       Appears below, user scrolls into it
       ════════════════════════════════════════ --}}
  <div id="ri-scene-wrap" style="
    position:relative;
    width:100%;
    min-height:100vh;
    overflow:hidden;
    background:linear-gradient(180deg,#03030a 0%,#0a0a18 40%,#12101e 70%,#1a1220 100%);
  ">
    <div class="ri-scene" id="ri-scene">
      <div class="ri-stars" id="ri-stars"></div>
      <div class="ri-particles" id="ri-particles"></div>
      <div class="ri-ground"></div>
      <div class="ri-ground-rocks"></div>
      <div class="ri-light-rays" id="ri-lightRays"></div>

      <div class="ri-tomb-container">
        <div class="ri-intro-prompt" id="ri-introPrompt">
          <div class="ri-intro-text">ሂዱ ንገሩ አውሩ ለ</div>
          <div class="ri-btn-wrap">
            <button class="ri-btn-aleme" id="ri-btnAleme" type="button">ዓለም</button>
            <div class="ri-click-hint" aria-hidden="true">
              <span class="ri-click-hint-ring"></span>
              <span class="ri-click-hint-head"></span>
              <span class="ri-click-hint-shaft"></span>
            </div>
          </div>
        </div>
        <div class="ri-tomb-mound"></div>
        <div class="ri-tomb-hole">
          <div class="ri-divine-light"></div>
          <div class="ri-jesus-figure">
            <div class="ri-figure-body">
              <div class="ri-figure-glow"></div>
              <div class="ri-halo"></div>
              <div class="ri-figure-head"></div>
              <div class="ri-figure-robe"></div>
              <div class="ri-arm ri-arm-left"></div>
              <div class="ri-arm ri-arm-right"></div>
            </div>
          </div>
        </div>
        <div class="ri-stone"></div>
      </div>

      <div class="ri-grass" id="ri-grass"></div>

      <div class="ri-flash-burst"></div>
      <div class="ri-light-wave-2"></div>
      <div class="ri-radial-white"></div>
      <div class="ri-white-takeover"></div>

      <div class="ri-title-text">
        <h1>ተነስቷል</h1>
        <p>ማቴ 28፡6</p>
        <span class="ri-flourish"></span>
      </div>
    </div>
  </div>

</div><!-- #ri-overlay -->

<style>
  @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+Ethiopic:wght@300;400;500;600;700;900&display=swap');

  @font-face {
    font-family:'Benaiah';
    src:url('{{ asset('fonts/Benaiah_536454528f.ttf') }}') format('truetype');
    font-display:swap;
  }

  /* ── verse base ── */
  .ri-bv {
    font-family:'Noto Serif Ethiopic',serif;
    font-size:clamp(1rem,2.6vw,1.18rem);
    line-height:2.05;
    color:rgba(255,252,245,.82);
    text-align:justify;
    padding:.7rem 0;
    border-bottom:1px solid rgba(184,150,62,.07);
    margin:0;
    opacity:0;
    animation: riFadeUp 1s ease var(--d, 1s) forwards;
  }
  .ri-bv:last-child { border-bottom:none; }

  /* key verses (6 & 7) */
  .ri-bv-key {
    color:rgba(255,252,245,1);
    font-weight:500;
    background:rgba(184,150,62,.04);
    border-radius:.5rem;
    padding:.75rem .65rem;
    border-bottom:none;
    margin:.25rem 0;
    border:1px solid rgba(184,150,62,.12);
  }

  /* verse numbers */
  .ri-vn {
    display:inline-block;
    font-size:.78em;font-weight:700;
    color:rgba(184,150,62,.65);
    min-width:1.7em;
    margin-inline-end:.3em;
  }
  .ri-vn-key {
    color:#e2ca18;
    text-shadow:0 0 10px rgba(226,202,24,.35);
    opacity:1;
  }

  @keyframes riFadeUp {
    0%  { opacity:0; transform:translateY(16px); }
    100%{ opacity:1; transform:translateY(0);    }
  }

  /* scroll arrow chevrons */
  .ri-scroll-arrow {
    display:flex;flex-direction:column;align-items:center;gap:2px;
    animation:riScrollBounce 1.6s ease-in-out infinite;
  }
  .ri-scroll-arrow span {
    display:block;width:16px;height:16px;
    border-right:2px solid rgba(184,150,62,.55);
    border-bottom:2px solid rgba(184,150,62,.55);
    transform:rotate(45deg);
  }
  .ri-scroll-arrow span:first-child{ opacity:.45; }
  @keyframes riScrollBounce {
    0%,100%{ transform:translateY(0);   }
    50%    { transform:translateY(6px); }
  }

  /* ══ TOMB SCENE ══ */
  .ri-scene {
    position:relative;width:100%;min-height:100vh;overflow:hidden;
  }

  .ri-stars{position:absolute;inset:0;}
  .ri-star {
    position:absolute;background:#fff;border-radius:50%;
    animation:riTwinkle 3s ease-in-out infinite alternate;
  }
  @keyframes riTwinkle{0%{opacity:.2}100%{opacity:1}}

  .ri-ground {
    position:absolute;bottom:0;width:100%;height:40%;
    background:linear-gradient(180deg,#1a1510 0%,#0d0b08 100%);
    clip-path:ellipse(85% 100% at 50% 100%);z-index:2;
  }
  .ri-ground-rocks {
    position:absolute;bottom:0;width:100%;height:38%;
    background:linear-gradient(180deg,#15120d 0%,#0a0908 100%);
    clip-path:ellipse(78% 90% at 50% 100%);z-index:2;
  }

  .ri-tomb-container {
    position:absolute;bottom:18%;left:50%;transform:translateX(-50%);
    z-index:5;width:260px;height:300px;
  }
  .ri-tomb-mound {
    position:absolute;bottom:0;left:50%;transform:translateX(-50%);
    width:300px;height:200px;
    background:radial-gradient(ellipse at 50% 80%,#2a2520,#1a1510);
    border-radius:50% 50% 10% 10%;
    box-shadow:inset -10px -5px 25px rgba(0,0,0,.5),inset 3px 3px 10px rgba(255,255,255,.02),0 15px 50px rgba(0,0,0,.8);
  }
  .ri-tomb-hole {
    position:absolute;bottom:30px;left:50%;transform:translateX(-50%);
    width:150px;height:150px;
    background:radial-gradient(circle,#000 60%,#0a0a0a 100%);
    border-radius:50%;z-index:6;
    box-shadow:inset 0 0 30px rgba(0,0,0,1),0 0 15px rgba(0,0,0,.8);
    overflow:visible;
  }
  .ri-tomb-hole::before {
    content:'';position:absolute;inset:-8px;border-radius:50%;
    background:conic-gradient(from 0deg,#3a3530,#2a2520,#3a3530,#2d2925,#3a3530);
    z-index:-1;box-shadow:inset 0 0 20px rgba(0,0,0,.6);
  }

  .ri-stone {
    position:absolute;bottom:25px;left:50%;transform:translateX(-50%);
    width:160px;height:160px;border-radius:50%;z-index:8;
    transition:transform 2.8s cubic-bezier(.22,.61,.36,1);
    background:radial-gradient(circle at 35% 35%,#555048,#3a3530 40%,#2a2520 70%,#1f1b18 100%);
    box-shadow:inset -12px -8px 25px rgba(0,0,0,.5),inset 6px 6px 15px rgba(255,255,255,.05),8px 8px 30px rgba(0,0,0,.7);
  }
  .ri-stone::after{content:'';position:absolute;inset:12px;border-radius:50%;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.06) 0%,transparent 50%),radial-gradient(circle at 65% 70%,rgba(0,0,0,.2) 0%,transparent 40%);}
  .ri-stone::before{content:'';position:absolute;top:50%;left:20%;width:60%;height:1px;background:rgba(0,0,0,.4);transform:rotate(-12deg);box-shadow:0 20px 0 rgba(0,0,0,.25),-10px 40px 0 rgba(0,0,0,.2);}
  .ri-scene.active .ri-stone{transform:translateX(-50%) translateX(180px) rotate(90deg);}

  .ri-divine-light{position:absolute;inset:-5px;border-radius:50%;z-index:5;opacity:0;background:radial-gradient(circle,#fff 0%,#fffbe6 20%,#f5d76e 40%,transparent 70%);transition:opacity 1.5s ease 1.8s;}
  .ri-scene.active .ri-divine-light{opacity:1;}

  .ri-light-rays{position:absolute;bottom:105px;left:50%;transform:translateX(-50%);width:0;height:0;z-index:4;opacity:0;transition:opacity 1s ease 2s;}
  .ri-scene.active .ri-light-rays{opacity:1;}
  .ri-ray{position:absolute;bottom:0;left:50%;width:3px;height:0;background:linear-gradient(0deg,#fff,#fffbe688,transparent);transform-origin:bottom center;border-radius:2px;filter:blur(1.5px);}
  .ri-scene.active .ri-ray{animation:riRayGrow 2.5s ease-out forwards;}
  @keyframes riRayGrow{0%{height:0;opacity:0}40%{opacity:.9}100%{height:var(--ray-h);opacity:var(--ray-o)}}

  .ri-jesus-figure{position:absolute;bottom:0;left:50%;transform:translateX(-50%) translateY(60px) scale(.8);z-index:9;opacity:0;transition:opacity 2s ease 2.8s,transform 3.5s ease 2.8s;}
  .ri-scene.active .ri-jesus-figure{opacity:1;transform:translateX(-50%) translateY(-40px) scale(1);}
  .ri-figure-body{position:relative;width:70px;height:160px;}
  .ri-figure-head{position:absolute;top:0;left:50%;transform:translateX(-50%);width:26px;height:30px;background:radial-gradient(ellipse,#f5e6d3,#d4b896);border-radius:50%;box-shadow:0 0 30px rgba(255,255,255,.8),0 0 60px rgba(255,248,220,.5);}
  .ri-halo{position:absolute;top:-14px;left:50%;transform:translateX(-50%);width:56px;height:56px;border-radius:50%;border:2px solid rgba(255,255,255,.7);box-shadow:0 0 20px rgba(255,255,255,.5),0 0 40px rgba(255,248,220,.3),inset 0 0 15px rgba(255,255,255,.15);animation:riHaloGlow 1.8s ease-in-out infinite alternate;animation-play-state:paused;}
  .ri-scene.active .ri-halo{animation-play-state:running;}
  @keyframes riHaloGlow{0%{box-shadow:0 0 20px rgba(255,255,255,.5),0 0 40px rgba(255,248,220,.3),inset 0 0 15px rgba(255,255,255,.15)}100%{box-shadow:0 0 35px rgba(255,255,255,.8),0 0 70px rgba(255,248,220,.5),inset 0 0 25px rgba(255,255,255,.25)}}
  .ri-figure-robe{position:absolute;top:28px;left:50%;transform:translateX(-50%);width:54px;height:125px;background:linear-gradient(180deg,#fff 0%,#f5f0e8 30%,#ebe5da 100%);clip-path:polygon(18% 0%,82% 0%,100% 100%,0% 100%);box-shadow:0 0 30px rgba(255,255,255,.4);}
  .ri-figure-robe::before{content:'';position:absolute;top:0;left:12%;width:76%;height:100%;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.3) 50%,transparent 100%);}
  .ri-arm{position:absolute;top:40px;width:48px;height:8px;background:linear-gradient(180deg,#fff,#ebe5da);border-radius:4px;opacity:0;transition:opacity 1.2s ease 4.2s,transform 1.8s ease 4.2s;}
  .ri-arm-left{right:52px;transform:rotate(20deg) scaleX(0);transform-origin:right center;}
  .ri-arm-right{left:52px;transform:rotate(-20deg) scaleX(0);transform-origin:left center;}
  .ri-scene.active .ri-arm{opacity:1;}
  .ri-scene.active .ri-arm-left{transform:rotate(-20deg) scaleX(1);}
  .ri-scene.active .ri-arm-right{transform:rotate(20deg) scaleX(1);}
  .ri-figure-glow{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:120px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.5),rgba(255,255,255,.1),transparent);filter:blur(15px);opacity:0;transition:opacity 2s ease 3.5s;}
  .ri-scene.active .ri-figure-glow{opacity:1;}

  .ri-particles{position:absolute;inset:0;z-index:3;pointer-events:none;}
  .ri-particle{position:absolute;background:#fff;border-radius:50%;opacity:0;}
  .ri-scene.active .ri-particle{animation:riFloatUp var(--dur) ease-out var(--delay) infinite;}
  @keyframes riFloatUp{0%{opacity:0;transform:translateY(0) scale(.5)}15%{opacity:.9}100%{opacity:0;transform:translateY(-350px) scale(0)}}

  .ri-grass{position:absolute;bottom:0;width:100%;height:30px;z-index:9;pointer-events:none;}
  .ri-tuft{position:absolute;bottom:-2px;width:3px;border-radius:2px 2px 0 0;}

  .ri-flash-burst{position:absolute;inset:0;z-index:12;opacity:0;pointer-events:none;background:radial-gradient(circle at 50% 62%,#fff,rgba(255,255,255,.6) 30%,transparent 65%);}
  .ri-scene.active .ri-flash-burst{animation:riFlashBurst 1.2s ease-out 2s forwards;}
  @keyframes riFlashBurst{0%{opacity:0}40%{opacity:.7}100%{opacity:0}}

  .ri-radial-white{position:absolute;bottom:30%;left:50%;transform:translate(-50%,50%);width:10px;height:10px;border-radius:50%;background:radial-gradient(circle,#fff,rgba(255,255,255,.8),transparent);z-index:45;opacity:0;pointer-events:none;}
  .ri-scene.active .ri-radial-white{animation:riRadialExpand 5s ease-out 3.5s forwards;}
  @keyframes riRadialExpand{0%{opacity:0;width:10px;height:10px}10%{opacity:.9}100%{opacity:1;width:300vw;height:300vh}}

  .ri-light-wave-2{position:absolute;bottom:30%;left:50%;transform:translate(-50%,50%);width:5px;height:5px;border-radius:50%;background:radial-gradient(circle,#fff,rgba(255,248,220,.9),transparent);z-index:44;opacity:0;pointer-events:none;}
  .ri-scene.active .ri-light-wave-2{animation:riRadialExpand2 6s ease-out 3s forwards;}
  @keyframes riRadialExpand2{0%{opacity:0;width:5px;height:5px}15%{opacity:.6}100%{opacity:.8;width:250vw;height:250vh}}

  .ri-white-takeover{position:absolute;inset:0;background:#fff;z-index:50;opacity:0;pointer-events:none;}
  .ri-scene.active .ri-white-takeover{animation:riWhiteTakeover 6s ease-in 4s forwards;}
  @keyframes riWhiteTakeover{0%{opacity:0}30%{opacity:.3}60%{opacity:.7}85%{opacity:.95}100%{opacity:1}}

  .ri-title-text{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:60;text-align:center;opacity:0;pointer-events:none;}
  .ri-scene.active .ri-title-text{animation:riTitleReveal 3s ease 8s forwards;}
  @keyframes riTitleReveal{0%{opacity:0;transform:translate(-50%,-50%) scale(.85)}100%{opacity:1;transform:translate(-50%,-50%) scale(1)}}
  .ri-title-text h1{font-family:'Noto Serif Ethiopic',serif;font-weight:700;font-size:clamp(2.5rem,8vw,6rem);color:#b8963e;text-shadow:0 0 30px rgba(184,150,62,.5),0 0 60px rgba(184,150,62,.25),0 0 100px rgba(184,150,62,.1);letter-spacing:.15em;}
  .ri-title-text p{font-family:'Noto Serif Ethiopic',serif;font-weight:400;font-size:clamp(1rem,3vw,1.6rem);color:rgba(150,130,90,.8);margin-top:20px;letter-spacing:.1em;}
  .ri-flourish{display:block;margin:24px auto 0;width:0;height:2px;background:linear-gradient(90deg,transparent,#b8963e,transparent);opacity:0;}
  .ri-scene.active .ri-flourish{animation:riFlourishIn 2s ease 9.5s forwards;}
  @keyframes riFlourishIn{0%{opacity:0;width:0}100%{opacity:.6;width:120px}}

  /* Intro prompt card */
  .ri-intro-prompt{position:absolute;left:50%;bottom:208px;transform:translateX(-50%);z-index:25;width:min(calc(100vw - 2rem),292px);max-width:calc(100% + 32px);padding:1rem 1.15rem 1.2rem;text-align:center;border-radius:1.25rem;background:linear-gradient(155deg,rgba(42,32,38,.42) 0%,rgba(14,10,20,.38) 45%,rgba(10,8,16,.48) 100%);border:1px solid rgba(226,202,24,.12);box-shadow:0 12px 40px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.04) inset,inset 0 1px 0 rgba(255,248,220,.08);backdrop-filter:blur(12px) saturate(1.2);-webkit-backdrop-filter:blur(12px) saturate(1.2);transition:opacity 1.2s ease,transform 1.2s ease,box-shadow 1.2s ease;}
  .ri-intro-prompt::before{content:'';position:absolute;inset:0;border-radius:inherit;pointer-events:none;background:linear-gradient(180deg,rgba(255,248,220,.07) 0%,transparent 42%);}
  .ri-scene.active .ri-intro-prompt{opacity:0;transform:translateX(-50%) translateY(16px);pointer-events:none;box-shadow:none;}
  .ri-intro-text{position:relative;z-index:1;font-family:'Benaiah','Noto Serif Ethiopic',serif;font-weight:700;font-size:clamp(1.05rem,2.6vw,1.35rem);color:rgba(255,252,245,.95);letter-spacing:.06em;margin-bottom:14px;line-height:1.65;animation:riIntroPulse 3s ease-in-out infinite alternate;}
  @keyframes riIntroPulse{0%{opacity:.88}100%{opacity:1}}
  .ri-intro-prompt .ri-btn-wrap{position:relative;z-index:1;display:inline-block;}

  /* Button */
  .ri-btn-aleme{position:relative;display:inline-block;font-family:'Noto Serif Ethiopic',serif;font-weight:700;font-size:clamp(1.2rem,3vw,1.8rem);color:#fffbe6;background:transparent;border:2px solid rgba(184,150,62,.5);padding:14px 48px;border-radius:60px;cursor:pointer;letter-spacing:.15em;overflow:hidden;transition:all .4s ease;animation:riBtnFloat 3s ease-in-out infinite;}
  @keyframes riBtnFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
  .ri-btn-aleme:hover{border-color:rgba(184,150,62,.9);color:#fff;text-shadow:0 0 15px rgba(255,248,220,.6);box-shadow:0 0 25px rgba(184,150,62,.3),0 0 50px rgba(184,150,62,.15),inset 0 0 20px rgba(184,150,62,.1);}
  .ri-btn-aleme::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent 0%,rgba(255,248,220,.15) 40%,rgba(255,248,220,.3) 50%,rgba(255,248,220,.15) 60%,transparent 100%);animation:riShimmer 2.5s ease-in-out infinite;}
  @keyframes riShimmer{0%{left:-100%}100%{left:200%}}
  .ri-btn-aleme::after{content:'';position:absolute;inset:-6px;border-radius:60px;border:1px solid rgba(184,150,62,.2);animation:riRingPulse 2s ease-in-out infinite;}
  @keyframes riRingPulse{0%,100%{opacity:.3;inset:-6px}50%{opacity:.7;inset:-12px}}

  /* Click hint */
  .ri-click-hint{position:absolute;bottom:-56px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;pointer-events:none;animation:riClickBounce 1.2s ease-in-out infinite;opacity:.85;}
  .ri-click-hint-head{position:relative;z-index:1;width:0;height:0;border-left:11px solid transparent;border-right:11px solid transparent;border-bottom:15px solid #d4b45a;filter:drop-shadow(0 0 6px rgba(226,202,24,.55)) drop-shadow(0 0 14px rgba(184,150,62,.45));}
  .ri-click-hint-shaft{position:relative;z-index:1;width:3px;height:20px;margin-top:-1px;border-radius:2px;background:linear-gradient(180deg,#fffbe6 0%,#c9a04a 45%,#8a7030 100%);box-shadow:0 0 10px rgba(226,202,24,.35),0 0 18px rgba(184,150,62,.25),inset 0 0 2px rgba(255,255,255,.25);}
  .ri-click-hint-ring{position:absolute;left:50%;top:42%;transform:translate(-50%,-50%);width:40px;height:58px;border-radius:999px;border:1px solid rgba(184,150,62,.22);box-shadow:0 0 20px rgba(184,150,62,.12),inset 0 0 12px rgba(184,150,62,.06);animation:riHintRingPulse 2s ease-in-out infinite;pointer-events:none;}
  @keyframes riHintRingPulse{0%,100%{opacity:.35;transform:translate(-50%,-50%) scale(1)}50%{opacity:.65;transform:translate(-50%,-50%) scale(1.06)}}
  @keyframes riClickBounce{0%,100%{transform:translateX(-50%) translateY(0);opacity:.55}50%{transform:translateX(-50%) translateY(-8px);opacity:1}}

  .ri-btn-sparkle{position:absolute;width:4px;height:4px;background:#fffbe6;border-radius:50%;pointer-events:none;animation:riSparkleOrbit var(--dur) linear infinite;opacity:0;}
  @keyframes riSparkleOrbit{0%{opacity:0;transform:rotate(var(--start)) translateX(var(--radius)) scale(0)}20%{opacity:.8}80%{opacity:.6}100%{opacity:0;transform:rotate(calc(var(--start) + 360deg)) translateX(var(--radius)) scale(0)}}

  .ri-scene.active{animation:riScreenShake .8s ease 1.5s;}
  @keyframes riScreenShake{0%,100%{transform:translate(0,0)}10%{transform:translate(-3px,2px)}20%{transform:translate(4px,-2px)}30%{transform:translate(-2px,3px)}40%{transform:translate(3px,-1px)}50%{transform:translate(-1px,2px)}60%{transform:translate(2px,-3px)}70%{transform:translate(-3px,1px)}80%{transform:translate(1px,-2px)}90%{transform:translate(-2px,1px)}}

  #ri-overlay{transition:opacity 1.8s ease;}
  #ri-overlay.ri-fade-out{opacity:0;pointer-events:none;}
</style>

<script>
(function riInit() {
  var overlay  = document.getElementById('ri-overlay');
  var scene    = document.getElementById('ri-scene');
  var sceneWrap= document.getElementById('ri-scene-wrap');
  var btn      = document.getElementById('ri-btnAleme');

  /* ── Bible section stars — identical style to the tomb stars ── */
  (function buildBibleStars() {
    var container = document.getElementById('ri-bible-stars');
    if (!container) return;
    for (var i = 0; i < 90; i++) {
      var s = document.createElement('div');
      s.className = 'ri-star';
      var sz = Math.random() * 2.5 + 0.8;
      s.style.cssText = 'width:'+sz+'px;height:'+sz+'px;top:'+Math.random()*100+'%;left:'+Math.random()*100+'%;animation-delay:'+Math.random()*3+'s;animation-duration:'+(2+Math.random()*3)+'s;';
      container.appendChild(s);
    }
  })();

  /* ── Tomb scene: build elements ── */
  (function buildTomb() {
    // Stars
    var starsEl = document.getElementById('ri-stars');
    for (var i = 0; i < 90; i++) {
      var s = document.createElement('div');
      s.className = 'ri-star';
      var sz = Math.random() * 2.5 + 0.8;
      s.style.cssText = 'width:'+sz+'px;height:'+sz+'px;top:'+Math.random()*55+'%;left:'+Math.random()*100+'%;animation-delay:'+Math.random()*3+'s;animation-duration:'+(2+Math.random()*3)+'s;';
      starsEl.appendChild(s);
    }

    // Light rays
    var raysEl = document.getElementById('ri-lightRays');
    for (var i = 0; i < 32; i++) {
      var r = document.createElement('div');
      r.className = 'ri-ray';
      var angle = (i / 32) * 360;
      var h = 120 + Math.random() * 350;
      var o = 0.15 + Math.random() * 0.4;
      var w = 2 + Math.random() * 5;
      r.style.cssText = 'transform:rotate('+angle+'deg);--ray-h:'+h+'px;--ray-o:'+o+';width:'+w+'px;animation-delay:'+(2+Math.random()*1.5)+'s;';
      raysEl.appendChild(r);
    }

    // Particles
    var particlesEl = document.getElementById('ri-particles');
    for (var i = 0; i < 55; i++) {
      var p = document.createElement('div');
      p.className = 'ri-particle';
      var sz = 2 + Math.random() * 5;
      p.style.cssText = 'width:'+sz+'px;height:'+sz+'px;left:'+(25+Math.random()*50)+'%;bottom:'+(10+Math.random()*35)+'%;--dur:'+(3+Math.random()*4)+'s;--delay:'+(2.5+Math.random()*3)+'s;filter:blur('+Math.random()*2+'px);';
      particlesEl.appendChild(p);
    }

    // Grass
    var grassEl = document.getElementById('ri-grass');
    for (var i = 0; i < 130; i++) {
      var t2 = document.createElement('div');
      t2.className = 'ri-tuft';
      var h = 5 + Math.random() * 18;
      var hue = 75 + Math.random() * 45;
      var light = 7 + Math.random() * 10;
      t2.style.cssText = 'left:'+(i/130)*100+'%;height:'+h+'px;background:hsl('+hue+',25%,'+light+'%);transform:rotate('+(-15+Math.random()*30)+'deg);';
      grassEl.appendChild(t2);
    }

    // Button sparkles
    for (var i = 0; i < 8; i++) {
      var sp = document.createElement('div');
      sp.className = 'ri-btn-sparkle';
      var startAngle = (i / 8) * 360;
      var radius = 55 + Math.random() * 20;
      var dur = 3 + Math.random() * 2;
      sp.style.cssText = 'top:50%;left:50%;--start:'+startAngle+'deg;--radius:'+radius+'px;--dur:'+dur+'s;animation-delay:'+(i/8)*dur+'s;width:'+(2+Math.random()*3)+'px;height:'+(2+Math.random()*3)+'px;';
      btn.parentElement.appendChild(sp);
    }
  })();

  /* ── When user scrolls to tomb scene, lock scroll and freeze overlay ── */
  var tombLocked = false;
  overlay.addEventListener('scroll', function() {
    if (tombLocked) return;
    var scrolled = overlay.scrollTop;
    var galaxyH  = document.getElementById('ri-galaxy').offsetHeight;

    // Once user scrolls past the galaxy section, lock into tomb scene
    if (scrolled >= galaxyH - 80) {
      tombLocked = true;
      overlay.style.overflowY = 'hidden';
      overlay.scrollTop = galaxyH; // snap to tomb top
    }
  });

  /* ── Button: trigger resurrection animation ── */
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    if (scene.classList.contains('active')) return;
    scene.classList.add('active');

    setTimeout(function() {
      overlay.classList.add('ri-fade-out');
      var main = document.getElementById('ybb-main-content');
      if (main) main.classList.add('ybb-revealed');
    }, 12000);

    setTimeout(function() {
      overlay.remove();
    }, 14000);
  });
})();
</script>
