import '../css/reader.css';
import {
    getDocument,
    GlobalWorkerOptions,
    TextLayer,
    version as pdfjsVersion,
} from 'pdfjs-dist';
import {
    commitAnnotationHistory,
    createAnnotationHistory,
    eraseAnnotationsAtPoint,
    buildRenderPriority,
    calculateAdjacentWindow,
    calculateInitialWindow,
    normalizeAnnotationPoint,
    pagesOutsideCache,
    redoAnnotationHistory,
    resolvePdfAssetUrls,
    resolveNavigationWindow,
    undoAnnotationHistory,
} from './reader-core.js';

if (typeof window !== 'undefined' && !GlobalWorkerOptions.workerPort) {
    GlobalWorkerOptions.workerPort = new Worker(
        new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url),
        { type: 'module' },
    );
}

const reader = document.querySelector('[data-pdf-reader]');

if (reader) {
    const MAX_CONCURRENT_RENDERS = 2;
    const pdfAssetUrls = resolvePdfAssetUrls(
        import.meta.url,
        import.meta.env.DEV,
        window.libraFlowPdfConfig,
        pdfjsVersion,
    );
    const stage = document.getElementById('readerStage');
    const pagesContainer = document.getElementById('readerPages');
    const pageTemplate = document.getElementById('readerPageTemplate');
    const renderStatus = document.getElementById('readerRenderStatus');
    const highlightPopover = document.getElementById('readerHighlightPopover');
    const highlightsData = document.getElementById('readerHighlightsData');
    const loadingState = document.getElementById('readerLoading');
    const errorState = document.getElementById('readerError');
    const retryButton = document.getElementById('readerRetry');
    const previousButton = document.getElementById('readerPrevious');
    const nextButton = document.getElementById('readerNext');
    const zoomOutButton = document.getElementById('readerZoomOut');
    const zoomInButton = document.getElementById('readerZoomIn');
    const zoomLabel = document.getElementById('readerZoom');
    const pageLabel = document.getElementById('readerPage');
    const totalLabel = document.getElementById('readerTotal');
    const saveStatus = document.getElementById('readerSaveStatus');
    const thumbnailList = document.getElementById('readerThumbnailList');
    const sidebarCount = document.getElementById('readerSidebarCount');
    const toggleSidebarButton = document.getElementById('readerToggleSidebar');
    const closeSidebarButton = document.getElementById('readerCloseSidebar');
    const sidebarBackdrop = document.getElementById('readerSidebarBackdrop');
    const toolButtons = [...document.querySelectorAll('[data-annotation-tool]')];
    const annotationColorInput = document.getElementById('readerAnnotationColor');
    const brushSizeInput = document.getElementById('readerBrushSize');
    const undoButton = document.getElementById('readerUndo');
    const redoButton = document.getElementById('readerRedo');
    const floatingZoomOutButton = document.getElementById('readerFloatingZoomOut');
    const floatingZoomInButton = document.getElementById('readerFloatingZoomIn');
    const fullscreenButton = document.getElementById('readerFullscreen');
    const searchForm = document.getElementById('readerSearchForm');
    const searchInput = document.getElementById('readerSearchInput');
    const printButton = document.getElementById('readerPrint');
    const textNoteDialog = document.getElementById('readerTextNoteDialog');
    const textNoteContent = document.getElementById('readerTextNoteContent');
    const saveTextNoteButton = document.getElementById('readerSaveTextNote');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let loadingTask = null;
    let pdfDocument = null;
    let currentPage = Number.parseInt(reader.dataset.initialPage, 10) || 1;
    let totalPages = 0;
    let zoom = 100;
    let activeGeneration = 0;
    let activeWindow = { start: 1, end: 1 };
    let pageStates = new Map();
    let observer = null;
    let thumbnailObserver = null;
    let renderQueue = [];
    let runningRenders = 0;
    let navigating = false;
    let searching = false;
    let resizeTimer = null;
    let zoomTimer = null;
    let heartbeatTimer = null;
    let statusTimer = null;
    let preloadTimer = null;
    let pendingHighlight = null;
    let pendingTextNote = null;
    let activeTool = null;
    let activeAnnotationGesture = null;
    let highlights = [];
    const activeRenderPages = new Set();
    const metadataCache = new Map();
    const thumbnailStates = new Map();
    const annotationsByPage = new Map();
    const annotationHistories = new Map();
    const loadedAnnotationPages = new Set();
    const annotationSaveTimers = new Map();

    try {
        highlights = JSON.parse(highlightsData?.textContent || '[]');
    } catch {
        highlights = [];
    }

    const waitForLayout = () => new Promise((resolve) => {
        window.requestAnimationFrame(() => window.requestAnimationFrame(resolve));
    });

    const setDocumentLoading = (loading) => {
        loadingState.classList.toggle('hidden', !loading);

        if (loading) {
            pagesContainer.classList.add('invisible');
        }
    };

    const setDocumentError = (hasError) => {
        errorState.classList.toggle('hidden', !hasError);
        errorState.classList.toggle('grid', hasError);
        pagesContainer.classList.toggle('hidden', hasError);
    };

    const setSaveStatus = (message, failed = false) => {
        window.clearTimeout(statusTimer);
        saveStatus.textContent = message;
        saveStatus.classList.toggle('is-error', failed);
        statusTimer = window.setTimeout(() => {
            saveStatus.textContent = '';
            saveStatus.classList.remove('is-error');
        }, 2200);
    };

    const updateControls = () => {
        const annotationHistory = annotationHistories.get(currentPage);

        pageLabel.textContent = String(currentPage);
        totalLabel.textContent = totalPages > 0 ? String(totalPages) : '-';
        zoomLabel.textContent = `${zoom}%`;
        previousButton.disabled = navigating || currentPage <= 1;
        nextButton.disabled = navigating || totalPages === 0 || currentPage >= totalPages;
        zoomOutButton.disabled = navigating || zoom <= 60;
        zoomInButton.disabled = navigating || zoom >= 180;
        floatingZoomOutButton.disabled = navigating || zoom <= 60;
        floatingZoomInButton.disabled = navigating || zoom >= 180;
        undoButton.disabled = !annotationHistory?.canUndo;
        redoButton.disabled = !annotationHistory?.canRedo;

        thumbnailStates.forEach((thumbnail, pageNumber) => {
            thumbnail.button.classList.toggle('is-active', pageNumber === currentPage);
            thumbnail.button.setAttribute(
                'aria-current',
                pageNumber === currentPage ? 'page' : 'false',
            );
        });
    };

    const updateRenderStatus = () => {
        const runningStates = [...activeRenderPages]
            .filter((state) => state.generation === activeGeneration);
        const renderingState = runningStates.find((state) => state.pageNumber === currentPage)
            || runningStates[0];
        const renderingPage = renderingState?.pageNumber;

        if (!renderingPage) {
            renderStatus.classList.add('hidden');
            renderStatus.textContent = '';

            return;
        }

        const windowSize = activeWindow.end - activeWindow.start + 1;
        const windowPosition = renderingPage - activeWindow.start + 1;
        renderStatus.textContent = `Memuat halaman ${windowPosition} dari ${windowSize}...`;
        renderStatus.classList.remove('hidden');
    };

    const sendHeartbeat = (finish = false) => {
        if (totalPages < 1) {
            return;
        }

        const url = finish ? reader.dataset.finishUrl : reader.dataset.heartbeatUrl;

        fetch(url, {
            method: 'POST',
            keepalive: finish,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                page: currentPage,
                total_pages: totalPages,
            }),
        }).catch(() => {});
    };

    const scheduleHeartbeat = () => {
        window.clearTimeout(heartbeatTimer);
        heartbeatTimer = window.setTimeout(sendHeartbeat, 900);
    };

    const hideHighlightPopover = () => {
        highlightPopover.classList.add('hidden');
        pendingHighlight = null;
    };

    const pageHighlights = (pageNumber) => highlights.filter(
        (highlight) => Number(highlight.page_number) === pageNumber,
    );

    const renderHighlights = (state) => {
        state.highlightLayer.replaceChildren();

        for (const highlight of pageHighlights(state.pageNumber)) {
            const rects = highlight.serialized_range?.rects;

            if (!Array.isArray(rects)) {
                continue;
            }

            for (const rect of rects) {
                const mark = document.createElement('div');
                mark.className = 'reader-highlight-mark';
                mark.dataset.highlightId = String(highlight.id);
                mark.style.left = `${Number(rect.x) * 100}%`;
                mark.style.top = `${Number(rect.y) * 100}%`;
                mark.style.width = `${Number(rect.width) * 100}%`;
                mark.style.height = `${Number(rect.height) * 100}%`;
                mark.style.backgroundColor = highlight.color;
                state.highlightLayer.append(mark);
            }
        }
    };

    const closestTextNode = (node) => {
        const element = node?.nodeType === Node.TEXT_NODE ? node.parentElement : node;

        return element instanceof Element ? element.closest('span, br') : null;
    };

    const normalizedNumber = (value) => Math.min(1, Math.max(0, Number(value.toFixed(6))));

    const serializeSelection = (state) => {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
            return null;
        }

        const range = selection.getRangeAt(0);
        const startElement = closestTextNode(range.startContainer);
        const endElement = closestTextNode(range.endContainer);

        if (
            !startElement
            || !endElement
            || !state.textLayer.contains(startElement)
            || !state.textLayer.contains(endElement)
        ) {
            return null;
        }

        const textNodes = [...state.textLayer.querySelectorAll('span, br')];
        const startIndex = textNodes.indexOf(startElement);
        const endIndex = textNodes.indexOf(endElement);
        const layerBounds = state.textLayer.getBoundingClientRect();

        if (startIndex < 0 || endIndex < 0 || layerBounds.width <= 0 || layerBounds.height <= 0) {
            return null;
        }

        const rects = [...range.getClientRects()]
            .map((rect) => {
                const left = Math.max(rect.left, layerBounds.left);
                const top = Math.max(rect.top, layerBounds.top);
                const right = Math.min(rect.right, layerBounds.right);
                const bottom = Math.min(rect.bottom, layerBounds.bottom);

                if (right <= left || bottom <= top) {
                    return null;
                }

                return {
                    x: normalizedNumber((left - layerBounds.left) / layerBounds.width),
                    y: normalizedNumber((top - layerBounds.top) / layerBounds.height),
                    width: normalizedNumber((right - left) / layerBounds.width),
                    height: normalizedNumber((bottom - top) / layerBounds.height),
                };
            })
            .filter(Boolean);

        const highlightedText = selection.toString().replace(/\s+/g, ' ').trim().slice(0, 5000);

        if (!highlightedText || rects.length === 0) {
            return null;
        }

        return {
            pageNumber: state.pageNumber,
            highlightedText,
            serializedRange: {
                version: 1,
                start: {
                    index: startIndex,
                    offset: range.startOffset,
                },
                end: {
                    index: endIndex,
                    offset: range.endOffset,
                },
                rects,
            },
            bounds: range.getBoundingClientRect(),
        };
    };

    const showHighlightPopover = (state) => {
        const selectionData = serializeSelection(state);

        if (!selectionData) {
            hideHighlightPopover();

            return;
        }

        pendingHighlight = selectionData;
        const centerX = selectionData.bounds.left + (selectionData.bounds.width / 2);
        const safeX = Math.min(window.innerWidth - 64, Math.max(64, centerX));
        const safeY = Math.max(64, selectionData.bounds.top);
        highlightPopover.style.left = `${safeX}px`;
        highlightPopover.style.top = `${safeY}px`;
        highlightPopover.classList.remove('hidden');
    };

    const saveHighlight = async (color) => {
        if (!pendingHighlight) {
            return;
        }

        const selectionData = pendingHighlight;
        highlightPopover.classList.add('hidden');
        setSaveStatus('Menyimpan...');

        try {
            const response = await fetch(reader.dataset.highlightStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    digital_loan_id: Number(reader.dataset.digitalLoanId),
                    page_number: selectionData.pageNumber,
                    highlighted_text: selectionData.highlightedText,
                    color,
                    serialized_range: selectionData.serializedRange,
                }),
            });

            if (!response.ok) {
                throw new Error('Highlight could not be saved.');
            }

            const payload = await response.json();
            highlights.push(payload.data);
            const state = pageStates.get(selectionData.pageNumber);

            if (state?.status === 'ready') {
                renderHighlights(state);
            }

            window.getSelection()?.removeAllRanges();
            setSaveStatus('Stabilo tersimpan');
        } catch {
            setSaveStatus('Stabilo gagal disimpan', true);
        } finally {
            pendingHighlight = null;
        }
    };

    const deleteHighlight = async (highlight, state) => {
        setSaveStatus('Menghapus...');

        try {
            const response = await fetch(
                `${reader.dataset.highlightDeleteUrlBase}/${encodeURIComponent(highlight.id)}`,
                {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                },
            );

            if (!response.ok) {
                throw new Error('Highlight could not be deleted.');
            }

            highlights = highlights.filter((item) => Number(item.id) !== Number(highlight.id));
            renderHighlights(state);
            setSaveStatus('Stabilo dihapus');
        } catch {
            setSaveStatus('Stabilo gagal dihapus', true);
        }
    };

    const stateFromTarget = (target) => {
        const frame = target instanceof Element ? target.closest('[data-reader-page]') : null;
        const pageNumber = Number.parseInt(frame?.dataset.pageNumber || '', 10);

        return pageStates.get(pageNumber) || null;
    };

    const annotationId = () => (
        window.crypto?.randomUUID?.()
        || `annotation-${Date.now()}-${Math.random().toString(16).slice(2)}`
    );

    const pageAnnotations = (pageNumber) => {
        if (!annotationsByPage.has(pageNumber)) {
            annotationsByPage.set(pageNumber, []);
        }

        return annotationsByPage.get(pageNumber);
    };

    const ensureAnnotationHistory = (pageNumber) => {
        if (!annotationHistories.has(pageNumber)) {
            annotationHistories.set(
                pageNumber,
                createAnnotationHistory(pageAnnotations(pageNumber)),
            );
        }

        return annotationHistories.get(pageNumber);
    };

    const drawAnnotation = (context, annotation, width, height) => {
        const points = Array.isArray(annotation.points) ? annotation.points : [];

        if (points.length === 0) {
            return;
        }

        const pixelPoints = points.map((point) => ({
            x: Number(point.x) * width,
            y: Number(point.y) * height,
        }));
        const lineWidth = Math.max(1, Number(annotation.brush_size) * width);

        context.save();
        context.strokeStyle = annotation.color;
        context.fillStyle = annotation.color;
        context.lineWidth = lineWidth;
        context.lineCap = 'round';
        context.lineJoin = 'round';

        if (annotation.type === 'highlighter') {
            context.globalAlpha = 0.28;
            context.globalCompositeOperation = 'multiply';
        }

        if (annotation.type === 'text') {
            const anchor = pixelPoints[0];
            const fontSize = Math.max(12, lineWidth * 4);
            const content = String(annotation.content || '').trim();
            const padding = 6;

            context.globalAlpha = 0.92;
            context.font = `600 ${fontSize}px system-ui, sans-serif`;
            context.textBaseline = 'top';
            const textWidth = Math.min(
                context.measureText(content).width,
                Math.max(80, width - anchor.x - (padding * 2)),
            );
            context.fillStyle = 'rgba(15, 23, 42, 0.88)';
            context.fillRect(
                anchor.x - padding,
                anchor.y - padding,
                textWidth + (padding * 2),
                fontSize + (padding * 2),
            );
            context.fillStyle = annotation.color;
            context.fillText(
                content,
                anchor.x,
                anchor.y,
                Math.max(80, width - anchor.x - padding),
            );
            context.restore();

            return;
        }

        context.beginPath();
        context.moveTo(pixelPoints[0].x, pixelPoints[0].y);

        if (pixelPoints.length === 1) {
            context.lineTo(pixelPoints[0].x + 0.01, pixelPoints[0].y + 0.01);
        } else {
            pixelPoints.slice(1).forEach((point) => context.lineTo(point.x, point.y));
        }

        context.stroke();
        context.restore();
    };

    const resizeAndRedrawAnnotations = (state, draft = null) => {
        if (!state?.annotationCanvas || state.width <= 0 || state.height <= 0) {
            return;
        }

        const outputScale = Math.min(window.devicePixelRatio || 1, 2);
        const context = state.annotationCanvas.getContext('2d');
        state.annotationCanvas.width = Math.max(1, Math.floor(state.width * outputScale));
        state.annotationCanvas.height = Math.max(1, Math.floor(state.height * outputScale));
        state.annotationCanvas.style.width = `${state.width}px`;
        state.annotationCanvas.style.height = `${state.height}px`;
        context.setTransform(outputScale, 0, 0, outputScale, 0, 0);
        context.clearRect(0, 0, state.width, state.height);

        pageAnnotations(state.pageNumber).forEach((annotation) => {
            drawAnnotation(context, annotation, state.width, state.height);
        });

        if (draft) {
            drawAnnotation(context, draft, state.width, state.height);
        }
    };

    const saveAnnotationPage = async (pageNumber, keepalive = false) => {
        try {
            const response = await fetch(reader.dataset.annotationStoreUrl, {
                method: 'POST',
                keepalive,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    page_number: pageNumber,
                    data: {
                        version: 1,
                        annotations: pageAnnotations(pageNumber),
                    },
                }),
            });

            if (!response.ok) {
                throw new Error('Annotations could not be saved.');
            }

            setSaveStatus('Anotasi tersimpan');
        } catch {
            setSaveStatus('Anotasi gagal disimpan', true);
        }
    };

    const scheduleAnnotationSave = (pageNumber) => {
        window.clearTimeout(annotationSaveTimers.get(pageNumber));
        setSaveStatus('Menyimpan anotasi...');
        const timer = window.setTimeout(() => {
            annotationSaveTimers.delete(pageNumber);
            saveAnnotationPage(pageNumber);
        }, 800);
        annotationSaveTimers.set(pageNumber, timer);
    };

    const loadAnnotations = async (range) => {
        const rangeAlreadyLoaded = Array.from(
            { length: range.end - range.start + 1 },
            (_, index) => range.start + index,
        ).every((pageNumber) => loadedAnnotationPages.has(pageNumber));

        if (rangeAlreadyLoaded) {
            pageStates.forEach((state) => resizeAndRedrawAnnotations(state));
            updateControls();

            return;
        }

        const url = new URL(reader.dataset.annotationIndexUrl, window.location.href);
        url.searchParams.set('start', String(range.start));
        url.searchParams.set('end', String(range.end));
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Annotations could not be loaded.');
        }

        const payload = await response.json();
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const rowsByPage = new Map(rows.map((row) => [Number(row.page_number), row]));

        for (let pageNumber = range.start; pageNumber <= range.end; pageNumber += 1) {
            if (!annotationSaveTimers.has(pageNumber)) {
                const annotations = rowsByPage.get(pageNumber)?.data?.annotations;
                annotationsByPage.set(pageNumber, Array.isArray(annotations) ? annotations : []);
                annotationHistories.set(
                    pageNumber,
                    createAnnotationHistory(pageAnnotations(pageNumber)),
                );
            }

            loadedAnnotationPages.add(pageNumber);
            resizeAndRedrawAnnotations(pageStates.get(pageNumber));
        }

        updateControls();
    };

    const commitPageAnnotations = (pageNumber, annotations) => {
        const history = commitAnnotationHistory(
            ensureAnnotationHistory(pageNumber),
            annotations,
        );
        annotationHistories.set(pageNumber, history);
        annotationsByPage.set(pageNumber, history.current);
        resizeAndRedrawAnnotations(pageStates.get(pageNumber));
        scheduleAnnotationSave(pageNumber);
        updateControls();
    };

    const setActiveTool = (tool) => {
        activeTool = activeTool === tool ? null : tool;
        reader.classList.toggle('has-active-tool', Boolean(activeTool));

        if (activeTool) {
            reader.dataset.activeTool = activeTool;
            hideHighlightPopover();
            window.getSelection()?.removeAllRanges();
        } else {
            delete reader.dataset.activeTool;
        }

        toolButtons.forEach((button) => {
            const isActive = button.dataset.annotationTool === activeTool;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });
    };

    const startAnnotationGesture = (event) => {
        if (!activeTool || !event.target.matches('[data-page-annotation-layer]')) {
            return;
        }

        const state = stateFromTarget(event.target);

        if (!state || state.status !== 'ready' || !loadedAnnotationPages.has(state.pageNumber)) {
            if (state?.status === 'ready') {
                setSaveStatus('Anotasi masih dimuat', true);
            }

            return;
        }

        event.preventDefault();
        event.target.setPointerCapture?.(event.pointerId);
        const point = normalizeAnnotationPoint(
            event,
            state.annotationCanvas.getBoundingClientRect(),
        );

        if (activeTool !== 'eraser' && pageAnnotations(state.pageNumber).length >= 500) {
            setSaveStatus('Batas anotasi halaman tercapai', true);

            return;
        }

        if (activeTool === 'text') {
            pendingTextNote = { pageNumber: state.pageNumber, point };
            textNoteContent.value = '';
            textNoteDialog.showModal();
            textNoteContent.focus();

            return;
        }

        if (activeTool === 'eraser') {
            const result = eraseAnnotationsAtPoint(pageAnnotations(state.pageNumber), point, 0.025);
            activeAnnotationGesture = {
                type: 'eraser',
                pageNumber: state.pageNumber,
                changed: result.removedIds.length > 0,
            };
            annotationsByPage.set(state.pageNumber, result.annotations);
            resizeAndRedrawAnnotations(state);

            return;
        }

        const brushPixels = Math.max(1, Number(brushSizeInput.value) || 4);
        const annotation = {
            id: annotationId(),
            type: activeTool,
            color: annotationColorInput.value,
            brush_size: Number(Math.min(0.2, brushPixels / state.width).toFixed(6)),
            points: [point],
        };
        activeAnnotationGesture = {
            type: activeTool,
            pageNumber: state.pageNumber,
            annotation,
        };
        resizeAndRedrawAnnotations(state, annotation);
    };

    const continueAnnotationGesture = (event) => {
        if (!activeAnnotationGesture || !event.target.matches('[data-page-annotation-layer]')) {
            return;
        }

        const state = pageStates.get(activeAnnotationGesture.pageNumber);

        if (!state) {
            return;
        }

        event.preventDefault();
        const point = normalizeAnnotationPoint(
            event,
            state.annotationCanvas.getBoundingClientRect(),
        );

        if (activeAnnotationGesture.type === 'eraser') {
            const result = eraseAnnotationsAtPoint(pageAnnotations(state.pageNumber), point, 0.025);

            if (result.removedIds.length > 0) {
                activeAnnotationGesture.changed = true;
                annotationsByPage.set(state.pageNumber, result.annotations);
                resizeAndRedrawAnnotations(state);
            }

            return;
        }

        const points = activeAnnotationGesture.annotation.points;
        const previousPoint = points[points.length - 1];
        const movedEnough = Math.hypot(
            point.x - previousPoint.x,
            point.y - previousPoint.y,
        ) >= 0.001;

        if (movedEnough && points.length < 2000) {
            points.push(point);
        }

        resizeAndRedrawAnnotations(state, activeAnnotationGesture.annotation);
    };

    const finishAnnotationGesture = () => {
        if (!activeAnnotationGesture) {
            return;
        }

        const gesture = activeAnnotationGesture;
        activeAnnotationGesture = null;

        if (gesture.type === 'eraser') {
            if (gesture.changed) {
                commitPageAnnotations(
                    gesture.pageNumber,
                    pageAnnotations(gesture.pageNumber),
                );
            }

            return;
        }

        commitPageAnnotations(
            gesture.pageNumber,
            [...pageAnnotations(gesture.pageNumber), gesture.annotation],
        );
    };

    const handleHighlightClick = (event) => {
        if (activeTool) {
            return;
        }

        const selection = window.getSelection();

        if (!selection?.isCollapsed) {
            return;
        }

        const state = stateFromTarget(event.target);

        if (!state || state.status !== 'ready') {
            return;
        }

        const bounds = state.textLayer.getBoundingClientRect();

        if (bounds.width <= 0 || bounds.height <= 0) {
            return;
        }

        const x = (event.clientX - bounds.left) / bounds.width;
        const y = (event.clientY - bounds.top) / bounds.height;
        const highlight = [...pageHighlights(state.pageNumber)]
            .reverse()
            .find((item) => item.serialized_range?.rects?.some((rect) => (
                x >= Number(rect.x)
                && x <= Number(rect.x) + Number(rect.width)
                && y >= Number(rect.y)
                && y <= Number(rect.y) + Number(rect.height)
            )));

        if (highlight) {
            deleteHighlight(highlight, state);
        }
    };

    const getAvailableWidth = () => Math.max(
        280,
        Math.min(980, stage.clientWidth - 48),
    );

    const getPageMetadata = async (pageNumber) => {
        if (metadataCache.has(pageNumber)) {
            return metadataCache.get(pageNumber);
        }

        const pdfPage = await pdfDocument.getPage(pageNumber);
        const viewport = pdfPage.getViewport({ scale: 1 });
        const metadata = {
            width: viewport.width,
            height: viewport.height,
        };

        metadataCache.set(pageNumber, metadata);
        pdfPage.cleanup();

        return metadata;
    };

    const applyPageLayout = (state) => {
        const availableWidth = getAvailableWidth();
        const naturalWidth = state.metadata?.width || 612;
        const naturalHeight = state.metadata?.height || 792;
        const fitScale = availableWidth / naturalWidth;
        const scale = fitScale * (zoom / 100);
        const width = Math.max(1, Math.floor(naturalWidth * scale));
        const height = Math.max(1, Math.floor(naturalHeight * scale));

        state.scale = scale;
        state.width = width;
        state.height = height;
        state.surface.style.width = `${width}px`;
        state.surface.style.height = `${height}px`;
        state.surface.style.setProperty('--scale-factor', String(scale));
        state.canvas.style.width = `${width}px`;
        state.canvas.style.height = `${height}px`;
        state.annotationCanvas.style.width = `${width}px`;
        state.annotationCanvas.style.height = `${height}px`;
    };

    const createPageState = async (pageNumber, generation, prioritizeMetadata = false) => {
        const frame = pageTemplate.content.firstElementChild.cloneNode(true);
        const surface = frame.querySelector('[data-page-surface]');
        const skeleton = frame.querySelector('[data-page-skeleton]');
        const canvas = frame.querySelector('[data-page-canvas]');
        const annotationCanvas = frame.querySelector('[data-page-annotation-layer]');
        const textLayer = frame.querySelector('[data-page-text-layer]');
        const highlightLayer = frame.querySelector('[data-page-highlight-layer]');
        const error = frame.querySelector('[data-page-error]');
        const retry = frame.querySelector('[data-page-retry]');
        const loadingText = frame.querySelector('[data-page-loading-text]');
        const numberLabel = frame.querySelector('[data-page-number]');
        let metadata = null;

        if (prioritizeMetadata) {
            try {
                metadata = await getPageMetadata(pageNumber);
            } catch {
                metadata = null;
            }
        }

        frame.dataset.pageNumber = String(pageNumber);
        surface.setAttribute('role', 'img');
        surface.setAttribute('aria-label', `Halaman ${pageNumber} dari ${totalPages}`);
        canvas.setAttribute('aria-label', `Halaman ${pageNumber} dari ${totalPages}`);
        annotationCanvas.setAttribute('aria-label', `Anotasi halaman ${pageNumber}`);
        textLayer.setAttribute('aria-label', `Teks halaman ${pageNumber}`);
        loadingText.textContent = `Memuat halaman ${pageNumber}...`;
        numberLabel.textContent = `Halaman ${pageNumber}`;

        const state = {
            pageNumber,
            generation,
            frame,
            surface,
            skeleton,
            canvas,
            annotationCanvas,
            textLayer,
            highlightLayer,
            error,
            retry,
            metadata,
            scale: 1,
            width: 1,
            height: 1,
            status: 'skeleton',
            token: 0,
            renderTask: null,
            textLayerTask: null,
            completionPromise: null,
            resolveCompletion: null,
            intersectionRatio: 0,
        };

        applyPageLayout(state);
        retry.addEventListener('click', () => {
            state.error.classList.add('hidden');
            state.surface.classList.remove('is-ready');
            state.status = 'skeleton';
            enqueuePage(state, true);
        });

        return state;
    };

    const settlePage = (state) => {
        state.resolveCompletion?.();
        state.resolveCompletion = null;
        state.completionPromise = null;
    };

    const cancelPage = (state, preserveVisual = false) => {
        state.token += 1;
        state.renderTask?.cancel();
        state.textLayerTask?.cancel();
        state.renderTask = null;
        state.textLayerTask = null;
        renderQueue = renderQueue.filter((queuedState) => queuedState !== state);

        if (state.status === 'queued') {
            settlePage(state);
        }

        state.status = 'cancelled';

        if (!preserveVisual) {
            state.surface.classList.remove('is-ready');
            state.error.classList.add('hidden');
        }
    };

    const disposePage = (state) => {
        cancelPage(state, true);
        state.canvas.width = 1;
        state.canvas.height = 1;
        state.annotationCanvas.width = 1;
        state.annotationCanvas.height = 1;
        state.textLayer.replaceChildren();
        state.highlightLayer.replaceChildren();
    };

    const cancelQueuedAndRunningPages = (states, preserveVisual = false) => {
        renderQueue.forEach((state) => {
            if (states.includes(state)) {
                settlePage(state);
            }
        });
        renderQueue = renderQueue.filter((state) => !states.includes(state));
        states.forEach((state) => cancelPage(state, preserveVisual));
    };

    const isCancellationError = (error, state, token) => (
        token !== state.token
        || state.generation !== activeGeneration
        || error?.name === 'RenderingCancelledException'
        || error?.name === 'AbortException'
    );

    const showPageError = (state) => {
        state.status = 'error';
        state.surface.classList.remove('is-ready');
        state.error.classList.remove('hidden');
    };

    const renderPage = async (state) => {
        const token = state.token + 1;
        state.token = token;
        state.status = 'rendering';
        state.error.classList.add('hidden');
        state.surface.classList.remove('is-ready');
        state.textLayer.replaceChildren();
        state.highlightLayer.replaceChildren();

        let pdfPage = null;
        let renderTask = null;
        let textLayerTask = null;

        try {
            pdfPage = await pdfDocument.getPage(state.pageNumber);

            if (token !== state.token || state.generation !== activeGeneration) {
                return;
            }

            if (!state.metadata) {
                const naturalViewport = pdfPage.getViewport({ scale: 1 });
                state.metadata = {
                    width: naturalViewport.width,
                    height: naturalViewport.height,
                };
                metadataCache.set(state.pageNumber, state.metadata);
                applyPageLayout(state);
            }

            const viewport = pdfPage.getViewport({ scale: state.scale });
            const outputScale = Math.min(window.devicePixelRatio || 1, 2);
            state.canvas.width = Math.max(1, Math.floor(viewport.width * outputScale));
            state.canvas.height = Math.max(1, Math.floor(viewport.height * outputScale));
            const canvasContext = state.canvas.getContext('2d', { alpha: false });

            textLayerTask = new TextLayer({
                textContentSource: pdfPage.streamTextContent({
                    includeMarkedContent: true,
                    disableNormalization: true,
                }),
                container: state.textLayer,
                viewport,
            });
            renderTask = pdfPage.render({
                canvasContext,
                viewport,
                transform: outputScale === 1
                    ? null
                    : [outputScale, 0, 0, outputScale, 0, 0],
                background: '#ffffff',
            });
            state.renderTask = renderTask;
            state.textLayerTask = textLayerTask;

            await Promise.all([
                renderTask.promise,
                textLayerTask.render(),
            ]);

            if (token !== state.token || state.generation !== activeGeneration) {
                return;
            }

            state.status = 'ready';
            renderHighlights(state);
            resizeAndRedrawAnnotations(state);
            state.surface.classList.add('is-ready');
        } catch (error) {
            if (!isCancellationError(error, state, token)) {
                showPageError(state);
            }
        } finally {
            if (state.renderTask === renderTask) {
                state.renderTask = null;
            }

            if (state.textLayerTask === textLayerTask) {
                state.textLayerTask = null;
            }

            pdfPage?.cleanup();
            settlePage(state);
        }
    };

    const pumpRenderQueue = () => {
        while (runningRenders < MAX_CONCURRENT_RENDERS && renderQueue.length > 0) {
            const state = renderQueue.shift();

            if (
                !state
                || state.generation !== activeGeneration
                || state.status !== 'queued'
            ) {
                settlePage(state);
                continue;
            }

            runningRenders += 1;
            activeRenderPages.add(state);
            updateRenderStatus();

            renderPage(state).finally(() => {
                runningRenders -= 1;
                activeRenderPages.delete(state);
                updateRenderStatus();
                pumpRenderQueue();
            });
        }
    };

    const enqueuePage = (state, priority = false) => {
        if (!state || state.generation !== activeGeneration || state.status === 'ready') {
            return Promise.resolve();
        }

        if (state.status === 'rendering' || state.status === 'queued') {
            return state.completionPromise || Promise.resolve();
        }

        state.status = 'queued';
        state.completionPromise = new Promise((resolve) => {
            state.resolveCompletion = resolve;
        });

        if (priority) {
            renderQueue.unshift(state);
        } else {
            renderQueue.push(state);
        }

        pumpRenderQueue();

        return state.completionPromise;
    };

    const preloadWindowMetadata = async (direction) => {
        if (!pdfDocument) {
            return;
        }

        const range = calculateAdjacentWindow(activeWindow, totalPages, direction);

        if (
            range.start === activeWindow.start
            || (direction < 0 && activeWindow.start <= 1)
            || (direction > 0 && activeWindow.end >= totalPages)
        ) {
            return;
        }

        const requests = [];

        for (let pageNumber = range.start; pageNumber <= range.end; pageNumber += 1) {
            requests.push(getPageMetadata(pageNumber).catch(() => null));
        }

        await Promise.all(requests);
    };

    const scheduleAdjacentPreload = () => {
        window.clearTimeout(preloadTimer);

        let direction = 0;

        if (currentPage >= activeWindow.end - 1 && activeWindow.end < totalPages) {
            direction = 1;
        } else if (currentPage <= activeWindow.start + 1 && activeWindow.start > 1) {
            direction = -1;
        }

        if (direction !== 0) {
            preloadTimer = window.setTimeout(() => preloadWindowMetadata(direction), 500);
        }
    };

    const pruneMetadataCache = () => {
        const radius = window.matchMedia('(max-width: 640px)').matches ? 8 : 15;
        const protectedPages = new Set();

        for (let page = activeWindow.start; page <= activeWindow.end; page += 1) {
            protectedPages.add(page);
        }

        for (const page of pagesOutsideCache(currentPage, [...metadataCache.keys()], radius)) {
            if (!protectedPages.has(page)) {
                metadataCache.delete(page);
            }
        }
    };

    const renderThumbnail = async (thumbnail) => {
        if (!pdfDocument || thumbnail.status !== 'idle') {
            return;
        }

        thumbnail.status = 'rendering';
        let pdfPage = null;

        try {
            pdfPage = await pdfDocument.getPage(thumbnail.pageNumber);
            const viewport = pdfPage.getViewport({ scale: 0.18 });
            const outputScale = Math.min(window.devicePixelRatio || 1, 2);
            thumbnail.canvas.width = Math.max(1, Math.floor(viewport.width * outputScale));
            thumbnail.canvas.height = Math.max(1, Math.floor(viewport.height * outputScale));
            const context = thumbnail.canvas.getContext('2d', { alpha: false });
            const renderTask = pdfPage.render({
                canvasContext: context,
                viewport,
                transform: outputScale === 1
                    ? null
                    : [outputScale, 0, 0, outputScale, 0, 0],
                background: '#ffffff',
            });

            await renderTask.promise;
            thumbnail.status = 'ready';
            thumbnail.button.classList.add('is-ready');
            thumbnailObserver?.unobserve(thumbnail.button);
        } catch {
            thumbnail.status = 'idle';
        } finally {
            pdfPage?.cleanup();
        }
    };

    const buildThumbnails = () => {
        thumbnailObserver?.disconnect();
        thumbnailStates.clear();
        thumbnailList.replaceChildren();
        sidebarCount.textContent = `${totalPages} halaman`;
        const fragment = document.createDocumentFragment();

        for (let pageNumber = 1; pageNumber <= totalPages; pageNumber += 1) {
            const button = document.createElement('button');
            const preview = document.createElement('span');
            const canvas = document.createElement('canvas');
            const label = document.createElement('span');
            button.type = 'button';
            button.className = 'reader-thumbnail';
            button.dataset.pageNumber = String(pageNumber);
            button.setAttribute('aria-label', `Buka halaman ${pageNumber}`);
            preview.className = 'reader-thumbnail-preview';
            label.textContent = `Halaman ${pageNumber}`;
            canvas.setAttribute('aria-hidden', 'true');
            preview.append(canvas);
            button.append(preview, label);
            button.addEventListener('click', () => {
                goToPage(pageNumber);

                if (window.matchMedia('(max-width: 767px)').matches) {
                    reader.classList.remove('is-sidebar-open');
                }
            });
            fragment.append(button);
            thumbnailStates.set(pageNumber, {
                pageNumber,
                button,
                canvas,
                status: 'idle',
            });
        }

        thumbnailList.append(fragment);
        thumbnailObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const pageNumber = Number.parseInt(entry.target.dataset.pageNumber, 10);
                renderThumbnail(thumbnailStates.get(pageNumber));
            });
        }, {
            root: thumbnailList,
            rootMargin: '180px 0px',
            threshold: 0.01,
        });
        thumbnailStates.forEach((thumbnail) => thumbnailObserver.observe(thumbnail.button));
    };

    const setActivePage = (pageNumber, trackProgress = true) => {
        const nextState = pageStates.get(pageNumber);

        if (!nextState) {
            return;
        }

        pageStates.forEach((state) => {
            state.frame.classList.toggle('is-active', state.pageNumber === pageNumber);
        });

        const changed = currentPage !== pageNumber;
        currentPage = pageNumber;
        updateControls();

        if (changed) {
            const thumbnail = thumbnailStates.get(pageNumber);
            thumbnail?.button.scrollIntoView({
                block: 'nearest',
                inline: 'nearest',
            });
        }

        if (nextState.status === 'skeleton' || nextState.status === 'cancelled') {
            enqueuePage(nextState, true);
        }

        if (changed) {
            hideHighlightPopover();
            pruneMetadataCache();
            scheduleAdjacentPreload();

            if (trackProgress) {
                scheduleHeartbeat();
            }
        }
    };

    const observePages = () => {
        observer?.disconnect();
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                const state = stateFromTarget(entry.target);

                if (state) {
                    state.intersectionRatio = entry.isIntersecting ? entry.intersectionRatio : 0;
                }
            });

            const stageBounds = stage.getBoundingClientRect();
            const stageCenter = stageBounds.top + (stageBounds.height / 2);
            const visibleStates = [...pageStates.values()]
                .filter((state) => state.intersectionRatio > 0)
                .sort((left, right) => {
                    if (right.intersectionRatio !== left.intersectionRatio) {
                        return right.intersectionRatio - left.intersectionRatio;
                    }

                    const leftBounds = left.frame.getBoundingClientRect();
                    const rightBounds = right.frame.getBoundingClientRect();
                    const leftDistance = Math.abs(
                        leftBounds.top + (leftBounds.height / 2) - stageCenter,
                    );
                    const rightDistance = Math.abs(
                        rightBounds.top + (rightBounds.height / 2) - stageCenter,
                    );

                    return leftDistance - rightDistance;
                });

            if (visibleStates[0]) {
                setActivePage(visibleStates[0].pageNumber);
            }
        }, {
            root: stage,
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1],
        });

        pageStates.forEach((state) => observer.observe(state.frame));
    };

    const scrollToPage = (pageNumber, behavior = 'auto') => {
        const state = pageStates.get(pageNumber);

        if (!state) {
            return;
        }

        const stageBounds = stage.getBoundingClientRect();
        const pageBounds = state.frame.getBoundingClientRect();
        const top = stage.scrollTop + pageBounds.top - stageBounds.top - 12;
        stage.scrollTo({ top: Math.max(0, top), left: 0, behavior });
    };

    const mountWindow = async (range, targetPage, behavior = 'auto') => {
        const generation = activeGeneration + 1;
        activeGeneration = generation;
        const oldStates = [...pageStates.values()];
        cancelQueuedAndRunningPages(oldStates, true);
        observer?.disconnect();
        hideHighlightPopover();

        const pageNumbers = [];

        for (let pageNumber = range.start; pageNumber <= range.end; pageNumber += 1) {
            pageNumbers.push(pageNumber);
        }

        const preparedStates = await Promise.all(
            pageNumbers.map((pageNumber) => createPageState(
                pageNumber,
                generation,
                pageNumber === targetPage,
            )),
        );

        if (generation !== activeGeneration) {
            preparedStates.forEach(disposePage);

            return;
        }

        const fragment = document.createDocumentFragment();
        const nextStates = new Map();

        preparedStates.forEach((state) => {
            nextStates.set(state.pageNumber, state);
            fragment.append(state.frame);
        });

        pagesContainer.replaceChildren(fragment);
        oldStates.forEach(disposePage);
        pageStates = nextStates;
        activeWindow = range;
        currentPage = Math.min(Math.max(targetPage, range.start), range.end);
        pagesContainer.classList.remove('hidden', 'invisible');
        setDocumentLoading(false);
        updateControls();
        loadAnnotations(range).catch(() => {
            setSaveStatus('Anotasi belum dapat dimuat', true);
        });

        await waitForLayout();

        if (generation !== activeGeneration) {
            return;
        }

        scrollToPage(currentPage, behavior);
        observePages();
        setActivePage(currentPage, false);

        const priority = buildRenderPriority(currentPage, range.start, range.end);
        const targetState = pageStates.get(priority[0]);

        enqueuePage(targetState, true).finally(() => {
            if (generation !== activeGeneration) {
                return;
            }

            priority.slice(1).forEach((pageNumber) => {
                enqueuePage(pageStates.get(pageNumber));
            });
        });
    };

    const goToPage = async (pageNumber, behavior = 'smooth') => {
        if (!pdfDocument || navigating) {
            return;
        }

        const targetPage = Math.min(Math.max(Number(pageNumber) || 1, 1), totalPages);

        if (pageStates.has(targetPage)) {
            setActivePage(targetPage);
            scrollToPage(targetPage, behavior);

            return;
        }

        navigating = true;
        updateControls();

        try {
            const range = resolveNavigationWindow(targetPage, activeWindow, totalPages);
            await mountWindow(range, targetPage, 'auto');
            sendHeartbeat();
        } finally {
            navigating = false;
            updateControls();
        }
    };

    const navigateWindow = async (direction) => {
        if (navigating || !pdfDocument) {
            return;
        }

        const range = calculateAdjacentWindow(activeWindow, totalPages, direction);

        if (range.start === activeWindow.start && range.end === activeWindow.end) {
            return;
        }

        navigating = true;
        updateControls();

        try {
            await preloadWindowMetadata(direction);
            await mountWindow(range, range.start, 'auto');
            sendHeartbeat();
        } finally {
            navigating = false;
            updateControls();
        }
    };

    const remountCurrentWindow = async () => {
        if (!pdfDocument || navigating) {
            return;
        }

        navigating = true;
        updateControls();

        try {
            await mountWindow(activeWindow, currentPage, 'auto');
        } finally {
            navigating = false;
            updateControls();
        }
    };

    const changeZoom = (delta) => {
        const nextZoom = Math.min(180, Math.max(60, zoom + delta));

        if (nextZoom === zoom) {
            return;
        }

        zoom = nextZoom;
        updateControls();
        window.clearTimeout(zoomTimer);
        zoomTimer = window.setTimeout(remountCurrentWindow, 200);
    };

    const loadDocument = async () => {
        setDocumentError(false);
        setDocumentLoading(true);
        renderStatus.classList.add('hidden');
        observer?.disconnect();
        thumbnailObserver?.disconnect();
        cancelQueuedAndRunningPages([...pageStates.values()]);
        pageStates.forEach(disposePage);
        pageStates = new Map();
        pagesContainer.replaceChildren();
        metadataCache.clear();

        try {
            await loadingTask?.destroy();
            loadingTask = getDocument({
                url: reader.dataset.documentUrl,
                withCredentials: true,
                ...pdfAssetUrls,
                cMapPacked: true,
                isEvalSupported: true,
            });
            pdfDocument = await loadingTask.promise;
            totalPages = pdfDocument.numPages;
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
            buildThumbnails();
            const range = calculateInitialWindow(currentPage, totalPages);
            activeWindow = range;
            updateControls();
            await mountWindow(range, currentPage);
            sendHeartbeat();
        } catch {
            setDocumentLoading(false);
            setDocumentError(true);
        }
    };

    const applyHistory = (history) => {
        annotationHistories.set(currentPage, history);
        annotationsByPage.set(currentPage, history.current);
        resizeAndRedrawAnnotations(pageStates.get(currentPage));
        scheduleAnnotationSave(currentPage);
        updateControls();
    };

    const undoAnnotation = () => {
        const history = ensureAnnotationHistory(currentPage);

        if (history.canUndo) {
            applyHistory(undoAnnotationHistory(history));
        }
    };

    const redoAnnotation = () => {
        const history = ensureAnnotationHistory(currentPage);

        if (history.canRedo) {
            applyHistory(redoAnnotationHistory(history));
        }
    };

    const toggleSidebar = () => {
        const isMobile = window.matchMedia('(max-width: 767px)').matches;

        if (isMobile) {
            reader.classList.toggle('is-sidebar-open');
        } else {
            reader.classList.toggle('is-sidebar-collapsed');
        }

        const isOpen = isMobile
            ? reader.classList.contains('is-sidebar-open')
            : !reader.classList.contains('is-sidebar-collapsed');
        toggleSidebarButton.setAttribute('aria-expanded', String(isOpen));
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(remountCurrentWindow, 240);
    };

    const searchDocument = async (query) => {
        const normalizedQuery = query.trim().toLocaleLowerCase('id-ID');

        if (!normalizedQuery || !pdfDocument || searching) {
            return;
        }

        searching = true;
        searchInput.disabled = true;
        setSaveStatus('Mencari...');
        const pages = [
            ...Array.from(
                { length: totalPages - currentPage + 1 },
                (_, index) => currentPage + index,
            ),
            ...Array.from({ length: currentPage - 1 }, (_, index) => index + 1),
        ];

        try {
            for (const pageNumber of pages) {
                const pdfPage = await pdfDocument.getPage(pageNumber);
                const textContent = await pdfPage.getTextContent();
                const text = textContent.items
                    .map((item) => ('str' in item ? item.str : ''))
                    .join(' ')
                    .toLocaleLowerCase('id-ID');
                pdfPage.cleanup();

                if (text.includes(normalizedQuery)) {
                    await goToPage(pageNumber);
                    setSaveStatus(`Ditemukan di halaman ${pageNumber}`);

                    return;
                }
            }

            setSaveStatus('Teks tidak ditemukan', true);
        } catch {
            setSaveStatus('Pencarian gagal', true);
        } finally {
            searching = false;
            searchInput.disabled = false;
        }
    };

    const flushAnnotationSaves = () => {
        annotationSaveTimers.forEach((timer, pageNumber) => {
            window.clearTimeout(timer);
            saveAnnotationPage(pageNumber, true);
        });
        annotationSaveTimers.clear();
    };

    previousButton.addEventListener('click', () => goToPage(currentPage - 1));
    nextButton.addEventListener('click', () => goToPage(currentPage + 1));
    zoomOutButton.addEventListener('click', () => changeZoom(-10));
    zoomInButton.addEventListener('click', () => changeZoom(10));
    floatingZoomOutButton.addEventListener('click', () => changeZoom(-10));
    floatingZoomInButton.addEventListener('click', () => changeZoom(10));
    toggleSidebarButton.addEventListener('click', toggleSidebar);
    closeSidebarButton.addEventListener('click', () => reader.classList.remove('is-sidebar-open'));
    sidebarBackdrop.addEventListener('click', () => reader.classList.remove('is-sidebar-open'));
    toolButtons.forEach((button) => {
        button.addEventListener('click', () => setActiveTool(button.dataset.annotationTool));
    });
    undoButton.addEventListener('click', undoAnnotation);
    redoButton.addEventListener('click', redoAnnotation);
    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        searchDocument(searchInput.value);
    });
    printButton.addEventListener('click', () => {
        const printWindow = window.open(reader.dataset.documentUrl, '_blank');

        if (!printWindow) {
            setSaveStatus('Popup cetak diblokir browser', true);

            return;
        }

        printWindow.addEventListener('load', () => {
            printWindow.focus();
            printWindow.print();
        }, { once: true });
    });
    fullscreenButton.addEventListener('click', async () => {
        if (document.fullscreenElement) {
            await document.exitFullscreen();
        } else {
            await reader.requestFullscreen();
        }
    });
    document.addEventListener('fullscreenchange', () => {
        fullscreenButton.classList.toggle('is-active', Boolean(document.fullscreenElement));
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(remountCurrentWindow, 180);
    });
    saveTextNoteButton.addEventListener('click', (event) => {
        event.preventDefault();
        const content = textNoteContent.value.trim();

        if (!pendingTextNote || !content) {
            textNoteContent.focus();

            return;
        }

        const state = pageStates.get(pendingTextNote.pageNumber);
        const brushPixels = Math.max(2, Number(brushSizeInput.value) || 4);
        const annotation = {
            id: annotationId(),
            type: 'text',
            color: annotationColorInput.value,
            brush_size: Number(
                Math.min(0.2, brushPixels / Math.max(1, state?.width || 1)).toFixed(6),
            ),
            points: [pendingTextNote.point],
            content,
        };
        commitPageAnnotations(
            pendingTextNote.pageNumber,
            [...pageAnnotations(pendingTextNote.pageNumber), annotation],
        );
        pendingTextNote = null;
        textNoteDialog.close();
    });
    textNoteDialog.addEventListener('close', () => {
        pendingTextNote = null;
    });
    retryButton.addEventListener('click', loadDocument);
    pagesContainer.addEventListener('pointerdown', startAnnotationGesture);
    pagesContainer.addEventListener('pointermove', continueAnnotationGesture);
    window.addEventListener('pointerup', finishAnnotationGesture);
    window.addEventListener('pointercancel', finishAnnotationGesture);
    pagesContainer.addEventListener('mouseup', (event) => {
        const state = stateFromTarget(event.target);

        if (!activeTool && state?.status === 'ready') {
            window.setTimeout(() => showHighlightPopover(state), 0);
        }
    });
    pagesContainer.addEventListener('touchend', (event) => {
        const state = stateFromTarget(event.target);

        if (!activeTool && state?.status === 'ready') {
            window.setTimeout(() => showHighlightPopover(state), 0);
        }
    });
    pagesContainer.addEventListener('click', handleHighlightClick);
    highlightPopover.addEventListener('pointerdown', (event) => event.stopPropagation());
    highlightPopover.querySelectorAll('[data-highlight-color]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => saveHighlight(button.dataset.highlightColor));
    });

    document.addEventListener('pointerdown', (event) => {
        const isTextLayer = event.target instanceof Element
            && event.target.closest('[data-page-text-layer]');

        if (!highlightPopover.contains(event.target) && !isTextLayer) {
            hideHighlightPopover();
        }
    });
    window.addEventListener('keydown', (event) => {
        const interactiveTarget = event.target instanceof HTMLElement
            && event.target.closest('input, textarea, select, [contenteditable="true"]');

        if (interactiveTarget) {
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            goToPage(currentPage - 1);
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            goToPage(currentPage + 1);
        }

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
            event.preventDefault();

            if (event.shiftKey) {
                redoAnnotation();
            } else {
                undoAnnotation();
            }
        }

        if (event.key === 'Escape') {
            hideHighlightPopover();
            setActiveTool(activeTool);
            reader.classList.remove('is-sidebar-open');
            window.getSelection()?.removeAllRanges();
        }
    });
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(remountCurrentWindow, 180);
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushAnnotationSaves();
            sendHeartbeat();
        }
    });
    window.addEventListener('beforeunload', () => {
        flushAnnotationSaves();
        sendHeartbeat(true);
    });
    window.setInterval(sendHeartbeat, 15000);

    updateControls();
    loadDocument();
}
