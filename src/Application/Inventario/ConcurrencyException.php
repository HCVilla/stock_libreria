<?php

declare(strict_types=1);

namespace App\Application\Inventario;

use RuntimeException;

/**
 * Se lanza cuando la base de datos no puede completar la transacción por
 * conflictos de bloqueo entre operaciones simultáneas.
 */
final class ConcurrencyException extends RuntimeException
{
}
