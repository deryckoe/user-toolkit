document.addEventListener("DOMContentLoaded", function (event) {

    const ut_toggle = document.querySelectorAll('.ut-toggle');

    ut_toggle.forEach(function (element) {
        element.addEventListener('click', function () {

            if (element.dataset.active === '1') {
                element.dataset.active = '0';
            } else {
                element.dataset.active = '1';
            }

            updateUserStatus(element);

        })


    })

});

function updateUserStatus(element) {
    fetch(wpApiSettings.root + wpApiSettings.versionString + "users/" + element.dataset.userId, {
        method: "POST",
        headers: {
            "X-WP-Nonce": wpApiSettings.nonce,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            can_login: element.dataset.active,
        }),
    })
        .then(res => res.json())
        .then(result => {
            console.log(result);
        });
}