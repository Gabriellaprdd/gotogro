document.addEventListener("DOMContentLoaded", () => {
    const mobileToggleBtn = document.getElementById('mobileToggleBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

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
        mobileToggleBtn.addEventListener('click', () => {
            if (sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarOverlay.addEventListener('click', closeSidebar);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && sidebar.classList.contains("active")) {
                closeSidebar();
            }
        });
    }
});