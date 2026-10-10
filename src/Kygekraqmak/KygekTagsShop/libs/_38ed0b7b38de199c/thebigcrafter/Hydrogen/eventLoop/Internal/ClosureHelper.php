<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_38ed0b7b38de199c\thebigcrafter\Hydrogen\eventLoop\Internal;

/**
 * @internal
 */
final class ClosureHelper
{
	public static function getDescription(\Closure $closure) : string
	{
		try {
			$reflection = new \ReflectionFunction($closure);

			$description = $reflection->name;

			if ($scopeClass = $reflection->getClosureScopeClass()) {
				$description = $scopeClass->name . '::' . $description;
			}

			if ($reflection->getFileName() && $reflection->getStartLine()) {
				$description .= " defined in " . $reflection->getFileName() . ':' . $reflection->getStartLine();
			}

			return $description;
		} catch (\ReflectionException) {
			return '???';
		}
	}
}