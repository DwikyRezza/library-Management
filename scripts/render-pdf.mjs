import { promises as fs } from 'node:fs';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { createCanvas, DOMMatrix, ImageData, Path2D } from '@napi-rs/canvas';

const [inputPath, outputPath, scaleArgument = '1.6'] = process.argv.slice(2);

if (!inputPath || !outputPath) {
    throw new Error('Usage: node scripts/render-pdf.mjs <input.pdf> <output-directory> [scale]');
}

globalThis.DOMMatrix ??= DOMMatrix;
globalThis.ImageData ??= ImageData;
globalThis.Path2D ??= Path2D;

const { getDocument } = await import('pdfjs-dist/legacy/build/pdf.mjs');
const scale = Number.parseFloat(scaleArgument);
const temporaryPath = `${outputPath}.tmp-${randomUUID()}`;

await fs.mkdir(path.dirname(outputPath), { recursive: true });
await fs.mkdir(temporaryPath, { recursive: true });

try {
    const source = new Uint8Array(await fs.readFile(inputPath));
    const document = await getDocument({
        data: source,
        disableWorker: true,
        useSystemFonts: true,
    }).promise;

    for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber += 1) {
        const page = await document.getPage(pageNumber);
        const viewport = page.getViewport({ scale });
        const canvas = createCanvas(Math.ceil(viewport.width), Math.ceil(viewport.height));
        const context = canvas.getContext('2d');

        await page.render({ canvasContext: context, viewport }).promise;

        const filename = `page-${String(pageNumber).padStart(4, '0')}.png`;
        await fs.writeFile(path.join(temporaryPath, filename), canvas.toBuffer('image/png'));
        page.cleanup();
    }

    await fs.rm(outputPath, { recursive: true, force: true });
    await fs.rename(temporaryPath, outputPath);

    process.stdout.write(JSON.stringify({ pageCount: document.numPages }));
} catch (error) {
    await fs.rm(temporaryPath, { recursive: true, force: true });
    throw error;
}
