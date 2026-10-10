<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\exceptions;

final class NullCancellation implements Cancellation
{
	public function subscribe(\Closure $callback) : string
	{
		return "null-cancellation";
	}

	public function unsubscribe(string $id) : void
	{
		// nothing to do
	}

	public function isRequested() : bool
	{
		return false;
	}

	public function throwIfRequested() : void
	{
		// nothing to do
	}
}