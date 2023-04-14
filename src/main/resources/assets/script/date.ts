export class DateHelper {
    date: Date;

    public constructor(date: any) {
        this.date = new Date(date);
    }

    public get timestamp() {
        return this.date.getTime();
    }

    public get shortFormat() {
        return this.date.toLocaleDateString(undefined, {
            weekday: "short",
            year: "numeric",
            month: "2-digit",
            day: "2-digit"
        });
    }

    public get isoFormat() {
        return this.date.toISOString();
    }

    public get keyFormat() {
        let day = this.date.getDate();
        let month = this.date.getMonth() + 1;
        let year = this.date.getFullYear();

        return `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`
    }
}