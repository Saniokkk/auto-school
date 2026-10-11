// Посилання на інший філіал записані як data-href, а не href, — щоб Parcel
// не намагався забандлити їх як окрему сторінку під час збірки (у нього є
// вбудована multi-page-логіка саме для <a href="*.html">, яка конфліктує з
// тим, що /skvyra.html збирається окремим `parcel build` викликом).
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('[data-href]').forEach(function (el) {
        el.setAttribute('href', el.getAttribute('data-href'));
    });
});
