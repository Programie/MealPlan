import "../style/main.scss";

import {Dropdown, Modal, Toast} from "bootstrap";
import * as Mustache from "mustache";
import {highlightTodayRow} from "./utils";
import "./dropdown-submenu";

class MealNotification {
    public time: Date;
    public text: string;
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
        data.notification.time = new Date(dataset.notificationTime);
        data.notification.text = dataset.notificationText;
        data.text = element.value;

        return data;
    }
}

class Editor {
    private dataChanged: boolean = false;

    constructor() {
        this.configureModal("url", null, this.saveUrlModal.bind(this));
        this.configureModal("notification", this.configureNotificationModal.bind(this), this.saveNotificationModal.bind(this));
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

        inputElement.addEventListener("input", (event: InputEvent) => {
            if (event.inputType === "insertReplacementText") {
                let elements: HTMLOptionElement[] = Array.from(document.querySelectorAll("#existing-meals > option"));

                let optionElement = elements.find((optionElement: HTMLOptionElement) => {
                    return optionElement.value === event.data;
                });

                if (optionElement !== undefined) {
                    inputElement.dataset.url = optionElement.dataset.url;
                }

                this.updateOptionButtons(inputElement);
            }
        });

        containerElement.querySelector(".week-edit-meal-button-link").addEventListener("click", () => {
            this.showModal("url", inputElement, this.showEditUrlModal.bind(this));
        });

        containerElement.querySelector(".week-edit-meal-button-notification").addEventListener("click", () => {
            this.showModal("notification", inputElement, this.showEditNotificationModal.bind(this));
        });
    }

    updateOptionButtons(inputElement: HTMLInputElement) {
        let containerElement = inputElement.closest(".week-edit-meal");
        let mealDataset = inputElement.dataset;

        containerElement.querySelector(".week-edit-meal-button-link i").classList.toggle("active", mealDataset.url !== "");
        containerElement.querySelector(".week-edit-meal-button-notification i").classList.toggle("active", mealDataset.notificationTime !== "");
    }

    configureAddButtons() {
        document.querySelectorAll(".week-edit-meal-add").forEach((element: HTMLButtonElement) => {
            element.addEventListener("click", () => {
                this.addMeal(element.closest("td").querySelector(".week-edit-meal-container"));
            });
        });
    }

    addMeal(containerElement: Element) {
        let date = (containerElement.closest("tr") as HTMLElement).dataset.date;
        let mealType = (containerElement.closest("td") as HTMLElement).dataset.type;

        let newContainer = Mustache.render(document.querySelector("#week-edit-meal-template").innerHTML, {
            date: date,
            type: mealType
        });

        containerElement.insertAdjacentHTML("beforeend", newContainer);
        let newContainerElement = containerElement.parentElement.querySelector(".week-edit-meal:last-of-type");

        this.addMealEventListeners(newContainerElement);
    }

    showEditUrlModal(modalElement: Element, mealDataset: DOMStringMap) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");
        urlInputElement.value = mealDataset.url;
    }

    saveUrlModal(modalElement: Element, mealDataset: DOMStringMap) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");

        mealDataset.url = urlInputElement.value;

        return true;
    }

    updateNotificationModalTimeState() {
        let checkboxElement: HTMLInputElement = document.querySelector("#week-edit-notification-enable");
        let timeElement: HTMLInputElement = document.querySelector("#week-edit-notification-time");
        let textElement: HTMLInputElement = document.querySelector("#week-edit-notification-text");

        timeElement.disabled = !checkboxElement.checked;
        textElement.disabled = !checkboxElement.checked;
    }

    configureNotificationModal(modalElement: Element) {
        modalElement.querySelector("#week-edit-notification-enable").addEventListener("change", () => {
            this.updateNotificationModalTimeState();
        });
    }

    showEditNotificationModal(modalElement: Element, mealDataset: DOMStringMap) {
        let invalidElement: HTMLElement = modalElement.querySelector("#week-edit-notification-invalid");
        invalidElement.style.display = null;

        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        enableElement.checked = mealDataset.notification !== "";

        let dateTimeElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-time");

        let date = new Date(mealDataset.notificationTime);
        let year = date.getFullYear();
        let month = String(date.getMonth() + 1).padStart(2, "0");
        let day = String(date.getDate()).padStart(2, "0");
        let hour = String(date.getHours()).padStart(2, "0");
        let minute = String(date.getMinutes()).padStart(2, "0");

        dateTimeElement.value = `${year}-${month}-${day}T${hour}:${minute}`;

        let textElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-text");
        textElement.value = mealDataset.notificationText;

        this.updateNotificationModalTimeState();
    }

    saveNotificationModal(modalElement: Element, mealDataset: DOMStringMap) {
        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        let dateTimeElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-time");
        let textElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-text");

        if (enableElement.checked && dateTimeElement.valueAsDate === null) {
            (modalElement.querySelector("#week-edit-notification-invalid") as HTMLElement).style.display = "block";
            return false;
        }

        if (enableElement.checked) {
            mealDataset.notificationTime = dateTimeElement.value;
            mealDataset.notificationText = textElement.value;
        } else {
            mealDataset.notificationTime = "";
            mealDataset.notificationText = "";
        }

        return true;
    }

    showModal(name: string, mealInputElement: HTMLInputElement, callback: (modalElement: Element, mealDataset: DOMStringMap) => void) {
        let mealDataset = mealInputElement.dataset;

        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.dataset.mealId = mealDataset.id;

        callback(modalElement, mealDataset);

        new Modal(modalElement).show();
    }

    configureModal(name: string, configureCallback: (modalElement: Element) => void, saveCallback: (modalElement: Element, mealDataset: DOMStringMap) => boolean) {
        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.querySelector(".modal-button-ok").addEventListener("click", () => {
            let mealInputElement: HTMLInputElement = document.querySelector(`.week-edit-meal input[data-id='${modalElement.dataset.mealId}']`);

            if (!saveCallback(modalElement, mealInputElement.dataset)) {
                return;
            }

            this.updateOptionButtons(mealInputElement);

            this.dataChanged = true;

            Modal.getInstance(modalElement).hide();
        });

        let firstInputElement = modalElement.querySelector("input");
        if (firstInputElement !== null) {
            modalElement.addEventListener("shown.bs.modal", () => {
                firstInputElement.focus();
            });
        }

        if (configureCallback !== null) {
            configureCallback(modalElement);
        }
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

    highlightTodayRow();
};