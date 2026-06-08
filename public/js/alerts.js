document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-auto-dismiss-alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.classList.add('is-dismissing');
            setTimeout(function () {
                alertEl.remove();
            }, 250);
        }, 4000);
    });
});
