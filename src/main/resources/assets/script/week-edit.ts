import "../style/main.scss";

import {Dropdown, Modal, Toast} from "bootstrap";
import * as Mustache from "mustache";

import MealAutocompletion from "./meal-autocompletion";
import {boolean2string, string2boolean} from "./utils";

class MealNotification {
    public enabled: boolean;
    public time: string;
}

class MealData {
    public id: number;
    public date: string;
    public type: number;
    public text: string;
    public url: string;
    public notification: MealNotification;

    public constructor() {
        this.notification = new MealNotification();
    }

    public static fromElement(element: HTMLInputElement) {
        let dataset = element.dataset;

        let data = new this();

        data.id = parseInt(dataset.id);
        data.type = parseInt(dataset.type);
        data.date = dataset.date;
        data.url = dataset.url;
        data.notification.enabled = string2boolean(dataset.notificationEnabled);
        data.notification.time = dataset.notificationTime;
        data.text = element.value;

        return data;
    }
}

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

        document.querySelector("#week-edit-save-button").addEventListener("click", () => {
            this.save();
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

        let time = mealDataset.notificationTime;

        let radioElement: HTMLInputElement;
        let inputElement: HTMLInputElement;
        let otherInputElement: HTMLInputElement;

        if (time.includes(":")) {
            radioElement = modalElement.querySelector("#week-edit-notification-time-absolute-radio");
            inputElement = modalElement.querySelector("#week-edit-notification-time-absolute-input");
            otherInputElement = modalElement.querySelector("#week-edit-notification-time-relative-input");
        } else {
            radioElement = modalElement.querySelector("#week-edit-notification-time-relative-radio");
            inputElement = modalElement.querySelector("#week-edit-notification-time-relative-input");
            otherInputElement = modalElement.querySelector("#week-edit-notification-time-absolute-input");
        }

        radioElement.checked = true;
        inputElement.value = time;
        otherInputElement.value = "";
    }

    saveNotificationModal(modalElement: Element, mealDataset: DOMStringMap) {
        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        let radioElement: HTMLInputElement;
        let inputElement: HTMLInputElement;

        radioElement = modalElement.querySelector("#week-edit-notification-time-absolute-radio");
        if (radioElement.checked) {
            inputElement = modalElement.querySelector("#week-edit-notification-time-absolute-input");
        } else {
            inputElement = modalElement.querySelector("#week-edit-notification-time-relative-input");
        }

        mealDataset.notificationEnabled = boolean2string(enableElement.checked);
        mealDataset.notificationTime = inputElement.value;
    }

    showModal(name: string, mealInputElement: HTMLInputElement, callback: (modalElement: Element, mealDataset: DOMStringMap) => void) {
        let mealDataset = mealInputElement.dataset;

        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.dataset.mealId = mealDataset.id;

        callback(modalElement, mealDataset);

        new Modal(modalElement).show();
    }

    configureModal(name: string, saveCallback: (modalElement: Element, mealDataset: DOMStringMap) => void) {
        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.querySelector(".modal-button-ok").addEventListener("click", () => {
            let mealInputElement: HTMLInputElement = document.querySelector(`.week-edit-meal input[data-id='${modalElement.dataset.mealId}']`);

            saveCallback(modalElement, mealInputElement.dataset);

            this.dataChanged = true;

            Modal.getInstance(modalElement).hide();
        });
    }

    showError(message: string) {
        let toastElement = document.querySelector("#week-edit-error-toast");
        toastElement.querySelector(".toast-body").textContent = message;
        new Toast(toastElement).show();
    }

    async save() {
        let meals: MealData[] = [];

        document.querySelectorAll(".week-edit-meal input").forEach((element: HTMLInputElement) => {
            meals.push(MealData.fromElement(element));
        });

        let tableDataset = (document.querySelector("#week-table") as HTMLElement).dataset;
        let spaceId = tableDataset.spaceId;
        let date = tableDataset.date;

        try {
            let response = await fetch(`/space/${spaceId}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "text/plain"
                },
                body: JSON.stringify(meals)
            });

            if (response.ok) {
                this.dataChanged = false;
                document.location.href = `/space/${spaceId}/week/${date}`;
            } else {
                let responseText = (await response.text()).trim();
                if (responseText !== "") {
                    this.showError(responseText);
                } else {
                    this.showError(`${response.status}: ${response.statusText}`);
                }
            }
        } catch (error) {
            this.showError(error);
        }
    }
}

window.onload = () => {
    new Editor();
};