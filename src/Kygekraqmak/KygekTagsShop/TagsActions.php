<?php

/*
 *  PLUGIN BY:
 *   _    __                  _                                     _
 *  | |  / /                 | |                                   | |
 *  | | / /                  | |                                   | |
 *  | |/ / _   _  ____   ____| | ______ ____   _____ ______   ____ | | __
 *  | |\ \| | | |/ __ \ / __ \ |/ /  __/ __ \ / __  | _  _ \ / __ \| |/ /
 *  | | \ \ \_| | <__> |  ___/   <| / | <__> | <__| | |\ |\ | <__> |   <
 *  |_|  \_\__  |\___  |\____|_|\_\_|  \____^_\___  |_||_||_|\____^_\|\_\
 *            | |    | |                          | |
 *         ___/ | ___/ |                          | |
 *        |____/ |____/                           |_|
 *
 * A PocketMine-MP plugin that allows players to use tags
 * Copyright (C) 2020-2023 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop;

use Closure;
use Kygekraqmak\KygekTagsShop\event\TagBuyEvent;
use Kygekraqmak\KygekTagsShop\event\TagSellEvent;
use NhanAZ\EcoAPI\EconomyProvider;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use function array_keys;
use function array_values;
use function explode;
use function is_array;
use function is_string;
use function preg_match;
use function str_replace;
use function str_starts_with;
use function strtolower;

/**
 * KygekTagsShop API class
 *
 * @package Kygekraqmak\KygekTagsShop
 */
class TagsActions {
	public const API_VERSION = 1.2;

	/** @var TagsShop */
	private $plugin;

	/** @var array<string, mixed> */
	private array $config;
	/** @var DataConnector */
	private $data;

	/** @var bool */
	private $economyEnabled;

	private ?EconomyProvider $economyProvider;

	/** @var bool */
	private $purePermsEnabled;

	/** @var array<string, true> */
	private array $pendingTransactions = [];

	/** @param array<string, mixed> $config */
	public function __construct(
		TagsShop $plugin,
		array $config,
		DataConnector $data,
		bool $economyEnabled,
		bool $purePermsEnabled,
		?EconomyProvider $economyProvider = null,
	) {
		$this->plugin = $plugin;
		$this->config = $config;
		$this->data = $data;
		$this->economyEnabled = $economyEnabled;
		$this->economyProvider = $economyProvider;
		$this->purePermsEnabled = $purePermsEnabled;
	}

	private function getEconomyProvider() : EconomyProvider {
		if ($this->economyProvider === null) {
			throw new \LogicException("The economy provider is not available");
		}
		return $this->economyProvider;
	}

	/**
	 * Get tags in config file
	 *
	 * Returns an multidimensional associative array (ID => [tag => [price, [permissions]]) or null if there are no tags
	 * ID always starts from 0 and is ordered as that of in config file
	 */
	/** @return array<int, array<string, array{0: string, 1: string}>>|null */
	public function getAllTags() : ?array {
		$alltags = [];
		$rawTags = $this->config["tags"] ?? null;
		if (!is_array($rawTags) || $rawTags === []) {
			return null;
		}

		foreach ($rawTags as $rawTag) {
			if (!is_string($rawTag)) {
				continue;
			}
			$parts = explode(":", $rawTag, 3);
			if (count($parts) !== 3) {
				continue;
			}
			$alltags[][str_replace("&", "§", $parts[0] . "&r")] = [
				$parts[1],
				$parts[2],
			];
		}

		return $alltags;
	}

	/**
	 * Get price of a tag
	 *
	 * Returns null if:
	 * - SimpleEconomy plugin is not installed or enabled, and/or
	 * - tag ID doesn't exists
	 */
	public function getTagPrice(int $tagid) : ?int {
		$tag = $this->getTagData($tagid);
		if (!$this->economyEnabled || $tag === null) {
			return null;
		}
		return (int) $tag[0];
	}

	/**
	 * Get permissions of a tag
	 *
	 * Returns null if:
	 * - PurePerms plugin is not installed or enabled, and/or
	 * - tag ID doesn't exists
	 */
	/** @return list<string>|null */
	public function getTagPermissions(int $tagid) : ?array {
		$tag = $this->getTagData($tagid);
		if (!$this->purePermsEnabled || $tag === null) {
			return null;
		}

		$permissions = $tag[1];
		$permissions = str_replace(
			["[", "]", ",", "'", "\""],
			"",
			$permissions,
		);
		$permissions = explode(" ", $permissions);
		return (array) $permissions;
	}

	/**
	 * Get tag display
	 *
	 * Returns null if tag ID doesn't exists
	 */
	public function getTagName(int $tagid) : ?string {
		$tags = $this->getAllTags();
		if ($tags === null || !isset($tags[$tagid])) {
			return null;
		}
		$name = array_key_first($tags[$tagid]);
		return is_string($name) ? $name : null;
	}

	/**
	 * Checks if tag exists in config
	 */
	public function tagExists(int $tagid) : bool {
		return isset($this->getAllTags()[$tagid]);
	}

	/**
	 * Gets player's tag ID from database
	 *
	 * Returns null if player doesn't have tag
	 */
	public function getPlayerTag(Player $player, Closure $callback) : void {
		$this->getData($player, $callback);
	}

	public function unSetUperm(Player $player, int $tagid) : void {
		$permissions = $this->getTagPermissions($tagid);
		if ($permissions === null) {
			return;
		}
		foreach ($permissions as $permission) {
			TagsShop::getInstance()
				->getPurePerms()
				->getUserDataMgr()
				->unsetPermission($player, $permission);
		}
	}

	/**
	 * Removes tag from player
	 *
	 * Sends a warning message if player doesn't have a tag
	 */
	public function unsetPlayerTag(Player $player) : void {
		$transactionKey = strtolower($player->getName());
		if (isset($this->pendingTransactions[$transactionKey])) {
			return;
		}
		$this->getPlayerTag($player, function (int $tagid) use (
			$player,
			$transactionKey,
		) : void {
			if ($tagid == -1) {
				$player->sendMessage(
					$this->plugin->messages["kygektagsshop.warning.playerhasnotag"],
				);
				return;
			}
			if ($this->economyEnabled) {
				$tagprice = $this->getTagPrice($tagid);
				if ($tagprice === null) {
					return;
				}
				$this->pendingTransactions[$transactionKey] = true;
				$this->getEconomyProvider()->addMoney(
					$player,
					$tagprice,
					function (bool $updated) use ($player, $tagid, $tagprice, $transactionKey) : void {
						if (!$updated) {
							unset($this->pendingTransactions[$transactionKey]);
							$player->sendMessage(
								$this->plugin->messages["kygektagsshop.warning.transactionfailed"] ??
									"The transaction failed; your tag was not changed.",
							);
							return;
						}

						$this->removeData($player, function () use ($player, $tagid, $tagprice, $transactionKey) : void {
							unset($this->pendingTransactions[$transactionKey]);
							(new TagSellEvent($player, $tagid))->call();
							// TODO: Set player display name to original display name after the database schema supports it.
							$player->setDisplayName($player->getName());
							if ($this->purePermsEnabled) {
								$this->unSetUperm($player, $tagid);
							}
							$player->sendMessage(
								str_replace(
									"{price}",
									"$" . $tagprice,
									$this->plugin->messages["kygektagsshop.info.economyselltagsuccess"],
								),
							);
						}, function () use ($player, $tagprice, $transactionKey) : void {
							$this->getEconomyProvider()->takeMoney(
								$player,
								$tagprice,
								function (bool $refunded) use ($player, $transactionKey) : void {
									unset($this->pendingTransactions[$transactionKey]);
									if (!$refunded) {
										$this->plugin->getLogger()->warning("KygekTagsShop could not reverse a failed tag sale.");
									}
									$player->sendMessage(
										$this->plugin->messages["kygektagsshop.warning.transactionfailed"] ??
											"The transaction failed; your tag was not changed.",
									);
								},
							);
						});
					},
				);
				return;
			}

			(new TagSellEvent($player, $tagid))->call();
			$this->removeData($player);
			$player->setDisplayName($player->getName());
			$player->sendMessage(
				$this->plugin->messages["kygektagsshop.info.freeselltagsuccess"],
			);
		});
	}

	public function setUperm(Player $player, int $tagid) : void {
		$permissions = $this->getTagPermissions($tagid);
		if ($permissions === null) {
			return;
		}
		foreach ($permissions as $permission) {
			if ($permission == "") {
				break;
			}
			TagsShop::getInstance()
				->getPurePerms()
				->getUserDataMgr()
				->setPermission($player, $permission);
		}
	}

	/**
	 * Sets a tag to player
	 *
	 * Sends a warning message if player have a tag or player doesn't have enough money
	 */
	public function setPlayerTag(Player $player, int $tagid) : void {
		$transactionKey = strtolower($player->getName());
		if (isset($this->pendingTransactions[$transactionKey])) {
			return;
		}
		$this->getPlayerTag($player, function (?int $currentid) use (
			$player,
			$tagid,
			$transactionKey,
		) : void {
			if ($currentid == $tagid) {
				$player->sendMessage(
					$this->plugin->messages["kygektagsshop.warning.playerhastag"],
				);
				return;
			}
			if ($this->economyEnabled) {
				$this->pendingTransactions[$transactionKey] = true;
				$this->getEconomyProvider()->getMoney(
					$player,
					function (float|int $balance) use (
						$tagid,
						$player,
						$transactionKey,
					) : void {
						$tagprice = $this->getTagPrice($tagid);
						$balance = (int) floor($balance);
						if ($tagprice === null) {
							unset($this->pendingTransactions[$transactionKey]);
							return;
						}
						$money = "$" . ($tagprice - $balance);

						if ($balance < $tagprice) {
							$player->sendMessage(
								str_replace(
									"{price}",
									$money,
									$this->plugin->messages["kygektagsshop.warning.notenoughmoney"],
								),
							);
							unset($this->pendingTransactions[$transactionKey]);
							return;
						}

						$this->getEconomyProvider()->takeMoney(
							$player,
							$tagprice,
							function (bool $updated) use ($player, $tagid, $tagprice, $transactionKey) : void {
								if (!$updated) {
									unset($this->pendingTransactions[$transactionKey]);
									$player->sendMessage(
										$this->plugin->messages["kygektagsshop.warning.transactionfailed"] ??
											"The transaction failed; your tag was not changed.",
									);
									return;
								}

								$this->setData($player, $tagid, function () use ($player, $tagid, $tagprice, $transactionKey) : void {
									unset($this->pendingTransactions[$transactionKey]);
									(new TagBuyEvent($player, $tagid))->call();
									// TODO: Store original player display name after the database schema supports it.
									$displayName = $player->getDisplayName();
									$tag = $this->getTagName($tagid) ?? "Unknown tag";
									$player->setDisplayName(
										str_replace(
											["{displayname}", "{tag}"],
											[$displayName, $tag],
											$this->getDisplayNameFormat(),
										),
									);
									if ($this->purePermsEnabled) {
										$this->setUperm($player, $tagid);
									}
									$player->sendMessage(
										str_replace(
											"{price}",
											"$" . $tagprice,
											$this->plugin->messages["kygektagsshop.info.economybuytagsuccess"],
										),
									);
								}, function () use ($player, $tagprice, $transactionKey) : void {
									$this->getEconomyProvider()->addMoney(
										$player,
										$tagprice,
										function (bool $refunded) use ($player, $transactionKey) : void {
											unset($this->pendingTransactions[$transactionKey]);
											if (!$refunded) {
												$this->plugin->getLogger()->warning("KygekTagsShop could not refund a failed tag purchase.");
											}
											$player->sendMessage(
												$this->plugin->messages["kygektagsshop.warning.transactionfailed"] ??
													"The transaction failed; your tag was not changed.",
											);
										},
									);
								});
							},
						);
					},
				);
				return;
			}

			(new TagBuyEvent($player, $tagid))->call();
			$this->setData($player, $tagid);
			$player->setDisplayName(
				$player->getName() . " " . $this->getTagName($tagid),
			);
			$player->sendMessage(
				$this->plugin->messages["kygektagsshop.info.freebuytagsuccess"],
			);
		});
	}

	/**
	 * Gets the display name format from the KygekTagsShop configuration file
	 */
	public function getDisplayNameFormat() : string {
		$value = $this->config["display-name-format"] ?? null;
		return is_string($value) && $value !== "" ? $value : "{displayname} {tag}";
	}

	/**
	 * Gets the tag ID of a player from KygekTagsShop database
	 */
	private function getData(Player $player, Closure $callback) : void {
		$this->data->executeSelect(
			"kygektagsshop.get",
			[
				"player" => strtolower($player->getName()),
			],
			function (array $data) use ($callback) {
				if (empty($data)) {
					$callback(-1);
				} else {
					$row = $data[0] ?? null;
					$tagid = is_array($row) ? ($row["tagid"] ?? null) : null;
					$callback(is_numeric($tagid) ? (int) $tagid : -1);
				}
			},
		);
	}

	/**
	 * Sets tag ID to a player inside KygekTagsShop database
	 */
	private function setData(Player $player, int $tagid, ?Closure $onSuccess = null, ?Closure $onError = null) : void {
		$success = static function () use ($onSuccess) : void {
			$onSuccess?->__invoke();
		};
		$error = static function () use ($onError) : void {
			$onError?->__invoke();
		};
		$this->data->executeSelect(
			"kygektagsshop.get",
			[
				"player" => strtolower($player->getName()),
			],
			function (array $data) use ($player, $tagid, $success, $error) {
				if (empty($data)) {
					$this->data->executeInsert("kygektagsshop.insert", [
						"player" => strtolower($player->getName()),
						"tagid" => $tagid,
					], $success, $error);
				} else {
					$this->data->executeChange("kygektagsshop.update", [
						"player" => strtolower($player->getName()),
						"tagid" => $tagid,
					], $success, $error);
				}
			},
			$error,
		);
	}

	/**
	 * Removes player tag ID from KygekTagsShop database
	 */
	private function removeData(Player $player, ?Closure $onSuccess = null, ?Closure $onError = null) : void {
		$this->data->executeChange("kygektagsshop.remove", [
			"player" => strtolower($player->getName()),
		], $onSuccess, $onError);
	}

	/**
	 * Gets all KygekTagsShop database contents
	 */
	public function getAllData(Closure $callback) : void {
		$this->data->executeSelect("kygektagsshop.getall", [], function (
			array $data,
		) use ($callback) {
			if (empty($data)) {
				$callback($data);
			} else {
				$callback($data[0]);
			}
		});
	}

	/**
	 * Gets the KygekTagsShop database location
	 */
	public function getDataLocation() : string {
		$database = $this->config["database"] ?? null;
		if (!is_array($database) || ($database["type"] ?? null) !== "sqlite") {
			return "";
		}

		$sqlite = $database["sqlite"] ?? null;
		$file = is_array($sqlite) ? ($sqlite["file"] ?? null) : null;
		if (!is_string($file) || $file === "" || $file === ":memory:") {
			return "";
		}

		if (str_starts_with($file, "/") || str_starts_with($file, "\\") || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1) {
			return $file;
		}

		return $this->plugin->getDataFolder() . $file;
	}

	/** @return array{0: string, 1: string}|null */
	private function getTagData(int $tagid) : ?array {
		$tags = $this->getAllTags();
		if ($tags === null || !isset($tags[$tagid])) {
			return null;
		}
		$tag = array_values($tags[$tagid])[0] ?? null;
		return is_array($tag) ? $tag : null;
	}
}
