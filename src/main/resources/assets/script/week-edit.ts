import "../style/main.scss";

import {Dropdown, Modal} from "bootstrap";
import * as Mustache from "mustache";

import MealAutocompletion from "./meal-autocompletion";
import {string2boolean} from "./utils";

class Editor {
    private dataChanged: boolean = false;
    private autocompletion: MealAutocompletion;

    constructor() {
        this.autocompletion = new MealAutocompletion(".week-edit-meal input");

        this.configureModal("url", this.saveUrlModal);
        this.configureModal("notification", this.saveNotificationModal);
        this.configureAddButtons();

        document.querySelectorAll(".week-edit-meal").forEach((element) => {
            this.addMealEventListeners(element);
        });

        window.addEventListener("beforeunload", (event) => {
            if (this.dataChanged) {
                event.preventDefault();
            }
        });
    }

    addMealEventListeners(containerElement: Element) {
        let inputElement = containerElement.querySelector("input");

        inputElement.addEventListener("change", () => {
            this.dataChanged = true;
        });

        containerElement.querySelector(".week-edit-meal-button-link").addEventListener("click", () => {
            this.showModal("url", inputElement, this.showEditUrlModal);
        });

        containerElement.querySelector(".week-edit-meal-button-notification").addEventListener("click", () => {
            this.showModal("notification", inputElement, this.showEditNotificationModal);
        });
    }

    configureAddButtons() {
        document.querySelectorAll(".week-edit-meal-add").forEach((element: HTMLButtonElement) => {
            element.addEventListener("click", () => {
                this.addMeal(element.closest("td").querySelector(".week-edit-meal-container"));
            });
        });
    }

    addMeal(containerElement: Element) {
        let date = containerElement.closest("tr").getAttribute("data-date");
        let mealType = containerElement.closest("td").getAttribute("data-type");

        let newContainer = Mustache.render(document.querySelector("#week-edit-meal-template").innerHTML, {
            date: date,
            type: mealType
        });

        containerElement.insertAdjacentHTML("beforeend", newContainer);
        let newContainerElement = containerElement.parentElement.querySelector(".week-edit-meal:last-of-type");

        this.autocompletion.updateElement(newContainerElement.querySelector("input"));
        this.addMealEventListeners(newContainerElement);
    }

    showEditUrlModal(modalElement: Element, mealDataset: DOMStringMap) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");
        urlInputElement.value = mealDataset.url;
    }

    saveUrlModal(modalElement: Element, mealDataset: DOMStringMap) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");

        mealDataset.url = urlInputElement.value;
    }

    showEditNotificationModal(modalElement: Element, mealDataset: DOMStringMap) {
        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        enableElement.checked = string2boolean(mealDataset.notificationEnabled);

        console.log(mealDataset.notificationTime);
    }

    saveNotificationModal(modalElement: Element, mealDataset: DOMStringMap) {
    }

    showModal(name: string, mealInputElement: HTMLInputElement, callback: (modalElement: Element, mealDataset: DOMStringMap) => void) {
        let mealDataset = mealInputElement.dataset;

        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.dataset.mealId = mealDataset.id;

        callback(modalElement, mealDataset);

        let modal = new Modal(modalElement);
        modal.show();
    }

    configureModal(name: string, callback: (modalElement: Element, mealDataset: DOMStringMap) => void) {
        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.querySelector(".modal-button-ok").addEventListener("click", () => {
            let mealInputElement: HTMLInputElement = document.querySelector(`.week-edit-meal input[data-id='${modalElement.dataset.mealId}']`);

            callback(modalElement, mealInputElement.dataset);

            this.dataChanged = true;

            Modal.getInstance(modalElement).hide();
        });
    }
}

window.onload = () => {
    new Editor();
};