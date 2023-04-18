export class Sidebar {
    private rootElement: HTMLElement;

    constructor(rootElement: HTMLElement) {
        this.rootElement = rootElement;
        let buttonElement = this.rootElement.querySelector(".sidebar-button");

        buttonElement.addEventListener("click", () => {
            this.rootElement.classList.toggle("show");
        });
    }
}