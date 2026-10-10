<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\trait;

trait ForbidSerialization
{
	final public function __serialize() : never
	{
		throw new \Error(__CLASS__ . ' does not support serialization');
	}
}