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
use NhanAZ\EcoAPI\EconomyProvider;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Economy provider for onebone/EconomyAPI.
 *
 * @see https://poggit.pmmp.io/p/EconomyAPI
 */
class EconomyAPIProvider implements EconomyProvider {

	private EconomyAPI $api;

	public function __construct() {
		$this->api = EconomyAPI::getInstance();
	}

	public function getName(): string {
		return "EconomyAPI";
	}

	public function getCurrencySymbol(): string {
		return $this->api->getMonetaryUnit();
	}

	public static function isAvailable(): bool {
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI");
		return $plugin instanceof EconomyAPI && $plugin->isEnabled();
	}

	public function getMoney(Player $player, Closure $callback): void {
		$money = $this->api->myMoney($player);
		$callback($money === false ? 0.0 : (float) $money);
	}

	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!$this->validAmount($amount, false)) {
			$callback?->__invoke(false);
			return;
		}
		$result = $this->api->setMoney($player, $amount);
		if ($callback !== null) {
			$callback($result === EconomyAPI::RET_SUCCESS);
		}
	}

	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!$this->validAmount($amount, true)) {
			$callback?->__invoke(false);
			return;
		}
		$result = $this->api->addMoney($player, $amount);
		if ($callback !== null) {
			$callback($result === EconomyAPI::RET_SUCCESS);
		}
	}

	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!$this->validAmount($amount, true)) {
			$callback?->__invoke(false);
			return;
		}
		$result = $this->api->reduceMoney($player, $amount);
		if ($callback !== null) {
			$callback($result === EconomyAPI::RET_SUCCESS);
		}
	}

	private function validAmount(float $amount, bool $positive): bool {
		return is_finite($amount)
			&& ($positive ? $amount > 0 : $amount >= 0)
			&& round($amount, 2) === $amount;
	}
}
