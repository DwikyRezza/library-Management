// 

import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';

// PERBAIKAN: Gunakan konstruktor objek Worker bawaan browser
// yang didukung penuh oleh Vite untuk lingkungan produksi (ES Module).
if (typeof window !== 'undefined' && !GlobalWorkerOptions.workerSrc) {
    const PdfWorker = new Worker(
        new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url),
        { type: 'module' }
    );
    GlobalWorkerOptions.workerPort = PdfWorker;
}

const reader = document.querySelector('[data-pdf-reader]');

if (reader) {
    const canvas = document.getElementById('readerCanvas');
    const canvasContext = canvas.getContext('2d', { alpha: false });
    const stage = document.getElementById('readerStage');
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let pdfDocument = null;
    let currentPage = Number.parseInt(reader.dataset.initialPage, 10) || 1;
    let totalPages = 0;
    let zoom = 100;
    let rendering = false;
    let renderRequested = false;
    let resizeTimer = null;

    const setLoading = (loading) => {
        loadingState.classList.toggle('hidden', !loading);
        canvas.classList.toggle('invisible', loading);
    };

    const setError = (hasError) => {
        errorState.classList.toggle('hidden', !hasError);
        errorState.classList.toggle('grid', hasError);
        canvas.classList.toggle('hidden', hasError);
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

    const renderPage = async () => {
        if (!pdfDocument) {
            return;
        }

        if (rendering) {
            renderRequested = true;

            return;
        }

        rendering = true;

        do {
            renderRequested = false;
            setError(false);
            setLoading(true);
            updateControls();

            let pdfPage = null;

            try {
                const pageToRender = currentPage;
                const zoomToRender = zoom;
                pdfPage = await pdfDocument.getPage(pageToRender);
                const naturalViewport = pdfPage.getViewport({ scale: 1 });
                const availableWidth = Math.max(280, stage.clientWidth - 32);
                const fitScale = availableWidth / naturalViewport.width;
                const viewport = pdfPage.getViewport({ scale: fitScale * (zoomToRender / 100) });
                const outputScale = Math.min(window.devicePixelRatio || 1, 2);

                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = `${Math.floor(viewport.width)}px`;
                canvas.style.height = `${Math.floor(viewport.height)}px`;
                canvas.setAttribute('aria-label', `Halaman ${pageToRender} dari ${totalPages}`);

                await pdfPage.render({
                    canvasContext,
                    viewport,
                    transform: outputScale === 1 ? null : [outputScale, 0, 0, outputScale, 0, 0],
                    background: '#ffffff',
                }).promise;
            } catch {
                setLoading(false);
                setError(true);
                renderRequested = false;
            } finally {
                pdfPage?.cleanup();
            }
        } while (renderRequested);

        rendering = false;

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
        window.scrollTo({ top: 0, behavior: 'smooth' });
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

    window.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            changePage(currentPage - 1);
        }

        if (event.key === 'ArrowRight') {
            changePage(currentPage + 1);
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