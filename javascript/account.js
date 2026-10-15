let initialProfilePicture = '';

document.addEventListener("DOMContentLoaded", function () {
    const mobileToggleBtn = document.getElementById('mobileToggleBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }
    }

    function openSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        }
    }

    if (mobileToggleBtn && sidebar && sidebarOverlay) {
        mobileToggleBtn.addEventListener('click', () => {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarOverlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
    }

    const notification = document.getElementById('notification');
    const errorMessages = document.getElementById('errorMessages');
    const form = document.getElementById('regform');
    const imgPreview = document.getElementById('imagePreview');

    if (imgPreview) {
        initialProfilePicture = imgPreview.src;
    }

    function fadeOut(element) {
        if (!element) return;

        setTimeout(() => {
            element.style.transition = 'opacity 0.5s ease';
            element.style.opacity = '0';
        }, 3500);

        setTimeout(() => {
            element.style.display = 'none';
        }, 4000);
    }

    function showToast(messages, type) {
        let toast = document.querySelector('.dynamic-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'dynamic-toast';
            document.body.appendChild(toast);
        }

        if (type === 'success') {
            toast.className = 'notification success dynamic-toast';
            toast.innerHTML = `<i class='fa-solid fa-circle-check'></i> <span>${messages.join('<br>')}</span>`;
        } else {
            toast.className = 'notification error dynamic-toast';
            toast.innerHTML = `<i class='fa-solid fa-circle-exclamation'></i> <div>${messages.join('<br>')}</div>`;
        }

        toast.style.display = 'flex';
        toast.style.opacity = '1';
        fadeOut(toast);
    }

    if (notification && notification.innerHTML.trim() !== '') {
        fadeOut(notification);
    }

    if (!form) return;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const address = document.getElementById('addy').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('ph').value.trim();
        const oldPassword = document.getElementById('password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        let isValid = true;
        let messages = [];

        const addressRegex = /^[A-Za-z0-9\s,\.\-\/]*$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^\d{8}$/;
        const newPasswordRegex =
            /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

        if (!addressRegex.test(address)) {
            messages.push("Address can only contain letters, numbers, spaces, commas, slashes, and dots.");
            isValid = false;
        }

        if (!emailRegex.test(email)) {
            messages.push("Please enter a valid email address.");
            isValid = false;
        }

        if (!phoneRegex.test(phone)) {
            messages.push("Phone number must be exactly 8 digits after +61.");
            isValid = false;
        }

        if (newPassword && !newPasswordRegex.test(newPassword)) {
            messages.push("Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character.");
            isValid = false;
        }

        if (newPassword !== confirmPassword) {
            messages.push("Passwords do not match.");
            isValid = false;
        }

        if (oldPassword && oldPassword === newPassword) {
            messages.push("The old password and the new password must be different.");
            isValid = false;
        }

        if (oldPassword) {
            const oldPasswordValid = await checkOldPassword(oldPassword);

            if (!oldPasswordValid) {
                messages.push("Old password is incorrect.");
                isValid = false;
            }
        }

        if (!isValid) {
            showToast(messages, 'error');
            return;
        }

        const submitBtn = form.querySelector('.update-button');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action || 'php/update_account.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            let result;
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                result = await response.json();
            } else {
                result = { success: true, message: "Profile updated successfully." };
            }

            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';

            if (result.success || result.status === 'success') {
                showToast([result.message || "Profile updated successfully."], "success");
                document.getElementById('password').value = '';
                document.getElementById('new-password').value = '';
                document.getElementById('confirm-password').value = '';
                const fnameVal = document.getElementById('fname').value;
                const lnameVal = document.getElementById('lname').value;
                const greeting = document.querySelector('.greeting');
                if (greeting) greeting.textContent = `Welcome, ${fnameVal}!`;
                const nameHeader = document.querySelector('.user-name');
                if (nameHeader) nameHeader.textContent = `${fnameVal} ${lnameVal}`;
                const topbarImg = document.querySelector('.profile-img');
                if (imgPreview && topbarImg) {
                    topbarImg.src = imgPreview.src;
                    initialProfilePicture = imgPreview.src;
                }
            } else {
                showToast(Array.isArray(result.errors) ? result.errors : [result.message || "Failed to update profile."], "error");
            }
        } catch (err) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            showToast(["An error occurred while updating profile."], "error");
        }
    });
});

async function checkOldPassword(oldPassword) {
    try {
        const response = await fetch('verify_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                password: oldPassword,
            }),
        });

        const result = await response.json();
        return result.isValid;
    } catch (error) {
        return false;
    }
}

function previewImage(event) {
    var reader = new FileReader();

    reader.onload = function () {
        var output = document.getElementById('imagePreview');
        output.src = reader.result;
    };

    if (event.target.files && event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

function resetFormAndImage() {
    const form = document.getElementById('regform');

    if (form) {
        form.reset();
    }

    const imgPreview = document.getElementById('imagePreview');

    if (imgPreview && initialProfilePicture) {
        imgPreview.src = initialProfilePicture;
    }
}

function togglePasswordVisibility(inputId, icon) {
    const input = document.getElementById(inputId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}