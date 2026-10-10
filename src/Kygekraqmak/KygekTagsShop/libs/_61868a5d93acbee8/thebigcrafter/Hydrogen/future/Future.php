<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\future;

use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\EventLoop;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\exceptions\Cancellation;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\trait\ForbidCloning;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\trait\ForbidSerialization;
use function is_array;

/**
 * @template T
 */
final class Future
{
	use ForbidCloning;
	use ForbidSerialization;

	/**
	 * Iterate over the given futures in completion order.
	 */
	/**
	 * @template TKey
	 * @template TValue
	 * @param iterable<TKey, self<TValue>> $futures
	 * @return iterable<TKey, self<TValue>>
	 */
	public static function iterate(iterable $futures, ?Cancellation $cancellation = null) : iterable
	{
		$iterator = new FutureIterator($cancellation);

		if (is_array($futures)) {
			foreach ($futures as $key => $future) {
				if (!$future instanceof self) {
					throw new \TypeError('Array must only contain instances of ' . self::class);
				}
				$iterator->enqueue($future->state, $key, $future);
			}
			$iterator->complete();
		} else {
			EventLoop::queue(static function () use ($futures, $iterator) : void {
				try {
					foreach ($futures as $key => $future) {
						if (!$future instanceof self) {
							throw new \TypeError('Iterable must only provide instances of ' . self::class);
						}
						$iterator->enqueue($future->state, $key, $future);
					}
					$iterator->complete();
				} catch (\Throwable $exception) {
					$iterator->error($exception);
				}
			});
		}

		while ($item = $iterator->consume()) {
			/** @var array{0: TKey, 1: self<TValue>} $item */
			yield $item[0] => $item[1];
		}
	}

	/**
	 * @template TValue
	 * @param TValue $value
	 * @return self<TValue>
	 */
	public static function complete(mixed $value = null) : self
	{
		/** @var FutureState<TValue> $state */
		$state = new FutureState();
		$state->complete($value);

		return new self($state);
	}

	/**
	 * @return Future<never>
	 */
	/** @return self<never> */
	public static function error(\Throwable $throwable) : self
	{
		/** @var FutureState<never> $state */
		$state = new FutureState();
		$state->error($throwable);

		return new self($state);
	}

	/** @var FutureState<T> */
	private readonly FutureState $state;

	/** @param FutureState<T> $state */
	public function __construct(FutureState $state)
	{
		$this->state = $state;
	}

	/**
	 * True if the operation has completed.
	 */
	public function isComplete() : bool
	{
		return $this->state->isComplete();
	}

	/**
	 * Do not forward unhandled errors to the event loop handler.
	 */
	/** @return self<T> */
	public function ignore() : self
	{
		$this->state->ignore();

		return $this;
	}

	/**
	 * Attaches a callback that is invoked if this future completes. The returned future is completed with the return
	 * value of the callback, or errors with an exception thrown from the callback.
	 */
	/**
	 * @template U
	 * @param \Closure(T): U $map
	 * @return self<U>
	 */
	public function map(\Closure $map) : self
	{
		/** @var FutureState<U> $state */
		$state = new FutureState();

		$this->state->subscribe(static function (?\Throwable $error, mixed $value) use ($state, $map) : void {
			if ($error) {
				$state->error($error);
				return;
			}

			try {
				/** @var T $value */
				$state->complete($map($value));
			} catch (\Throwable $exception) {
				$state->error($exception);
			}
		});

		return new self($state);
	}

	/**
	 * Attaches a callback that is invoked if this future errors. The returned future is completed with the return
	 * value of the callback, or errors with an exception thrown from the callback.
	 */
	/**
	 * @template U
	 * @param \Closure(\Throwable): U $catch
	 * @return self<T|U>
	 */
	public function catch(\Closure $catch) : self
	{
		/** @var FutureState<T|U> $state */
		$state = new FutureState();

		$this->state->subscribe(static function (?\Throwable $error, mixed $value) use ($state, $catch) : void {
			if (!$error) {
				$state->complete($value);
				return;
			}

			try {
				$state->complete($catch($error));
			} catch (\Throwable $exception) {
				$state->error($exception);
			}
		});

		return new self($state);
	}

	/**
	 * Attaches a callback that is always invoked when the future is completed. The returned future resolves with the
	 * same value as this future once the callback has finished execution. If the callback throws, the returned future
	 * will error with the thrown exception.
	 */
	/** @return self<T> */
	public function finally(\Closure $finally) : self
	{
		/** @var FutureState<T> $state */
		$state = new FutureState();

		$this->state->subscribe(static function (?\Throwable $error, mixed $value) use ($state, $finally) : void {
			try {
				$finally();

				if ($error) {
					$state->error($error);
				} else {
					$state->complete($value);
				}
			} catch (\Throwable $exception) {
				$state->error($exception);
			}
		});

		return new self($state);
	}

	/**
	 * Awaits the operation to complete.
	 *
	 * Throws an exception if the operation fails.
	 */
	public function await(?Cancellation $cancellation = null) : mixed
	{
		$suspension = EventLoop::getSuspension();

		$callbackId = $this->state->subscribe(static function (?\Throwable $error, mixed $value) use (
			$suspension
		) : void {
			if ($error) {
				$suspension->throw($error);
			} else {
				$suspension->resume($value);
			}
		});

		$state = $this->state;
		$cancellationId = $cancellation?->subscribe(static function (\Throwable $reason) use (
			$callbackId,
			$suspension,
			$state
		) : void {
			$state->unsubscribe($callbackId);
			if (!$state->isComplete()) { // Resume has already been scheduled if complete.
				$suspension->throw($reason);
			}
		});

		try {
			return $suspension->suspend();
		} finally {
			if ($cancellation !== null && $cancellationId !== null) {
				$cancellation->unsubscribe($cancellationId);
			}
		}
	}
}