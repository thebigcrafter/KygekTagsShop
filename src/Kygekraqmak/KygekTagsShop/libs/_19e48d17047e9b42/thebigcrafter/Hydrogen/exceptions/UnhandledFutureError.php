<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\exceptions;

class UnhandledFutureError extends \Error
{
	public function __construct(\Throwable $previous, ?string $origin = null)
	{
		$message = 'Unhandled future: ' . $previous::class . ': "' . $previous->getMessage()
			. '"; Await the Future with Future::await() before the future is destroyed or use '
			. 'Future::ignore() to suppress this exception.';

		if ($origin) {
			$message .= ' The future has been created at ' . $origin;
		} else {
			$message .= ' Enable assertions and set AMP_DEBUG=true in the process environment to track its origin.';
		}

		parent::__construct($message, 0, $previous);
	}
}