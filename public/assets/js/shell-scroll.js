/* Scroll behaviour shared by every dashboard shell — the same treatment the
   landing page gives its floating nav, retargeted at the sticky .shell-topbar.

   Two independent pieces, each skipped when the page doesn't render its element,
   so this is safe to include from any view:

     #scrollProgressBar  fills left-to-right with scroll depth
     #shellTopbar        tints past the fold, hides on scroll-down / reveals up

   Pairs with .shell-topbar.is-scrolled / .is-hidden in modern.css. Load with
   `defer` so the DOM is parsed before this runs. */
(function () {
  var bar    = document.getElementById('scrollProgressBar');
  var topbar = document.getElementById('shellTopbar');
  if (!bar && !topbar) return;

  var reduceMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var TINT_AT   = 40;  /* px of scroll before the bar takes its lifted look */
  var HIDE_FROM = 80;  /* stay put near the top, where there's nothing to reclaim */
  var DELTA     = 4;   /* dead zone, so jitter and rubber-banding don't toggle it */

  var lastY = window.pageYOffset || document.documentElement.scrollTop || 0;
  var ticking = false;

  function update() {
    ticking = false;
    var doc = document.documentElement;
    var y = window.pageYOffset || doc.scrollTop || 0;

    if (bar) {
      var height = (doc.scrollHeight - doc.clientHeight) || 1;
      bar.style.width = Math.min(100, Math.max(0, (y / height) * 100)) + '%';
    }

    if (topbar) {
      if (y > TINT_AT) { topbar.classList.add('is-scrolled'); }
      else             { topbar.classList.remove('is-scrolled'); }

      if (!reduceMotion) {
        var delta = y - lastY;
        if (y < HIDE_FROM)       { topbar.classList.remove('is-hidden'); }
        else if (delta >  DELTA) { topbar.classList.add('is-hidden'); }
        else if (delta < -DELTA) { topbar.classList.remove('is-hidden'); }
      }
    }

    lastY = y;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
  }, { passive: true });

  /* Keyboard users can tab into the bar while it's hidden, which would leave
     the focused control off-screen — pull it back whenever focus lands inside. */
  if (topbar) {
    topbar.addEventListener('focusin', function () {
      topbar.classList.remove('is-hidden');
    });
  }

  update();
})();
