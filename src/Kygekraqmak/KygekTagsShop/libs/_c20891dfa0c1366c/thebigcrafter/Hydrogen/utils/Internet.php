<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\utils;

use pocketmine\utils\InternetException;
use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\EventLoop;
use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\future\DeferredFuture;
use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\future\Future;

class Internet
{
	/** @return Future<string> */
	public static function fetch(string $url) : Future
	{
		/** @var DeferredFuture<string> $deferred */
		$deferred = new DeferredFuture();

		EventLoop::defer(function () use ($deferred, $url) : void {
			$res = \pocketmine\utils\Internet::getURL($url);

			if ($res === null) {
				throw new InternetException("Unable to fetch {$url}");
			}

			$deferred->complete($res->getBody());
		});

		return $deferred->getFuture();
	}
}