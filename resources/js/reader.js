import '../css/reader.css';
import { getDocument, GlobalWorkerOptions, TextLayer } from 'pdfjs-dist';

if (typeof window !== 'undefined' && !GlobalWorkerOptions.workerPort) {
    GlobalWorkerOptions.workerPort = new Worker(
        new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url),
        { type: 'module' },
    );
}

const reader = document.querySelector('[data-pdf-reader]');

if (reader) {
    const canvas = document.getElementById('readerCanvas');
    const canvasContext = canvas.getContext('2d', { alpha: false });
    const stage = document.getElementById('readerStage');
    const pageSurface = document.getElementById('readerPageSurface');
    const textLayer = document.getElementById('readerTextLayer');
    const highlightLayer = document.getElementById('readerHighlightLayer');
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let pdfDocument = null;
    let currentPage = Number.parseInt(reader.dataset.initialPage, 10) || 1;
    let totalPages = 0;
    let zoom = 100;
    let rendering = false;
    let renderRequested = false;
    let resizeTimer = null;
    let statusTimer = null;
    let activeTextLayer = null;
    let pendingHighlight = null;
    let highlights = [];

    try {
        highlights = JSON.parse(highlightsData?.textContent || '[]');
    } catch {
        highlights = [];
    }

    const setLoading = (loading) => {
        loadingState.classList.toggle('hidden', !loading);
        pageSurface.classList.toggle('invisible', loading);
    };

    const setError = (hasError) => {
        errorState.classList.toggle('hidden', !hasError);
        errorState.classList.toggle('grid', hasError);
        pageSurface.classList.toggle('hidden', hasError);
    };

    const setSaveStatus = (message, failed = false) => {
        window.clearTimeout(statusTimer);
        saveStatus.textContent = message;
        saveStatus.classList.toggle('text-rose-300', failed);
        saveStatus.classList.toggle('text-slate-400', !failed);
        statusTimer = window.setTimeout(() => {
            saveStatus.textContent = '';
        }, 2200);
    };

    const updateControls = () => {
        pageLabel.textContent = String(currentPage);
        totalLabel.textContent = totalPages > 0 ? String(totalPages) : '-';
        zoomLabel.textContent = `${zoom}%`;
        previousButton.disabled = currentPage <= 1;
        nextButton.disabled = totalPages === 0 || currentPage >= totalPages;
        zoomOutButton.disabled = zoom <= 60;
        zoomInButton.disabled = zoom >= 180;
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

    const hideHighlightPopover = () => {
        highlightPopover.classList.add('hidden');
        pendingHighlight = null;
    };

    const pageHighlights = () => highlights.filter(
        (highlight) => Number(highlight.page_number) === currentPage,
    );

    const renderHighlights = () => {
        highlightLayer.replaceChildren();

        for (const highlight of pageHighlights()) {
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
                highlightLayer.append(mark);
            }
        }
    };

    const closestTextNode = (node) => {
        const element = node?.nodeType === Node.TEXT_NODE ? node.parentElement : node;

        return element instanceof Element ? element.closest('span, br') : null;
    };

    const normalizedNumber = (value) => Math.min(1, Math.max(0, Number(value.toFixed(6))));

    const serializeSelection = () => {
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
            || !textLayer.contains(startElement)
            || !textLayer.contains(endElement)
        ) {
            return null;
        }

        const textNodes = [...textLayer.querySelectorAll('span, br')];
        const startIndex = textNodes.indexOf(startElement);
        const endIndex = textNodes.indexOf(endElement);
        const layerBounds = textLayer.getBoundingClientRect();

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

    const showHighlightPopover = () => {
        const selectionData = serializeSelection();

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
                    page_number: currentPage,
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
            renderHighlights();
            window.getSelection()?.removeAllRanges();
            setSaveStatus('Stabilo tersimpan');
        } catch {
            setSaveStatus('Stabilo gagal disimpan', true);
        } finally {
            pendingHighlight = null;
        }
    };

    const deleteHighlight = async (highlight) => {
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
            renderHighlights();
            setSaveStatus('Stabilo dihapus');
        } catch {
            setSaveStatus('Stabilo gagal dihapus', true);
        }
    };

    const handleHighlightClick = (event) => {
        const selection = window.getSelection();

        if (!selection?.isCollapsed) {
            return;
        }

        const bounds = textLayer.getBoundingClientRect();

        if (bounds.width <= 0 || bounds.height <= 0) {
            return;
        }

        const x = (event.clientX - bounds.left) / bounds.width;
        const y = (event.clientY - bounds.top) / bounds.height;
        const highlight = [...pageHighlights()]
            .reverse()
            .find((item) => item.serialized_range?.rects?.some((rect) => (
                x >= Number(rect.x)
                && x <= Number(rect.x) + Number(rect.width)
                && y >= Number(rect.y)
                && y <= Number(rect.y) + Number(rect.height)
            )));

        if (highlight) {
            deleteHighlight(highlight);
        }
    };

    const renderPage = async () => {
        if (!pdfDocument) {
            return;
        }

        if (rendering) {
            renderRequested = true;

            return;
        }

        rendering = true;

        try {
            do {
                renderRequested = false;
                setError(false);
                setLoading(true);
                hideHighlightPopover();
                updateControls();
                activeTextLayer?.cancel();

                let pdfPage = null;

                try {
                    const pageToRender = currentPage;
                    const zoomToRender = zoom;
                    pdfPage = await pdfDocument.getPage(pageToRender);
                    const naturalViewport = pdfPage.getViewport({ scale: 1 });
                    const availableWidth = Math.max(280, stage.clientWidth - 40);
                    const fitScale = availableWidth / naturalViewport.width;
                    const viewport = pdfPage.getViewport({ scale: fitScale * (zoomToRender / 100) });
                    const outputScale = Math.min(window.devicePixelRatio || 1, 2);

                    pageSurface.style.width = `${Math.floor(viewport.width)}px`;
                    pageSurface.style.height = `${Math.floor(viewport.height)}px`;
                    pageSurface.style.setProperty('--scale-factor', String(viewport.scale));
                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = `${Math.floor(viewport.width)}px`;
                    canvas.style.height = `${Math.floor(viewport.height)}px`;
                    canvas.setAttribute('aria-label', `Halaman ${pageToRender} dari ${totalPages}`);
                    textLayer.replaceChildren();
                    highlightLayer.replaceChildren();

                    activeTextLayer = new TextLayer({
                        textContentSource: pdfPage.streamTextContent({
                            includeMarkedContent: true,
                            disableNormalization: true,
                        }),
                        container: textLayer,
                        viewport,
                    });

                    await Promise.all([
                        pdfPage.render({
                            canvasContext,
                            viewport,
                            transform: outputScale === 1
                                ? null
                                : [outputScale, 0, 0, outputScale, 0, 0],
                            background: '#ffffff',
                        }).promise,
                        activeTextLayer.render(),
                    ]);

                    if (pageToRender !== currentPage || zoomToRender !== zoom) {
                        renderRequested = true;

                        continue;
                    }

                    renderHighlights();
                } catch {
                    setLoading(false);
                    setError(true);
                    renderRequested = false;
                } finally {
                    pdfPage?.cleanup();
                }
            } while (renderRequested);
        } finally {
            rendering = false;
        }

        if (errorState.classList.contains('hidden')) {
            setLoading(false);
        }
    };

    const changePage = async (page) => {
        if (page < 1 || page > totalPages || page === currentPage) {
            return;
        }

        currentPage = page;
        updateControls();
        await renderPage();
        sendHeartbeat();
        stage.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
    };

    const loadDocument = async () => {
        setError(false);
        setLoading(true);

        try {
            pdfDocument = await getDocument({
                url: reader.dataset.documentUrl,
                withCredentials: true,
            }).promise;
            totalPages = pdfDocument.numPages;
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
            updateControls();
            await renderPage();
            sendHeartbeat();
        } catch {
            setLoading(false);
            setError(true);
        }
    };

    previousButton.addEventListener('click', () => changePage(currentPage - 1));
    nextButton.addEventListener('click', () => changePage(currentPage + 1));
    zoomOutButton.addEventListener('click', () => {
        zoom = Math.max(60, zoom - 10);
        updateControls();
        renderPage();
    });
    zoomInButton.addEventListener('click', () => {
        zoom = Math.min(180, zoom + 10);
        updateControls();
        renderPage();
    });
    retryButton.addEventListener('click', loadDocument);
    textLayer.addEventListener('mouseup', () => window.setTimeout(showHighlightPopover, 0));
    textLayer.addEventListener('touchend', () => window.setTimeout(showHighlightPopover, 0));
    textLayer.addEventListener('click', handleHighlightClick);
    highlightPopover.addEventListener('pointerdown', (event) => event.stopPropagation());
    highlightPopover.querySelectorAll('[data-highlight-color]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => saveHighlight(button.dataset.highlightColor));
    });

    document.addEventListener('pointerdown', (event) => {
        if (!highlightPopover.contains(event.target) && !textLayer.contains(event.target)) {
            hideHighlightPopover();
        }
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            changePage(currentPage - 1);
        }

        if (event.key === 'ArrowRight') {
            changePage(currentPage + 1);
        }

        if (event.key === 'Escape') {
            hideHighlightPopover();
            window.getSelection()?.removeAllRanges();
        }
    });
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(renderPage, 150);
    });
    window.addEventListener('beforeunload', () => sendHeartbeat(true));
    window.setInterval(sendHeartbeat, 15000);

    updateControls();
    loadDocument();
}
