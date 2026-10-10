<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen;

use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\InternetException;
use Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\exceptions\Cancellation;
use Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\exceptions\CancelledException;
use Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\future\Future;
use Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\future\FutureState;
use Kygekraqmak\KygekTagsShop\libs\_4e815ed074482851\thebigcrafter\Hydrogen\utils\Internet;
use function json_decode;
use function version_compare;

class Hydrogen
{

	/**
	 * Notify if an update is available on Poggit.
	 */
	public static function checkForUpdates(Plugin $plugin) : void
	{

		$logger = Server::getInstance()->getLogger();
		$highestVersion = $plugin->getDescription()->getVersion();
		$artifactUrl = "";

		$res = "";
		try {
			/** @var string $res */
			$res = Internet::fetch("https://poggit.pmmp.io/releases.min.json?name=" . $plugin->getName())->await();
		} catch (InternetException $e) {
			Server::getInstance()->getLogger()->debug($e->getMessage());
			$res = "";
		}

		$releases = (array) json_decode($res, true);

		if ($releases !== null) {
			/**
			 * @var array{'version': string, 'artifact_url': string} $release
			 */
			foreach ($releases as $release) {
				if (version_compare($highestVersion, $release["version"], ">=")) {
					continue;
				}

				$highestVersion = $release["version"];
				$artifactUrl = $release["artifact_url"];
			}
		}

		if ($highestVersion !== $plugin->getDescription()->getVersion()) {
			$artifactUrl .= "/{$plugin->getDescription()->getName()}_{$highestVersion}.phar";
			$logger->notice("{$plugin->getDescription()->getName()} v{$highestVersion} is available for download at {$artifactUrl}");
		}

	}

	/**
	 * Creates a new fiber asynchronously using the given closure, returning a Future that is completed with the
	 * eventual return value of the passed function or will fail if the closure throws an exception.
	 */
	/** @return Future<mixed> */
	public static function async(\Closure $closure, mixed ...$args) : Future
	{
		static $run = null;

		$run ??= static function (FutureState $state, \Closure $closure, array $args) : void {
			$s = $state;
			$c = $closure;

			$state = $closure = null;

			try {
				$s->complete($c(...$args, ...($args = [])));
			} catch (\Throwable $exception) {
				$s->error($exception);
			}
		};

		$state = new FutureState();

		EventLoop::queue($run, $state, $closure, $args);

		return new Future($state);
	}

	/**
	 * Non-blocking sleep for the specified number of seconds.
	 *
	 * @param float             $timeout      Number of seconds to wait.
	 * @param bool              $reference    If false, unreference the underlying watcher.
	 * @param Cancellation|null $cancellation Cancel waiting if cancellation is requested.
	 */
	function delay(float $timeout, bool $reference = true, ?Cancellation $cancellation = null) : void
	{
		$suspension = EventLoop::getSuspension();
		$callbackId = EventLoop::delay($timeout, static fn() => $suspension->resume());
		$cancellationId = $cancellation?->subscribe(
			static fn(CancelledException $exception) => $suspension->throw($exception)
		);

		if (!$reference) {
			EventLoop::unreference($callbackId);
		}

		try {
			$suspension->suspend();
		} finally {
			EventLoop::cancel($callbackId);

			if ($cancellation !== null && $cancellationId !== null) {
				$cancellation->unsubscribe($cancellationId);
			}
		}
	}
}