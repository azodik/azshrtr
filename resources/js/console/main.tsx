import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { App } from './App';
import { bootstrapI18n } from './i18n/useI18n';
import { AppProviders } from './providers/AppProviders';

bootstrapI18n();

const rootElement = document.getElementById('console-root');

if (!rootElement) {
    throw new Error('Console root element #console-root was not found.');
}

createRoot(rootElement).render(
    <StrictMode>
        <AppProviders>
            <BrowserRouter basename="/console">
                <App />
            </BrowserRouter>
        </AppProviders>
    </StrictMode>,
);
