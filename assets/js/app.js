document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form.js-confirm');
    var i;

    for (i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function (event) {
            var message = this.getAttribute('data-confirm');

            if (!message) {
                message = 'Soll dieser Eintrag wirklich gelöscht werden?';
            }

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    }
});
