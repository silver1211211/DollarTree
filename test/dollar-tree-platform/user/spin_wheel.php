<?php
/**
 * spin_wheel.php — Reusable Lucky Spin + Support component
 *
 * The wheel DISPLAYS enticing values ($380–$5,180) but the actual
 * awarded prize is always $0.66 or $1.66.
 */
if (!isset($spin_chances)) $spin_chances = 0;
?>

<style>
.sw-float-menu {
  position: fixed; right: 0; top: 50%; z-index: 350;
  transform: translateY(-50%);
  display: flex; flex-direction: column;
  background: #fff;
  border: 1px solid #d4ebd4; border-right: none;
  border-radius: 12px 0 0 12px;
  box-shadow: -4px 0 20px rgba(0,0,0,.10);
  overflow: hidden;
}
.sw-float-btn {
  width: 46px; height: 46px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border: none;
  color: #5a7a5a; font-size: 18px;
  cursor: pointer; transition: all .18s;
  position: relative;
  border-bottom: 1px solid #d4ebd4;
}
.sw-float-btn:last-child { border-bottom: none; }
.sw-float-btn:hover { background: #f0f5f0; color: #1a7a1a; }
.sw-badge {
  position: absolute; top: 6px; right: 6px;
  min-width: 16px; height: 16px; padding: 0 4px;
  background: #c8860a; color: #fff;
  border-radius: 8px; font-size: 9px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  border: 1.5px solid #fff; line-height: 1;
}
.sw-badge.zero { background: #8aaa8a; }

.sw-overlay {
  position: fixed; inset: 0; z-index: 600;
  background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
  display: none; align-items: center; justify-content: center; padding: 20px;
}
.sw-overlay.open { display: flex; animation: sw-fadein .2s ease; }
@keyframes sw-fadein { from{opacity:0} to{opacity:1} }
@keyframes sw-popin  { from{opacity:0;transform:scale(.92)} to{opacity:1;transform:scale(1)} }

.sw-modal {
  width: 100%; max-width: 360px;
  background: linear-gradient(160deg,#1a1a2e 0%,#0d3b0d 60%,#1a3a1a 100%);
  border-radius: 24px; overflow: hidden;
  box-shadow: 0 24px 60px rgba(0,0,0,.45);
  animation: sw-popin .25s cubic-bezier(.34,1.56,.64,1);
  position: relative;
}
.sw-sparkles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.sw-spark    { position: absolute; border-radius: 50%; animation: sw-sparkle 3s infinite; }
.sw-spark:nth-child(1){width:4px;height:4px;background:#f5d060;top:15%;left:20%;animation-delay:0s}
.sw-spark:nth-child(2){width:3px;height:3px;background:#7be87b;top:25%;right:18%;animation-delay:.4s}
.sw-spark:nth-child(3){width:5px;height:5px;background:#f5d060;bottom:30%;left:15%;animation-delay:.8s}
.sw-spark:nth-child(4){width:3px;height:3px;background:#fff;top:60%;right:22%;animation-delay:1.2s}
.sw-spark:nth-child(5){width:4px;height:4px;background:#7be87b;bottom:20%;right:12%;animation-delay:1.6s}
@keyframes sw-sparkle{0%,100%{opacity:0;transform:scale(0)}50%{opacity:1;transform:scale(1)}}

.sw-head {
  padding: 18px 18px 0;
  display: flex; align-items: center; justify-content: space-between;
  position: relative; z-index: 2;
}
.sw-title { font-size: 15px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }
.sw-title i { color: #c8860a; }
.sw-close {
  width: 32px; height: 32px; border-radius: 9px;
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.7); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; transition: all .18s;
}
.sw-close:hover { background: rgba(255,255,255,.2); color: #fff; }

.sw-chances-row { text-align: center; padding: 10px 18px 0; position: relative; z-index: 2; }
.sw-chances-pill {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(240,192,64,.15); border: 1px solid rgba(240,192,64,.3);
  border-radius: 100px; padding: 5px 14px;
  font-size: 12px; font-weight: 700; color: #f5d060;
}
.sw-chances-pill i { font-size: 11px; }

.sw-wheel-wrap {
  display: flex; align-items: center; justify-content: center;
  padding: 20px 18px 10px; position: relative; z-index: 2;
}
.sw-wheel-outer { position: relative; width: 240px; height: 240px; flex-shrink: 0; }
.sw-pointer {
  position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
  width: 0; height: 0; z-index: 10;
  border-left: 10px solid transparent; border-right: 10px solid transparent;
  border-top: 22px solid #c8860a;
  filter: drop-shadow(0 2px 6px rgba(200,134,10,.6));
}
.sw-ring {
  position: absolute; inset: -6px; border-radius: 50%;
  background: conic-gradient(
    #f5d060 0deg,#e8b830 45deg,#f5d060 45deg,#e8b830 90deg,
    #f5d060 90deg,#e8b830 135deg,#f5d060 135deg,#e8b830 180deg,
    #f5d060 180deg,#e8b830 225deg,#f5d060 225deg,#e8b830 270deg,
    #f5d060 270deg,#e8b830 315deg,#f5d060 315deg,#e8b830 360deg
  );
  box-shadow: 0 0 20px rgba(240,192,64,.5),0 0 40px rgba(240,192,64,.2);
  animation: sw-ringpulse 2s ease-in-out infinite;
}
@keyframes sw-ringpulse{
  0%,100%{box-shadow:0 0 18px rgba(240,192,64,.5),0 0 36px rgba(240,192,64,.2)}
  50%{box-shadow:0 0 28px rgba(240,192,64,.7),0 0 56px rgba(240,192,64,.35)}
}
canvas#sw-canvas { position: relative; z-index: 2; width: 240px; height: 240px; border-radius: 50%; }
.sw-spin-btn {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
  z-index: 10; width: 56px; height: 56px; border-radius: 50%;
  background: radial-gradient(135deg,#f5d060 0%,#c8860a 60%,#8a5c00 100%);
  border: 3px solid rgba(255,255,255,.3);
  box-shadow: 0 4px 16px rgba(0,0,0,.4),inset 0 1px 2px rgba(255,255,255,.3);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: transform .18s,box-shadow .18s;
  font-family: 'Barlow Condensed',Barlow,sans-serif;
  font-size: 11px; font-weight: 900; color: #fff;
  text-transform: uppercase; letter-spacing: .04em; text-align: center; line-height: 1.2;
}
.sw-spin-btn:hover  { transform: translate(-50%,-50%) scale(1.06); box-shadow: 0 6px 22px rgba(0,0,0,.5); }
.sw-spin-btn:active { transform: translate(-50%,-50%) scale(.97); }
.sw-spin-btn.spinning { pointer-events: none; opacity: .8; }

.sw-hint {
  font-size: 10px; font-weight: 700; color: rgba(255,255,255,.35);
  text-align: center; padding: 0 18px 4px; position: relative; z-index: 2;
}
.sw-footer {
  background: rgba(0,0,0,.25); border-top: 1px solid rgba(255,255,255,.07);
  padding: 12px 18px; position: relative; z-index: 2;
}
.sw-invite-note {
  font-size: 11px; color: rgba(255,255,255,.45);
  text-align: center; line-height: 1.6;
  display: flex; align-items: flex-start; gap: 6px;
}
.sw-invite-note i { color: #f5d060; flex-shrink: 0; margin-top: 1px; }

.sw-win {
  position: absolute; inset: 0; z-index: 20;
  background: rgba(0,0,0,.85);
  display: none; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 10px; border-radius: 24px;
  animation: sw-fadein .3s ease;
}
.sw-win.show { display: flex; }
.sw-win-emoji  { font-size: 48px; animation: sw-bounce .5s ease infinite alternate; }
@keyframes sw-bounce{from{transform:translateY(0)}to{transform:translateY(-10px)}}
.sw-win-title  { font-size: 22px; font-weight: 900; color: #f5d060; font-family: 'Barlow Condensed',sans-serif; letter-spacing: .04em; }
.sw-win-amount { font-family: 'Barlow Condensed',sans-serif; font-size: 48px; font-weight: 900; color: #fff; line-height: 1; }
.sw-win-sub    { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 2px; }
.sw-win-claim  {
  margin-top: 8px;
  background: linear-gradient(135deg,#f5d060 0%,#c8860a 100%);
  color: #fff; border: none; border-radius: 12px;
  padding: 13px 32px;
  font-family: 'Barlow',sans-serif; font-size: 14px; font-weight: 800;
  text-transform: uppercase; letter-spacing: .05em;
  cursor: pointer; transition: all .18s;
  box-shadow: 0 4px 16px rgba(200,134,10,.4);
}
.sw-win-claim:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(200,134,10,.5); }

.sw-sup-modal {
  width: 100%; max-width: 380px; background: #fff;
  border-radius: 20px; overflow: hidden;
  box-shadow: 0 24px 60px rgba(0,0,0,.2);
  animation: sw-popin .25s cubic-bezier(.34,1.56,.64,1);
}
.sw-sup-head {
  background: linear-gradient(135deg,#1a7a1a 0%,#0f5c0f 100%);
  padding: 20px 18px 18px; position: relative; overflow: hidden;
}
.sw-sup-head::before {
  content:''; position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse 80% 80% at 90% 120%,rgba(255,255,255,.08) 0%,transparent 55%);
}
.sw-sup-head-row { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2; }
.sw-sup-title { font-size: 16px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }
.sw-sup-title i { color: rgba(255,255,255,.7); }
.sw-sup-close {
  width: 32px; height: 32px; border-radius: 9px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2);
  color: rgba(255,255,255,.8); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; transition: all .18s;
}
.sw-sup-close:hover { background: rgba(255,255,255,.25); color: #fff; }
.sw-sup-sub { font-size: 12px; color: rgba(255,255,255,.55); margin-top: 6px; position: relative; z-index: 2; }
.sw-sup-status {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(123,232,123,.15); border: 1px solid rgba(123,232,123,.3);
  border-radius: 100px; padding: 4px 11px;
  font-size: 11px; font-weight: 700; color: #7be87b;
  margin-top: 10px; position: relative; z-index: 2;
}
.sw-sup-dot { width: 7px; height: 7px; border-radius: 50%; background: #7be87b; animation: sw-pulse 1.5s ease-in-out infinite; }
@keyframes sw-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)}}
.sw-sup-body { padding: 20px 18px; }
.sw-sup-desc { font-size: 13px; color: #5a7a5a; line-height: 1.7; margin-bottom: 18px; }
.sw-sup-channel {
  display: flex; align-items: center; gap: 13px;
  background: #f0f5f0; border: 1px solid #d4ebd4;
  border-radius: 14px; padding: 14px 16px;
  text-decoration: none; color: inherit;
  transition: all .2s; cursor: pointer; margin-bottom: 10px;
}
.sw-sup-channel:hover { border-color: #b8d8b8; background: #e8f5e8; transform: translateX(3px); }
.sw-sup-ch-ic {
  width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg,#2aabee 0%,#229ed9 100%);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(42,171,238,.3);
}
.sw-sup-ch-ic svg { width: 24px; height: 24px; }
.sw-sup-ch-info { flex: 1; min-width: 0; }
.sw-sup-ch-name { font-size: 14px; font-weight: 800; color: #0d1a0d; margin-bottom: 2px; }
.sw-sup-ch-sub  { font-size: 11px; color: #8aaa8a; }
.sw-sup-ch-arr  { color: #8aaa8a; font-size: 14px; flex-shrink: 0; transition: color .18s; }
.sw-sup-channel:hover .sw-sup-ch-arr { color: #1a7a1a; }
.sw-sup-hours {
  display: flex; align-items: center; gap: 8px;
  background: rgba(26,122,26,.06); border: 1px solid #d4ebd4;
  border-radius: 10px; padding: 10px 14px;
  font-size: 11px; color: #5a7a5a; font-weight: 600; margin-top: 4px;
}
.sw-sup-hours i { color: #1a7a1a; font-size: 13px; flex-shrink: 0; }

/* ── sw-toast: always on top (z-index 9999) so it shows above any overlay ── */
#sw-toast {
  position: fixed; bottom: calc(var(--nav-h,64px) + 16px);
  left: 50%; transform: translateX(-50%) translateY(8px);
  background: #1a7a1a; color: #fff;
  padding: 10px 18px; border-radius: 10px;
  font-size: 13px; font-weight: 700;
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 6px 24px rgba(26,122,26,.3);
  z-index: 9999; opacity: 0; pointer-events: none;
  transition: all .25s cubic-bezier(.34,1.56,.64,1);
  white-space: nowrap; max-width: calc(100vw - 40px);
}
#sw-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
#sw-toast.err  { background: #c0392b; }
</style>

<!-- Floating side menu -->
<div class="sw-float-menu">
  <button class="sw-float-btn" onclick="openSpinner()" title="Lucky Spin">
    <i class="fa-solid fa-gift"></i>
    <span class="sw-badge <?php echo $spin_chances === 0 ? 'zero' : ''; ?>" id="sw-badge"><?php echo (int)$spin_chances; ?></span>
  </button>
  <button class="sw-float-btn" onclick="openSupport()" title="Support">
    <i class="fa-solid fa-headphones"></i>
  </button>
</div>

<!-- sw-toast: dedicated toast for spin wheel — always z-index 9999, never blocked by overlays -->
<div id="sw-toast"></div>

<!-- Spinner overlay -->
<div class="sw-overlay" id="sw-overlay" onclick="if(event.target===this)closeSpinner()">
  <div class="sw-modal">
    <div class="sw-sparkles">
      <div class="sw-spark"></div><div class="sw-spark"></div>
      <div class="sw-spark"></div><div class="sw-spark"></div><div class="sw-spark"></div>
    </div>

    <div class="sw-win" id="sw-win">
      <div class="sw-win-emoji">🎉</div>
      <div class="sw-win-title">You Won!</div>
      <div class="sw-win-amount" id="sw-win-amount">$0.66</div>
      <div class="sw-win-sub">Added to your balance</div>
      <button class="sw-win-claim" onclick="swClaimWin()">Claim Reward</button>
    </div>

    <div class="sw-head">
      <div class="sw-title"><i class="fa-solid fa-gift"></i>Lucky Spin</div>
      <button class="sw-close" onclick="closeSpinner()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="sw-chances-row">
      <div class="sw-chances-pill">
        <i class="fa-solid fa-ticket"></i>
        <span>Remaining Spins: <strong id="sw-count"><?php echo (int)$spin_chances; ?></strong></span>
      </div>
    </div>

    <div class="sw-wheel-wrap">
      <div class="sw-wheel-outer">
        <div class="sw-ring"></div>
        <div class="sw-pointer"></div>
        <canvas id="sw-canvas" width="240" height="240"></canvas>
        <div class="sw-spin-btn" id="sw-spin-btn" onclick="swDoSpin()">SPIN</div>
      </div>
    </div>

    <div class="sw-hint">Possible prizes on this wheel</div>

    <div class="sw-footer">
      <div class="sw-invite-note">
        <i class="fa-solid fa-users"></i>
        Earn 1 free spin for each friend (SVIP 3+) you invite. The more you invite, the more you spin!
      </div>
    </div>
  </div>
</div>

<!-- Support overlay -->
<div class="sw-overlay" id="sw-sup-overlay" onclick="if(event.target===this)closeSupport()">
  <div class="sw-sup-modal">
    <div class="sw-sup-head">
      <div class="sw-sup-head-row">
        <div class="sw-sup-title"><i class="fa-solid fa-headset"></i>Online Service</div>
        <button class="sw-sup-close" onclick="closeSupport()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="sw-sup-sub">Choose your preferred customer service contact method</div>
      <div class="sw-sup-status"><span class="sw-sup-dot"></span>Online Now</div>
    </div>
    <div class="sw-sup-body">
      <p class="sw-sup-desc">Our support team is ready to help you with deposits, withdrawals, tasks, and account issues.</p>
      <a href="https://t.me/Dollartreesupport" target="_blank" rel="noopener" class="sw-sup-channel">
        <div class="sw-sup-ch-ic">
          <svg viewBox="0 0 24 24" fill="white"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/></svg>
        </div>
        <div class="sw-sup-ch-info">
          <div class="sw-sup-ch-name">Dollar Support</div>
          <div class="sw-sup-ch-sub">Chat with us on Telegram</div>
        </div>
        <i class="fa-solid fa-chevron-right sw-sup-ch-arr"></i>
      </a>
      <a href="https://t.me/DOLLARTREE_USDT" target="_blank" rel="noopener" class="sw-sup-channel">
        <div class="sw-sup-ch-ic" style="background:linear-gradient(135deg,#1a7a1a 0%,#0f5c0f 100%);box-shadow:0 4px 12px rgba(26,122,26,.3);">
          <i class="fa-solid fa-bullhorn" style="color:#fff;font-size:20px;"></i>
        </div>
        <div class="sw-sup-ch-info">
          <div class="sw-sup-ch-name">Updates &amp; Deals</div>
          <div class="sw-sup-ch-sub">Join our Telegram channel for latest news</div>
        </div>
        <i class="fa-solid fa-chevron-right sw-sup-ch-arr"></i>
      </a>
      <div class="sw-sup-hours">
        <i class="fa-regular fa-clock"></i>
        Available 24/7 — Average response time under 5 minutes
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var SW = {
    chances:       <?php echo (int)$spin_chances; ?>,
    spinning:      false,
    angle:         0,
    pendingWin:    null,
    claimEndpoint: 'ajax_spin_claim.php'
  };

  var SEGMENTS = [
    { label: '$0.66',  color: '#1a7a1a', prize: 0.66,  landable: true  },
    { label: '$5,180', color: '#7b1c1c', prize: null,   landable: false },
    { label: '$1.66',  color: '#22a322', prize: 1.66,   landable: true  },
    { label: '$980',   color: '#5a2d82', prize: null,   landable: false },
    { label: '$0.66',  color: '#0f5c0f', prize: 0.66,   landable: true  },
    { label: '$380',   color: '#9c5200', prize: null,   landable: false },
    { label: '$1.66',  color: '#2db82d', prize: 1.66,   landable: true  },
    { label: '$680',   color: '#0a4a7a', prize: null,   landable: false },
    { label: '$0.66',  color: '#145e14', prize: 0.66,   landable: true  },
    { label: '$180',   color: '#6b3000', prize: null,   landable: false },
    { label: '$1.66',  color: '#2a8a2a', prize: 1.66,   landable: true  },
    { label: '$1,580', color: '#1a3a6a', prize: null,   landable: false }
  ];

  var N = SEGMENTS.length, SA = 2 * Math.PI / N;

  /* ── swToast: ALWAYS uses #sw-toast (z-index 9999).
   *   Never delegates to the page's showToast() because that element
   *   may have a lower z-index (e.g. 500 on dashboard) and would be
   *   hidden behind the spin overlay (z-index 600).
   * ─────────────────────────────────────────────────────────────────── */
  var _tt;
  function swToast(msg, isErr) {
    var el = document.getElementById('sw-toast');
    if (!el) return;
    clearTimeout(_tt);
    el.innerHTML = '<i class="fa-solid fa-' + (isErr ? 'circle-exclamation' : 'circle-check') + '"></i> ' + msg;
    el.className = 'show' + (isErr ? ' err' : '');
    _tt = setTimeout(function () { el.classList.remove('show'); }, 3200);
  }

  /* ── Draw wheel ── */
  function swDraw(angle) {
    var c = document.getElementById('sw-canvas');
    if (!c) return;
    var ctx = c.getContext('2d'), W = c.width, cx = W/2, cy = W/2, r = W/2 - 4;
    ctx.clearRect(0, 0, W, W);
    for (var i = 0; i < N; i++) {
      var sa = angle + i * SA - Math.PI/2, ea = sa + SA;
      ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,sa,ea); ctx.closePath();
      ctx.fillStyle = SEGMENTS[i].color; ctx.fill();
      ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,sa,ea); ctx.closePath();
      ctx.strokeStyle = 'rgba(255,255,255,.25)'; ctx.lineWidth = 1.5; ctx.stroke();
      ctx.save();
      ctx.translate(cx, cy);
      ctx.rotate(sa + SA / 2);
      ctx.textAlign = 'right';
      ctx.fillStyle = '#fff';
      var lbl = SEGMENTS[i].label;
      ctx.font = (lbl.length > 5 ? 'bold 11px' : 'bold 13px') + ' "Barlow Condensed",Barlow,sans-serif';
      ctx.shadowColor = 'rgba(0,0,0,.5)'; ctx.shadowBlur = 4;
      ctx.fillText(lbl, r - 10, 5);
      ctx.restore();
    }
    ctx.beginPath(); ctx.arc(cx,cy,28,0,2*Math.PI);
    ctx.fillStyle = 'rgba(0,0,0,.35)'; ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.2)'; ctx.lineWidth = 2; ctx.stroke();
  }

  /* ── Badge sync ── */
  function swSyncBadge() {
    var b = document.getElementById('sw-badge'), ct = document.getElementById('sw-count');
    if (b)  { b.textContent = SW.chances; b.className = 'sw-badge' + (SW.chances === 0 ? ' zero' : ''); }
    if (ct) { ct.textContent = SW.chances; }
  }

  /* ── Live balance update ── */
  function swUpdateBalance(v) {
    var p = v.toFixed(2).split('.');
    var bi = document.getElementById('bi'), bd = document.getElementById('bd');
    if (bi) bi.textContent = parseInt(p[0]).toLocaleString();
    if (bd) bd.textContent = p[1];
  }

  /* ── Server claim ── */
  function swClaim(prize) {
    fetch(SW.claimEndpoint, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({prize: prize})
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.success) {
        SW.chances = d.spin_chances;
        swSyncBadge();
        if (d.new_balance !== undefined) swUpdateBalance(d.new_balance);
      } else {
        SW.chances++;
        swSyncBadge();
        document.getElementById('sw-win').classList.remove('show');
        swToast(d.error || 'Spin failed. Please try again.', true);
      }
    })
    .catch(function () {
      SW.chances++;
      swSyncBadge();
      swToast('Network error. Balance may not have updated.', true);
    });
  }

  /* ── Spin ── */
  window.swDoSpin = function () {
    if (SW.spinning) return;
    if (SW.chances <= 0) {
      swToast('No spins available! Refer SVIP 3+ friends to earn spins.', true);
      return;
    }

    SW.spinning = true;
    var btn = document.getElementById('sw-spin-btn');
    btn.classList.add('spinning'); btn.textContent = '...';

    var prize = Math.random() < 0.5 ? 0.66 : 1.66;
    var targets = [];
    for (var i = 0; i < N; i++) {
      if (SEGMENTS[i].landable && SEGMENTS[i].prize === prize) targets.push(i);
    }

    var seg = targets[Math.floor(Math.random() * targets.length)];
    var targetCenter = -(seg * SA + SA / 2);
    var fullSpins = (5 + Math.floor(Math.random() * 3)) * 2 * Math.PI;
    var finalAngle = SW.angle + fullSpins + ((targetCenter - SW.angle % (2 * Math.PI) + 2 * Math.PI * 3) % (2 * Math.PI));
    var duration = 3800, startAngle = SW.angle, startTime = null;

    function ease(t) { return 1 - Math.pow(1 - t, 4); }
    function animate(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      SW.angle = startAngle + (finalAngle - startAngle) * ease(progress);
      swDraw(SW.angle);
      if (progress < 1) { requestAnimationFrame(animate); return; }

      SW.pendingWin = prize;
      SW.chances--;
      SW.spinning = false;
      btn.classList.remove('spinning'); btn.textContent = 'SPIN';
      swSyncBadge();

      setTimeout(function () {
        document.getElementById('sw-win-amount').textContent = '$' + prize.toFixed(2);
        document.getElementById('sw-win').classList.add('show');
      }, 300);

      swClaim(prize);
    }
    requestAnimationFrame(animate);
  };

  /* ── Claim ── */
  window.swClaimWin = function () {
    document.getElementById('sw-win').classList.remove('show');
    closeSpinner();
    if (SW.pendingWin !== null) {
      swToast('🎉 $' + SW.pendingWin.toFixed(2) + ' added to your balance!');
      SW.pendingWin = null;
    }
  };

  /* ── Open / close ── */
  window.openSpinner = function () {
    document.getElementById('sw-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { swDraw(SW.angle); }, 50);
  };
  window.closeSpinner = function () {
    document.getElementById('sw-overlay').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('sw-win').classList.remove('show');
  };
  window.openSupport = function () {
    document.getElementById('sw-sup-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  };
  window.closeSupport = function () {
    document.getElementById('sw-sup-overlay').classList.remove('open');
    document.body.style.overflow = '';
  };

  /* ── Init ── */
  document.addEventListener('DOMContentLoaded', function () { swDraw(SW.angle); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeSpinner(); closeSupport(); }
  });

})();
</script>