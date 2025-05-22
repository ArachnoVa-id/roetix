import 'flowbite';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import ErrorPage from './Components/ErrorPage';

document.addEventListener('DOMContentLoaded', () => {
    const errorDiv = document.getElementById('error-page');

    if (errorDiv) {
        const code = Number(errorDiv.getAttribute('data-code')) || 500; // Convert to number, default to 500
        const message = errorDiv.getAttribute('data-message') || '';
        const headers = JSON.parse(
            errorDiv.getAttribute('data-headers') || '{}',
        );

        const root = createRoot(errorDiv);
        root.render(
            <ErrorPage code={code} message={message} headers={headers} />,
        );
        return;
    }

    createInertiaApp({
        title: (title) => `${title}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob('./Pages/**/*.tsx'),
            ),
        setup({ el, App, props }) {
            const root = createRoot(el);

            root.render(<App {...props} />);
        },
        progress: {
            color: '#4B5563',
        },
    });
});
