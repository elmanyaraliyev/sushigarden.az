    </div>
  </div>
</div>

<div id="sg-order-toast" class="sg-order-toast"></div>

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
    if (name === 'beep2') { playTone([700, 700], 0.16); return; }
    if (name === 'chime') { playTone([523, 659, 784], 0.18); return; }
    playTone([880], 0.22); // 'beep1' (defolt)
  };

  // ---- Yeni sifariş üçün fon sorğusu (polling) ----
  var toast = document.getElementById('sg-order-toast');
  var STORAGE_KEY = 'sg_admin_last_order_id';
  var SOUND_KEY = 'sg_admin_sound';

  function getSound(){
    try { return localStorage.getItem(SOUND_KEY) || 'chime'; } catch (e) { return 'chime'; }
  }
  function getLastId(){
    try { return parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10); } catch (e) { return 0; }
  }
  function setLastId(id){
    try { localStorage.setItem(STORAGE_KEY, String(id)); } catch (e) {}
  }
  function showToast(id){
    toast.textContent = '🔔 Yeni sifariş daxil oldu — #' + id;
    toast.classList.add('show');
    setTimeout(function(){ toast.classList.remove('show'); }, 6000);
  }
  toast.addEventListener('click', function(){ window.location.href = 'orders.php'; });

  var first = true;
  function checkOrders(){
    fetch('order-count.php', { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(data){
        var last = getLastId();
        if (first) {
          // İlk yükləmədə mövcud ən böyük ID-ni sadəcə yadda saxla, xəbərdarlıq vermə.
          if (!last || data.latest_id > last) setLastId(data.latest_id);
          first = false;
          return;
        }
        if (data.latest_id > last) {
          setLastId(data.latest_id);
          window.sgPlayAdminSound(getSound());
          showToast(data.latest_id);
        }
      })
      .catch(function(){});
  }
  checkOrders();
  setInterval(checkOrders, 20000);
})();
</script>
</body>
</html>
