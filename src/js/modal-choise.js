document.addEventListener("DOMContentLoaded", function () {
    document.body.style.overflow = "hidden";

    const overlay = document.getElementById('overlay');
    const historyBtn = document.getElementById('history-btn');
    const schoolBtn = document.getElementById('school-btn'); // Кнопка для навчання в автошколі

    // Обробники подій для кнопок
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener("click", function (event) {
            // Закриваємо модалку
            overlay.style.display = "none";
            document.body.style.overflow = "auto";

            if (event.target === historyBtn) {
                const targetElement = document.getElementById("history");

                const headerHeight = document.querySelector('header') ? document.querySelector('header').offsetHeight : 0;

                window.scrollTo({
                    top: targetElement.offsetTop - headerHeight,
                    behavior: "smooth"
                });
            }

            if (event.target === schoolBtn) {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            }
        });
    });
});