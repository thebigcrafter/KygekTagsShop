<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_38ed0b7b38de199c\thebigcrafter\Hydrogen\future;

use Kygekraqmak\KygekTagsShop\libs\_38ed0b7b38de199c\thebigcrafter\Hydrogen\trait\ForbidCloning;
use Kygekraqmak\KygekTagsShop\libs\_38ed0b7b38de199c\thebigcrafter\Hydrogen\trait\ForbidSerialization;

/**
 * @template T
 */
class DeferredFuture
{
	use ForbidCloning;
	use ForbidSerialization;

	/** @var FutureState<T> */
	private readonly FutureState $state;

	/** @var Future<T> */
	private readonly Future $future;

	public function __construct()
	{
		$this->state = new FutureState();
		$this->future = new Future($this->state);
	}

	/**
	 * Completes the operation with a result value.
	 *
	 * @param T|null $value Result of the operation.
	 */
	public function complete(mixed $value = null) : void
	{
		$this->state->complete($value);
	}

	/**
	 * Marks the operation as failed.
	 *
	 * @param \Throwable $throwable Throwable to indicate the error.
	 */
	public function error(\Throwable $throwable) : void
	{
		$this->state->error($throwable);
	}

	/**
	 * @return bool True if the operation has completed.
	 */
	public function isComplete() : bool
	{
		return $this->state->isComplete();
	}

	/**
	 * @return Future<T> The future associated with this Deferred.
	 */
	public function getFuture() : Future
	{
		return $this->future;
	}
}