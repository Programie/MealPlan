import "./common";

import {highlightTodayRow, tr} from "./utils";
import {DateTimeHelper} from "./date";

function highlightMeal(mealId: number) {
    let dayCellElement = document.querySelector(`div[data-meal-id="${mealId}"]`).closest("td");
    dayCellElement.classList.add("highlight-meal");
}

function highlightMealFromQueryParam() {
    let urlSearchParams = new URLSearchParams(window.location.search);
    let mealId = urlSearchParams.get("show");
    if (mealId !== null) {
        highlightMeal(parseInt(mealId));
    }
}

window.onload = () => {
    document.querySelector("#week-current-date").addEventListener("click", () => {
        (document.querySelector("#week-date-selection") as HTMLInputElement).showPicker();
    });

    document.querySelector("#week-date-selection").addEventListener("change", (event: InputEvent) => {
        let newDate = (event.target as HTMLInputElement).value;

        document.location.href = `${newDate}`;
    });

    document.querySelectorAll(".meal-notification").forEach((notificationIconElement: HTMLElement) => {
        let dataset = notificationIconElement.dataset;
        let date = new DateTimeHelper(dataset.time);

        let tooltip = tr("meal.notification.tooltip");

        let text = dataset.text.trim();
        if (text !== "") {
            tooltip = `${tooltip}: ${text}`;
        }

        notificationIconElement.title = `${tooltip} (${date.getShortFormat()})`;
    });

    highlightTodayRow();
    highlightMealFromQueryParam();
};