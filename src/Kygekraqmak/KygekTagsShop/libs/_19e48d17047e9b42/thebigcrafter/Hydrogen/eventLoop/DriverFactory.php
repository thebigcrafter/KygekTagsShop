<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop;

// @codeCoverageIgnoreStart
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Driver\EvDriver;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Driver\EventDriver;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Driver\StreamSelectDriver;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Driver\TracingDriver;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\eventLoop\Driver\UvDriver;
use function class_exists;
use function getenv;
use function is_subclass_of;
use function sprintf;

final class DriverFactory
{
	/**
	 * Creates a new loop instance and chooses the best available driver.
	 *
	 * @throws \Error If an invalid class has been specified via REVOLT_LOOP_DRIVER
	 */
	public function create() : Driver
	{
		$driver = (function () {
			if ($driver = $this->createDriverFromEnv()) {
				return $driver;
			}

			if (UvDriver::isSupported()) {
				return new UvDriver();
			}

			if (EvDriver::isSupported()) {
				return new EvDriver();
			}

			if (EventDriver::isSupported()) {
				return new EventDriver();
			}

			return new StreamSelectDriver();
		})();

		if (getenv("REVOLT_DRIVER_DEBUG_TRACE")) {
			return new TracingDriver($driver);
		}

		return $driver;
	}

	private function createDriverFromEnv() : ?Driver
	{
		$driver = getenv("REVOLT_DRIVER");

		if (!$driver) {
			return null;
		}

		if (!class_exists($driver)) {
			throw new \Error(sprintf(
				"Driver '%s' does not exist.",
				$driver
			));
		}

		if (!is_subclass_of($driver, Driver::class)) {
			throw new \Error(sprintf(
				"Driver '%s' is not a subclass of '%s'.",
				$driver,
				Driver::class
			));
		}

		return new $driver();
	}
}