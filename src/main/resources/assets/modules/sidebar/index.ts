import "./style.scss";

export class Sidebar {
    private rootElement: HTMLElement;

    constructor(rootElement: HTMLElement, onShow: () => void = null, onHide: () => void = null) {
        this.rootElement = rootElement;
        let buttonElement = this.rootElement.querySelector(".sidebar-button");

        buttonElement.addEventListener("click", () => {
            if (this.rootElement.classList.toggle("show")) {
                if (onShow) {
                    onShow();
                }
            } else {
                if (onHide) {
                    onHide();
                }
            }
        });
    }
}