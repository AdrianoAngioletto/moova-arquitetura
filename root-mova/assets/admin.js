/* Painel — redimensiona fotos no navegador antes de enviar (poupa dados e espaço).
   O servidor sempre revalida a imagem de verdade; isso aqui é só otimização. */
(function () {
  'use strict';

  var MAX_EDGE = 2000;
  var QUALITY = 0.82;

  function resizeFile(file) {
    return new Promise(function (resolve) {
      if (!file.type || file.type.indexOf('image/') !== 0 || typeof HTMLCanvasElement === 'undefined') {
        resolve(file);
        return;
      }
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth, h = img.naturalHeight;
        var scale = Math.min(1, MAX_EDGE / Math.max(w, h));
        if (scale >= 1) { resolve(file); return; }

        var canvas = document.createElement('canvas');
        canvas.width = Math.round(w * scale);
        canvas.height = Math.round(h * scale);
        var ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function (blob) {
          if (!blob) { resolve(file); return; }
          resolve(new File([blob], file.name.replace(/\.\w+$/, '.jpg'), { type: 'image/jpeg' }));
        }, 'image/jpeg', QUALITY);
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }

  function setupForm(form) {
    var inputs = Array.prototype.slice.call(form.querySelectorAll('input[type=file]'));
    if (!inputs.length) return;

    form.addEventListener('submit', function (e) {
      if (form.dataset.resized === '1') return; // já processado, deixa enviar
      var hasFiles = inputs.some(function (i) { return i.files && i.files.length; });
      if (!hasFiles) return;

      e.preventDefault();
      var submitBtn = form.querySelector('[type=submit]');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Enviando...'; }

      var jobs = inputs.map(function (input) {
        if (!input.files || !input.files.length) return Promise.resolve();
        return Promise.all(Array.prototype.slice.call(input.files).map(resizeFile))
          .then(function (files) {
            var dt = new DataTransfer();
            files.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
          });
      });

      Promise.all(jobs).then(function () {
        form.dataset.resized = '1';
        form.submit();
      });
    });
  }

  Array.prototype.slice.call(document.querySelectorAll('form[data-resize-images]')).forEach(setupForm);
})();
