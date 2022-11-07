import './app.scss';

document.addEventListener("DOMContentLoaded", function (event) {

    const ut_toggle = document.querySelectorAll('.ut-toggle');

    ut_toggle.forEach(function (element) {
        element.addEventListener('click', function () {

            const prev_value = element.dataset.active;

            if (prev_value === '1') {
                element.dataset.active = '0';
            } else {
                element.dataset.active = '1';
            }

            updateUserStatus(element).then((e) => {
                element.dataset.active = e.can_login;
            }, (e) => {
                element.dataset.active = prev_value;
            });

        })


    })

});

function updateUserStatus(element) {
    return fetch(wpApiSettings.root + wpApiSettings.versionString + "users/" + element.dataset.userId, {
        method: "POST",
        headers: {
            "X-WP-Nonce": wpApiSettings.nonce,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            can_login: element.dataset.active,
        }),
    })
        .then((response) => {

            if ( ! response.ok ) {
                throw new Error(response.status + ': ' + response.statusText);
            }

            return response.json()
        })
        .catch(result => {
            return Promise.reject(result);
        });
}