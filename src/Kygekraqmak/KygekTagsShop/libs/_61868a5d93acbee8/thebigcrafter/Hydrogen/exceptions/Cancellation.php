<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\exceptions;

interface Cancellation
{
	/**
	 * Subscribes a new handler to be invoked on a cancellation request.
	 *
	 * This handler might be invoked immediately in case the cancellation has already been requested. Any unhandled
	 * exceptions will be thrown into the event loop.
	 *
	 * @param \Closure $callback Callback to be invoked on a cancellation request. Will receive a
	 *                           `CancelledException` as first argument that may be used to fail the operation.
	 *
	 * @return string Identifier that can be used to cancel the subscription.
	 */
	public function subscribe(\Closure $callback) : string;

	/**
	 * Unsubscribes a previously registered handler.
	 *
	 * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
	 */
	public function unsubscribe(string $id) : void;

	/**
	 * Returns whether cancellation has been requested yet.
	 */
	public function isRequested() : bool;

	/**
	 * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
	 *
	 * @throws CancelledException
	 */
	public function throwIfRequested() : void;
}