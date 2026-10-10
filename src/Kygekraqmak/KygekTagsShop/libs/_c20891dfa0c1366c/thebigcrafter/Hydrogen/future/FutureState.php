<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\future;

use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\EventLoop;
use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\exceptions\UnhandledFutureError;

/**
 * @template T
 */
class FutureState
{
	private static string $nextId = 'a';

	private bool $complete = false;

	private bool $handled = false;

	/** @var array<string, \Closure(?\Throwable, ?T, string): void> */
	private array $callbacks = [];

	/** @var T|null */
	private mixed $result = null;

	private ?\Throwable $throwable = null;

	private ?string $origin = null;

	public function __destruct()
	{
		if ($this->throwable && !$this->handled) {
			$throwable = new UnhandledFutureError($this->throwable, $this->origin);
			EventLoop::queue(static fn () => throw $throwable);
		}
	}

	/**
	 * Registers a callback to be notified once the operation is complete or errored.
	 *
	 * The callback is invoked directly from the event loop context, so suspension within the callback is not possible.
	 */
	public function subscribe(\Closure $callback) : string
	{
		$id = self::$nextId++;

		$this->handled = true;

		if ($this->complete) {
			EventLoop::queue($callback, $this->throwable, $this->result, $id);
		} else {
			$this->callbacks[$id] = $callback;
		}

		return $id;
	}

	/**
	 * Cancels a subscription.
	 *
	 * Cancellations are advisory only. The callback might still be called if it is already queued for execution.
	 */
	public function unsubscribe(string $id) : void
	{
		unset($this->callbacks[$id]);
	}

	/**
	 * Completes the operation with a result value.
	 */
	/** @param T|null $result */
	public function complete(mixed $result) : void
	{
		if ($this->complete) {
			throw new \Error('Operation is no longer pending');
		}

		if ($result instanceof Future) {
			throw new \Error('Cannot complete with an instance of ' . Future::class);
		}

		$this->result = $result;
		$this->invokeCallbacks();
	}

	/**
	 * Marks the operation as failed.
	 */
	public function error(\Throwable $throwable) : void
	{
		if ($this->complete) {
			throw new \Error('Operation is no longer pending');
		}

		$this->throwable = $throwable;
		$this->invokeCallbacks();
	}

	/**
	 * True if the operation has completed.
	 */
	public function isComplete() : bool
	{
		return $this->complete;
	}

	/**
	 * Suppress the exception thrown to the loop error handler if and operation error is not handled by a callback.
	 */
	public function ignore() : void
	{
		$this->handled = true;
	}

	private function invokeCallbacks() : void
	{
		$this->complete = true;

		foreach ($this->callbacks as $id => $callback) {
			EventLoop::queue($callback, $this->throwable, $this->result, $id);
		}

		$this->callbacks = [];
	}
}