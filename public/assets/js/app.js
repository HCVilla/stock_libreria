(() => {
    'use strict';

    // ---------------------------------------------------------
    // Navegación móvil
    // ---------------------------------------------------------
    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach((element) => {
        element.addEventListener('click', () => {
            document.body.classList.remove('sidebar-open');
        });
    });

    // ---------------------------------------------------------
    // Avisos descartables
    // ---------------------------------------------------------
    document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.alert')?.remove();
        });
    });

    // ---------------------------------------------------------
    // Confirmación de acciones destructivas
    // ---------------------------------------------------------
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || '¿Confirmás la acción?')) {
                event.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------
    // Mostrar / ocultar contraseña
    // ---------------------------------------------------------
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.input-with-icon')?.querySelector('[data-password-input]');

            if (!input) {
                return;
            }

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            button.classList.toggle('is-visible', show);
        });
    });

    // ---------------------------------------------------------
    // Búsqueda instantánea de artículos (HTML renderizado en servidor)
    // ---------------------------------------------------------
    const searchInput = document.querySelector('[data-article-search]');

    if (searchInput) {
        const tableBody = document.querySelector('[data-article-rows]');
        const countElement = document.querySelector('[data-result-count]');
        const spinner = document.querySelector('[data-search-spinner]');
        const form = searchInput.closest('form');
        const endpoint = searchInput.dataset.endpoint;
        const loginUrl = document.body.dataset.loginUrl || '/login';
        let timer = null;
        let controller = null;

        const updateCount = (count) => {
            if (countElement) {
                countElement.textContent = `${count} artículo${count === 1 ? '' : 's'}`;
            }
        };

        const runSearch = async () => {
            if (!endpoint || !tableBody) {
                return;
            }

            controller?.abort();
            controller = new AbortController();

            spinner?.removeAttribute('hidden');
            tableBody.classList.add('is-loading');
            tableBody.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(searchInput.value.trim())}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                if (response.status === 401) {
                    window.location.href = loginUrl;
                    return;
                }

                if (!response.ok) {
                    throw new Error(`Error ${response.status}`);
                }

                const data = await response.json();
                tableBody.innerHTML = data.html;
                tableBody.classList.remove('is-loading');
                updateCount(Number(data.count) || 0);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('No se pudo ejecutar la búsqueda:', error);
                }
            } finally {
                spinner?.setAttribute('hidden', '');
                tableBody.classList.remove('is-loading');
                tableBody.removeAttribute('aria-busy');
            }
        };

        searchInput.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(runSearch, 180);
        });

        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            window.clearTimeout(timer);
            void runSearch();
        });
    }

    // ---------------------------------------------------------
    // Margen estimado en el formulario de artículos
    // ---------------------------------------------------------
    const costInput = document.querySelector('[data-price-cost]');
    const saleInput = document.querySelector('[data-price-sale]');
    const marginHint = document.querySelector('[data-margin-hint]');

    if (costInput && saleInput && marginHint) {
        const updateMargin = () => {
            const cost = Number.parseFloat(costInput.value || '0');
            const sale = Number.parseFloat(saleInput.value || '0');

            if (cost > 0 && sale > 0) {
                const margin = ((sale - cost) / cost) * 100;
                marginHint.textContent = `Margen: ${margin.toFixed(1)}%`;
                marginHint.classList.toggle('text-danger', margin < 0);
            } else {
                marginHint.textContent = 'Margen: —';
                marginHint.classList.remove('text-danger');
            }
        };

        costInput.addEventListener('input', updateMargin);
        saleInput.addEventListener('input', updateMargin);
        updateMargin();
    }

    // ---------------------------------------------------------
    // Resumen en vivo del formulario de artículos
    // ---------------------------------------------------------
    const codeInput = document.querySelector('#codigo');
    const stockInput = document.querySelector('#stock_actual');
    const unitInput = document.querySelector('#unidad_medida');

    if (codeInput) {
        const summaryCode = document.querySelector('[data-summary-code]');

        codeInput.addEventListener('input', () => {
            if (summaryCode) {
                summaryCode.textContent = codeInput.value.trim() || '—';
            }
        });
    }

    if (stockInput) {
        const summaryStock = document.querySelector('[data-summary-stock]');

        const updateStockSummary = () => {
            if (summaryStock) {
                const unit = unitInput?.value || 'unidad';
                summaryStock.textContent = `${stockInput.value || '0'} ${unit}`;
            }
        };

        stockInput.addEventListener('input', updateStockSummary);
        unitInput?.addEventListener('change', updateStockSummary);
        updateStockSummary();
    }

    // ---------------------------------------------------------
    // Formulario de movimientos de stock
    // ---------------------------------------------------------
    const movementForm = document.querySelector('[data-movement-form]');

    if (movementForm) {
        const typeOptions = Array.from(movementForm.querySelectorAll('[data-movement-type-option]'));
        const articleSelect = movementForm.querySelector('[data-movement-article]');
        const quantityField = movementForm.querySelector('[data-movement-quantity]');
        const quantityInput = movementForm.querySelector('#cantidad');
        const quantityLabel = movementForm.querySelector('[data-quantity-label]');
        const quantityHelp = movementForm.querySelector('[data-quantity-help]');
        const adjustField = movementForm.querySelector('[data-movement-adjust]');
        const adjustInput = movementForm.querySelector('#stock_real');
        const motiveInput = movementForm.querySelector('[data-movement-motive]');
        const motiveOptional = movementForm.querySelector('[data-motive-optional]');
        const motiveHelp = movementForm.querySelector('[data-movement-motive-help]');
        const modeHint = movementForm.querySelector('[data-movement-mode-hint]');
        const priceInput = movementForm.querySelector('[data-movement-price]');
        const priceHelp = movementForm.querySelector('[data-movement-price-help]');

        const summaryType = movementForm.querySelector('[data-summary-type]');
        const summaryArticle = movementForm.querySelector('[data-summary-article]');
        const summaryCurrent = movementForm.querySelector('[data-summary-current]');
        const summaryMin = movementForm.querySelector('[data-summary-min]');
        const summaryResult = movementForm.querySelector('[data-summary-result]');
        const summaryState = movementForm.querySelector('[data-summary-state]');

        const numberFormat = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
        const moneyFormat = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            maximumFractionDigits: 2,
        });

        const selectedType = () => {
            const checked = typeOptions.find((option) => option.checked);

            return checked ? checked.value : 'entrada';
        };

        const selectedArticle = () => {
            const option = articleSelect?.selectedOptions?.[0];

            return option && option.value !== '' ? option : null;
        };

        const parseValue = (input) => {
            if (!input) {
                return null;
            }

            const raw = String(input.value).replace(',', '.').trim();

            if (raw === '') {
                return null;
            }

            const value = Number.parseFloat(raw);

            return Number.isFinite(value) ? value : null;
        };

        const formatQty = (value) => `${numberFormat.format(value)}`;

        const updateMode = () => {
            const type = selectedType();
            const isAdjust = type === 'ajuste';

            quantityField.hidden = isAdjust;
            quantityInput.disabled = isAdjust;
            adjustField.hidden = !isAdjust;
            adjustInput.disabled = !isAdjust;

            if (isAdjust) {
                quantityInput.removeAttribute('required');
                adjustInput.setAttribute('required', '');
                motiveInput.setAttribute('required', '');
                motiveOptional.hidden = true;
            } else {
                adjustInput.removeAttribute('required');
                motiveInput.removeAttribute('required');
                motiveOptional.hidden = false;
            }

            if (type === 'salida') {
                quantityLabel.textContent = 'Cantidad a retirar';
                quantityHelp.textContent = 'El stock actual debe alcanzar para cubrir la salida.';
                modeHint.textContent = 'Se va a registrar una salida de stock. No se permite dejar el artículo en negativo.';
            } else if (type === 'ajuste') {
                modeHint.textContent = 'Se va a fijar el stock real contado y se auditará la diferencia contra el stock actual.';
            } else {
                quantityLabel.textContent = 'Cantidad a ingresar';
                quantityHelp.textContent = 'Unidades que ingresan al inventario.';
                modeHint.textContent = 'Se va a registrar una entrada de stock a favor del inventario.';
            }

            motiveHelp.textContent = isAdjust
                ? 'Obligatorio: explicá por qué se corrige el stock.'
                : 'Si lo dejás vacío se usa un motivo predeterminado.';

            if (summaryType) {
                summaryType.textContent = type === 'salida' ? 'Salida' : (type === 'ajuste' ? 'Ajuste' : 'Entrada');
            }

            updateSummary();
        };

        const updateSummary = () => {
            const type = selectedType();
            const article = selectedArticle();

            if (!article) {
                if (summaryArticle) {
                    summaryArticle.textContent = '—';
                }
                if (summaryCurrent) {
                    summaryCurrent.textContent = '—';
                }
                if (summaryMin) {
                    summaryMin.textContent = '—';
                }
                if (summaryResult) {
                    summaryResult.textContent = '—';
                    summaryResult.classList.remove('text-danger');
                }
                if (summaryState) {
                    summaryState.innerHTML = '<span class="badge badge-muted">Elegí un artículo</span>';
                }

                return;
            }

            const stock = Number.parseFloat(article.dataset.stock || '0');
            const minimo = Number.parseFloat(article.dataset.min || '0');
            const unit = article.dataset.unit || 'unidad';
            const code = article.dataset.code || '';
            const name = article.dataset.name || article.textContent.trim();

            let result = null;

            if (type === 'ajuste') {
                result = parseValue(adjustInput);
            } else {
                const cantidad = parseValue(quantityInput);

                if (cantidad !== null) {
                    result = type === 'salida' ? stock - cantidad : stock + cantidad;
                }
            }

            if (summaryArticle) {
                summaryArticle.textContent = name;
                summaryArticle.title = `${code} · ${name}`;
            }
            if (summaryCurrent) {
                summaryCurrent.textContent = `${formatQty(stock)} ${unit}`;
            }
            if (summaryMin) {
                summaryMin.textContent = formatQty(minimo);
            }
            if (summaryResult) {
                summaryResult.textContent = result === null ? '—' : `${formatQty(result)} ${unit}`;
                summaryResult.classList.toggle('text-danger', result !== null && result < 0);
            }

            if (priceInput) {
                const defaultPrice = Number.parseFloat(
                    type === 'salida' ? (article.dataset.sale || '0') : (article.dataset.cost || '0')
                );
                priceInput.placeholder = `Automático: ${moneyFormat.format(defaultPrice)}`;
            }

            if (priceHelp) {
                priceHelp.textContent = type === 'salida'
                    ? 'Si lo dejás vacío se usa el precio de venta del artículo.'
                    : 'Si lo dejás vacío se usa el precio de costo del artículo.';
            }

            if (summaryState) {
                let label = 'Disponible';
                let badge = 'success';

                if (result !== null && result < 0) {
                    label = 'Stock inválido';
                    badge = 'danger';
                } else if (result !== null && result <= 0) {
                    label = 'Sin stock';
                    badge = 'danger';
                } else if ((result === null ? stock : result) <= minimo) {
                    label = 'Bajo stock';
                    badge = 'warning';
                }

                summaryState.innerHTML = `<span class="badge badge-${badge}">${label}</span>`;
            }
        };

        typeOptions.forEach((option) => option.addEventListener('change', updateMode));
        articleSelect?.addEventListener('change', updateSummary);
        quantityInput?.addEventListener('input', updateSummary);
        adjustInput?.addEventListener('input', updateSummary);

        updateMode();
    }

    // ---------------------------------------------------------
    // Filtros que se aplican al cambiar el select
    // ---------------------------------------------------------
    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => {
            select.form?.submit();
        });
    });

    // ---------------------------------------------------------
    // Formulario de usuarios: resumen y descripción del rol
    // ---------------------------------------------------------
    const userRoleSelect = document.querySelector('[data-user-role]');

    if (userRoleSelect) {
        const roleDescriptions = {
            admin: 'Acceso total: usuarios, catálogo, movimientos y reportes.',
            encargado: 'Catálogo, importación y registro de movimientos.',
            vendedor: 'Consulta el catálogo, movimientos y reposición.',
            consulta: 'Solo lectura de todas las secciones operativas.',
        };
        const roleLabels = {
            admin: 'Administrador',
            encargado: 'Encargado',
            vendedor: 'Vendedor',
            consulta: 'Consulta',
        };
        const roleHint = document.querySelector('[data-user-role-hint]');
        const summaryRole = document.querySelector('[data-summary-role]');
        const summaryName = document.querySelector('[data-summary-name]');
        const summaryUser = document.querySelector('[data-summary-user]');
        const nameInput = document.querySelector('#nombre');
        const userInput = document.querySelector('#usuario');

        const updateRole = () => {
            const role = userRoleSelect.value;

            if (roleHint) {
                roleHint.textContent = roleDescriptions[role] || '';
            }
            if (summaryRole) {
                summaryRole.textContent = roleLabels[role] || role;
            }
        };

        const updateName = () => {
            if (summaryName) {
                summaryName.textContent = nameInput?.value.trim() || '—';
            }
        };

        const updateUser = () => {
            if (summaryUser) {
                const value = userInput?.value.trim() || '';
                summaryUser.textContent = value ? `@${value}` : '—';
            }
        };

        userRoleSelect.addEventListener('change', updateRole);
        nameInput?.addEventListener('input', updateName);
        userInput?.addEventListener('input', updateUser);
        updateRole();
    }

    // ---------------------------------------------------------
    // Lector de códigos de barras (cámara o lector USB)
    // ---------------------------------------------------------
    const barcodeModal = document.querySelector('[data-barcode-modal]');

    if (barcodeModal) {
        const video = barcodeModal.querySelector('[data-barcode-video]');
        const stage = barcodeModal.querySelector('[data-barcode-stage]');
        const placeholder = barcodeModal.querySelector('[data-barcode-placeholder]');
        const statusElement = barcodeModal.querySelector('[data-barcode-status]');
        const manualForm = barcodeModal.querySelector('[data-barcode-manual]');
        const manualInput = barcodeModal.querySelector('[data-barcode-input]');
        const closeButton = barcodeModal.querySelector('[data-barcode-close]');

        let stream = null;
        let detector = null;
        let scanTimer = null;
        let scanning = false;
        let activeTrigger = null;
        let cameraToken = 0;

        const setStatus = (message, state = 'info') => {
            if (!statusElement) {
                return;
            }

            statusElement.textContent = message;
            statusElement.dataset.state = state;
        };

        const stopCamera = () => {
            cameraToken++;
            window.clearInterval(scanTimer);
            scanTimer = null;
            scanning = false;

            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            }

            video.srcObject = null;
            stage?.classList.remove('is-active');
            placeholder?.removeAttribute('hidden');
        };

        const closeModal = () => {
            stopCamera();
            barcodeModal.hidden = true;
            document.body.classList.remove('modal-open');
            detector = null;

            if (activeTrigger) {
                activeTrigger.focus();
            }

            activeTrigger = null;
        };

        const applyCode = (rawCode) => {
            const code = String(rawCode || '').trim();

            if (code === '') {
                setStatus('El código está vacío. Escribilo de nuevo.', 'warning');
                return false;
            }

            const targetSelector = activeTrigger?.dataset.barcodeTarget;
            const selectSelector = activeTrigger?.dataset.barcodeSelect;

            if (selectSelector) {
                const select = document.querySelector(selectSelector);

                if (!select) {
                    setStatus('No se encontró el selector de artículos.', 'danger');
                    return false;
                }

                const option = Array.from(select.options).find(
                    (item) => String(item.dataset.code || '').trim().toLowerCase() === code.toLowerCase()
                );

                if (!option) {
                    setStatus(`No hay un artículo activo con el código «${code}». Revisá el código o cargalo primero.`, 'warning');
                    manualInput?.select();
                    return false;
                }

                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeModal();
                return true;
            }

            if (targetSelector) {
                const input = document.querySelector(targetSelector);

                if (!input) {
                    setStatus('No se encontró el campo donde pegar el código.', 'danger');
                    return false;
                }

                input.value = code;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
                closeModal();
                return true;
            }

            document.dispatchEvent(new CustomEvent('barcode:detected', { detail: { code, trigger: activeTrigger } }));
            closeModal();
            return true;
        };

        const startCamera = async () => {
            const token = ++cameraToken;

            if (!('BarcodeDetector' in window)) {
                setStatus('La lectura por cámara no está disponible en este navegador. Escribí el código o usá un lector USB.', 'warning');
                manualInput?.focus();
                return;
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                setStatus('El navegador bloquea la cámara en esta dirección. Usá localhost/HTTPS o escribí el código manualmente.', 'warning');
                manualInput?.focus();
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });

                if (token !== cameraToken || barcodeModal.hidden) {
                    stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                    return;
                }

                video.srcObject = stream;
                await video.play();
                stage?.classList.add('is-active');
                placeholder?.setAttribute('hidden', '');

                const preferred = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'codabar', 'qr_code'];
                let formats = preferred;

                try {
                    const supported = await window.BarcodeDetector.getSupportedFormats();
                    const filtered = preferred.filter((format) => supported.includes(format));

                    if (filtered.length > 0) {
                        formats = filtered;
                    }
                } catch (error) {
                    // Algunos navegadores no exponen getSupportedFormats; se usan los formatos preferidos.
                }

                detector = new window.BarcodeDetector({ formats });
                setStatus('Buscando un código…');

                scanTimer = window.setInterval(async () => {
                    if (scanning || !detector || video.readyState < 2) {
                        return;
                    }

                    scanning = true;

                    try {
                        const codes = await detector.detect(video);

                        if (codes.length > 0 && codes[0].rawValue) {
                            applyCode(codes[0].rawValue);
                        }
                    } catch (error) {
                        // Un frame fallido no debe cortar el escaneo.
                    } finally {
                        scanning = false;
                    }
                }, 300);
            } catch (error) {
                stopCamera();

                const message = error.name === 'NotAllowedError'
                    ? 'Permiso de cámara denegado. Habilitalo en el navegador o escribí el código manualmente.'
                    : error.name === 'NotFoundError'
                        ? 'No se encontró una cámara disponible. Escribí el código o usá un lector USB.'
                        : 'No se pudo iniciar la cámara. Escribí el código o usá un lector USB.';

                setStatus(message, 'warning');
                manualInput?.focus();
            }
        };

        document.querySelectorAll('[data-barcode-scan]').forEach((button) => {
            button.addEventListener('click', () => {
                activeTrigger = button;
                barcodeModal.hidden = false;
                document.body.classList.add('modal-open');
                setStatus('');
                manualInput.value = '';
                window.setTimeout(() => manualInput.focus(), 120);
                void startCamera();
            });
        });

        closeButton?.addEventListener('click', closeModal);

        barcodeModal.addEventListener('click', (event) => {
            if (event.target === barcodeModal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !barcodeModal.hidden) {
                closeModal();
            }
        });

        manualForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            applyCode(manualInput.value);
        });
    }
})();
