<?php

declare(strict_types=1);
?>
<div class="modal-backdrop" data-barcode-modal hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="barcode-title">
        <header class="modal-head">
            <div>
                <h2 id="barcode-title">Escanear código</h2>
                <p>Acercá el código de barras a la cámara o usá un lector USB.</p>
            </div>
            <button type="button" class="icon-btn" data-barcode-close aria-label="Cerrar escáner">
                <?= icon('close') ?>
            </button>
        </header>

        <div class="modal-body">
            <div class="barcode-stage" data-barcode-stage>
                <video class="barcode-video" data-barcode-video playsinline muted></video>
                <span class="barcode-frame" aria-hidden="true"></span>
                <div class="barcode-placeholder" data-barcode-placeholder>
                    <?= icon('camera') ?>
                    <span>La cámara se activará al abrir el escáner.</span>
                </div>
            </div>

            <p class="barcode-status" data-barcode-status role="status" aria-live="polite"></p>

            <form class="barcode-manual" data-barcode-manual>
                <label class="label" for="barcode-manual-input">Código manual</label>
                <div class="barcode-manual-row">
                    <input
                        class="input"
                        id="barcode-manual-input"
                        type="text"
                        inputmode="text"
                        autocomplete="off"
                        placeholder="Ej. 7791234567890"
                        data-barcode-input
                    >
                    <button type="submit" class="btn btn-primary"><?= icon('check') ?><span>Usar código</span></button>
                </div>
                <span class="help">Si usás un lector USB, hacé clic acá y escaneá: el código se envía solo al presionar Enter.</span>
            </form>
        </div>
    </div>
</div>
