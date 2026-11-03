import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openButton = document.getElementById('sidebar-open');
    const closeButton = document.getElementById('sidebar-close');

    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    };

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    };

    openButton?.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-dismiss]').forEach((element) => {
        element.addEventListener('click', () => element.closest('[data-flash]')?.remove());
    });

    initModals();
    initDrawers();
    initCroppers();
    initReportChart();

    function initModals() {
        const modals = Array.from(document.querySelectorAll('[data-modal]'));

        if (modals.length === 0) {
            return;
        }

        const setOpen = (modal, open) => {
            modal.dataset.open = open ? 'true' : 'false';
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        };

        const syncBodyLock = () => {
            document.body.classList.toggle('overflow-hidden', modals.some((modal) => modal.dataset.open === 'true'));
        };

        document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const id = trigger.getAttribute('data-modal-open');

                modals.forEach((modal) => setOpen(modal, modal.id === id));
                syncBodyLock();

                const target = document.getElementById(id);
                const focusable = target?.querySelector('input:not([type="hidden"]):not([type="file"]), select, textarea') ?? target?.querySelector('[data-modal-close]');

                focusable?.focus({ preventScroll: true });
            });
        });

        modals.forEach((modal) => {
            modal.querySelectorAll('[data-modal-close]').forEach((closer) => {
                closer.addEventListener('click', () => {
                    setOpen(modal, false);
                    syncBodyLock();
                });
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const open = modals.find((modal) => modal.dataset.open === 'true');

            if (open) {
                setOpen(open, false);
                syncBodyLock();
            }
        });

        if (modals.some((modal) => modal.dataset.open === 'true')) {
            syncBodyLock();
        }
    }

    function initDrawers() {
        const drawers = Array.from(document.querySelectorAll('[data-drawer]'));

        if (drawers.length === 0) {
            return;
        }

        const setOpen = (drawer, open) => {
            drawer.dataset.open = open ? 'true' : 'false';
            drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle('drawer-open', drawers.some((item) => item.dataset.open === 'true'));

            if (!open && drawer.hasAttribute('data-autoopen')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('employee');
                window.history.replaceState({}, '', url.toString());
            }
        };

        document.querySelectorAll('[data-drawer-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const drawer = document.getElementById(trigger.getAttribute('data-drawer-open'));

                if (!drawer) {
                    return;
                }

                drawers.forEach((item) => setOpen(item, item === drawer));
                drawer.querySelector('input:not([type="hidden"]), select, textarea, [data-drawer-close]')?.focus({ preventScroll: true });
            });
        });

        drawers.forEach((drawer) => {
            drawer.querySelectorAll('[data-drawer-close]').forEach((closer) => {
                closer.addEventListener('click', () => setOpen(drawer, false));
            });

            if (drawer.hasAttribute('data-autoopen')) {
                window.requestAnimationFrame(() => window.setTimeout(() => setOpen(drawer, true), 30));
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const open = drawers.find((drawer) => drawer.dataset.open === 'true');

            if (open) {
                setOpen(open, false);
            }
        });
    }

    function initCroppers() {
        document.querySelectorAll('[data-cropper]').forEach(setupCropper);
    }

    function setupCropper(root) {
        const pick = root.querySelector('[data-crop-pick]');
        const input = root.querySelector('[data-crop-input]');
        const editor = root.querySelector('[data-crop-editor]');
        const viewport = root.querySelector('[data-crop-viewport]');
        const image = root.querySelector('[data-crop-image]');
        const zoomInput = root.querySelector('[data-crop-zoom]');
        const applyButton = root.querySelector('[data-crop-apply]');
        const dataInput = root.querySelector('[data-crop-data]');
        const preview = root.querySelector('[data-crop-preview]');
        const initials = root.querySelector('[data-crop-initials]');

        if (!pick || !input || !editor || !viewport || !image || !zoomInput || !applyButton || !dataInput) {
            return;
        }

        // Tailwind's base styles cap images at max-width: 100%, which would
        // squeeze the zoomed preview against the square viewport. The crop
        // preview must keep its natural aspect ratio.
        image.style.maxWidth = 'none';
        image.style.maxHeight = 'none';

        const applyLabel = applyButton.textContent;
        const state = { width: 0, height: 0, zoom: 1, offsetX: 0, offsetY: 0 };
        let objectUrl = null;
        let dragging = false;
        let startX = 0;
        let startY = 0;
        let originX = 0;
        let originY = 0;

        const scale = () => {
            const base = Math.max(viewport.clientWidth / state.width, viewport.clientHeight / state.height);

            return base * state.zoom;
        };

        const render = () => {
            if (!state.width || !state.height) {
                return;
            }

            const currentScale = scale();
            const width = state.width * currentScale;
            const height = state.height * currentScale;
            const maxX = Math.max(0, (width - viewport.clientWidth) / 2);
            const maxY = Math.max(0, (height - viewport.clientHeight) / 2);

            state.offsetX = Math.min(maxX, Math.max(-maxX, state.offsetX));
            state.offsetY = Math.min(maxY, Math.max(-maxY, state.offsetY));

            image.style.width = `${width}px`;
            image.style.height = `${height}px`;
            image.style.left = `${(viewport.clientWidth - width) / 2 + state.offsetX}px`;
            image.style.top = `${(viewport.clientHeight - height) / 2 + state.offsetY}px`;
        };

        pick.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            objectUrl = URL.createObjectURL(file);
            dataInput.value = '';
            applyButton.textContent = applyLabel;
            editor.classList.remove('hidden');
            state.zoom = 1;
            state.offsetX = 0;
            state.offsetY = 0;
            zoomInput.value = '1';

            image.onload = () => {
                state.width = image.naturalWidth;
                state.height = image.naturalHeight;
                render();
            };

            image.src = objectUrl;
        });

        zoomInput.addEventListener('input', () => {
            state.zoom = Number(zoomInput.value);
            render();
        });

        viewport.addEventListener('pointerdown', (event) => {
            if (!state.width) {
                return;
            }

            dragging = true;
            startX = event.clientX;
            startY = event.clientY;
            originX = state.offsetX;
            originY = state.offsetY;
            viewport.setPointerCapture(event.pointerId);
        });

        viewport.addEventListener('pointermove', (event) => {
            if (!dragging) {
                return;
            }

            state.offsetX = originX + (event.clientX - startX);
            state.offsetY = originY + (event.clientY - startY);
            render();
        });

        const stopDragging = (event) => {
            dragging = false;

            if (event?.pointerId !== undefined && viewport.hasPointerCapture(event.pointerId)) {
                viewport.releasePointerCapture(event.pointerId);
            }
        };

        viewport.addEventListener('pointerup', stopDragging);
        viewport.addEventListener('pointercancel', stopDragging);

        applyButton.addEventListener('click', () => {
            if (!state.width) {
                return;
            }

            const currentScale = scale();
            const viewWidth = viewport.clientWidth;
            const viewHeight = viewport.clientHeight;

            // Instagram-style crop: always a square taken from the visible
            // area, never stretched to fit it.
            const cropSize = Math.min(viewWidth, viewHeight);
            const square = Math.min(state.width, state.height, cropSize / currentScale);
            const left = (viewWidth - state.width * currentScale) / 2 + state.offsetX;
            const top = (viewHeight - state.height * currentScale) / 2 + state.offsetY;
            const sourceX = Math.min(Math.max(-left / currentScale, 0), state.width - square);
            const sourceY = Math.min(Math.max(-top / currentScale, 0), state.height - square);
            const output = 512;

            const canvas = document.createElement('canvas');
            canvas.width = output;
            canvas.height = output;

            const context = canvas.getContext('2d');
            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            context.drawImage(
                image,
                sourceX,
                sourceY,
                square,
                square,
                0,
                0,
                output,
                output,
            );

            const dataUrl = canvas.toDataURL('image/png');
            dataInput.value = dataUrl;

            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
            }

            initials?.classList.add('hidden');
            applyButton.textContent = '✓';
        });
    }

    function initReportChart() {
        const container = document.getElementById('report-chart');

        if (!container || typeof window.Chart === 'undefined') {
            return;
        }

        const canvas = container.querySelector('canvas');
        const typeButtons = Array.from(document.querySelectorAll('[data-chart-type]'));
        const datasetSelect = document.querySelector('[data-chart-dataset]');

        if (!canvas || !datasetSelect) {
            return;
        }

        const datasets = {
            departments: {
                label: container.dataset.departmentsLabel || 'Department average',
                labels: JSON.parse(container.dataset.departments || '[]').map((row) => row.label),
                values: JSON.parse(container.dataset.departments || '[]').map((row) => row.average),
                suffix: '',
            },
            grades: {
                label: container.dataset.gradesLabel || 'Grade distribution',
                labels: JSON.parse(container.dataset.grades || '[]').map((row) => `${row.grade} · ${row.label}`),
                values: JSON.parse(container.dataset.grades || '[]').map((row) => row.count),
                suffix: '',
            },
            criteria: {
                label: container.dataset.criteriaLabel || 'Criteria average',
                labels: JSON.parse(container.dataset.criteria || '[]').map((row) => row.label),
                values: JSON.parse(container.dataset.criteria || '[]').map((row) => row.average),
                suffix: '/5',
            },
        };

        const palette = ['#6366f1', '#8b5cf6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#14b8a6', '#64748b'];
        let chart = null;
        let type = 'bar';

        const draw = () => {
            const dataset = datasets[datasetSelect.value] || datasets.departments;

            chart?.destroy();
            chart = new window.Chart(canvas, {
                type,
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        label: dataset.label,
                        data: dataset.values,
                        backgroundColor: type === 'pie' ? palette : palette[0],
                        borderRadius: type === 'bar' ? 8 : 0,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type === 'pie', position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const value = context.parsed.y ?? context.parsed;
                                    return `${dataset.label}: ${value}${dataset.suffix}`;
                                },
                            },
                        },
                    },
                    scales: type === 'bar' ? {
                        y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                        x: { grid: { display: false } },
                    } : {},
                },
            });
        };

        typeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                type = button.dataset.chartType;
                typeButtons.forEach((item) => {
                    item.classList.toggle('bg-indigo-600', item === button);
                    item.classList.toggle('text-white', item === button);
                    item.classList.toggle('text-slate-600', item !== button);
                });
                draw();
            });
        });

        datasetSelect.addEventListener('change', draw);
        draw();
    }
});
