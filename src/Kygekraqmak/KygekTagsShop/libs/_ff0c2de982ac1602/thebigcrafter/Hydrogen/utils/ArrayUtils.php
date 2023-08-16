<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_ff0c2de982ac1602\thebigcrafter\Hydrogen\utils;

use function floor;
use function strcmp;

class ArrayUtils
{
	/**
	 * @param array<string> $arr
	 */
	public static function binarySearch(array $arr, string $target, int $left, int $right) : int
	{
		if ($left <= $right) {
			$middle = (int) floor(($left + $right) / 2);
			$result = strcmp($arr[$middle], $target);

			if ($result === 0) {
				return $middle;
			} elseif ($result < 0) {
				return self::binarySearch($arr, $target, $middle + 1, $right);
			} else {
				return self::binarySearch($arr, $target, $left, $middle - 1);
			}
		}

		return -1;
	}
}