import { promises as fs } from 'node:fs';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { createCanvas, loadImage } from '@napi-rs/canvas';

const [inputPath, outputPath] = process.argv.slice(2);

if (!inputPath || !outputPath) {
    throw new Error('Usage: node scripts/watermark-page.mjs <input.png> <output.png>');
}

let payloadText = '';
for await (const chunk of process.stdin) {
    payloadText += chunk;
}

const payload = JSON.parse(payloadText);
const lines = Array.isArray(payload.lines) ? payload.lines.map(String) : [];

if (lines.length === 0) {
    throw new Error('Watermark text is required.');
}

const image = await loadImage(inputPath);
const canvas = createCanvas(image.width, image.height);
const context = canvas.getContext('2d');
context.drawImage(image, 0, 0);

const fontSize = Math.max(15, Math.round(image.width / 55));
const stepX = Math.max(360, Math.round(image.width * 0.42));
const stepY = Math.max(240, Math.round(image.height * 0.25));

context.save();
context.translate(image.width / 2, image.height / 2);
context.rotate(-Math.PI / 6);
context.translate(-image.width / 2, -image.height / 2);
context.font = `600 ${fontSize}px sans-serif`;
context.textAlign = 'center';
context.textBaseline = 'middle';

for (let y = -image.height; y < image.height * 2; y += stepY) {
    for (let x = -image.width; x < image.width * 2; x += stepX) {
        lines.forEach((line, index) => {
            const offset = (index - (lines.length - 1) / 2) * fontSize * 1.35;
            context.fillStyle = index === 0 ? 'rgba(15, 23, 42, 0.18)' : 'rgba(15, 23, 42, 0.13)';
            context.fillText(line, x, y + offset);
        });
    }
}

context.restore();

await fs.mkdir(path.dirname(outputPath), { recursive: true });
const temporaryPath = `${outputPath}.tmp-${randomUUID()}`;
await fs.writeFile(temporaryPath, canvas.toBuffer('image/png'));
await fs.rename(temporaryPath, outputPath);
