<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\eventLoop\Internal;

use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\thebigcrafter\Hydrogen\eventLoop\Suspension;
use function array_keys;
use function array_map;
use function assert;
use function gc_collect_cycles;
use function implode;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * @internal
 *
 * @template T
 * @implements Suspension<T>
 */
final class DriverSuspension implements Suspension
{
	private ?\Fiber $suspendedFiber = null;

	/** @var \WeakReference<\Fiber>|null */
	private readonly ?\WeakReference $fiberRef;

	private ?\FiberError $fiberError = null;

	private bool $pending = false;

	public function __construct(
		private readonly \Closure $run,
		private readonly \Closure $queue,
		private readonly \Closure $interrupt,
		private readonly \WeakMap $suspensions,
	) {
		$fiber = \Fiber::getCurrent();

		$this->fiberRef = $fiber ? \WeakReference::create($fiber) : null;
	}

	public function resume(mixed $value = null) : void
	{
		if (!$this->pending) {
			throw $this->fiberError ?? new \Error('Must call suspend() before calling resume()');
		}

		$this->pending = false;

		/** @var \Fiber|null $fiber */
		$fiber = $this->fiberRef?->get();

		if ($fiber) {
			($this->queue)(static function () use ($fiber, $value) : void {
				// The fiber may be destroyed with suspension as part of the GC cycle collector.
				if (!$fiber->isTerminated()) {
					$fiber->resume($value);
				}
			});
		} else {
			// Suspend event loop fiber to {main}.
			($this->interrupt)(static fn () => $value);
		}
	}

	public function suspend() : mixed
	{
		if ($this->pending) {
			throw new \Error('Must call resume() or throw() before calling suspend() again');
		}

		$fiber = $this->fiberRef?->get();

		if ($fiber !== \Fiber::getCurrent()) {
			throw new \Error('Must not call suspend() from another fiber');
		}

		$this->pending = true;

		// Awaiting from within a fiber.
		if ($fiber) {
			$this->suspendedFiber = $fiber;

			try {
				$value = \Fiber::suspend();
				$this->suspendedFiber = null;
			} catch (\FiberError $exception) {
				$this->pending = false;
				$this->suspendedFiber = null;
				$this->fiberError = $exception;

				throw $exception;
			}

			// Setting $this->suspendedFiber = null in finally will set the fiber to null if a fiber is destroyed
			// as part of a cycle collection, causing an error if the suspension is subsequently resumed.

			return $value;
		}

		// Awaiting from {main}.
		$result = ($this->run)();

		/** @psalm-suppress RedundantCondition $this->pending should be changed when resumed. */
		if ($this->pending) {
			$this->pending = false;
			$result && $result(); // Unwrap any uncaught exceptions from the event loop

			gc_collect_cycles(); // Collect any circular references before dumping pending suspensions.

			$info = '';
			foreach ($this->suspensions as $suspensionRef) {
				if ($suspension = $suspensionRef->get()) {
					assert($suspension instanceof self);
					$fiber = $suspension->fiberRef?->get();
					if ($fiber === null) {
						continue;
					}

					$reflectionFiber = new \ReflectionFiber($fiber);
					$info .= "\n\n" . $this->formatStacktrace($reflectionFiber->getTrace(DEBUG_BACKTRACE_IGNORE_ARGS));
				}
			}

			throw new \Error('Event loop terminated without resuming the current suspension (the cause is either a fiber deadlock, or an incorrectly unreferenced/canceled watcher):' . $info);
		}

		return $result();
	}

	public function throw(\Throwable $throwable) : void
	{
		if (!$this->pending) {
			throw $this->fiberError ?? new \Error('Must call suspend() before calling throw()');
		}

		$this->pending = false;

		/** @var \Fiber|null $fiber */
		$fiber = $this->fiberRef?->get();

		if ($fiber) {
			($this->queue)(static function () use ($fiber, $throwable) : void {
				// The fiber may be destroyed with suspension as part of the GC cycle collector.
				if (!$fiber->isTerminated()) {
					$fiber->throw($throwable);
				}
			});
		} else {
			// Suspend event loop fiber to {main}.
			($this->interrupt)(static fn () => throw $throwable);
		}
	}

	private function formatStacktrace(array $trace) : string
	{
		return implode("\n", array_map(static function ($e, $i) {
			$line = "#{$i} ";

			if (isset($e["file"])) {
				$line .= "{$e['file']}:{$e['line']} ";
			}

			if (isset($e["class"], $e["type"])) {
				$line .= $e["class"] . $e["type"];
			}

			return $line . $e["function"] . "()";
		}, $trace, array_keys($trace)));
	}
}