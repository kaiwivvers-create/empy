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
    initSearchableSelects();
    initMemberFilters();
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
        const modal = root.querySelector('[data-crop-modal]');
        const viewport = modal?.querySelector('[data-crop-viewport]');
        const canvas = modal?.querySelector('[data-crop-canvas]');
        const zoomInput = modal?.querySelector('[data-crop-zoom]');
        const rotationInput = modal?.querySelector('[data-crop-rotation]');
        const applyButton = modal?.querySelector('[data-crop-apply]');
        const resetButton = modal?.querySelector('[data-crop-reset]');
        const zoomLabel = modal?.querySelector('[data-crop-zoom-label]');
        const rotationLabel = modal?.querySelector('[data-crop-rotation-label]');
        const dataInput = root.querySelector('[data-crop-data]');
        const preview = root.querySelector('[data-crop-preview]');
        const initials = root.querySelector('[data-crop-initials]');
        const removeInput = root.querySelector('[data-crop-remove]');

        if (!pick || !input || !modal || !viewport || !canvas || !zoomInput || !rotationInput || !applyButton || !dataInput) {
            return;
        }

        // Keep the crop window at the end of <body> so it covers the whole
        // screen even when the cropper lives inside another modal.
        document.body.appendChild(modal);

        const context = canvas.getContext('2d');
        const size = 512;
        canvas.width = size;
        canvas.height = size;

        const state = { image: null, zoom: 1, rotation: 0, flipX: false, flipY: false, offsetX: 0, offsetY: 0 };
        let objectUrl = null;
        let dragging = false;
        let lastX = 0;
        let lastY = 0;

        const corners = [
            [size / 2, size / 2],
            [size / 2, -size / 2],
            [-size / 2, size / 2],
            [-size / 2, -size / 2],
        ];
        const normalizeRotation = (degrees) => ((degrees + 180) % 360 + 360) % 360 - 180;

        const geometry = () => {
            if (!state.image) {
                return null;
            }

            const radians = state.rotation * Math.PI / 180;
            const cos = Math.cos(radians);
            const sin = Math.sin(radians);
            const halfImageWidth = state.image.width / 2;
            const halfImageHeight = state.image.height / 2;

            // Scale the image so the rotated image still covers every corner
            // of the square crop area (not merely its bounding box).
            let baseScale = 0;

            corners.forEach(([x, y]) => {
                const imageX = x * cos + y * sin;
                const imageY = -x * sin + y * cos;
                baseScale = Math.max(
                    baseScale,
                    Math.abs(imageX) / halfImageWidth,
                    Math.abs(imageY) / halfImageHeight,
                );
            });

            return { radians, scale: baseScale * state.zoom };
        };

        const covers = (offsetX, offsetY, shape) => {
            if (!state.image) {
                return false;
            }

            const cos = Math.cos(shape.radians);
            const sin = Math.sin(shape.radians);
            const halfImageWidth = state.image.width / 2;
            const halfImageHeight = state.image.height / 2;

            return corners.every(([x, y]) => {
                const dx = x - offsetX;
                const dy = y - offsetY;
                const imageX = (dx * cos + dy * sin) / shape.scale;
                const imageY = (-dx * sin + dy * cos) / shape.scale;

                return Math.abs(imageX) <= halfImageWidth + 0.5
                    && Math.abs(imageY) <= halfImageHeight + 0.5;
            });
        };

        const drawInto = (targetContext, targetSize) => {
            const shape = geometry();

            if (!shape) {
                return;
            }

            const ratio = targetSize / size;

            // Zooming or rotating can make the current drag position invalid;
            // return to the centre instead of exposing empty corners.
            if (!covers(state.offsetX, state.offsetY, shape)) {
                state.offsetX = 0;
                state.offsetY = 0;
            }

            targetContext.save();
            targetContext.translate(targetSize / 2 + state.offsetX * ratio, targetSize / 2 + state.offsetY * ratio);
            targetContext.rotate(shape.radians);
            targetContext.scale(
                (state.flipX ? -1 : 1) * shape.scale * ratio,
                (state.flipY ? -1 : 1) * shape.scale * ratio,
            );
            targetContext.drawImage(state.image, -state.image.width / 2, -state.image.height / 2);
            targetContext.restore();
        };

        const render = () => {
            if (!state.image) {
                return;
            }

            context.clearRect(0, 0, size, size);
            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            drawInto(context, size);

            if (zoomLabel) {
                zoomLabel.textContent = `${Math.round(state.zoom * 100)}%`;
            }

            if (rotationLabel) {
                rotationLabel.textContent = `${Math.round(state.rotation)}°`;
            }

            zoomInput.value = String(state.zoom);
            rotationInput.value = String(state.rotation);
        };

        const reset = () => {
            state.zoom = 1;
            state.rotation = 0;
            state.flipX = false;
            state.flipY = false;
            state.offsetX = 0;
            state.offsetY = 0;
            render();
        };

        const setModalOpen = (open) => {
            modal.dataset.open = open ? 'true' : 'false';
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle(
                'overflow-hidden',
                open || Boolean(document.querySelector('[data-modal][data-open="true"]')),
            );
        };

        const closeModal = () => setModalOpen(false);

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

            const image = new Image();
            image.onload = () => {
                state.image = image;
                reset();
                setModalOpen(true);
                window.requestAnimationFrame(render);
            };
            image.src = objectUrl;

            // Allow picking the same file again later.
            input.value = '';
        });

        zoomInput.addEventListener('input', () => {
            state.zoom = Number(zoomInput.value);
            render();
        });

        rotationInput.addEventListener('input', () => {
            state.rotation = Number(rotationInput.value);
            render();
        });

        modal.querySelectorAll('[data-crop-rotate]').forEach((button) => {
            button.addEventListener('click', () => {
                state.rotation = normalizeRotation(state.rotation + Number(button.dataset.cropRotate));
                render();
            });
        });

        modal.querySelectorAll('[data-crop-flip]').forEach((button) => {
            button.addEventListener('click', () => {
                const axis = button.dataset.cropFlip;

                if (axis === 'x') {
                    state.flipX = !state.flipX;
                } else {
                    state.flipY = !state.flipY;
                }

                button.setAttribute('aria-pressed', String(axis === 'x' ? state.flipX : state.flipY));
                render();
            });
        });

        resetButton?.addEventListener('click', reset);

        viewport.addEventListener('pointerdown', (event) => {
            if (!state.image) {
                return;
            }

            dragging = true;
            lastX = event.clientX;
            lastY = event.clientY;
            viewport.setPointerCapture(event.pointerId);
        });

        viewport.addEventListener('pointermove', (event) => {
            if (!dragging) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const factor = rect.width > 0 ? size / rect.width : 1;
            const shape = geometry();
            const nextX = state.offsetX + (event.clientX - lastX) * factor;
            const nextY = state.offsetY + (event.clientY - lastY) * factor;

            lastX = event.clientX;
            lastY = event.clientY;

            if (shape && covers(nextX, nextY, shape)) {
                state.offsetX = nextX;
                state.offsetY = nextY;
                render();
            }
        });

        const stopDragging = (event) => {
            dragging = false;

            if (event?.pointerId !== undefined && viewport.hasPointerCapture(event.pointerId)) {
                viewport.releasePointerCapture(event.pointerId);
            }
        };

        viewport.addEventListener('pointerup', stopDragging);
        viewport.addEventListener('pointercancel', stopDragging);

        modal.querySelectorAll('[data-crop-close]').forEach((closer) => {
            closer.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.dataset.open === 'true') {
                event.preventDefault();
                event.stopPropagation();
                closeModal();
            }
        }, true);

        applyButton.addEventListener('click', () => {
            if (!state.image) {
                return;
            }

            const output = document.createElement('canvas');
            output.width = size;
            output.height = size;

            const outputContext = output.getContext('2d');
            outputContext.imageSmoothingEnabled = true;
            outputContext.imageSmoothingQuality = 'high';
            outputContext.fillStyle = '#ffffff';
            outputContext.fillRect(0, 0, size, size);
            drawInto(outputContext, size);

            const dataUrl = output.toDataURL('image/png');
            dataInput.value = dataUrl;

            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
            }

            initials?.classList.add('hidden');

            if (removeInput) {
                removeInput.checked = false;
            }

            pick.textContent = pick.dataset.labelChange || pick.textContent;
            closeModal();
        });
    }

    function initSearchableSelects() {
        const selects = Array.from(document.querySelectorAll('select[data-searchable]'));

        if (selects.length === 0) {
            return;
        }

        const searchPlaceholder = document.body.dataset.searchPlaceholder || 'Search...';
        let active = null;

        const enhance = (select) => {
            if (select.multiple || select.disabled) {
                return;
            }

            const wrapper = document.createElement('div');
            const autoWidth = select.classList.contains('!w-auto');
            wrapper.className = `searchable-select relative ${autoWidth ? 'inline-block' : 'w-full'}`;

            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            select.classList.add('sr-only');
            select.setAttribute('tabindex', '-1');

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = `${select.className} flex items-center justify-between gap-2 text-left`;
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.innerHTML = '<span class="min-w-0 truncate" data-searchable-label></span><svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" data-searchable-chevron><use href="#i-chevron"/></svg>';

            const panel = document.createElement('div');
            panel.className = 'absolute left-0 z-50 mt-1 hidden w-full min-w-[14rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl';
            panel.dataset.searchablePanel = 'true';
            panel.innerHTML = `
                <div class="border-b border-slate-100 p-2">
                    <input type="search" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/10" autocomplete="off">
                </div>
                <div class="max-h-56 overflow-y-auto p-1.5" role="listbox"></div>
            `;

            wrapper.appendChild(trigger);
            wrapper.appendChild(panel);

            const label = trigger.querySelector('[data-searchable-label]');
            const chevron = trigger.querySelector('[data-searchable-chevron]');
            const search = panel.querySelector('input[type="search"]');
            const list = panel.querySelector('[role="listbox"]');
            const options = Array.from(select.options).map((option, index) => ({
                option,
                index,
                label: option.textContent.trim(),
            }));

            search.placeholder = searchPlaceholder;

            const component = {
                wrapper,
                close: () => {
                    panel.classList.add('hidden');
                    trigger.setAttribute('aria-expanded', 'false');
                    chevron.classList.remove('rotate-180');

                    if (active === component) {
                        active = null;
                    }
                },
            };

            const updateLabel = () => {
                const selected = select.options[select.selectedIndex];
                const text = selected?.textContent.trim() ?? '';

                label.textContent = text;
                label.classList.toggle('text-slate-400', ! select.value);
                label.classList.toggle('text-slate-800', Boolean(select.value));
            };

            const choose = (option, index) => {
                select.selectedIndex = index;
                updateLabel();
                component.close();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const render = (query = '') => {
                const needle = query.trim().toLowerCase();
                const matches = options.filter((item) => item.label.toLowerCase().includes(needle));

                list.innerHTML = '';

                if (! matches.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-3 py-2 text-xs text-slate-400';
                    empty.textContent = '—';
                    list.appendChild(empty);

                    return;
                }

                matches.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.setAttribute('role', 'option');
                    button.className = 'block w-full truncate rounded-lg px-2.5 py-2 text-left text-sm transition hover:bg-slate-50 hover:text-slate-900';
                    button.textContent = item.label;
                    button.disabled = item.option.disabled;

                    if (item.index === select.selectedIndex) {
                        button.classList.add('bg-indigo-50', 'font-semibold', 'text-indigo-700');
                        button.setAttribute('aria-selected', 'true');
                    } else {
                        button.setAttribute('aria-selected', 'false');
                    }

                    if (item.option.disabled) {
                        button.classList.add('cursor-not-allowed', 'opacity-50');
                    }

                    button.addEventListener('click', () => choose(item.option, item.index));
                    list.appendChild(button);
                });
            };

            const open = () => {
                if (active && active !== component) {
                    active.close();
                }

                active = component;
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                chevron.classList.add('rotate-180');
                search.value = '';
                render();
                search.focus({ preventScroll: true });
            };

            trigger.addEventListener('click', () => {
                if (panel.classList.contains('hidden')) {
                    open();
                } else {
                    component.close();
                }
            });

            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open();
                }
            });

            search.addEventListener('input', () => render(search.value));

            search.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    component.close();
                    trigger.focus({ preventScroll: true });
                }

                if (event.key === 'Enter') {
                    const first = list.querySelector('button:not(:disabled)');

                    if (first) {
                        event.preventDefault();
                        first.click();
                    }
                }
            });

            select.addEventListener('change', updateLabel);
            select.form?.addEventListener('reset', () => window.setTimeout(updateLabel, 0));

            updateLabel();
        };

        selects.forEach(enhance);

        document.addEventListener('click', (event) => {
            if (active && ! active.wrapper.contains(event.target)) {
                active.close();
            }
        });
    }

    function initMemberFilters() {
        document.querySelectorAll('[data-member-filter]').forEach((input) => {
            const list = document.getElementById(input.dataset.memberFilter);
            const empty = document.querySelector(`[data-member-filter-empty="${input.dataset.memberFilter}"]`);

            if (!list) {
                return;
            }

            const items = Array.from(list.querySelectorAll('[data-member-filter-item]'));

            const apply = () => {
                const needle = input.value.trim().toLowerCase();
                let visible = 0;

                items.forEach((item) => {
                    const matches = (item.dataset.memberName || '').toLowerCase().includes(needle);
                    item.classList.toggle('hidden', !matches);

                    if (matches) {
                        visible++;
                    }
                });

                empty?.classList.toggle('hidden', visible > 0);
            };

            input.addEventListener('input', apply);
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
