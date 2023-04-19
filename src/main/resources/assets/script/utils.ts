import {DateHelper} from "./date";

export function string2boolean(value: string): boolean {
    return value?.toLowerCase?.() === "true";
}

export function boolean2string(value: boolean): string {
    return value ? "true" : "false";
}

export function highlightTodayRow(): void {
    document.querySelectorAll(".week-day-row").forEach((row: HTMLElement) => {
        let date = new DateHelper(row.dataset.date);

        row.classList.toggle("active", date.isToday());
    });
}

export function tr(key: string): string {
    return (document.querySelector(`#translations > option[value="${key}"]`) as HTMLOptionElement)?.text;
}