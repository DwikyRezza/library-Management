import test from 'node:test';
import assert from 'node:assert/strict';

import {
    annotationIntersectsPoint,
    buildRenderPriority,
    calculateAdjacentWindow,
    calculateInitialWindow,
    commitAnnotationHistory,
    createAnnotationHistory,
    eraseAnnotationsAtPoint,
    normalizeAnnotationPoint,
    pagesOutsideCache,
    redoAnnotationHistory,
    resolveNavigationWindow,
    resolvePdfAssetUrls,
    resolvePdfWasmUrl,
    undoAnnotationHistory,
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

test('page navigation advances by a full batch at the window boundary', () => {
    assert.deepEqual(resolveNavigationWindow(11, { start: 1, end: 10 }, 324), {
        start: 11,
        end: 20,
    });
    assert.deepEqual(resolveNavigationWindow(10, { start: 11, end: 20 }, 324), {
        start: 1,
        end: 10,
    });
});

test('direct page navigation centers the resume window around the target', () => {
    assert.deepEqual(resolveNavigationWindow(37, { start: 1, end: 10 }, 324), {
        start: 31,
        end: 40,
    });
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

test('annotation pointer coordinates are normalized and clamped to the page', () => {
    const bounds = {
        left: 100,
        top: 200,
        width: 400,
        height: 800,
    };

    assert.deepEqual(
        normalizeAnnotationPoint({ clientX: 300, clientY: 600 }, bounds),
        { x: 0.5, y: 0.5 },
    );
    assert.deepEqual(
        normalizeAnnotationPoint({ clientX: 20, clientY: 1200 }, bounds),
        { x: 0, y: 1 },
    );
});

test('annotation collision detects a point near a normalized stroke segment', () => {
    const annotation = {
        id: 'stroke-1',
        type: 'pen',
        brush_size: 0.01,
        points: [
            { x: 0.1, y: 0.1 },
            { x: 0.9, y: 0.9 },
        ],
    };

    assert.equal(
        annotationIntersectsPoint(annotation, { x: 0.51, y: 0.49 }, 0.02),
        true,
    );
    assert.equal(
        annotationIntersectsPoint(annotation, { x: 0.9, y: 0.2 }, 0.02),
        false,
    );
});

test('eraser removes only annotations that collide with its normalized point', () => {
    const annotations = [
        {
            id: 'hit',
            type: 'highlighter',
            brush_size: 0.03,
            points: [{ x: 0.2, y: 0.2 }, { x: 0.8, y: 0.2 }],
        },
        {
            id: 'keep',
            type: 'pen',
            brush_size: 0.01,
            points: [{ x: 0.2, y: 0.8 }, { x: 0.8, y: 0.8 }],
        },
        {
            id: 'note',
            type: 'text',
            brush_size: 0.02,
            points: [{ x: 0.5, y: 0.5 }],
            content: 'Catatan',
        },
    ];

    const result = eraseAnnotationsAtPoint(
        annotations,
        { x: 0.5, y: 0.21 },
        0.025,
    );

    assert.deepEqual(result.removedIds, ['hit']);
    assert.deepEqual(result.annotations.map(({ id }) => id), ['keep', 'note']);
    assert.equal(annotations.length, 3, 'source annotation array stays immutable');
});

test('annotation history supports immutable undo and redo snapshots', () => {
    const initial = [{ id: 'one', type: 'pen', points: [{ x: 0.1, y: 0.1 }] }];
    let history = createAnnotationHistory(initial);
    history = commitAnnotationHistory(history, [
        ...initial,
        { id: 'two', type: 'text', points: [{ x: 0.2, y: 0.2 }], content: 'Note' },
    ]);

    const undone = undoAnnotationHistory(history);
    assert.deepEqual(undone.current, initial);
    assert.equal(undone.canRedo, true);

    initial[0].points[0].x = 0.9;
    assert.equal(undone.current[0].points[0].x, 0.1);

    const redone = redoAnnotationHistory(undone);
    assert.deepEqual(redone.current.map(({ id }) => id), ['one', 'two']);
    assert.equal(redone.canUndo, true);
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

test('matching global PDF.js configuration uses the pinned CDN assets', () => {
    assert.deepEqual(
        resolvePdfAssetUrls(
            'https://library.example/build/assets/reader-abc123.js',
            false,
            {
                version: '6.0.227',
                wasmUrl: 'https://cdn.example/pdfjs/wasm/',
                cMapUrl: 'https://cdn.example/pdfjs/cmaps/',
                standardFontDataUrl: 'https://cdn.example/pdfjs/standard_fonts/',
            },
            '6.0.227',
        ),
        {
            wasmUrl: 'https://cdn.example/pdfjs/wasm/',
            cMapUrl: 'https://cdn.example/pdfjs/cmaps/',
            standardFontDataUrl: 'https://cdn.example/pdfjs/standard_fonts/',
        },
    );
});

test('mismatched global PDF.js version falls back to local build assets', () => {
    assert.deepEqual(
        resolvePdfAssetUrls(
            'https://library.example/build/assets/reader-abc123.js',
            false,
            {
                version: '4.0.359',
                wasmUrl: 'https://cdn.example/pdfjs/wasm/',
                cMapUrl: 'https://cdn.example/pdfjs/cmaps/',
                standardFontDataUrl: 'https://cdn.example/pdfjs/standard_fonts/',
            },
            '6.0.227',
        ),
        {
            wasmUrl: 'https://library.example/build/pdfjs/wasm/',
            cMapUrl: 'https://library.example/build/pdfjs/cmaps/',
            standardFontDataUrl: 'https://library.example/build/pdfjs/standard_fonts/',
        },
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
    assert.ok(emittedFiles.includes('pdfjs/cmaps/78-H.bcmap'));
    assert.ok(emittedFiles.includes('pdfjs/standard_fonts/FoxitSerif.pfb'));
});
