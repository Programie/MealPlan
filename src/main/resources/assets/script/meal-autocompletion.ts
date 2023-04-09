import Autocomplete from "bootstrap5-autocomplete";


class Data {
    public value: string;
    public label: string;

    constructor(text: string) {
        this.value = text;
        this.label = text;
    }
}

class MealAutocompletion {
    private data: Data[];
    private readonly selector: string;

    public constructor(selector: string) {
        this.selector = selector;

        Autocomplete.init();

        if (document.querySelector(this.selector) === null) {
            return;
        }

        this.refreshData().then(() => {
            this.updateElements();
        });
    }

    private async refreshData() {
        let response = await fetch("/space/1/autocompletion.json");// TODO: Use current space ID
        let data = await response.json();

        this.data = [];

        data.forEach((item: string) => {
            this.data.push(new Data(item));
        });
    }

    private updateElements() {
        document.querySelectorAll(this.selector).forEach((element: HTMLInputElement) => {
            this.updateElement(element);
        });
    }

    public updateElement(element: Element) {
        let autocomplete = Autocomplete.getOrCreateInstance(element);
        autocomplete.setData(this.data);
    }
}

export default MealAutocompletion;