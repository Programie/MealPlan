class TouchDropdownSubmenu {
    constructor() {
        document.querySelectorAll(".navbar .dropdown").forEach((dropdownElement) => {
            dropdownElement.addEventListener("hidden.bs.dropdown", () => {
                // after dropdown is hidden, then find all submenus
                dropdownElement.querySelectorAll(".submenu").forEach((dropdownSubmenuElement: HTMLElement) => {
                    // hide every submenu as well
                    dropdownSubmenuElement.style.display = "";
                });
            });
        });

        document.querySelectorAll(".dropdown-menu button").forEach((dropdownMenuElement) => {
            dropdownMenuElement.addEventListener("click", this.handleDropdownMenuEvent.bind(this));
        });
    }

    handleDropdownMenuEvent(event: Event) {
        event.stopPropagation();

        let dropdownMenuElement = event.target as HTMLElement;
        let thisSubmenu = dropdownMenuElement.nextElementSibling;

        let submenus = dropdownMenuElement.closest(".dropdown-menu").querySelectorAll(":scope > li > .submenu");
        submenus.forEach((submenu: HTMLElement) => {
            if (submenu === thisSubmenu) {
                return;
            }

            submenu.style.display = "";
        });

        let nextElement = (event.target as HTMLElement).nextElementSibling as HTMLElement;
        if (nextElement && nextElement.classList.contains("submenu")) {
            // prevent opening link if link needs to open dropdown
            event.preventDefault();

            if (nextElement.style.display == "block") {
                nextElement.style.display = "";
            } else {
                nextElement.style.display = "block";
            }
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    if (navigator.maxTouchPoints === 0) {
        return;
    }

    new TouchDropdownSubmenu();
});