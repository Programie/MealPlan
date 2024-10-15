import "./common";

import {Modal, Toast} from "bootstrap";
import * as Mustache from "mustache";
import {highlightTodayRow} from "./utils";
import {Autocomplete} from "../modules/autocomplete";
import {Sidebar} from "../modules/sidebar";

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
            this.configureMealElement(element);
        });

        document.querySelector("#week-edit-save-button").addEventListener("click", () => {
            this.save();
        });

        document.querySelector("#notes-sidebar-text").addEventListener("input", () => {
            this.dataChanged = true;
        });

        window.addEventListener("beforeunload", (event) => {
            if (this.dataChanged) {
                event.preventDefault();
            }
        });
    }

    configureMealElement(containerElement: Element) {
        let inputElement = containerElement.querySelector("input");
        new Autocomplete(inputElement, document.querySelector("#meal-autocompletion-source"), (item) => {
            this.dataChanged = true;
            inputElement.dataset.url = item.data.url;
            this.updateOptionButtons(inputElement);
        });

        inputElement.addEventListener("change", () => {
            this.dataChanged = true;
        });

        containerElement.querySelector(".week-edit-meal-button-link").addEventListener("click", () => {
            this.showModal("url", inputElement, this.showEditUrlModal.bind(this));
        });

        containerElement.querySelector(".week-edit-meal-button-notification").addEventListener("click", () => {
            this.showModal("notification", inputElement, this.showEditNotificationModal.bind(this));
        });

        containerElement.querySelector(".week-edit-meal-button-move-to-notes").addEventListener("click", () => {
            let text = inputElement.value.trim();
            if (text === "") {
                return;
            }

            let url = inputElement.dataset.url.trim();
            if (url !== "") {
                text = `${text}: ${url}`;
            }

            let textareaElement = document.querySelector("#notes-sidebar-text") as HTMLTextAreaElement;
            textareaElement.value = `${textareaElement.value.trim()}\n${text}\n`;

            this.dataChanged = true;

            this.removeMealElement(inputElement);

            let toastElement = document.querySelector("#week-edit-move-to-notes-toast");
            new Toast(toastElement).show();
        });
    }

    removeMealElement(inputElement: HTMLInputElement) {
        inputElement.value = "";

        let containerElement = inputElement.closest(".week-edit-meal") as HTMLElement;
        containerElement.style.display = "none";
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

        this.configureMealElement(newContainerElement);
    }

    showEditUrlModal(modalElement: Element, mealInputElement: HTMLInputElement) {
        let urlInputElement: HTMLInputElement = modalElement.querySelector("#week-edit-url-input");
        urlInputElement.value = mealInputElement.dataset.url;
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

    showEditNotificationModal(modalElement: Element, mealInputElement: HTMLInputElement) {
        let dayDate = mealInputElement.closest("tr").dataset.date;
        let defaultNotificationTime = mealInputElement.closest("td").dataset.notificationTime;
        let mealDataset = mealInputElement.dataset;

        let invalidElement: HTMLElement = modalElement.querySelector("#week-edit-notification-invalid");
        invalidElement.style.display = null;

        let enableElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-enable");
        enableElement.checked = mealDataset.notificationTime !== "";

        let dateTimeElement: HTMLInputElement = modalElement.querySelector("#week-edit-notification-time");

        let date = null;
        if (mealDataset.notifcationTime !== null && mealDataset.notificationTime !== "") {
            date = new Date(mealDataset.notificationTime);
        } else if (defaultNotificationTime !== null && defaultNotificationTime !== "") {
            date = new Date(`${dayDate} ${defaultNotificationTime}`);
        }

        if (date === null) {
            dateTimeElement.value = null;
        } else {
            let year = date.getFullYear();
            let month = String(date.getMonth() + 1).padStart(2, "0");
            let day = String(date.getDate()).padStart(2, "0");
            let hour = String(date.getHours()).padStart(2, "0");
            let minute = String(date.getMinutes()).padStart(2, "0");

            dateTimeElement.value = `${year}-${month}-${day}T${hour}:${minute}`;
        }

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

    showModal(name: string, mealInputElement: HTMLInputElement, callback: (modalElement: Element, mealInputElement: HTMLInputElement) => void) {
        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);

        document.querySelectorAll(".week-edit-meal").forEach((element) => element.classList.remove("meal-modal-open"));
        mealInputElement.closest(".week-edit-meal").classList.add("meal-modal-open");

        callback(modalElement, mealInputElement);

        new Modal(modalElement).show();
    }

    configureModal(name: string, configureCallback: (modalElement: Element) => void, saveCallback: (modalElement: Element, mealDataset: DOMStringMap) => boolean) {
        let modalElement: HTMLElement = document.querySelector(`#week-edit-${name}-modal`);
        modalElement.querySelector(".modal-button-ok").addEventListener("click", () => {
            let mealInputElement: HTMLInputElement = document.querySelector(".meal-modal-open input");

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
        let notes = (document.querySelector("#notes-sidebar-text") as HTMLTextAreaElement).value;

        let saveButtonElement = document.querySelector("#week-edit-save-button");

        try {
            saveButtonElement.setAttribute("disabled", "disabled");

            let response = await fetch(`/space/${spaceId}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "text/plain"
                },
                body: JSON.stringify({
                    meals: meals,
                    notes: notes
                })
            });

            if (response.ok) {
                this.dataChanged = false;
                document.location.href = (document.querySelector(".goto-view") as HTMLLinkElement).href;
            } else {
                saveButtonElement.removeAttribute("disabled");

                let responseText = (await response.text()).trim();
                if (responseText !== "") {
                    this.showError(responseText);
                } else {
                    this.showError(`${response.status}: ${response.statusText}`);
                }
            }
        } catch (error) {
            saveButtonElement.removeAttribute("disabled");

            this.showError(error);
        }
    }
}

window.addEventListener("DOMContentLoaded", () => {
    new Editor();
    new Sidebar(document.querySelector("#notes-sidebar"), () => {
        let textareaElement = document.querySelector("#notes-sidebar-text") as HTMLTextAreaElement;
        textareaElement.focus();
        textareaElement.setSelectionRange(textareaElement.value.length, textareaElement.value.length);
    });

    highlightTodayRow();
});