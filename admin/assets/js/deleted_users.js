/* =====================================================
   CAMPUSXCHANGE
   DELETED USERS PAGE JAVASCRIPT
===================================================== */

document.addEventListener('DOMContentLoaded', function () {


    /* =================================================
       ELEMENTS
    ================================================= */

    const restoreModal =
        document.getElementById('restoreModal');

    const restoreForm =
        document.getElementById('restoreForm');

    const restoreUserId =
        document.getElementById('restoreUserId');

    const restoreUserName =
        document.getElementById('restoreUserName');

    const restoreButtons =
        document.querySelectorAll('.deleted-restore-btn');

    const closeRestoreButtons =
        document.querySelectorAll('[data-close-restore]');


    /* =================================================
       RESTORE MODAL
    ================================================= */

    function openRestoreModal(userId, userName) {

        if (!restoreModal) {
            return;
        }


        if (restoreUserId) {

            restoreUserId.value = userId;

        }


        if (restoreUserName) {

            restoreUserName.textContent = userName;

        }


        restoreModal.classList.add('show');

        restoreModal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.classList.add(
            'modal-open'
        );


        const closeButton =
            restoreModal.querySelector(
                '.deleted-modal-close'
            );


        if (closeButton) {

            setTimeout(function () {

                closeButton.focus();

            }, 100);

        }

    }


    /* =================================================
       CLOSE RESTORE MODAL
    ================================================= */

    function closeRestoreModal() {

        if (!restoreModal) {
            return;
        }


        restoreModal.classList.remove('show');

        restoreModal.setAttribute(
            'aria-hidden',
            'true'
        );


        document.body.classList.remove(
            'modal-open'
        );


        if (restoreUserId) {

            restoreUserId.value = '';

        }


        if (restoreUserName) {

            restoreUserName.textContent = 'User';

        }

    }


    /* =================================================
       RESTORE BUTTONS
    ================================================= */

    restoreButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const userId =
                    button.getAttribute(
                        'data-user-id'
                    );

                const userName =
                    button.getAttribute(
                        'data-user-name'
                    );


                if (!userId) {
                    return;
                }


                openRestoreModal(
                    userId,
                    userName || 'User'
                );

            }
        );

    });


    /* =================================================
       CLOSE BUTTONS
    ================================================= */

    closeRestoreButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                closeRestoreModal();

            }
        );

    });


    /* =================================================
       ESCAPE KEY
    ================================================= */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }


            if (
                restoreModal &&
                restoreModal.classList.contains('show')
            ) {

                closeRestoreModal();

            }

        }
    );


    /* =================================================
       RESTORE FORM
    ================================================= */

    if (restoreForm) {

        restoreForm.addEventListener(
            'submit',
            function () {

                const button =
                    restoreForm.querySelector(
                        '.deleted-confirm-restore-btn'
                    );


                if (button) {

                    button.disabled = true;

                    button.textContent =
                        'Restoring...';

                }

            }
        );

    }


    /* =================================================
       AUTO HIDE MESSAGE
    ================================================= */

    const message =
        document.querySelector('.deleted-message');


    if (message) {

        setTimeout(function () {

            message.style.opacity = '0';

            message.style.transform =
                'translateY(-4px)';


            setTimeout(function () {

                if (message.parentNode) {

                    message.remove();

                }

            }, 300);

        }, 5000);

    }

});