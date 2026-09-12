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

<script src="../js/notify-sounds.js"></script>
<script>
(function(){
  // ---- Bildiriş səsi — ton tipləri (chime/beep1/beep2) js/notify-sounds.js-dəki
  // ortaq funksiya ilə yaradılır (müştərinin "tamamlandı" bildirişi ilə eyni kod).
  window.sgPlayAdminSound = function(name){
    if (name === 'none') return;
    if (name === 'bundled') {
      try {
        var bundled = new Audio('../assets/sounds/order-alert-admin.mp3');
        bundled.volume = 1;
        bundled.play().catch(function(){});
        return;
      } catch (e) {}
    }
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
    sgPlayToneSound(name);
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

  // "Görülmüş" (admin tərəfindən açılmış/həll edilmiş) sifariş ID-lərini saxlayırıq.
  // Bildiriş nə qədər ki bir sifariş bu siyahıda deyil VƏ hələ "pending" statusundadır,
  // hər tick-də (səhifə yenilənsə, başqa bölümə keçilsə belə) təkrar-təkrar davam edir.
  var ACK_KEY = 'sg_admin_ack_orders';
  var SOUND_KEY = 'sg_admin_sound';
  var REPEAT_MS = 9000;

  var originalTitle = document.title;
  var titleFlashTimer = null;

  function getSound(){
    try { return localStorage.getItem(SOUND_KEY) || 'chime'; } catch (e) { return 'chime'; }
  }
  function getAck(){
    try { return JSON.parse(localStorage.getItem(ACK_KEY) || '[]'); } catch (e) { return []; }
  }
  function setAck(arr){
    try { localStorage.setItem(ACK_KEY, JSON.stringify(arr)); } catch (e) {}
  }
  function ackOrder(id){
    var ack = getAck();
    if (ack.indexOf(id) === -1) { ack.push(id); setAck(ack); }
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
    // sifarişə keçəndə həmin ID-ni dərhal "görülmüş" say ki, geri qayıtsa bildiriş təkrarlanmasın
    var m = alertBtn.href.match(/id=(\d+)/);
    if (m) ackOrder(parseInt(m[1], 10));
    hideAlert();
  });

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) { stopTitleFlash(); tick(); }
  });

  function tick(){
    fetch('order-count.php', { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(data){
        var pendingIds = data.pending_ids || [];
        var ack = getAck();

        // hazırda order-view.php-də açıq olan sifarişi görülmüş say
        var viewingId = getViewingOrderId();
        if (viewingId !== null && ack.indexOf(viewingId) === -1) ack.push(viewingId);

        // artıq gözləmədə olmayan (statusu dəyişdirilmiş/təsdiqlənmiş) ID-ləri təmizlə
        ack = ack.filter(function(id){ return pendingIds.indexOf(id) !== -1; });
        setAck(ack);

        // "görünməmiş" = hazırda gözləyən AMMA hələ açılmamış sifarişlər.
        // Bu, sadəcə son ID-ni izləməkdənsə hər dəfə real vəziyyətdən hesablanır —
        // ona görə səhifə yenilənsə, başqa bölümə keçilsə belə itmir, təsdiqlənənə qədər davam edir.
        var unseen = pendingIds.filter(function(id){ return ack.indexOf(id) === -1; });

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
