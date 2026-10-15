var searchBar = document.getElementById("searchBar");
var addForm = document.getElementById("addForm");
var updateForm = document.getElementById("updateForm");
var viewForm = document.getElementById("viewForm");
var deleteForm = document.getElementById("deleteForm");
var AddFormButton = document.getElementById("AddButton");
var closeAddForm = document.getElementById("closeAddForm");
var closeUpdateForm = document.getElementById("closeUpdateForm");
var closeViewForm = document.getElementById("closeViewForm");
var cancelButton = document.getElementById("cancelButton");

if (AddFormButton) {
    AddFormButton.onclick = function () {
        addForm.style.display = "flex";
    };
}

if (closeAddForm) {
    closeAddForm.onclick = function () {
        addForm.style.display = "none";
    };
}

if (closeUpdateForm) {
    closeUpdateForm.onclick = function () {
        updateForm.style.display = "none";
    };
}

if (closeViewForm) {
    closeViewForm.onclick = function () {
        viewForm.style.display = "none";
    };
}

if (cancelButton) {
    cancelButton.onclick = function () {
        deleteForm.style.display = "none";
    };
}

if (searchBar) {
    searchBar.addEventListener("input", function () {
        var searchValue = searchBar.value.toLowerCase();
        var productCards = document.getElementsByClassName("product-card");

        for (var i = 0; i < productCards.length; i++) {
            var productCard = productCards[i];
            var productName = productCard
                .getElementsByTagName("h3")[0]
                .innerText.toLowerCase();

            var productID = productCard
                .getElementsByTagName("p")[0]
                .innerText.toLowerCase();

            productID = productID.replace("product id: ", "").trim();

            if (
                productName.startsWith(searchValue) ||
                productID.startsWith(searchValue)
            ) {
                productCard.style.display = "block";
            } else {
                productCard.style.display = "none";
            }
        }
    });
}

let originalData = {};

function openUpdateForm(id, name, price, category, quantity, restockDate) {
    originalData = {
        id: id,
        name: name,
        price: price,
        category: category,
        quantity: quantity,
        restockDate: restockDate,
    };

    document.getElementById("update_product_id").value = id;
    document.getElementById("update_product_name").value = name;
    document.getElementById("update_inv_qty").value = quantity;
    document.getElementById("update_restock_date").value = restockDate;

    document.getElementById("updateFormTitle").innerText = `Update ${name}`;

    updateForm.style.display = "flex";
}

function resetForm() {
    document.getElementById("update_product_id").value = originalData.id;
    document.getElementById("update_product_name").value = originalData.name;
    document.getElementById("update_inv_qty").value = originalData.quantity;
    document.getElementById("update_restock_date").value = originalData.restockDate;

    document.getElementById("updateFormTitle").innerText =
        `Update ${originalData.name}`;
}

function openViewForm(name, updatedQuantity, restockDate) {
    document.getElementById("restockDateValue").innerText = restockDate;
    document.getElementById("stockValue").innerText = updatedQuantity;
    document.getElementById("viewFormTitle").innerText = `${name} Details`;

    viewForm.style.display = "flex";
}

function openDeleteForm(id, name) {
    document.getElementById("delete_product_id").value = id;
    document.getElementById("deleteFormTitle").innerText = `Delete ${name}`;

    deleteForm.style.display = "flex";
}

const addFormElement = document.getElementById("addForm");
if (addFormElement) {
    addFormElement.addEventListener("reset", function () {
        var restockDateField = document.getElementById("last_restock_date");
        var savedRestockDate = restockDateField.value;

        setTimeout(function () {
            restockDateField.value = savedRestockDate;
        }, 0);
    });
}

const filterButton = document.getElementById("filterButton");
const filterSidebar = document.getElementById("filterSidebar");
const closeSidebar = document.getElementById("closeSidebar");

if (filterButton && filterSidebar) {
    filterButton.addEventListener("click", () => {
        filterSidebar.classList.add("sidebar-active");
    });
}

if (closeSidebar && filterSidebar) {
    closeSidebar.addEventListener("click", () => {
        filterSidebar.classList.remove("sidebar-active");
    });
}

function toggleCategoryCheckboxes() {
    const selectAllCheckbox = document.getElementById("selectAllCategories");
    const categoryCheckboxes = document.querySelectorAll(
        'input[name="category[]"]'
    );

    categoryCheckboxes.forEach((checkbox) => {
        checkbox.checked = selectAllCheckbox.checked;
    });
}

function clearFilters() {
    const selectAll = document.getElementById("selectAllCategories");
    if (selectAll) selectAll.checked = false;

    const categoryCheckboxes = document.querySelectorAll(
        'input[name="category[]"]'
    );

    categoryCheckboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });

    const stockOptions = document.querySelectorAll(
        'input[name="stock_status[]"]'
    );

    stockOptions.forEach((checkbox) => {
        checkbox.checked = false;
    });
}

window.onload = function () {
    const lastRestockDateInput = document.getElementById("last_restock_date");
    if (lastRestockDateInput) {
        const today = new Date();

        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");

        lastRestockDateInput.value = `${year}-${month}-${day}`;
    }
};

document.addEventListener("DOMContentLoaded", function () {
    const mobileToggleBtn = document.getElementById("mobileToggleBtn");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const notification = document.querySelector(".notification");
    const errorMessage = document.querySelector(".error-message");
    const categorySelect = document.getElementById("category");

    const formFields = [
        "product_name",
        "product_price",
        "inv_qty",
        "last_restock_date",
    ].map((id) => document.getElementById(id));

    if (mobileToggleBtn && sidebar && sidebarOverlay) {
        mobileToggleBtn.addEventListener("click", () => {
            sidebar.classList.add("active");
            sidebarOverlay.classList.add("active");
        });

        sidebarOverlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            sidebarOverlay.classList.remove("active");
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && sidebar.classList.contains("active")) {
                sidebar.classList.remove("active");
                sidebarOverlay.classList.remove("active");
            }
        });
    }

    function fadeOut(element) {
        if (!element) return;
        setTimeout(() => {
            element.style.opacity = "0";
        }, 3000);

        setTimeout(() => {
            element.style.display = "none";
        }, 3500);
    }

    if (notification) {
        fadeOut(notification);
    }

    if (errorMessage) {
        fadeOut(errorMessage);
    }

    function toggleFormFields(enabled) {
        formFields.forEach((field) => {
            if (field) field.disabled = !enabled;
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener("change", function () {
            toggleFormFields(this.value !== "");
        });
    }
});

window.onclick = function (event) {
    if (event.target === addForm) {
        addForm.style.display = "none";
    }

    if (event.target === updateForm) {
        updateForm.style.display = "none";
    }

    if (event.target === deleteForm) {
        deleteForm.style.display = "none";
    }

    if (event.target === viewForm) {
        viewForm.style.display = "none";
    }
};