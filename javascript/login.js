document.addEventListener("DOMContentLoaded", function () {
    const notification = document.querySelector(
        '.notification, .errors, .error-message'
    );
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const notificationText = document.getElementById('notificationText');
    const bgImg = document.getElementById('bgImg');

    if (bgImg) {
        const img = new Image();
        img.src = 'styles/images/background.jpg';
        if (img.complete) {
            bgImg.classList.add('loaded');
        } else {
            img.onload = function () {
                bgImg.classList.add('loaded');
            };
        }
    }

    let lockoutTime = parseInt(
        loginForm?.getAttribute('data-lockout-time') || '0',
        10
    );

    if (lockoutTime > 0) {
        const countdownInterval = setInterval(function () {
            lockoutTime--;

            if (notificationText) {
                const minutes = Math.floor(lockoutTime / 60);
                const seconds = lockoutTime % 60;

                notificationText.innerText =
                    `Please wait ${minutes} minute(s) and ${seconds} second(s) before trying again.`;
            }

            if (lockoutTime <= 0) {
                clearInterval(countdownInterval);

                if (loginBtn) {
                    loginBtn.disabled = false;
                    loginBtn.innerText = "Log In";
                }

                if (emailInput) {
                    emailInput.disabled = false;
                }

                if (passwordInput) {
                    passwordInput.disabled = false;
                }

                if (notification) {
                    notification.style.opacity = '0';

                    setTimeout(function () {
                        notification.style.display = 'none';
                    }, 500);
                }
            }
        }, 1000);
    } else if (notification) {
        setTimeout(function () {
            notification.style.opacity = '0';
        }, 3500);

        setTimeout(function () {
            notification.style.display = 'none';
        }, 4000);
    }
});

function togglePasswordVisibility(fieldId, icon) {
    const inputField = document.getElementById(fieldId);

    if (inputField && !inputField.disabled) {
        if (inputField.type === 'password') {
            inputField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            inputField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}