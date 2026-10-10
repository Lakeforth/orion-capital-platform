import countriesData from '../config/countries.json';

function getFlag(code: string): string {
    return code.toUpperCase().split('').map(c =>
        String.fromCodePoint(0x1F1E6 + c.charCodeAt(0) - 65)
    ).join('');
}

function toSlug(name: string): string {
    return name.toLowerCase()
        .replace(/[,'.()]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
}

export const countries = countriesData
    .map(c => ({
        value: toSlug(c.name),
        label: c.name,
        flag: getFlag(c.code),
        code: c.code,
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

export const countryCodeMap: Record<string, string> = Object.fromEntries(
    countriesData.map(c => [c.code, toSlug(c.name)])
);
