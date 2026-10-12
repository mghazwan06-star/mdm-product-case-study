<?php

namespace App\Mdm\Exceptions;

use RuntimeException;

/**
 * The provider's list can't be read completely, so nothing may change.
 * The message is the technical reason, kept for the sync history.
 */
class UnreadableSourceException extends RuntimeException {}
