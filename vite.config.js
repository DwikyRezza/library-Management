import { createReadStream, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const pdfJsWasmDirectory = path.resolve('node_modules/pdfjs-dist/wasm');
const pdfJsWasmFiles = readdirSync(pdfJsWasmDirectory, { withFileTypes: true })
    .filter((entry) => entry.isFile())
    .map((entry) => entry.name);

const pdfJsContentType = (filename) => {
    if (filename.endsWith('.wasm')) {
        return 'application/wasm';
    }

    if (filename.endsWith('.js')) {
        return 'text/javascript; charset=utf-8';
    }

    return 'text/plain; charset=utf-8';
};

export function pdfJsWasmAssets() {
    const serveWasmAsset = (request, response, next) => {
        const filename = path.posix.basename(
            new URL(request.url || '/', 'http://localhost').pathname,
        );

        if (!pdfJsWasmFiles.includes(filename)) {
            next();

            return;
        }

        response.setHeader('Content-Type', pdfJsContentType(filename));
        createReadStream(path.join(pdfJsWasmDirectory, filename)).pipe(response);
    };

    return {
        name: 'pdfjs-wasm-assets',
        configureServer(server) {
            server.middlewares.use('/pdfjs/wasm/', serveWasmAsset);
        },
        generateBundle() {
            for (const filename of pdfJsWasmFiles) {
                this.emitFile({
                    type: 'asset',
                    fileName: `pdfjs/wasm/${filename}`,
                    source: readFileSync(path.join(pdfJsWasmDirectory, filename)),
                });
            }
        },
    };
}

export default defineConfig({
    plugins: [
        pdfJsWasmAssets(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/reader.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
