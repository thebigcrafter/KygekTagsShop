<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI\provider;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\type\ClosureAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\currency\Currency;
use cooldogedev\BedrockEconomy\database\constant\UpdateMode;
use cooldogedev\BedrockEconomy\database\cache\GlobalCache;
use NhanAZ\EcoAPI\EconomyProvider;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Economy provider for cooldogedev/BedrockEconomy (v4.0+).
 *
 * BedrockEconomy uses asynchronous database operations,
 * so database result callbacks may be called on a later tick.
 *
 * @see https://poggit.pmmp.io/p/BedrockEconomy
 */
class BedrockEconomyProvider implements EconomyProvider {

	private ClosureAPI $api;
	private Currency $currency;
	private BedrockEconomyCancellationListener $cancellationListener;

	public function __construct() {
		$backend = BedrockEconomy::getInstance();
		$this->api = BedrockEconomyAPI::CLOSURE();
		$this->currency = $backend->getCurrency();
		$this->cancellationListener = BedrockEconomyCancellationListener::forBackend($backend);
	}

	public function getName(): string {
		return "BedrockEconomy";
	}

	public function getCurrencySymbol(): string {
		return $this->currency->symbol;
	}

	public static function isAvailable(): bool {
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy");
		return $plugin instanceof BedrockEconomy
			&& $plugin->isEnabled()
			&& version_compare($plugin->getDescription()->getVersion(), "4.0", ">=");
	}

	public function getMoney(Player $player, Closure $callback): void {
		$entry = GlobalCache::ONLINE()->get($player->getName());
		if ($entry !== null) {
			$callback($entry->amount + $entry->decimals / 100);
		} else {
			$callback($this->currency->defaultAmount + $this->currency->defaultDecimals / 100);
		}
	}

	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$parts = $this->splitAmount($amount, false);
		if ($parts === null) {
			$callback?->__invoke(false);
			return;
		}
		$this->submitMutation($player, $parts, UpdateMode::SET, $callback);
	}

	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$parts = $this->splitAmount($amount, true);
		if ($parts === null) {
			$callback?->__invoke(false);
			return;
		}
		$this->submitMutation($player, $parts, UpdateMode::ADD, $callback);
	}

	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$parts = $this->splitAmount($amount, true);
		if ($parts === null) {
			$callback?->__invoke(false);
			return;
		}
		$this->submitMutation($player, $parts, UpdateMode::SUBTRACT, $callback);
	}

	/** @param array{int, int} $parts */
	private function submitMutation(Player $player, array $parts, int $mode, ?Closure $callback): void {
		$xuid = $player->getXuid();
		$username = $player->getName();
		$amount = $parts[0];
		$decimals = $parts[1];
		$request = function(Closure $onSuccess, Closure $onError) use ($xuid, $username, $amount, $decimals, $mode): void {
			switch ($mode) {
				case UpdateMode::SET:
					$this->api->set($xuid, $username, $amount, $decimals, $onSuccess, $onError);
					return;
				case UpdateMode::ADD:
					$this->api->add($xuid, $username, $amount, $decimals, $onSuccess, $onError);
					return;
				case UpdateMode::SUBTRACT:
					$this->api->subtract($xuid, $username, $amount, $decimals, $onSuccess, $onError);
					return;
				default:
					throw new \InvalidArgumentException("Unsupported BedrockEconomy update mode: {$mode}");
			}
		};

		$this->cancellationListener->submitMutation($xuid, $username, $mode, $amount, $decimals, $request, $callback);
	}

	/** @return array{int, int}|null */
	private function splitAmount(float $amount, bool $positive): ?array {
		if (!is_finite($amount) || ($positive ? $amount <= 0 : $amount < 0) || $amount >= PHP_INT_MAX) {
			return null;
		}
		$formatted = number_format($amount, 2, ".", "");
		if ((float) $formatted !== $amount) {
			return null;
		}
		[$units, $cents] = explode(".", $formatted, 2);
		$cents = (int) $cents;
		// BedrockEconomy 4.0.4 persists 05 cents as .5; refuse amounts it cannot store faithfully.
		if (($cents > 0 && $cents < 10) || (!$this->currency->decimals && $cents !== 0)) {
			return null;
		}
		return [(int) $units, $cents];
	}
}
