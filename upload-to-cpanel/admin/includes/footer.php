    </div>
  </div>
</div>

<div id="sg-order-alert" class="sg-order-alert" role="alert">
  <div class="sg-order-alert-icon">🔔</div>
  <div class="sg-order-alert-body">
    <div class="sg-order-alert-title" id="sg-order-alert-title">Yeni sifariş!</div>
    <div class="sg-order-alert-sub" id="sg-order-alert-sub"></div>
  </div>
  <a href="orders.php" class="sg-order-alert-btn" id="sg-order-alert-btn">Bax</a>
</div>

<script>
(function(){
  // ---- Bildiriş səsi (Web Audio ilə yaradılır, xarici fayl lazım deyil) ----
  var AudioCtx = window.AudioContext || window.webkitAudioContext;
  function playTone(freqs, dur){
    if (!AudioCtx) return;
    try {
      var ctx = new AudioCtx();
      freqs.forEach(function(f, i){
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = f;
        var start = ctx.currentTime + i * dur;
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.35, start + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(start);
        osc.stop(start + dur + 0.05);
      });
      setTimeout(function(){ ctx.close(); }, (freqs.length * dur + 0.3) * 1000);
    } catch (e) {}
  }
  window.sgPlayAdminSound = function(name){
    if (name === 'none') return;
    if (name === 'custom') {
      try {
        var data = localStorage.getItem('sg_admin_custom_sound');
        if (data) {
          var audio = new Audio(data);
          audio.volume = 1;
          audio.play().catch(function(){});
          return;
        }
      } catch (e) {}
      // ehtiyat variant: fayl tapılmasa defolt səs çalınsın
    }
    if (name === 'beep2') { playTone([700, 700], 0.16); return; }
    if (name === 'chime') { playTone([523, 659, 784], 0.18); return; }
    playTone([880], 0.22); // 'beep1' (defolt)
  };

  /* ------------------------------------------------------------------
   * Yeni sifariş bildirişi — admin sifarişi AÇANA və ya statusunu
   * dəyişənə (təsdiq edənə) qədər səs və popup TƏKRAR-TƏKRAR davam edir.
   * "Görünməmiş sifarişlər" siyahısı localStorage-də saxlanılır ki, hər
   * admin səhifəsi (bu fayl hər səhifəyə daxil edilir) eyni vəziyyəti bilsin.
   * ---------------------------------------------------------------- */
  var alertBox = document.getElementById('sg-order-alert');
  var alertTitle = document.getElementById('sg-order-alert-title');
  var alertSub = document.getElementById('sg-order-alert-sub');
  var alertBtn = document.getElementById('sg-order-alert-btn');

  var LAST_ID_KEY = 'sg_admin_last_order_id';
  var UNSEEN_KEY = 'sg_admin_unseen_orders';
  var SOUND_KEY = 'sg_admin_sound';
  var REPEAT_MS = 9000;

  var originalTitle = document.title;
  var titleFlashTimer = null;

  function getSound(){
    try { return localStorage.getItem(SOUND_KEY) || 'chime'; } catch (e) { return 'chime'; }
  }
  function getLastId(){
    try { return parseInt(localStorage.getItem(LAST_ID_KEY) || '0', 10); } catch (e) { return 0; }
  }
  function setLastId(id){
    try { localStorage.setItem(LAST_ID_KEY, String(id)); } catch (e) {}
  }
  function getUnseen(){
    try { return JSON.parse(localStorage.getItem(UNSEEN_KEY) || '[]'); } catch (e) { return []; }
  }
  function setUnseen(arr){
    try { localStorage.setItem(UNSEEN_KEY, JSON.stringify(arr)); } catch (e) {}
  }

  // Hazırda order-view.php-də hansı sifarişə baxıldığını oxuyur — həmin sifariş
  // "görülmüş" sayılır, çünki admin onu artıq açıb.
  function getViewingOrderId(){
    if (!/order-view\.php/.test(window.location.pathname)) return null;
    var params = new URLSearchParams(window.location.search);
    var id = parseInt(params.get('id') || '', 10);
    return isNaN(id) ? null : id;
  }

  function startTitleFlash(label){
    stopTitleFlash();
    var on = false;
    titleFlashTimer = setInterval(function(){
      document.title = on ? originalTitle : label;
      on = !on;
    }, 1000);
  }
  function stopTitleFlash(){
    if (titleFlashTimer) { clearInterval(titleFlashTimer); titleFlashTimer = null; }
    document.title = originalTitle;
  }

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(function(){});
  }

  function showAlert(unseen){
    var count = unseen.length;
    if (count === 1) {
      alertTitle.textContent = 'Yeni sifariş — #' + unseen[0];
      alertSub.textContent = 'Diqqətinizi gözləyir';
      alertBtn.href = 'order-view.php?id=' + unseen[0];
    } else {
      alertTitle.textContent = count + ' sifariş gözləyir!';
      alertSub.textContent = 'Baxılmamış sifarişlər var';
      alertBtn.href = 'orders.php';
    }
    alertBox.classList.add('show');
    // animasiyanı hər dəfə YENİDƏN oynatmaq üçün class-ı çıxarıb reflow ilə geri qoyuruq
    alertBox.classList.remove('pop');
    void alertBox.offsetWidth;
    alertBox.classList.add('pop');
  }
  function hideAlert(){
    alertBox.classList.remove('show', 'pop');
    stopTitleFlash();
  }

  function fireAlert(unseen){
    window.sgPlayAdminSound(getSound());
    showAlert(unseen);
    if (document.hidden) {
      var label = unseen.length === 1 ? ('🔔 Sifariş #' + unseen[0] + '!') : ('🔔 ' + unseen.length + ' sifariş!');
      startTitleFlash(label);
      if ('Notification' in window && Notification.permission === 'granted') {
        try {
          var n = new Notification('🍣 Sushi Garden — Yeni sifariş', {
            body: unseen.length === 1 ? ('Sifariş #' + unseen[0] + ' daxil oldu.') : (unseen.length + ' sifariş cavab gözləyir.'),
            icon: '../assets/logo-icon.jpg',
            tag: 'sg-order-alert'
          });
          n.onclick = function(){ window.focus(); window.location.href = alertBtn.href; };
        } catch (e) {}
      }
    }
  }

  alertBtn.addEventListener('click', function(){
    // sifarişə keçəndə həmin ID-ni dərhal "görülmüş" say
    var unseen = getUnseen();
    if (unseen.length === 1) { setUnseen([]); hideAlert(); }
  });

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) { stopTitleFlash(); tick(); }
  });

  function tick(){
    fetch('order-count.php', { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(data){
        var pendingIds = data.pending_ids || [];
        var last = getLastId();
        var unseen = getUnseen();

        // yeni gələn (indiyədək görülməmiş) sifarişləri əlavə et
        pendingIds.forEach(function(id){
          if (id > last && unseen.indexOf(id) === -1) unseen.push(id);
        });

        // hazırda açıq olan sifarişi görülmüş say
        var viewingId = getViewingOrderId();
        if (viewingId !== null) unseen = unseen.filter(function(id){ return id !== viewingId; });

        // artıq gözləmədə olmayan (statusu dəyişdirilmiş/təsdiqlənmiş) sifarişləri sil
        unseen = unseen.filter(function(id){ return pendingIds.indexOf(id) !== -1; });

        setUnseen(unseen);
        setLastId(data.latest_id);

        if (unseen.length) {
          fireAlert(unseen);
        } else {
          hideAlert();
        }
      })
      .catch(function(){});
  }

  tick();
  setInterval(tick, REPEAT_MS);
})();
</script>
</body>
</html>
