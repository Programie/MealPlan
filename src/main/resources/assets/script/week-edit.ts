import "../style/main.scss";

import "bootstrap";
import * as Mustache from "mustache";

import MealAutocompletion from "./meal-autocompletion";

window.onload = () => {
    let dataChanged = false;
    let mealAutocompletion = new MealAutocompletion(".week-edit-meal");

    function addChangeEventListener(element: Element) {
        element.addEventListener("change", () => {
            dataChanged = true;
        });
    }

    document.querySelectorAll(".week-edit-meal").forEach(addChangeEventListener);

    document.querySelectorAll(".week-edit-meal-add").forEach((element: HTMLButtonElement) => {
        element.addEventListener("click", () => {
            let date = element.closest("tr").getAttribute("data-date");
            let mealType = element.closest("td").getAttribute("data-type");

            let inputElement = Mustache.render(document.querySelector("#week-edit-meal-template").innerHTML, {
                date: date,
                type: mealType
            });

            element.insertAdjacentHTML("beforebegin", inputElement);
            let insertedElement = element.parentElement.querySelector(".week-edit-meal:last-of-type");

            mealAutocompletion.updateElement(insertedElement);
            addChangeEventListener(insertedElement);
        });
    });

    window.addEventListener("beforeunload", (event) => {
        if (dataChanged) {
            event.preventDefault();
        }
    });
};

