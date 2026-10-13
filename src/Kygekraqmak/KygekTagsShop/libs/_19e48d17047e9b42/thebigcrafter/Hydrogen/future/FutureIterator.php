<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\future;

use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\EventLoop;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\exceptions\Cancellation;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\exceptions\NullCancellation;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\trait\ForbidCloning;
use Kygekraqmak\KygekTagsShop\libs\_19e48d17047e9b42\thebigcrafter\Hydrogen\trait\ForbidSerialization;
use function array_key_first;

class FutureIterator
{
	use ForbidCloning;
	use ForbidSerialization;

	/** @var FutureIteratorQueue<mixed> */
	private readonly FutureIteratorQueue $queue;

	private readonly Cancellation $cancellation;

	private readonly string $cancellationId;

	/** @var Future<null>|Future<never>|null */
	private ?Future $complete = null;

	public function __construct(?Cancellation $cancellation = null)
	{
		$this->queue = $queue = new FutureIteratorQueue();
		$this->cancellation = $cancellation ?? new NullCancellation();
		$this->cancellationId = $this->cancellation->subscribe(static function (\Throwable $reason) use ($queue) : void {
			if ($queue->suspension) {
				$queue->suspension->throw($reason);
				$queue->suspension = null;
			}
		});
	}

	/**
	 * @param FutureState<mixed> $state
	 * @param Future<mixed>      $future
	 */
	public function enqueue(FutureState $state, mixed $key, Future $future) : void
	{
		if ($this->complete) {
			throw new \Error('Iterator has already been marked as complete');
		}

		$queue = $this->queue; // Using separate object to avoid a circular reference.

		/** @var FutureIteratorQueue<mixed> $queue */
		$handler = static function (?\Throwable $error, mixed $result, string $id) use (
			$key,
			$future,
			$queue
		) : void {
			unset($queue->pending[$id]);

			if ($queue->suspension) {
				$queue->suspension->resume([$key, $future]);
				$queue->suspension = null;
				return;
			}

			$queue->items[] = [$key, $future];
		};

		$id = $state->subscribe($handler);

		$queue->pending[$id] = $state;
	}

	public function complete() : void
	{
		if ($this->complete) {
			throw new \Error('Iterator has already been marked as complete');
		}

		$this->complete = Future::complete();

		if (!$this->queue->pending && $this->queue->suspension) {
			$this->queue->suspension->resume();
			$this->queue->suspension = null;
		}
	}

	public function error(\Throwable $exception) : void
	{
		if ($this->complete) {
			throw new \Error('Iterator has already been marked as complete');
		}

		$this->complete = Future::error($exception);

		if (!$this->queue->pending && $this->queue->suspension) {
			$this->queue->suspension->throw($exception);
			$this->queue->suspension = null;
		}
	}

	/** @return array{0: mixed, 1: Future<mixed>}|null */
	public function consume() : ?array
	{
		if ($this->queue->suspension) {
			throw new \Error('Concurrent consume() operations are not supported');
		}

		if (!$this->queue->items) {
			if ($this->complete && !$this->queue->pending) {
				/** @var array{0: mixed, 1: Future<mixed>}|null $result */
				$result = $this->complete->await();
				return $result;
			}

			$this->cancellation->throwIfRequested();

			$this->queue->suspension = EventLoop::getSuspension();

			/** @var array{0: mixed, 1: Future<mixed>}|null $result */
			$result = $this->queue->suspension->suspend();
			return $result;
		}

		$key = array_key_first($this->queue->items);
		$item = $this->queue->items[$key];

		unset($this->queue->items[$key]);

		return $item;
	}

	public function __destruct()
	{
		$this->cancellation->unsubscribe($this->cancellationId);
		foreach ($this->queue->pending as $id => $state) {
			$state->unsubscribe($id);
		}
	}
}