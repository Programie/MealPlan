export function string2boolean(value: string): boolean {
    return value?.toLowerCase?.() === "true";
}

export function boolean2string(value: boolean): string {
    return value ? "true" : "false";
}