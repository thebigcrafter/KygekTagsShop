<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\eventLoop\Internal;

/**
 * @internal
 */
abstract class DriverCallback
{
	public bool $invokable = false;

	public bool $enabled = true;

	public bool $referenced = true;

	public function __construct(
		public readonly string $id,
		public readonly \Closure $closure
	) {
	}

	public function __get(string $property) : never
	{
		throw new \Error("Unknown property '{$property}'");
	}

	public function __set(string $property, mixed $value) : never
	{
		throw new \Error("Unknown property '{$property}'");
	}
}