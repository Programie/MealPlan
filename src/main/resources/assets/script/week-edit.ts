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
            this.showEditUrlModal(inputElement.getAttribute("data-id"), inputElement.getAttribute("data-url"));
        });

        containerElement.querySelector(".week-edit-meal-button-notification").addEventListener("click", () => {
            this.showEditNotificationModal(inputElement.getAttribute("data-id"), string2boolean(inputElement.getAttribute("data-notification-enabled")), inputElement.getAttribute("data-notification-time"));
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

    showEditUrlModal(mealId: string, url: string) {
        let modalElement = document.querySelector("#week-edit-url-modal");
        modalElement.setAttribute("data-meal-id", mealId);

        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");
        urlInputElement.value = url;

        let modal = new Modal(modalElement);
        modal.show();
    }

    saveUrlModal(modalElement: Element, mealInputElement: HTMLInputElement) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");

        mealInputElement.setAttribute("data-url", urlInputElement.value);
    }

    showEditNotificationModal(mealId: string, enable: boolean, time: string) {
        let modalElement = document.querySelector("#week-edit-notification-modal");
        modalElement.setAttribute("data-meal-id", mealId);

        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        enableElement.checked = enable;

        let modal = new Modal(modalElement);
        modal.show();
    }

    saveNotificationModal(modalElement: Element, mealInputElement: HTMLInputElement) {
    }

    configureModal(name: string, callback: (modalElement: Element, mealInputElement: HTMLInputElement) => void) {
        let modalElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.querySelector(".modal-button-ok").addEventListener("click", () => {
            let mealId = modalElement.getAttribute("data-meal-id");

            let mealInputElement: HTMLInputElement = document.querySelector(`.week-edit-meal input[data-id='${mealId}']`);

            callback(modalElement, mealInputElement);

            this.dataChanged = true;

            Modal.getInstance(modalElement).hide();
        });
    }
}

window.onload = () => {
    new Editor();
};