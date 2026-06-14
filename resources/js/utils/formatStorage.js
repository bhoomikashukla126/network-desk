export function formatStorageBytes(bytes) {
    const value = Math.max(0, Number(bytes ?? 0));

    if (value < 1024) {
        return `${new Intl.NumberFormat().format(value)} B`;
    }

    if (value < 1024 * 1024) {
        return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value / 1024)} KB`;
    }

    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value / 1024 / 1024)} MB`;
}
