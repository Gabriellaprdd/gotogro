document.addEventListener("DOMContentLoaded", function () {
    const notification = document.querySelector(".notification");
    const errorMessages = document.querySelector(".error-messages");
    const errors = document.querySelector(".errors");
    const form = document.querySelector(".signup-form");
    const bgImg = document.getElementById("bgImg");

    const profilePictureInput = document.getElementById("profile-picture");
    const profilePreview = document.getElementById("profile-preview");

    if (bgImg) {
        const img = new Image();
        img.src = "styles/images/background.jpg";
        if (img.complete) {
            bgImg.classList.add("loaded");
        } else {
            img.onload = function () {
                bgImg.classList.add("loaded");
            };
        }
    }

    if (!form) {
        return;
    }

    let currentPreviewUrl = null;

    if (profilePictureInput && profilePreview) {
        profilePictureInput.addEventListener("change", function () {
            const file = profilePictureInput.files[0];

            if (currentPreviewUrl) {
                URL.revokeObjectURL(currentPreviewUrl);
                currentPreviewUrl = null;
            }

            if (!file) {
                profilePreview.style.display = "none";
                profilePreview.src = "";
                return;
            }

            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp",
            ];

            const maximumFileSize = 2 * 1024 * 1024;

            if (!allowedTypes.includes(file.type)) {
                showToast(["Please select a JPG, PNG, or WebP image."], "error");
                profilePictureInput.value = "";
                profilePreview.style.display = "none";
                return;
            }

            if (file.size > maximumFileSize) {
                showToast(["The profile picture must be smaller than 2 MB."], "error");
                profilePictureInput.value = "";
                profilePreview.style.display = "none";
                return;
            }

            currentPreviewUrl = URL.createObjectURL(file);
            profilePreview.src = currentPreviewUrl;
            profilePreview.style.display = "block";
        });

        form.addEventListener("reset", function () {
            setTimeout(function () {
                if (currentPreviewUrl) {
                    URL.revokeObjectURL(currentPreviewUrl);
                    currentPreviewUrl = null;
                }

                profilePreview.src = "";
                profilePreview.style.display = "none";
            }, 0);
        });
    }

    function fadeOut(element) {
        if (!element) return;

        setTimeout(() => {
            element.style.opacity = "0";
        }, 3500);

        setTimeout(() => {
            element.style.display = "none";
        }, 4000);
    }

    function showToast(messages, type) {
        let toast = document.querySelector(".dynamic-toast");
        if (!toast) {
            toast = document.createElement("div");
            toast.className = "dynamic-toast";
            document.body.appendChild(toast);
        }

        if (type === "success") {
            toast.className = "notification success dynamic-toast";
            toast.innerHTML = `<i class='fa-solid fa-circle-check'></i> <span>${messages.join("<br>")}</span>`;
        } else {
            toast.className = "notification errors dynamic-toast";
            toast.innerHTML = `<i class='fa-solid fa-circle-exclamation'></i> <div>${messages.join("<br>")}</div>`;
        }

        toast.style.display = "flex";
        toast.style.opacity = "1";
        fadeOut(toast);
    }

    if (notification) {
        fadeOut(notification);
    }

    if (errors) {
        fadeOut(errors);
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const fname = document.getElementById("fname").value.trim();
        const lname = document.getElementById("lname").value.trim();
        const dob = document.getElementById("dob").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const address = document.getElementById("address").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm-password").value;

        let isValid = true;
        const messages = [];

        const nameRegex = /^[A-Za-z]{1,30}$/;
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^\d{8}$/;
        const addressRegex = /^[A-Za-z0-9\s,.\-\/]*$/;
        const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;

        if (!nameRegex.test(fname)) {
            messages.push("First name must contain only letters and be up to 30 characters.");
            isValid = false;
        }

        if (!nameRegex.test(lname)) {
            messages.push("Last name must contain only letters and be up to 30 characters.");
            isValid = false;
        }

        if (dateRegex.test(dob)) {
            const birthDate = new Date(dob + "T00:00:00");
            const today = new Date();

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 23 || age > 55) {
                messages.push("You must be between the age of 23 and 55 to register.");
                isValid = false;
            }
        } else {
            messages.push("Please enter a valid date.");
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

        if (!addressRegex.test(address)) {
            messages.push("Address can only contain letters, numbers, spaces, commas, slashes, and dots.");
            isValid = false;
        }

        if (!passwordRegex.test(password)) {
            messages.push("Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character.");
            isValid = false;
        }

        if (password !== confirmPassword) {
            messages.push("Passwords do not match.");
            isValid = false;
        }

        const profileFile = profilePictureInput?.files[0];

        if (!profileFile) {
            messages.push("Please upload a profile picture to proceed.");
            isValid = false;
        }

        if (!isValid) {
            showToast(messages, "error");
            return;
        }

        const submitBtn = form.querySelector(".signup-button");
        submitBtn.disabled = true;
        submitBtn.style.opacity = "0.7";

        const formData = new FormData(form);
        formData.append("is_ajax", "1");

        fetch(window.location.href, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";

            if (data.success) {
                showToast([data.message], "success");
                form.reset();
                if (currentPreviewUrl) {
                    URL.revokeObjectURL(currentPreviewUrl);
                    currentPreviewUrl = null;
                }
                profilePreview.src = "";
                profilePreview.style.display = "none";
            } else {
                showToast(data.errors, "error");
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";
            showToast(["An unexpected error occurred. Please try again."], "error");
        });
    });

    const textFields = document.querySelectorAll("#fname, #lname, #address");

    textFields.forEach(function (field) {
        field.addEventListener("input", function (event) {
            const inputField = event.target;
            const inputValue = inputField.value;

            if (inputValue.length > 0) {
                inputField.value = inputValue.charAt(0).toUpperCase() + inputValue.slice(1);
            }
        });
    });
});

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