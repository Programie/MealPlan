document.addEventListener("DOMContentLoaded", function () {
    // make it as accordion for smaller screens
    if (window.innerWidth < 992) {
        // close all inner dropdowns when parent is closed
        document.querySelectorAll(".navbar .dropdown").forEach((dropdownElement) => {
            dropdownElement.addEventListener("hidden.bs.dropdown", function () {
                // after dropdown is hidden, then find all submenus
                this.querySelectorAll(".submenu").forEach((dropdownSubmenuElement: HTMLElement) => {
                    // hide every submenu as well
                    dropdownSubmenuElement.style.display = "none";
                });
            });
        });

        document.querySelectorAll(".dropdown-menu a").forEach((dropdownMenuElement) => {
            dropdownMenuElement.addEventListener("click", (event) => {
                let nextElement = (event.target as HTMLElement).nextElementSibling as HTMLElement;
                if (nextElement && nextElement.classList.contains("submenu")) {
                    // prevent opening link if link needs to open dropdown
                    event.preventDefault();

                    if (nextElement.style.display == "block") {
                        nextElement.style.display = "none";
                    } else {
                        nextElement.style.display = "block";
                    }
                }
            });
        })
    }
});