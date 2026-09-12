/**
 * Müştəri saytda hər hansı səhifədə (track.php istisna — o özü ayrıca,
 * daha ətraflı stepper göstərir) olarkən öz sifarişinin statusu dəyişəndə
 * bildiriş versin deyə fon sorğusu (polling). "sg_my_orders" (bu brauzerdən
 * verilmiş sifarişlər) siyahısını izləyir, "sg_order_last_status" ilə
 * son bilinən statusu saxlayır ki, dəyişiklik səhifələr arasında itməsin.
 */
(function () {
  var POLL_MS = 15000;
  var ORDERS_KEY = 'sg_my_orders';
  var STATUS_CACHE_KEY = 'sg_order_last_status';
  var STEP_LABELS = {
    pending: 'Qəbul edildi', preparing: 'Hazırlanır', ready: 'Hazırdır',
    completed: 'Tamamlandı', cancelled: 'Ləğv edildi'
  };

  function getMyOrders() {
    try { return JSON.parse(localStorage.getItem(ORDERS_KEY) || '[]'); } catch (e) { return []; }
  }
  function getStatusCache() {
    try { return JSON.parse(localStorage.getItem(STATUS_CACHE_KEY) || '{}'); } catch (e) { return {}; }
  }
  function setStatusCache(c) {
    try { localStorage.setItem(STATUS_CACHE_KEY, JSON.stringify(c)); } catch (e) {}
  }
  window.sgMarkOrderStatusSeen = function (orderId, status) {
    var cache = getStatusCache();
    cache[orderId] = status;
    setStatusCache(cache);
  };

  // ---- Toast (proqramla yaradılır) ----
  var toastEl = null;
  function ensureToast() {
    if (toastEl) return toastEl;
    toastEl = document.createElement('div');
    toastEl.className = 'sg-status-toast';
    toastEl.innerHTML = '<span class="sg-status-toast-icon">🔔</span><span class="sg-status-toast-text"></span>';
    document.body.appendChild(toastEl);
    toastEl.addEventListener('click', function () {
      var link = toastEl.getAttribute('data-link');
      if (link) window.location.href = link;
    });
    return toastEl;
  }
  function showToast(text, link) {
    var el = ensureToast();
    el.querySelector('.sg-status-toast-text').textContent = text;
    el.setAttribute('data-link', link || '');
    el.classList.add('show');
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(function () { el.classList.remove('show'); }, 7000);
  }

  // ---- Rəy pəncərəsi (proqramla yaradılır, track.php-dəki ilə eyni CSS-i istifadə edir) ----
  var reviewPopup = null;
  function ensureReviewPopup() {
    if (reviewPopup) return reviewPopup;
    reviewPopup = document.createElement('div');
    reviewPopup.className = 'review-popup-overlay';
    document.body.appendChild(reviewPopup);
    return reviewPopup;
  }
  function showReviewPopup(order) {
    var el = ensureReviewPopup();
    el.innerHTML =
      '<div class="review-popup-card">' +
        '<div class="review-popup-icon">🎉</div>' +
        '<h3>Sifarişiniz tamamlandı!</h3>' +
        '<p>Necə idi? Rəyinizi bildirin:</p>' +
        '<div class="review-stars">' +
          [1, 2, 3, 4, 5].map(function (n) { return '<span data-star="' + n + '">★</span>'; }).join('') +
        '</div>' +
        '<textarea placeholder="Qeyd (istəyə bağlı)"></textarea>' +
        '<div class="review-popup-actions">' +
          '<button type="button" class="btn btn-ghost sg-review-skip">Bağla</button>' +
          '<button type="button" class="btn btn-primary sg-review-submit">Göndər</button>' +
        '</div>' +
      '</div>';
    var selected = 0;
    var starsWrap = el.querySelector('.review-stars');
    var stars = el.querySelectorAll('.review-stars span');
    function paint(n) {
      stars.forEach(function (s) { s.classList.toggle('filled', parseInt(s.getAttribute('data-star'), 10) <= n); });
    }
    stars.forEach(function (s) {
      s.addEventListener('mouseenter', function () { paint(parseInt(s.getAttribute('data-star'), 10)); });
      s.addEventListener('click', function () { selected = parseInt(s.getAttribute('data-star'), 10); paint(selected); });
    });
    starsWrap.addEventListener('mouseleave', function () { paint(selected); });

    el.querySelector('.sg-review-skip').addEventListener('click', function () { el.classList.remove('show'); });
    el.querySelector('.sg-review-submit').addEventListener('click', function () {
      if (!selected) { alert('Zəhmət olmasa ulduz seçin.'); return; }
      var textarea = el.querySelector('textarea');
      fetch('review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: order.id, t: order.t, rating: selected, comment: textarea.value.trim() })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok) {
            el.querySelector('.review-popup-card').innerHTML = '<div class="review-thanks">✓ Rəyiniz üçün təşəkkür edirik!</div>';
            setTimeout(function () { el.classList.remove('show'); }, 1800);
          } else {
            alert(data.error || 'Xəta baş verdi.');
          }
        })
        .catch(function () {});
    });
    el.classList.add('show');
  }

  // ---- Səs — admin panelin "müştəri tamamlandı" seçimi ilə eyni parametrləri istifadə edir ----
  function playCompletedSound() {
    var type = window.SG_COMPLETED_SOUND_TYPE || 'bundled';
    var file = window.SG_COMPLETED_SOUND_FILE || '';
    if (type === 'none') return;
    if (type === 'bundled') { try { new Audio('assets/sounds/order-completed.mp3').play().catch(function () {}); } catch (e) {} return; }
    if (type === 'custom' && file) { try { new Audio(file).play().catch(function () {}); } catch (e) {} return; }
    if (typeof sgPlayToneSound === 'function') sgPlayToneSound(type);
  }
  function playPing() {
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator(), gain = ctx.createGain();
      osc.type = 'sine'; osc.frequency.value = 660;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
      osc.connect(gain); gain.connect(ctx.destination);
      osc.start(); osc.stop(ctx.currentTime + 0.35);
      setTimeout(function () { ctx.close(); }, 500);
    } catch (e) {}
  }

  // Yüksək prioritetli (OS səviyyəli) bildirişlər üçün icazəni özümüz istəyirik —
  // müştəri heç nə etməsə belə, tab arxa planda olanda da bildiriş görünsün.
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(function () {});
  }

  function notifyStatusChange(order, status) {
    var label = STEP_LABELS[status] || status;
    var link = 'track.php?id=' + order.id + '&t=' + order.t;
    showToast('Sifariş ' + (order.number || ('#' + order.id)) + ': ' + label, link);
    if (status === 'completed') playCompletedSound();
    else playPing();

    if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
      try {
        var n = new Notification('Sushi Garden', {
          body: 'Sifariş ' + (order.number || ('#' + order.id)) + ': ' + label,
          tag: 'sg-order-' + order.id
        });
        n.onclick = function () { window.focus(); window.location.href = link; };
      } catch (e) {}
    }
  }

  // track.php hazırda açıq olan sifarişi artıq öz (daha ətraflı) skripti ilə
  // izləyir — həmin sifarişi burada TƏKRAR bildirməmək üçün URL-dən oxuyuruq.
  function getCurrentlyViewedOrderId() {
    if (!/track\.php/.test(window.location.pathname)) return null;
    var id = parseInt(new URLSearchParams(window.location.search).get('id') || '', 10);
    return isNaN(id) ? null : id;
  }

  function poll() {
    var orders = getMyOrders();
    if (!orders.length) return;
    var cache = getStatusCache();
    var viewingId = getCurrentlyViewedOrderId();
    orders.slice(-5).forEach(function (order) {
      if (viewingId !== null && order.id === viewingId) return; // track.php öz skripti ilə idarə edir
      var known = cache[order.id];
      if (known === 'completed' || known === 'cancelled') return; // artıq son mərhələdə, izləməyə ehtiyac yoxdur
      fetch('track-status.php?id=' + order.id + '&t=' + order.t)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) return;
          if (cache[order.id] === undefined) {
            // ilk dəfə görürük — köhnə statusu "yeni dəyişiklik" kimi bildirmə, sadəcə qeyd et
            cache[order.id] = data.status;
            setStatusCache(cache);
            if (data.status === 'completed' && !data.reviewed) showReviewPopup(order);
            return;
          }
          if (cache[order.id] !== data.status) {
            cache[order.id] = data.status;
            setStatusCache(cache);
            notifyStatusChange(order, data.status);
            if (data.status === 'completed' && !data.reviewed) showReviewPopup(order);
          }
        })
        .catch(function () {});
    });
  }

  poll();
  setInterval(poll, POLL_MS);
})();
