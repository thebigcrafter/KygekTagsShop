<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\exceptions;

/**
 * Will be thrown in case an operation is cancelled.
 *
 * @see Cancellation
 * @see DeferredCancellation
 */
class CancelledException extends \Exception
{
	public function __construct(?\Throwable $previous = null)
	{
		parent::__construct("The operation was cancelled", 0, $previous);
	}
}