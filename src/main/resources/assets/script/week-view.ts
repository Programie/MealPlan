import "../style/main.scss";
import "../images/favicon.svg";

import "bootstrap";

window.onload = () => {
    document.querySelector("#week-current-date").addEventListener("click", () => {
        (document.querySelector("#week-date-selection") as HTMLInputElement).showPicker();
    });

    document.querySelector("#week-date-selection").addEventListener("change", (event: InputEvent) => {
        let newDate = (event.target as HTMLInputElement).value;

        document.location.href = `${newDate}`;
    });
};