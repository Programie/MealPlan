import "../style/main.scss";
import "../images/favicon.svg";

import "bootstrap";
import * as $ from "jquery";
import * as Mustache from "mustache";
import DataTable from "datatables.net-dt";
import "datatables.net-bs5";
import {DateHelper} from "./date";

class GroupedMeal {
    text: string;
    dates: DateHelper[] = [];
    urls: string[] = [];

    public static fromObject(data: any) {
        let groupedMeal = new GroupedMeal();

        groupedMeal.text = data.text;

        data.dates.forEach((date: string) => {
            groupedMeal.dates.push(new DateHelper(date));
        });

        data.urls.forEach((url: string) => {
            groupedMeal.urls.push(url);
        });

        return groupedMeal;
    }

    public get lastDate() {
        return this.dates[0];
    }
}

window.onload = () => {
    let spaceId = (document.querySelector("#all-meals-table") as HTMLElement).dataset.spaceId;

    let table = new DataTable("#all-meals-table", {
        paging: false,
        searching: false,
        order: [[1, "asc"], [0, "asc"]],
        ajax: (data, callback, settings) => {
            fetch(`/space/${spaceId}/all-meals.json`)
                .then((response) => response.json())
                .then((response) => {
                    let groupedMeals: GroupedMeal[] = [];
                    let entries = [];

                    response.forEach((entry: any) => {
                        groupedMeals.push(GroupedMeal.fromObject(entry));
                    });

                    callback({
                        aaData: groupedMeals
                    });
                });
        },
        columns: [
            {
                data: "text"
            },
            {
                data: {
                    _: "lastDate.shortFormat",
                    sort: "lastDate.keyFormat"
                }
            },
            {
                data: "dates.length"
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

        let tableCell: HTMLTableCellElement = event.target as HTMLTableCellElement;
        let tableRow = tableCell.closest("tr");

        let row = table.row(tableRow);
        let rowData = row.data();
        if (!rowData) {
            return;
        }

        if (row.child.isShown()) {
            row.child.hide();
            tableRow.classList.remove("shown", "all-meals-table-child", "fw-bold");
        } else {
            let childRows: JQuery<HTMLElement>[] = [];

            rowData.dates.forEach((date: DateHelper) => {
                childRows.push($(Mustache.render(document.querySelector("#all-meals-table-child-template").innerHTML, {
                    type: "",
                    url: `/space/${spaceId}/week/${date.keyFormat}`,
                    date: date.shortFormat
                })));
            });

            // @ts-ignore
            row.child(childRows).show();
            tableRow.classList.add("shown", "all-meals-table-child", "fw-bold");
        }
    });
};