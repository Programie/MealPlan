import {Dropdown} from "bootstrap";
import "./style.scss";

class Item {
    public value: string;
    public data: DOMStringMap;

    public static fromOptionElement(element: HTMLOptionElement) {
        let item = new Item();

        item.value = element.value;
        item.data = element.dataset;

        return item;
    }
}

export class Autocomplete {
    private readonly inputElement;
    private readonly onSelect;
    private readonly dropdown;
    private readonly data: Map<string, Item>;

    constructor(inputElement: HTMLInputElement, datalistElement: HTMLDataListElement, onSelect: (item: Item) => void) {
        this.inputElement = inputElement;
        this.onSelect = onSelect;
        this.dropdown = null;

        this.data = new Map();

        datalistElement.querySelectorAll("option").forEach((optionElement: HTMLOptionElement) => {
            let item = Item.fromOptionElement(optionElement);
            this.data.set(item.value, item);
        });

        (inputElement.parentNode as HTMLElement).classList.add("dropdown");
        inputElement.setAttribute("data-bs-toggle", "dropdown");
        inputElement.classList.add("dropdown-toggle");

        let dropdownElement = document.createElement("div");
        dropdownElement.classList.add("dropdown-menu", "autocomplete-dropdown");
        inputElement.parentNode.insertBefore(dropdownElement, inputElement.nextSibling);

        this.dropdown = new Dropdown(inputElement);

        inputElement.addEventListener("click", (event) => {
            if (this.createItems() === 0) {
                event.stopPropagation();
                this.dropdown.hide();
            }
        });

        inputElement.addEventListener("input", () => {
            this.renderIfNeeded();
        });

        inputElement.addEventListener("keydown", (event) => {
            if (event.code === "Escape") {
                this.dropdown.hide();
                return;
            }

            if (event.code === "ArrowDown") {
                dropdownElement.querySelector("button")?.focus();
                return;
            }
        });
    }

    renderIfNeeded() {
        if (this.createItems() > 0) {
            this.dropdown.show();
        } else {
            this.inputElement.click();
        }
    }

    createItem(lookup: string, item: Item) {
        let index = item.value.toLowerCase().indexOf(lookup.toLowerCase());

        let leadingSpanElement = document.createElement("span");
        leadingSpanElement.textContent = item.value.substring(0, index);

        let highlightSpanElement = document.createElement("span");
        highlightSpanElement.classList.add("autocomplete-highlight");
        highlightSpanElement.textContent = item.value.substring(index, index + lookup.length);

        let trailingSpanElement = document.createElement("span");
        trailingSpanElement.textContent = item.value.substring(index + lookup.length, item.value.length);

        let buttonElement = document.createElement("button");
        buttonElement.type = "button";
        buttonElement.classList.add("dropdown-item");
        buttonElement.dataset.value = item.value;

        buttonElement.append(leadingSpanElement);
        buttonElement.append(highlightSpanElement);
        buttonElement.append(trailingSpanElement);

        return buttonElement;
    }

    createItems() {
        const lookup = this.inputElement.value;
        const lookupLowercase = lookup.toLowerCase();
        const items = this.inputElement.nextSibling as HTMLElement;
        items.innerHTML = "";

        this.data.forEach((item) => {
            if (item.value.toLowerCase().includes(lookupLowercase)) {
                items.appendChild(this.createItem(lookup, item));
            }
        });

        (this.inputElement.nextSibling as HTMLElement).querySelectorAll(".dropdown-item").forEach((itemElement: HTMLElement) => {
            itemElement.addEventListener("click", (event) => {
                let item = this.data.get((event.currentTarget as HTMLElement).dataset.value);

                this.inputElement.value = item.value;
                this.onSelect(item);

                this.dropdown.hide();
            });
        });

        return items.childNodes.length;
    }
}