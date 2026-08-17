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

    const refreshBtn =
        document.querySelector(".refresh-button");

    if (refreshBtn) {
        refreshBtn.addEventListener(
            "click",
            refreshNotifications
        );
    }

    const clearBtn =
        document.querySelector(".clear-button");

    if (clearBtn) {
        clearBtn.addEventListener(
            "click",
            clearNotifications
        );
    }
});

function updateSidebarUrgentDot(hasNotifications) {
    const navIconContainer =
        document.querySelector(".nav-icon-container");

    let urgentDot =
        document.getElementById("sidebar-urgent-dot");

    if (hasNotifications) {
        if (!urgentDot && navIconContainer) {
            urgentDot = document.createElement("span");
            urgentDot.className = "urgent-dot";
            urgentDot.id = "sidebar-urgent-dot";

            navIconContainer.appendChild(urgentDot);
        } else if (urgentDot) {
            urgentDot.style.display = "block";
        }
    } else {
        if (urgentDot) {
            urgentDot.remove();
        }
    }
}

function refreshNotifications() {
    const refreshBtn =
        document.querySelector(".refresh-button");

    const refreshIcon = refreshBtn
        ? refreshBtn.querySelector("img")
        : null;

    if (refreshIcon) {
        refreshIcon.classList.add("spinning");
    }

    fetch("notification.php", {
        method: "POST",
        headers: {
            "Content-Type":
                "application/x-www-form-urlencoded",
        },
        body: "refresh_notifications=true",
    })
        .then((response) => response.json())
        .then((data) => {
            const notificationContainer =
                document.getElementById(
                    "notification-container"
                );

            notificationContainer.innerHTML = data.html;

            updateSidebarUrgentDot(
                data.hasNotifications
            );
        })
        .catch((error) =>
            console.error(
                "Error refreshing notifications:",
                error
            )
        )
        .finally(() => {
            setTimeout(() => {
                if (refreshIcon) {
                    refreshIcon.classList.remove(
                        "spinning"
                    );
                }
            }, 500);
        });
}

function clearNotifications() {
    fetch("notification.php", {
        method: "POST",
        headers: {
            "Content-Type":
                "application/x-www-form-urlencoded",
        },
        body: "clear_notifications=true",
    })
        .then((response) => response.json())
        .then((data) => {
            document.getElementById(
                "notification-container"
            ).innerHTML = data.html;

            updateSidebarUrgentDot(
                data.hasNotifications
            );
        })
        .catch((error) =>
            console.error(
                "Error clearing notifications:",
                error
            )
        );
}

function redirectAndScrollToProduct(productId) {
    window.location.href =
        "inventory.php#product-" + productId;
}

function highlightProduct(productId) {
    const productElement = document.getElementById(
        "product-" + productId
    );

    if (productElement) {
        productElement.style.display = "flex";

        productElement.scrollIntoView({
            behavior: "smooth",
            block: "center",
        });

        productElement.classList.add("highlighted");

        setTimeout(function () {
            productElement.classList.remove(
                "highlighted"
            );
        }, 4500);
    }
}

function checkHashAndHighlight() {
    if (
        window.location.hash &&
        window.location.hash.startsWith("#product-")
    ) {
        const productId =
            window.location.hash.replace(
                "#product-",
                ""
            );

        setTimeout(function () {
            highlightProduct(productId);
        }, 850);
    }
}

window.addEventListener(
    "load",
    checkHashAndHighlight
);

window.addEventListener(
    "pageshow",
    checkHashAndHighlight
);

window.addEventListener(
    "hashchange",
    checkHashAndHighlight
);