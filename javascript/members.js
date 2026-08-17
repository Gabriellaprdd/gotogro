let originalValues = {};

function openModal(modalElement) {
    modalElement.classList.remove("closing");
    modalElement.style.display = "flex";
}

function closeModal(modalElement) {
    modalElement.classList.add("closing");

    setTimeout(() => {
        modalElement.style.display = "none";
        modalElement.classList.remove("closing");
    }, 250);
}

function openUpdateForm(
    memberID,
    fname,
    lname,
    dob,
    gender,
    address,
    email,
    phone,
    regDate
) {
    let phNumberOnly = phone.replace(/^\+61-?/, "");

    originalValues = {
        memberID: memberID,
        fname: fname,
        lname: lname,
        dob: dob,
        gender: gender,
        address: address,
        email: email,
        phone: phNumberOnly,
        regDate: regDate,
    };

    document.getElementById("update_member_id").value = memberID;
    document.getElementById("update_fname").value = fname;
    document.getElementById("update_lname").value = lname;
    document.getElementById("update_dob").value = dob;
    document.getElementById("update_email").value = email;
    document.getElementById("update_ph").value = phNumberOnly;
    document.getElementById("update_addy").value = address;
    document.getElementById("update_membership_start").value = regDate;

    if (gender === "male") {
        document.getElementById("update_male").checked = true;
    } else if (gender === "female") {
        document.getElementById("update_female").checked = true;
    } else {
        document.getElementById("update_nonbinary").checked = true;
    }

    openModal(document.getElementById("updateForm"));
}

function resetForm() {
    document.getElementById("update_member_id").value = originalValues.memberID;
    document.getElementById("update_fname").value = originalValues.fname;
    document.getElementById("update_lname").value = originalValues.lname;
    document.getElementById("update_dob").value = originalValues.dob;
    document.getElementById("update_email").value = originalValues.email;
    document.getElementById("update_ph").value = originalValues.phone;
    document.getElementById("update_addy").value = originalValues.address;
    document.getElementById("update_membership_start").value = originalValues.regDate;

    if (originalValues.gender === "male") {
        document.getElementById("update_male").checked = true;
    } else if (originalValues.gender === "female") {
        document.getElementById("update_female").checked = true;
    } else {
        document.getElementById("update_nonbinary").checked = true;
    }
}

function showNotification(message, type = "error") {
    let notification = document.getElementById("notification");

    if (!notification) {
        notification = document.createElement("div");
        notification.id = "notification";
        document.body.appendChild(notification);
    }

    const iconClass = type === "success"
        ? "fa-circle-check"
        : "fa-circle-exclamation";

    notification.className = `notification ${type}`;
    notification.innerHTML = `<i class="fa-solid ${iconClass}"></i> <span>${message}</span>`;
    notification.style.display = "flex";
    notification.style.opacity = "1";

    setTimeout(() => {
        notification.style.opacity = "0";

        setTimeout(() => {
            notification.style.display = "none";
        }, 400);
    }, 3500);
}

document.addEventListener("DOMContentLoaded", function () {
    const mobileToggleBtn = document.getElementById("mobileToggleBtn");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const regForm = document.getElementById("regForm");
    const addMember = document.getElementById("addMember");
    const closeRegForm = document.getElementById("closeRegForm");
    const updateForm = document.getElementById("updateForm");
    const closeUpdateForm = document.getElementById("closeUpdateForm");
    const deleteForm = document.getElementById("deleteForm");
    const membershipDate = document.getElementById("membership_start");
    const searchType = document.getElementById("searchType");
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.querySelector(".search-button");
    const tableRows = document.querySelectorAll("tbody tr");
    const existingNotification = document.querySelector(".notification");

    function closeSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.remove("active");
            sidebarOverlay.classList.remove("active");
        }
    }

    if (mobileToggleBtn && sidebar && sidebarOverlay) {
        mobileToggleBtn.addEventListener("click", () => {
            if (sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                sidebar.classList.add("active");
                sidebarOverlay.classList.add("active");
            }
        });

        sidebarOverlay.addEventListener("click", closeSidebar);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && sidebar.classList.contains("active")) {
                closeSidebar();
            }
        });
    }

    tableRows.forEach((row, index) => {
        const delay = Math.min(index * 0.05, 0.5);
        row.style.animationDelay = `${delay}s`;
    });

    if (existingNotification) {
        setTimeout(() => {
            existingNotification.style.opacity = "0";

            setTimeout(() => {
                existingNotification.style.display = "none";
            }, 500);
        }, 3500);
    }

    if (addMember) {
        addMember.addEventListener("click", () => openModal(regForm));
    }

    if (closeRegForm) {
        closeRegForm.addEventListener("click", () => closeModal(regForm));
    }

    if (closeUpdateForm) {
        closeUpdateForm.addEventListener("click", () => closeModal(updateForm));
    }

    window.openDeleteMemberModal = function (memberID, name) {
        document.getElementById("delete_member_id").value = memberID;
        document.getElementById("deleteMessage").textContent =
            "Are you sure you want to delete " +
            name +
            " (M" +
            memberID +
            ")? This action cannot be undone.";

        openModal(document.getElementById("deleteForm"));
    };

    const closeDeleteBtn = document.getElementById("closeDeleteForm");
    const cancelDeleteBtn = document.getElementById("cancelDeleteButton");

    if (closeDeleteBtn) {
        closeDeleteBtn.addEventListener("click", () => closeModal(deleteForm));
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener("click", () => closeModal(deleteForm));
    }

    window.addEventListener("click", function (event) {
        if (event.target === regForm) {
            closeModal(regForm);
        }

        if (event.target === updateForm) {
            closeModal(updateForm);
        }

        if (event.target === deleteForm) {
            closeModal(deleteForm);
        }
    });

    const textFields = document.querySelectorAll(
        "#fname, #lname, #addy, #update_addy"
    );

    textFields.forEach(function (field) {
        field.addEventListener("input", function (event) {
            const inputField = event.target;
            const inputValue = inputField.value;

            if (inputValue.length > 0) {
                inputField.value =
                    inputValue.charAt(0).toUpperCase() +
                    inputValue.slice(1);
            }
        });
    });

    if (membershipDate) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");

        membershipDate.value = `${year}-${month}-${day}`;
    }

    const regFormElement = regForm.querySelector("form");

    if (regFormElement) {
        regFormElement.addEventListener("submit", function (event) {
            event.preventDefault();

            const fname = document.getElementById("fname").value.trim();
            const lname = document.getElementById("lname").value.trim();
            const dob = document.getElementById("dob").value.trim();
            const gender = document.querySelector('input[name="gender"]:checked');
            const email = document.getElementById("email").value.trim();
            const phone = document.getElementById("ph").value.trim();
            const address = document.getElementById("addy").value.trim();
            let messages = [];

            const nameRegex = /^[A-Za-z]{1,30}$/;
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^\d{8}$/;
            const addressRegex = /^[A-Za-z0-9\s,.\-\/]*$/;

            if (!nameRegex.test(fname)) {
                messages.push("First name must contain only letters and be up to 30 characters.");
            }

            if (!nameRegex.test(lname)) {
                messages.push("Last name must contain only letters and be up to 30 characters.");
            }

            if (dateRegex.test(dob)) {
                const birthDate = new Date(dob + "T00:00:00");
                const today = new Date();

                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (
                    monthDiff < 0 ||
                    (monthDiff === 0 && today.getDate() < birthDate.getDate())
                ) {
                    age--;
                }

                if (age < 18 || age > 80) {
                    messages.push("Member age must be between 18 and 80.");
                }
            } else {
                messages.push("Please enter a valid date of birth.");
            }

            if (!gender) {
                messages.push("Please select a gender.");
            }

            if (!emailRegex.test(email)) {
                messages.push("Please enter a valid email address.");
            }

            if (!phoneRegex.test(phone)) {
                messages.push("Phone number must be exactly 8 digits after +61.");
            }

            if (!addressRegex.test(address)) {
                messages.push("Address can only contain letters, numbers, spaces, commas, slashes, and dots.");
            }

            if (messages.length === 0) {
                regFormElement.submit();
            } else {
                showNotification(messages[0], "error");
            }
        });
    }

    const updateFormElement = updateForm.querySelector("form");

    if (updateFormElement) {
        updateFormElement.addEventListener("submit", function (event) {
            event.preventDefault();

            const updateEmail = document.getElementById("update_email").value.trim();
            const updatePhone = document.getElementById("update_ph").value.trim();
            const updateAddress = document.getElementById("update_addy").value.trim();
            let upMessages = [];

            const updateEmailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const updatePhoneRegex = /^\d{8}$/;
            const updateAddressRegex = /^[A-Za-z0-9\s,.\-\/]*$/;

            if (!updateEmailRegex.test(updateEmail)) {
                upMessages.push("Please enter a valid email address.");
            }

            if (!updatePhoneRegex.test(updatePhone)) {
                upMessages.push("Phone number must be exactly 8 digits after +61.");
            }

            if (!updateAddressRegex.test(updateAddress)) {
                upMessages.push("Address can only contain letters, numbers, spaces, commas, slashes, and dots.");
            }

            if (upMessages.length === 0) {
                updateFormElement.submit();
            } else {
                showNotification(upMessages[0], "error");
            }
        });
    }

    function searchMember() {
        const searchTypeValue = searchType.value;
        const searchTerm = searchInput.value.trim().toLowerCase();

        if (!searchTypeValue) {
            showNotification(
                "Please select a search category (ID or Name).",
                "error"
            );
            return;
        }

        if (!searchTerm) {
            showNotification("Please enter a search term.", "error");
            return;
        }

        let found = false;

        tableRows.forEach((row) => {
            row.style.transition = "background-color 0.4s ease";
            row.style.backgroundColor = "";

            if (row.cells.length < 2) {
                return;
            }

            const memberID = row.cells[0].innerText.toLowerCase();
            const memberName = row.cells[1].innerText.toLowerCase();

            if (
                (searchTypeValue === "id" && memberID.includes(searchTerm)) ||
                (searchTypeValue === "name" && memberName.includes(searchTerm))
            ) {
                found = true;

                row.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });

                row.style.backgroundColor = "#dcfce7";
                row.style.transform = "scale(1.01)";
                row.style.boxShadow = "0 6px 16px rgba(22, 163, 74, 0.1)";

                setTimeout(() => {
                    row.style.transition = "all 1.5s ease-out";
                    row.style.backgroundColor = "";
                    row.style.transform = "";
                    row.style.boxShadow = "";
                }, 2500);
            }
        });

        if (!found) {
            showNotification("Member not found.", "error");
        }
    }

    if (searchButton) {
        searchButton.addEventListener("click", searchMember);
    }

    if (searchInput) {
        if (searchType) {
            searchType.addEventListener("change", function () {
                searchInput.value = "";
                searchInput.focus();
            });
        }

        searchInput.addEventListener("input", function () {
            const searchTypeValue = searchType.value;

            if (searchTypeValue === "id") {
                this.value = this.value.replace(/[^A-Za-z0-9]/g, "");
            } else if (searchTypeValue === "name") {
                this.value = this.value.replace(/[^A-Za-z\s]/g, "");
            } else {
                this.value = this.value.replace(/[^A-Za-z0-9\s]/g, "");
            }
        });

        searchInput.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                searchMember();
            }
        });
    }
});