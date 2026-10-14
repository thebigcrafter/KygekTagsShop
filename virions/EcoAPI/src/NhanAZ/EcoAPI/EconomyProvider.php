<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI;

use Closure;
use pocketmine\player\Player;

/**
 * Interface for economy providers.
 *
 * Each supported economy plugin (e.g., EconomyAPI, BedrockEconomy) has a class
 * that implements this interface. This provides a unified way to interact with
 * any economy plugin without worrying about their different APIs.
 *
 * All money-modifying methods use callbacks because some economy plugins
 * (like BedrockEconomy) perform database operations asynchronously.
 * For synchronous plugins (like EconomyAPI), the callback is called immediately.
 * A backend can suppress completion callbacks: BedrockEconomy 4.0.4 does so
 * when a listener cancels TransactionSubmitEvent. See the README before
 * relying on callback delivery for a transaction.
 */
interface EconomyProvider {

	/**
	 * Get the display name of this economy provider.
	 *
	 * @return string e.g., "EconomyAPI", "BedrockEconomy", "XP"
	 */
	public function getName(): string;

	/**
	 * Get the currency symbol used by this economy provider.
	 *
	 * @return string e.g., "$", "€", "XP"
	 */
	public function getCurrencySymbol(): string;

	/**
	 * Check if this economy provider's plugin is installed and available.
	 *
	 * @return bool true if the required economy plugin is installed and enabled
	 */
	public static function isAvailable(): bool;

	/**
	 * Get the balance of a player.
	 *
	 * @param Player $player The player to check
	 * @param Closure(float|int): void $callback Called with the player's balance
	 */
	public function getMoney(Player $player, Closure $callback): void;

	/**
	 * Set the balance of a player to a specific amount.
	 *
	 * @param Player $player The player to modify
	 * @param float $amount The new balance (must be >= 0)
	 * @param (Closure(bool): void)|null $callback If invoked, receives true on success or false on failure
	 */
	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void;

	/**
	 * Add money to a player's balance.
	 *
	 * @param Player $player The player to modify
	 * @param float $amount The amount to add (must be > 0)
	 * @param (Closure(bool): void)|null $callback If invoked, receives true on success or false on failure
	 */
	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void;

	/**
	 * Take money from a player's balance.
	 *
	 * @param Player $player The player to modify
	 * @param float $amount The amount to take (must be > 0)
	 * @param (Closure(bool): void)|null $callback If invoked, receives true on success or false on failure
	 */
	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void;
}
