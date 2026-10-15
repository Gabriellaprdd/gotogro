document.addEventListener("DOMContentLoaded", function () {
    const mobileToggleBtn = document.getElementById("mobileToggleBtn");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function closeSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.remove("active");
            sidebarOverlay.classList.remove("active");
        }
    }

    function openSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.add("active");
            sidebarOverlay.classList.add("active");
        }
    }

    if (mobileToggleBtn && sidebar && sidebarOverlay) {
        mobileToggleBtn.addEventListener("click", () => {
            if (sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarOverlay.addEventListener("click", closeSidebar);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && sidebar.classList.contains("active")) {
                closeSidebar();
            }
        });
    }

    const notification = document.getElementById('notification');
    if (notification && notification.innerHTML.trim() !== '') {
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease';
            notification.style.opacity = '0';
        }, 3500);

        setTimeout(() => {
            notification.style.display = 'none';
        }, 4000);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const selectedTable = urlParams.get("table");

    if (selectedTable) {
        const tableSelect = document.getElementById("table");

        if (tableSelect) {
            tableSelect.value = selectedTable;
            updateCheckboxes();
        }
    }

    setupDateConstraints();
    init();
});

function setupDateConstraints() {
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");

    if (!startDate || !endDate) return;

    const today = new Date().toISOString().split("T")[0];

    startDate.setAttribute("max", today);
    endDate.setAttribute("max", today);

    startDate.addEventListener("change", function () {
        if (startDate.value) {
            endDate.setAttribute("min", startDate.value);
        }
    });

    endDate.addEventListener("change", function () {
        if (endDate.value) {
            startDate.setAttribute("max", endDate.value);
        }
    });
}

function init() {
    const form = document.getElementById("reportForm");
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");
    const checkboxesContainer = document.getElementById("checkboxes");

    if (!form) return;

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        let isValid = validateForm(
            startDate,
            endDate,
            checkboxesContainer
        );

        if (isValid) {
            submitForm(form);
        }
    });
}

const tableColumns = {
    Members: [
        "First Name",
        "Last Name",
        "Date of Birth",
        "Gender",
        "Email",
        "Phone Number",
        "Address",
        "Registration Date",
    ],
    Products: [
        "Product Name",
        "Product Price",
        "Category",
        "Inventory Quantity",
        "Last Restock Date",
    ],
    Sales: [
        "Transaction ID",
        "Member ID",
        "Total Price",
        "Payment Method",
        "Date of Transaction",
    ],
};

function updateCheckboxes() {
    const tableSelect = document.getElementById("table");

    if (!tableSelect) return;

    const table = tableSelect.value;
    const checkboxesDiv = document.getElementById("checkboxes");

    checkboxesDiv.innerHTML = "";

    if (tableColumns[table]) {
        checkboxesDiv.style.display = "flex";

        tableColumns[table].forEach((column, index) => {
            const checkboxItem = document.createElement("div");
            const checkboxLabel = document.createElement("label");
            const checkbox = document.createElement("input");

            checkboxItem.classList.add("checkbox-item");
            checkboxItem.style.animationDelay = `${index * 0.05}s`;

            checkbox.type = "checkbox";
            checkbox.name = "columns[]";
            checkbox.value = column;

            checkboxLabel.appendChild(checkbox);
            checkboxLabel.appendChild(
                document.createTextNode(column)
            );

            checkboxItem.appendChild(checkboxLabel);
            checkboxesDiv.appendChild(checkboxItem);
        });
    } else {
        checkboxesDiv.style.display = "none";
    }
}

function validateForm(startDate, endDate, checkboxesContainer) {
    let isValid = true;

    if (!startDate.value || !endDate.value) {
        showAlert(
            "Please enter both start and end dates.",
            "error"
        );

        isValid = false;
    } else {
        const currentDate = new Date();
        const selectedStartDate = new Date(startDate.value);
        const selectedEndDate = new Date(endDate.value);

        currentDate.setHours(23, 59, 59, 999);

        if (selectedEndDate < selectedStartDate) {
            showAlert(
                "End Date cannot be earlier than Start Date.",
                "error"
            );

            isValid = false;
        } else if (selectedEndDate > currentDate) {
            showAlert(
                "End Date must not be later than the current date.",
                "error"
            );

            isValid = false;
        }
    }

    if (!isValid) return false;

    const checkboxes = checkboxesContainer.querySelectorAll(
        "input[type='checkbox']"
    );

    const isColumnSelected = Array.from(checkboxes).some(
        (checkbox) => checkbox.checked
    );

    if (!isColumnSelected) {
        showAlert(
            "Please select at least one column.",
            "error"
        );

        isValid = false;
    }

    return isValid;
}

function downloadCSV(data, filename = "report.csv") {
    const blob = new Blob([data], {
        type: "text/csv",
    });

    const link = document.createElement("a");

    link.href = URL.createObjectURL(blob);
    link.download = filename;

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function submitForm(form) {
    const formData = new FormData(form);

    fetch("php/export.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            const contentType = response.headers.get("Content-Type");

            if (
                contentType &&
                contentType.includes("application/json")
            ) {
                return response.json();
            }

            return response.text();
        })
        .then((data) => {
            const isJson = data && typeof data === "object";

            if (isJson) {
                if (data.status === "error") {
                    showAlert(data.message, "error");
                } else if (data.status === "info") {
                    showAlert(data.message, "info");
                }
            } else {
                downloadCSV(data);
                showAlert(
                    "Report generated successfully.",
                    "success"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);

            showAlert(
                "An error occurred. Please try again.",
                "error"
            );
        });
}

let notificationTimeout;

function showAlert(message, type = "error") {
    const notification = document.getElementById("notification");

    if (!notification) return;

    clearTimeout(notificationTimeout);

    let icon =
        '<i class="fa-solid fa-circle-exclamation"></i>';

    if (type === "success") {
        icon = '<i class="fa-solid fa-circle-check"></i>';
    } else if (type === "info") {
        icon = '<i class="fa-solid fa-circle-info"></i>';
    }

    notification.className = `notification ${type}`;
    notification.innerHTML = `${icon} <span>${message}</span>`;
    notification.style.display = "flex";
    notification.style.opacity = "1";

    notificationTimeout = setTimeout(() => {
        notification.style.opacity = "0";

        setTimeout(() => {
            notification.style.display = "none";
        }, 500);
    }, 3000);
}