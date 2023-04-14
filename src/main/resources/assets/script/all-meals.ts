import "../style/main.scss";
import "../images/favicon.svg";

import "bootstrap";
import * as $ from "jquery";
import * as Mustache from "mustache";
import DataTable, {Api, ApiRowMethods} from "datatables.net-dt";
import "datatables.net-bs5";
import {DateHelper} from "./date";

class Meal {
    date: DateHelper;
    url: string;
    type: string;

    public static fromObject(data: any) {
        let meal = new Meal();

        meal.date = new DateHelper(data.date);
        meal.url = data.url;
        meal.type = data.type;

        return meal;
    }
}

class GroupedMeal {
    text: string;
    meals: Meal[] = [];
    urls: string[] = [];

    public static fromObject(data: any) {
        let groupedMeal = new GroupedMeal();

        groupedMeal.text = data.text;

        data.meals.forEach((data: any) => {
            groupedMeal.meals.push(Meal.fromObject(data));
        });

        data.urls.forEach((url: string) => {
            groupedMeal.urls.push(url);
        });

        groupedMeal.meals.sort((meal1, meal2) => {
            return meal1.date.timestamp < meal2.date.timestamp ? 1 : -1;
        });

        return groupedMeal;
    }

    public get averageDaysBetweenMeals() {
        let numMeals = this.meals.length;

        let firstMeal = this.meals[numMeals - 1];
        let lastMeal = this.meals[0];

        let averageTime = ((new Date).getTime() - firstMeal.date.timestamp) / numMeals;

        return averageTime / 1000 / 60 / 60 / 24;
    }

    public get lastMeal() {
        return this.meals[0];
    }
}

class Table {
    dataTable: Api<any>;
    spaceId: string;

    constructor(spaceId: string) {
        this.spaceId = spaceId;

        this.dataTable = new DataTable("#all-meals-table", {
            paging: false,
            searching: false,
            order: [[1, "asc"], [0, "asc"]],
            ajax: this.fetchData.bind(this),
            columns: [
                {
                    data: "text"
                },
                {
                    data: {
                        _: "lastMeal.date.shortFormat",
                        sort: "lastMeal.date.keyFormat"
                    }
                },
                {
                    data: (row: GroupedMeal, type, set, meta) => {
                        let value = row.averageDaysBetweenMeals;

                        if (type === "display") {
                            if (value === 0) {
                                return "";
                            }

                            return value.toFixed(1);
                        }

                        return value;
                    }
                },
                {
                    data: "meals.length"
                },
                {
                    orderable: false,
                    data: "urls",
                    render: function (urls: string[]) {
                        var html: string[] = [];

                        urls.forEach((url) => {
                            html.push(`<a href="${url}" target="_blank"><i class="fa-solid fa-globe"></i></a>`);
                        });

                        return html.join(" ");
                    }
                }
            ]
        });

        document.querySelector("#all-meals-table tbody").addEventListener("click", (event) => {
            if (!(event.target instanceof HTMLTableCellElement)) {
                return;
            }

            let tableCell = event.target;
            let tableRow = tableCell.closest("tr");

            this.toggleChildRows(tableRow);
        });
    }

    fetchData(data: object, callback: ((data: any) => void), settings: any) {
        fetch(`/space/${this.spaceId}/all-meals.json`)
            .then((response) => response.json())
            .then((response) => {
                let groupedMeals: GroupedMeal[] = [];

                response.forEach((entry: any) => {
                    groupedMeals.push(GroupedMeal.fromObject(entry));
                });

                callback({
                    aaData: groupedMeals
                });
            });
    }

    toggleChildRows(tableRow: HTMLTableRowElement) {
        let row = this.dataTable.row(tableRow);
        if (!row.length) {
            return;
        }

        if (row.child.isShown()) {
            this.collapseChildRows(row);
        } else {
            this.expandChildRows(row);
        }
    }

    expandChildRows(row: ApiRowMethods<any>) {
        this.collapseAllChildRows();

        let childRows: JQuery<HTMLElement>[] = [];

        row.data().meals.forEach((meal: Meal) => {
            childRows.push($(Mustache.render(document.querySelector("#all-meals-table-child-template").innerHTML, {
                type: meal.type,
                url: `/space/${this.spaceId}/week/${meal.date.keyFormat}`,
                date: meal.date.shortFormat
            })));
        });

        // @ts-ignore
        row.child(childRows).show();
        (row.node() as HTMLTableRowElement).classList.add("shown", "datatable-child-expanded", "fw-bold");
    }

    collapseAllChildRows() {
        let expandedRows = this.dataTable.rows(".datatable-child-expanded");

        expandedRows.every((rowIdx, tableLoop, rowLoop) => {
            this.collapseChildRows(this.dataTable.row(rowIdx));
        });
    }

    collapseChildRows(row: ApiRowMethods<any>) {
        row.child.hide();
        (row.node() as HTMLTableRowElement).classList.remove("shown", "datatable-child-expanded", "fw-bold");
    }
}

window.onload = () => {
    let tableElement = (document.querySelector("#all-meals-table") as HTMLElement);
    new Table(tableElement.dataset.spaceId);
};