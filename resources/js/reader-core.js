export const WINDOW_SIZE = 10;

const clampUnit = (value) => Math.min(1, Math.max(0, value));
const cloneValue = (value) => (
    typeof structuredClone === 'function'
        ? structuredClone(value)
        : JSON.parse(JSON.stringify(value))
);

const clampPage = (page, totalPages) => Math.min(
    Math.max(Number.parseInt(page, 10) || 1, 1),
    Math.max(Number.parseInt(totalPages, 10) || 1, 1),
);

export function normalizeAnnotationPoint(pointer, bounds) {
    const width = Math.max(Number(bounds?.width) || 0, 1);
    const height = Math.max(Number(bounds?.height) || 0, 1);
    const x = (Number(pointer?.clientX) - (Number(bounds?.left) || 0)) / width;
    const y = (Number(pointer?.clientY) - (Number(bounds?.top) || 0)) / height;

    return {
        x: Number(clampUnit(x).toFixed(6)),
        y: Number(clampUnit(y).toFixed(6)),
    };
}

const distanceToSegment = (point, start, end) => {
    const segmentX = end.x - start.x;
    const segmentY = end.y - start.y;
    const segmentLengthSquared = (segmentX * segmentX) + (segmentY * segmentY);

    if (segmentLengthSquared === 0) {
        return Math.hypot(point.x - start.x, point.y - start.y);
    }

    const projection = clampUnit(
        (((point.x - start.x) * segmentX) + ((point.y - start.y) * segmentY))
        / segmentLengthSquared,
    );

    return Math.hypot(
        point.x - (start.x + (projection * segmentX)),
        point.y - (start.y + (projection * segmentY)),
    );
};

export function annotationIntersectsPoint(annotation, point, radius = 0.015) {
    const points = Array.isArray(annotation?.points) ? annotation.points : [];

    if (points.length === 0) {
        return false;
    }

    const collisionRadius = Math.max(
        Number(radius) || 0,
        (Number(annotation.brush_size) || 0) / 2,
    );

    if (points.length === 1) {
        return Math.hypot(
            point.x - Number(points[0].x),
            point.y - Number(points[0].y),
        ) <= collisionRadius;
    }

    return points.slice(1).some((end, index) => distanceToSegment(
        point,
        points[index],
        end,
    ) <= collisionRadius);
}

export function eraseAnnotationsAtPoint(annotations, point, radius) {
    const removedIds = [];
    const remaining = annotations.filter((annotation) => {
        if (!annotationIntersectsPoint(annotation, point, radius)) {
            return true;
        }

        removedIds.push(annotation.id);

        return false;
    });

    return {
        annotations: remaining,
        removedIds,
    };
}

const annotationHistoryState = (past, current, future) => ({
    past,
    current,
    future,
    canUndo: past.length > 0,
    canRedo: future.length > 0,
});

export function createAnnotationHistory(annotations = []) {
    return annotationHistoryState([], cloneValue(annotations), []);
}

export function commitAnnotationHistory(history, annotations, limit = 50) {
    const past = [
        ...history.past,
        cloneValue(history.current),
    ].slice(-Math.max(1, Number.parseInt(limit, 10) || 50));

    return annotationHistoryState(past, cloneValue(annotations), []);
}

export function undoAnnotationHistory(history) {
    if (history.past.length === 0) {
        return annotationHistoryState(
            cloneValue(history.past),
            cloneValue(history.current),
            cloneValue(history.future),
        );
    }

    const past = cloneValue(history.past);
    const current = past.pop();
    const future = [
        cloneValue(history.current),
        ...cloneValue(history.future),
    ];

    return annotationHistoryState(past, current, future);
}

export function redoAnnotationHistory(history) {
    if (history.future.length === 0) {
        return annotationHistoryState(
            cloneValue(history.past),
            cloneValue(history.current),
            cloneValue(history.future),
        );
    }

    const future = cloneValue(history.future);
    const current = future.shift();
    const past = [
        ...cloneValue(history.past),
        cloneValue(history.current),
    ];

    return annotationHistoryState(past, current, future);
}

export function resolvePdfWasmUrl(moduleUrl, isDevelopment) {
    return new URL(
        isDevelopment ? '/pdfjs/wasm/' : '../pdfjs/wasm/',
        moduleUrl,
    ).href;
}

export function resolvePdfAssetUrls(
    moduleUrl,
    isDevelopment,
    configuredAssets,
    expectedVersion,
) {
    const localAssetUrl = (directory) => new URL(
        isDevelopment ? `/pdfjs/${directory}/` : `../pdfjs/${directory}/`,
        moduleUrl,
    ).href;
    const hasMatchingConfiguration = configuredAssets
        && configuredAssets.version === expectedVersion
        && ['wasmUrl', 'cMapUrl', 'standardFontDataUrl'].every(
            (key) => typeof configuredAssets[key] === 'string'
                && configuredAssets[key].endsWith('/'),
        );

    if (hasMatchingConfiguration) {
        return {
            wasmUrl: configuredAssets.wasmUrl,
            cMapUrl: configuredAssets.cMapUrl,
            standardFontDataUrl: configuredAssets.standardFontDataUrl,
        };
    }

    return {
        wasmUrl: resolvePdfWasmUrl(moduleUrl, isDevelopment),
        cMapUrl: localAssetUrl('cmaps'),
        standardFontDataUrl: localAssetUrl('standard_fonts'),
    };
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

export function resolveNavigationWindow(targetPage, windowRange, totalPages) {
    const target = clampPage(targetPage, totalPages);

    if (target === windowRange.end + 1) {
        return calculateAdjacentWindow(windowRange, totalPages, 1);
    }

    if (target === windowRange.start - 1) {
        return calculateAdjacentWindow(windowRange, totalPages, -1);
    }

    return calculateInitialWindow(target, totalPages);
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
