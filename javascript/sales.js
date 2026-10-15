function toggleMobileMenu() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (sidebar && overlay) {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar && sidebar.classList.contains("active")) {
            sidebar.classList.remove("active");
            if (overlay) overlay.classList.remove("active");
        }
    });

    const notification = document.getElementById("notification");
    const memberIDField = document.getElementById("memberID");
    const transactionDateField = document.getElementById("transactionDate");
    const salesForm = document.getElementById("salesForm");

    if (notification && notification.innerText.trim() !== "") {
        setTimeout(function () {
            notification.style.transition = "opacity 0.5s ease";
            notification.style.opacity = "0";
        }, 3500);

        setTimeout(function () {
            notification.style.display = "none";
        }, 4000);
    }

    if (memberIDField) {
        memberIDField.addEventListener("input", function () {
            let value = memberIDField.value;

            if (!value.startsWith("M")) {
                value = "M" + value.replace(/[^0-9]/g, "");
            } else {
                value =
                    "M" +
                    value
                        .substring(1)
                        .replace(/[^0-9]/g, "");
            }

            memberIDField.value = value;
        });

        memberIDField.addEventListener("keydown", function (event) {
            if (
                (event.key === "Backspace" ||
                    event.key === "Delete") &&
                memberIDField.selectionStart <= 1
            ) {
                event.preventDefault();
            }
        });
    }

    if (transactionDateField) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(
            2,
            "0"
        );
        const day = String(today.getDate()).padStart(2, "0");

        transactionDateField.value =
            `${year}-${month}-${day}`;
    }

    if (salesForm) {
        salesForm.addEventListener("reset", function () {
            const savedDate = transactionDateField.value;

            setTimeout(function () {
                transactionDateField.value = savedDate;
                memberIDField.value = "M";
                resetProductFields();
            }, 0);
        });
    }
});

function generateProductFields() {
    const numProductsInput =
        document.getElementById("numProducts");
    const numProducts = parseInt(numProductsInput.value, 10);
    const productFields =
        document.getElementById("productFields");

    productFields.innerHTML = "";

    if (isNaN(numProducts) || numProducts < 1) {
        alert(
            "Please enter a valid number of products (minimum 1)."
        );
        return;
    }

    if (numProducts > 50) {
        alert(
            "Maximum limit is 50 products per transaction."
        );
        return;
    }

    for (let i = 1; i <= numProducts; i++) {
        const productCard = document.createElement("div");

        productCard.className = "product-entry";

        productCard.innerHTML = `
            <h3>Product ${i}</h3>
            <div class="product-row">
                <div class="input-group">
                    <label for="productID${i}">Product ID</label>
                    <input type="text" name="productID${i}" id="productID${i}" placeholder="e.g. F1" required oninput="sanitizeProductID(this)">
                </div>
                <div class="input-group">
                    <label for="quantity${i}">Quantity</label>
                    <input type="number" name="quantity${i}" id="quantity${i}" placeholder="Qty" required min="1" value="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
            </div>
        `;

        productFields.appendChild(productCard);
    }
}

function sanitizeProductID(input) {
    input.value = input.value
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, "");
}

function resetProductFields() {
    const productFields =
        document.getElementById("productFields");

    if (productFields) {
        productFields.innerHTML = "";
    }
}

function prepareMemberID() {
    const memberIDField =
        document.getElementById("memberID");

    if (
        memberIDField &&
        memberIDField.value.startsWith("M")
    ) {
    }
}