<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\future;

use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\eventLoop\Suspension;

/**
 * @template T
 */
class FutureIteratorQueue
{
	/** @var list<array{0: mixed, 1: Future<T>}> */
	public array $items = [];

	/** @var array<string, FutureState<T>> */
	public array $pending = [];

	/** @var Suspension<array{0: mixed, 1: Future<T>}|null>|null */
	public ?Suspension $suspension = null;
}