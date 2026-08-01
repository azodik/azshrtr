#!/usr/bin/env node
import { readFileSync, readdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const localesDir = join(dirname(fileURLToPath(import.meta.url)), '../resources/js/console/locales');
const files = readdirSync(localesDir).filter((name) => name.endsWith('.json'));
const catalogs = Object.fromEntries(
    files.map((name) => [
        name.replace(/\.json$/, ''),
        JSON.parse(readFileSync(join(localesDir, name), 'utf8')),
    ]),
);

const enKeys = new Set(Object.keys(catalogs.en ?? {}));
if (enKeys.size === 0) {
    console.error('en.json missing or empty');
    process.exit(1);
}

let failed = false;
for (const [locale, data] of Object.entries(catalogs)) {
    const keys = new Set(Object.keys(data));
    const missing = [...enKeys].filter((key) => !keys.has(key)).sort();
    const extra = [...keys].filter((key) => !enKeys.has(key)).sort();
    const empty = [...keys].filter((key) => typeof data[key] !== 'string' || data[key] === '');

    if (missing.length || extra.length || empty.length) {
        failed = true;
        console.error(`Locale ${locale}:`);
        if (missing.length) {
            console.error(`  missing (${missing.length}): ${missing.join(', ')}`);
        }
        if (extra.length) {
            console.error(`  extra (${extra.length}): ${extra.join(', ')}`);
        }
        if (empty.length) {
            console.error(`  empty (${empty.length}): ${empty.join(', ')}`);
        }
    } else {
        console.log(`Locale ${locale}: ${keys.size} keys OK`);
    }
}

if (failed) {
    process.exit(1);
}
