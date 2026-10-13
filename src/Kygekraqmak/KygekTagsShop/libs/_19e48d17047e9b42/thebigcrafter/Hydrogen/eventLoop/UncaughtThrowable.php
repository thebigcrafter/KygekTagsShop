<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop;

use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Internal\ClosureHelper;
use function get_class;
use function sprintf;
use function str_replace;

final class UncaughtThrowable extends \Error
{
	public static function throwingCallback(\Closure $closure, \Throwable $previous) : self
	{
		return new self(
			"Uncaught %s thrown in event loop callback %s; use Revolt\EventLoop::setErrorHandler() to gracefully handle such exceptions%s",
			$closure,
			$previous
		);
	}

	public static function throwingErrorHandler(\Closure $closure, \Throwable $previous) : self
	{
		return new self("Uncaught %s thrown in event loop error handler %s%s", $closure, $previous);
	}

	private function __construct(string $message, \Closure $closure, \Throwable $previous)
	{
		parent::__construct(sprintf(
			$message,
			str_replace("\0", '@', get_class($previous)), // replace NUL-byte in anonymous class name
			ClosureHelper::getDescription($closure),
			$previous->getMessage() !== '' ? ': ' . $previous->getMessage() : ''
		), 0, $previous);
	}
}