/**
 * Bildiriş "ton" səslərini yaradan ortaq modul — həm admin panelin öz
 * bildirişi (admin/includes/footer.php), həm də müştərinin "sifariş
 * tamamlandı" bildirişi (track.php) EYNİ bu funksiyanı çağırır ki,
 * hər iki yerdə tənzimləmə eyni koda əsaslansın.
 */
function sgPlayToneSound(type) {
  var AudioCtx = window.AudioContext || window.webkitAudioContext;
  if (!AudioCtx) return;
  function tone(freqs, dur) {
    try {
      var ctx = new AudioCtx();
      freqs.forEach(function (f, i) {
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
      setTimeout(function () { ctx.close(); }, (freqs.length * dur + 0.3) * 1000);
    } catch (e) {}
  }
  if (type === 'beep2') { tone([700, 700], 0.16); return; }
  if (type === 'chime') { tone([523, 659, 784], 0.18); return; }
  tone([880], 0.22); // 'beep1' (defolt)
}
