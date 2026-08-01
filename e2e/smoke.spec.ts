import { expect, test } from '@playwright/test';

test('homepage shortener creates a result', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /Short links/i })).toBeVisible();
    await page.getByLabel('Destination URL').fill('https://azshrtr.com/playwright');
    await page.getByRole('button', { name: 'Shorten' }).click();
    await expect(page.locator('#shorten-result')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('#result-url')).toContainText('azshrtr');
});

test('console login page renders', async ({ page }) => {
    await page.goto('/console/login');
    await expect(page.getByRole('heading', { name: 'azshrtr' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();
});
