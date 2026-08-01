/**
 * Capture light/dark console walkthrough frames and assemble GIFs for the README.
 *
 * Usage: node scripts/capture-console-demo.mjs
 * Requires: npm run test:e2e:install (Chromium), ffmpeg, seeded demo user.
 */
import { chromium } from '@playwright/test';
import { mkdirSync, rmSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';

const BASE = process.env.AZSHRTR_URL ?? 'https://azshrtr.test';
const EMAIL = process.env.AZSHRTR_DEMO_EMAIL ?? 'demo@azshrtr.test';
const PASSWORD = process.env.AZSHRTR_DEMO_PASSWORD ?? 'password';
const ROOT = new URL('..', import.meta.url).pathname;
const OUT = join(ROOT, 'public/images/demo');
const TMP = join(ROOT, 'storage/app/demo-frames');

const PAGES = [
    { name: '01-overview', path: '' },
    { name: '02-links', path: 'links' },
    { name: '03-qr', path: 'qr' },
    { name: '04-analytics', path: 'analytics' },
    { name: '05-api-keys', path: 'api-keys' },
    { name: '06-billing', path: 'billing' },
];

function run(cmd, args) {
    const result = spawnSync(cmd, args, { stdio: 'inherit' });
    if (result.status !== 0) {
        throw new Error(`${cmd} ${args.join(' ')} failed with ${result.status}`);
    }
}

async function setTheme(page, theme) {
    await page.evaluate((value) => {
        localStorage.setItem('azshrtr-theme', value);
        const root = document.documentElement;
        const dark = value === 'dark';
        root.classList.toggle('dark', dark);
        root.style.colorScheme = dark ? 'dark' : 'light';
    }, theme);
}

async function settle(page) {
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(700);
}

async function captureTheme(page, theme, orgId) {
    const dir = join(TMP, theme);
    mkdirSync(dir, { recursive: true });
    await setTheme(page, theme);
    await page.reload({ waitUntil: 'networkidle' });
    await settle(page);

    let index = 0;
    for (const step of PAGES) {
        const url = `${BASE}/console/${orgId}${step.path ? `/${step.path}` : ''}`;
        await page.goto(url, { waitUntil: 'networkidle' });
        await setTheme(page, theme);
        await settle(page);
        // Hide toasts / floating noise if present
        await page.evaluate(() => {
            document.querySelectorAll('[data-sonner-toaster], [role="status"]').forEach((el) => {
                el.style.visibility = 'hidden';
            });
        });
        const file = join(dir, `${String(index).padStart(2, '0')}-${step.name}.png`);
        await page.screenshot({ path: file, type: 'png' });
        index += 1;
    }
}

function buildGif(theme) {
    const dir = join(TMP, theme);
    const out = join(OUT, `console-tour-${theme}.gif`);
    // Hold each frame ~1.4s, scale to 960px wide, palette for clean GIF
    run('ffmpeg', [
        '-y',
        '-framerate', '10/14',
        '-pattern_type', 'glob',
        '-i', join(dir, '*.png'),
        '-vf', 'scale=960:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=128[p];[s1][p]paletteuse=dither=bayer:bayer_scale=3',
        '-loop', '0',
        out,
    ]);
    console.log('wrote', out);
}

async function main() {
    if (existsSync(TMP)) {
        rmSync(TMP, { recursive: true, force: true });
    }
    mkdirSync(OUT, { recursive: true });
    mkdirSync(TMP, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2,
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    await page.goto(`${BASE}/console/login`, { waitUntil: 'networkidle' });
    await page.getByLabel(/email/i).fill(EMAIL);
    await page.getByLabel(/^password$/i).fill(PASSWORD);
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(/\/console\/[0-9a-f-]{36}/i, { timeout: 20000 });
    const orgId = page.url().match(/\/console\/([0-9a-f-]{36})/i)?.[1];
    if (!orgId) {
        throw new Error(`Could not resolve org id from ${page.url()}`);
    }
    console.log('org', orgId);

    await captureTheme(page, 'light', orgId);
    await captureTheme(page, 'dark', orgId);
    await browser.close();

    buildGif('light');
    buildGif('dark');

    // Keep a still hero frame for each mode
    run('cp', [join(TMP, 'light', '00-01-overview.png'), join(OUT, 'console-overview-light.png')]);
    run('cp', [join(TMP, 'dark', '00-01-overview.png'), join(OUT, 'console-overview-dark.png')]);
    console.log('done');
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
