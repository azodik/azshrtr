export type JsonObject = Record<string, unknown>;
export type JsonValue = string | number | boolean | null | JsonObject | JsonValue[];

export function isJsonObject(value: unknown): value is JsonObject {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export function parseJsonText(text: string): unknown {
    if (text === '') {
        return null;
    }

    try {
        const value: unknown = JSON.parse(text);
        return value;
    } catch {
        return null;
    }
}

export function readStringField(value: unknown, key: string): string | undefined {
    if (!isJsonObject(value)) {
        return undefined;
    }

    const field = value[key];
    return typeof field === 'string' ? field : undefined;
}
