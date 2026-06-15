/**
 * Surtilec — tabla de contenidos del artículo (vanilla, sin jQuery).
 * Genera la TOC desde los h2/h3 del cuerpo, les añade id, y resalta la
 * sección activa al hacer scroll (IntersectionObserver). Respeta el header
 * sticky vía scroll-margin-top en CSS.
 */
(function () {
  var root = document.querySelector('[data-toc-root]');
  var nav = document.querySelector('[data-toc]');
  if (!root || !nav) {
    return;
  }
  var list = nav.querySelector('.su-toc-list');
  var heads = root.querySelectorAll('h2, h3');
  if (!heads.length || !list) {
    return;
  }

  var items = [];
  Array.prototype.forEach.call(heads, function (h, i) {
    if (!h.id) {
      var slug = (h.textContent || 'sec')
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .slice(0, 60) || ('sec-' + i);
      var id = slug;
      var n = 1;
      while (document.getElementById(id)) {
        id = slug + '-' + n++;
      }
      h.id = id;
    }
    var li = document.createElement('li');
    li.className = 'su-toc-item lvl-' + h.tagName.toLowerCase();
    var a = document.createElement('a');
    a.href = '#' + h.id;
    a.textContent = h.textContent;
    li.appendChild(a);
    list.appendChild(li);
    items.push({ h: h, a: a });
  });

  nav.hidden = false;

  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            items.forEach(function (it) {
              it.a.classList.toggle('is-active', it.h === e.target);
            });
          }
        });
      },
      { rootMargin: '-90px 0px -70% 0px', threshold: 0 }
    );
    items.forEach(function (it) {
      obs.observe(it.h);
    });
  }
})();
