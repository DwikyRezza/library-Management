import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildRenderPriority,
    calculateAdjacentWindow,
    calculateInitialWindow,
    pagesOutsideCache,
    resolvePdfWasmUrl,
} from '../../resources/js/reader-core.js';
import viteConfig from '../../vite.config.js';

test('initial reading starts with pages 1 through 10', () => {
    assert.deepEqual(calculateInitialWindow(1, 120), { start: 1, end: 10 });
});

test('resume reading places six pages before the active page', () => {
    assert.deepEqual(calculateInitialWindow(37, 120), { start: 31, end: 40 });
});

test('initial window stays inside the final document page', () => {
    assert.deepEqual(calculateInitialWindow(118, 120), { start: 111, end: 120 });
});

test('short documents use all available pages', () => {
    assert.deepEqual(calculateInitialWindow(3, 6), { start: 1, end: 6 });
});

test('adjacent navigation moves by a complete window', () => {
    assert.deepEqual(
        calculateAdjacentWindow({ start: 31, end: 40 }, 120, 1),
        { start: 41, end: 50 },
    );
    assert.deepEqual(
        calculateAdjacentWindow({ start: 31, end: 40 }, 120, -1),
        { start: 21, end: 30 },
    );
});

test('adjacent navigation clamps the last window', () => {
    assert.deepEqual(
        calculateAdjacentWindow({ start: 111, end: 120 }, 123, 1),
        { start: 121, end: 123 },
    );
});

test('render priority favors the target then the next reading page', () => {
    assert.deepEqual(
        buildRenderPriority(37, 31, 40),
        [37, 38, 36, 39, 35, 40, 34, 33, 32, 31],
    );
});

test('cache eviction returns only pages outside the active radius', () => {
    assert.deepEqual(
        pagesOutsideCache(37, [1, 22, 37, 52, 53], 15),
        [1, 53],
    );
});

test('PDF.js decoder URL resolves beside production build assets', () => {
    assert.equal(
        resolvePdfWasmUrl(
            'https://library.example/build/assets/reader-abc123.js',
            false,
        ),
        'https://library.example/build/pdfjs/wasm/',
    );
});

test('PDF.js decoder URL resolves from the Vite development origin', () => {
    assert.equal(
        resolvePdfWasmUrl(
            'http://localhost:5173/resources/js/reader.js',
            true,
        ),
        'http://localhost:5173/pdfjs/wasm/',
    );
});

test('Vite emits the PDF.js decoder runtime files', () => {
    const plugin = viteConfig.plugins.find(({ name }) => name === 'pdfjs-wasm-assets');

    assert.ok(plugin, 'pdfjs-wasm-assets plugin is registered');

    const emittedFiles = [];

    plugin.generateBundle.call({
        emitFile(asset) {
            emittedFiles.push(asset.fileName);
        },
    });

    assert.ok(emittedFiles.includes('pdfjs/wasm/jbig2.wasm'));
    assert.ok(emittedFiles.includes('pdfjs/wasm/jbig2_nowasm_fallback.js'));
    assert.ok(emittedFiles.includes('pdfjs/wasm/openjpeg.wasm'));
    assert.ok(emittedFiles.includes('pdfjs/wasm/qcms_bg.wasm'));
});
