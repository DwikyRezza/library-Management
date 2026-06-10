export const WINDOW_SIZE = 10;

const clampPage = (page, totalPages) => Math.min(
    Math.max(Number.parseInt(page, 10) || 1, 1),
    Math.max(Number.parseInt(totalPages, 10) || 1, 1),
);

export function resolvePdfWasmUrl(moduleUrl, isDevelopment) {
    return new URL(
        isDevelopment ? '/pdfjs/wasm/' : '../pdfjs/wasm/',
        moduleUrl,
    ).href;
}

export function calculateInitialWindow(page, totalPages) {
    const safeTotal = Math.max(Number.parseInt(totalPages, 10) || 1, 1);
    const targetPage = clampPage(page, safeTotal);

    if (safeTotal <= WINDOW_SIZE) {
        return { start: 1, end: safeTotal };
    }

    let start = targetPage <= WINDOW_SIZE ? 1 : targetPage - 6;
    start = Math.min(Math.max(start, 1), safeTotal - WINDOW_SIZE + 1);

    return {
        start,
        end: Math.min(start + WINDOW_SIZE - 1, safeTotal),
    };
}

export function calculateAdjacentWindow(windowRange, totalPages, direction) {
    const safeTotal = Math.max(Number.parseInt(totalPages, 10) || 1, 1);
    const step = direction < 0 ? -WINDOW_SIZE : WINDOW_SIZE;
    const requestedStart = windowRange.start + step;
    const start = Math.min(Math.max(requestedStart, 1), safeTotal);

    return {
        start,
        end: Math.min(start + WINDOW_SIZE - 1, safeTotal),
    };
}

export function buildRenderPriority(targetPage, startPage, endPage) {
    const start = Math.max(Number.parseInt(startPage, 10) || 1, 1);
    const end = Math.max(Number.parseInt(endPage, 10) || start, start);
    const target = Math.min(Math.max(Number.parseInt(targetPage, 10) || start, start), end);
    const priority = [target];
    let distance = 1;

    while (priority.length < end - start + 1) {
        const nextPage = target + distance;
        const previousPage = target - distance;

        if (nextPage <= end) {
            priority.push(nextPage);
        }

        if (previousPage >= start) {
            priority.push(previousPage);
        }

        distance += 1;
    }

    return priority;
}

export function pagesOutsideCache(activePage, pageNumbers, radius) {
    const center = Number.parseInt(activePage, 10) || 1;
    const safeRadius = Math.max(Number.parseInt(radius, 10) || 0, 0);

    return pageNumbers.filter((page) => Math.abs(page - center) > safeRadius);
}
