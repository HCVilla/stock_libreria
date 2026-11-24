<?php

declare(strict_types=1);

/**
 * Fallback de desarrollo cuando Apache sirve la carpeta Stock completa.
 * Lo recomendado es configurar el DocumentRoot en Stock/public.
 */
header('Location: public/');
exit;
