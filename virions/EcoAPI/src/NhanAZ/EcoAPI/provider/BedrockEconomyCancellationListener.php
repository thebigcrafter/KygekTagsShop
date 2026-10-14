<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP plugins
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI\provider;

use Closure;
use WeakMap;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\database\transaction\UpdateTransaction;
use cooldogedev\BedrockEconomy\event\transaction\TransactionSubmitEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

/**
 * Maps synchronous BedrockEconomy cancellation events to EcoAPI failure callbacks.
 */
final class BedrockEconomyCancellationListener implements Listener {

	/** @var WeakMap<BedrockEconomy, self>|null */
	private static ?WeakMap $instances = null;

	/**
	 * @var array<int, array{xuid: string, username: string, mode: int, amount: int, decimals: int, finish: Closure(bool): void}>
	 */
	private array $pending = [];

	private int $nextOperationId = 0;

	private function __construct() {
	}

	public static function forBackend(BedrockEconomy $backend): self {
		$instances = self::$instances ??= new WeakMap();
		if (isset($instances[$backend])) {
			return $instances[$backend];
		}

		$listener = new self();
		Server::getInstance()->getPluginManager()->registerEvents($listener, $backend);
		$instances[$backend] = $listener;
		return $listener;
	}

	/**
	 * @param Closure(Closure(): void, Closure(): void): void $request
	 */
	public function submitMutation(
		string $xuid,
		string $username,
		int $mode,
		int $amount,
		int $decimals,
		Closure $request,
		?Closure $callback
	): void {
		if ($callback === null) {
			$request(static function(): void {}, static function(): void {});
			return;
		}

		$operationId = $this->nextOperationId++;
		$resolved = false;
		$finish = function(bool $success) use ($operationId, $callback, &$resolved): void {
			if ($resolved) {
				return;
			}
			$resolved = true;
			unset($this->pending[$operationId]);
			$callback($success);
		};

		$this->pending[$operationId] = [
			'xuid' => $xuid,
			'username' => $username,
			'mode' => $mode,
			'amount' => $amount,
			'decimals' => $decimals,
			'finish' => $finish
		];

		try {
			$request(
				static function() use ($finish): void { $finish(true); },
				static function() use ($finish): void { $finish(false); }
			);
		} finally {
			unset($this->pending[$operationId]);
		}
	}

	/**
	 * @priority MONITOR
	 * @handleCancelled true
	 */
	public function onTransactionSubmit(TransactionSubmitEvent $event): void {
		if (!$event->isCancelled() || !$event->transaction instanceof UpdateTransaction) {
			return;
		}

		$transaction = $event->transaction;
		foreach (array_reverse($this->pending, true) as $operationId => $operation) {
			if (
				$transaction->xuid === $operation['xuid'] &&
				$transaction->username === $operation['username'] &&
				$transaction->mode === $operation['mode'] &&
				$transaction->amount === $operation['amount'] &&
				$transaction->decimals === $operation['decimals']
			) {
				$finish = $operation['finish'];
				unset($this->pending[$operationId]);
				$finish(false);
				return;
			}
		}
	}
}
