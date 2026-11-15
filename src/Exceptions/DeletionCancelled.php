<?php

namespace Nyamort\LaravelAnonymizer\Exceptions;

use RuntimeException;

/**
 * @internal Used to roll back a transaction when an Eloquent listener vetoes deletion.
 */
final class DeletionCancelled extends RuntimeException {}
