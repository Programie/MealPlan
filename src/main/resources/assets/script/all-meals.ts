import "./common";

import * as $ from "jquery";
import * as Mustache from "mustache";
import * as moment from "moment";
import "moment/locale/de";
import * as DateRangePicker from "daterangepicker";
import DataTable, {Api, ApiRowMethods} from "datatables.net-dt";
import "datatables.net-bs5";
import {DateHelper} from "./date";
import {tr} from "./utils";
import {DateOrString} from "daterangepicker";

class MealType {
    id: number;
    name: string;

    public static fromObject(data: any) {
        let mealType = new MealType();

        mealType.id = data.id;
        mealType.name = data.name;

        return mealType;
    }
}

class Meal {
    id: number;
    date: DateHelper;
    url: string;
    type: MealType;

    public static fromObject(data: any) {
        let meal = new Meal();

        meal.id = data.id;
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
            return meal1.date.getTimestamp() < meal2.date.getTimestamp() ? 1 : -1;
        });

        return groupedMeal;
    }

    public get averageDaysBetweenMeals() {
        let numMeals = this.meals.length;

        let firstMeal = this.meals[numMeals - 1];
        let lastMeal = this.meals[0];

        let averageTime = (Math.max((new Date).getTime(), lastMeal.date.getTimestamp()) - firstMeal.date.getTimestamp()) / numMeals;

        return averageTime / 1000 / 60 / 60 / 24;
    }

    public get lastMeal() {
        return this.meals[0];
    }
}

class Table {
    dataTable: Api<any>;
    dataUrl: string;
    spaceId: string;

    constructor(dataUrl: string, spaceId: string) {
        this.dataUrl = dataUrl;
        this.spaceId = spaceId;

        this.dataTable = new DataTable("#all-meals-table", {
            paging: false,
            order: [[1, "asc"], [0, "asc"]],
            ajax: this.fetchData.bind(this),
            columns: [
                {
                    data: "text"
                },
                {
                    searchable: false,
                    data: {
                        _: "lastMeal.date.getShortFormat()",
                        sort: "lastMeal.date.getKeyFormat()"
                    }
                },
                {
                    searchable: false,
                    data: (row: GroupedMeal, type, set, meta) => {
                        let value = row.averageDaysBetweenMeals;

                        if (type === "display") {
                            if (value <= 0) {
                                return "";
                            }

                            return value.toFixed(1);
                        }

                        return value;
                    }
                },
                {
                    searchable: false,
                    data: "meals.length"
                },
                {
                    searchable: false,
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
            ],
            language: {
                "emptyTable": tr("datatables.empty-table"),
                "info": tr("datatables.info"),
                "infoEmpty": tr("datatables.info-empty"),
                "infoFiltered": tr("datatables.info-filtered"),
                "loadingRecords": tr("datatables.loading-records"),
                "search": "_INPUT_",
                "searchPlaceholder": tr("datatables.search"),
                "zeroRecords": tr("datatables.zero-records")
            }
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
        fetch(this.dataUrl)
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
                type: meal.type.name,
                url: `/space/${this.spaceId}/week/${meal.date.getKeyFormat()}?show=${meal.id}`,
                date: meal.date.getShortFormat()
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

window.addEventListener("DOMContentLoaded", () => {
    moment.locale(window.navigator.language);

    let dateRangeContainer = document.querySelector("#all-meals-date-selection") as HTMLElement;

    let rangesMap: { [name: string]: [DateOrString, DateOrString] } = {
        "date-ranges.last-7-days": [moment().subtract(6, "days"), moment()],
        "date-ranges.last-30-days": [moment().subtract(29, "days"), moment()],
        "date-ranges.this-month": [moment().startOf("month"), moment().endOf("month")],
        "date-ranges.last-month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],
        "date-ranges.this-year": [moment().startOf("year"), moment().endOf("year")],
        "date-ranges.last-year": [moment().subtract(1, "year").startOf("year"), moment().subtract(1, "year").endOf("year")],
        "date-ranges.all": [null, null]
    };

    let ranges: { [name: string]: [DateOrString, DateOrString] } = {};

    Object.entries(rangesMap).forEach(([key, range]: [string, [DateOrString, DateOrString]]) => {
        ranges[tr(key)] = range;
    });

    new DateRangePicker(dateRangeContainer, {
        startDate: moment(dateRangeContainer.dataset.startdate),
        endDate: moment(dateRangeContainer.dataset.enddate),
        opens: "center",
        ranges: ranges,
        alwaysShowCalendars: true,
        cancelButtonClasses: "btn btn-sm btn-secondary",
        locale: {
            customRangeLabel: tr("date-ranges.custom"),
            applyLabel: tr("modal.ok"),
            cancelLabel: tr("modal.cancel")
        }
    }, (startDate, endDate) => {
        if (startDate.isValid() && endDate.isValid()) {
            document.location.search = `?start=${startDate.format("YYYY-MM-DD")}&end=${endDate.format("YYYY-MM-DD")}`;
        } else {
            document.location.search = "";
        }
    });

    let tableElement = (document.querySelector("#all-meals-table") as HTMLElement);

    new Table(tableElement.dataset.url, tableElement.dataset.spaceId);
});