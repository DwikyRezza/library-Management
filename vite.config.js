import { createReadStream, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const pdfJsAssetGroups = ['wasm', 'cmaps', 'standard_fonts'].map((directory) => {
    const sourceDirectory = path.resolve(`node_modules/pdfjs-dist/${directory}`);

    return {
        directory,
        sourceDirectory,
        files: readdirSync(sourceDirectory, { withFileTypes: true })
            .filter((entry) => entry.isFile())
            .map((entry) => entry.name),
    };
});

const pdfJsContentType = (filename) => {
    if (filename.endsWith('.wasm')) {
        return 'application/wasm';
    }

    if (filename.endsWith('.js')) {
        return 'text/javascript; charset=utf-8';
    }

    if (filename.endsWith('.ttf')) {
        return 'font/ttf';
    }

    return 'application/octet-stream';
};

export function pdfJsWasmAssets() {
    const serveAsset = (assetGroup) => (request, response, next) => {
        const filename = path.posix.basename(new URL(
            request.url || '/',
            'http://localhost',
        ).pathname);

        if (!assetGroup.files.includes(filename)) {
            next();

            return;
        }

        response.setHeader('Content-Type', pdfJsContentType(filename));
        createReadStream(path.join(assetGroup.sourceDirectory, filename)).pipe(response);
    };

    return {
        name: 'pdfjs-wasm-assets',
        configureServer(server) {
            for (const assetGroup of pdfJsAssetGroups) {
                server.middlewares.use(
                    `/pdfjs/${assetGroup.directory}/`,
                    serveAsset(assetGroup),
                );
            }
        },
        generateBundle() {
            for (const assetGroup of pdfJsAssetGroups) {
                for (const filename of assetGroup.files) {
                    this.emitFile({
                        type: 'asset',
                        fileName: `pdfjs/${assetGroup.directory}/${filename}`,
                        source: readFileSync(path.join(
                            assetGroup.sourceDirectory,
                            filename,
                        )),
                    });
                }
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
