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

        this.refreshData().then(() => {
            this.updateElements();
        });
    }

    private async refreshData() {
        let spaceId = (document.querySelector("#week-table") as HTMLElement).dataset.spaceId;

        let response = await fetch(`/space/${spaceId}/autocompletion.json`);
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
        let autocomplete = Autocomplete.getOrCreateInstance(element, {
            fullWidth: true,
            activeClasses: ["bg-secondary", "text-white"]
        });

        autocomplete.setData(this.data);
    }
}

export default MealAutocompletion;