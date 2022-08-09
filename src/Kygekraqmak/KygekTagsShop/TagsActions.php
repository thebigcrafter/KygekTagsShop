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
 * Copyright (C) 2020-2022 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use Kygekraqmak\KygekTagsShop\event\TagBuyEvent;
use Kygekraqmak\KygekTagsShop\event\TagSellEvent;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;

/**
 * KygekTagsShop API class
 *
 * @package Kygekraqmak\KygekTagsShop
 */
class TagsActions
{
	public const API_VERSION = 1.2;

	/** @var TagsShop */
	private $plugin;

	/** @var array */
	private $config;
	/** @var DataConnector */
	private $data;

	/** @var bool */
	private $economyEnabled;

	/** @var bool */
	private $purePermsEnabled;

	public function __construct(
		TagsShop $plugin,
		array $config,
		DataConnector $data,
		bool $economyEnabled,
		bool $purePermsEnabled,
	) {
		$this->plugin = $plugin;
		$this->config = $config;
		$this->data = $data;
		$this->economyEnabled = $economyEnabled;
		$this->purePermsEnabled = $purePermsEnabled;
	}

	/**
	 * Get tags in config file
	 *
	 * Returns an multidimensional associative array (ID => [tag => [price, [permissions]]) or null if there are no tags
	 * ID always starts from 0 and is ordered as that of in config file
	 */
	public function getAllTags(): ?array
	{
		$alltags = [];
		if (empty($this->config["tags"])) {
			return null;
		}

		foreach ($this->config["tags"] as $tag) {
			$tag = explode(":", $tag);
			$alltags[][str_replace("&", "§", $tag[0] . "&r")] = [
				$tag[1],
				$tag[2],
			];
		}

		return $alltags;
	}

	/**
	 * Get price of a tag
	 *
	 * Returns null if:
	 * - EconomyAPI plugin is not installed or enabled, and/or
	 * - tag ID doesn't exists
	 */
	public function getTagPrice(int $tagid): ?int
	{
		if (!$this->economyEnabled or !$this->tagExists($tagid)) {
			return null;
		}
		return (int) array_values($this->getAllTags()[$tagid])[0][0];
	}

	/**
	 * Get permissions of a tag
	 *
	 * Returns null if:
	 * - PurePerms plugin is not installed or enabled, and/or
	 * - tag ID doesn't exists
	 */
	public function getTagPermissions(int $tagid): ?array
	{
		if (!$this->purePermsEnabled or !$this->tagExists($tagid)) {
			return null;
		}

		$permissions = array_values($this->getAllTags()[$tagid])[0][1];
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
	public function getTagName(int $tagid): ?string
	{
		if (!$this->tagExists($tagid)) {
			return null;
		}

		return array_keys($this->getAllTags()[$tagid])[0];
	}

	/**
	 * Checks if tag exists in config
	 */
	public function tagExists(int $tagid): bool
	{
		return isset($this->getAllTags()[$tagid]);
	}

	/**
	 * Gets player's tag ID from database
	 *
	 * Returns null if player doesn't have tag
	 */
	public function getPlayerTag(Player $player, Closure $callback)
	{
		$this->getData($player, $callback);
	}

	public function unSetUperm(Player $player, int $tagid)
	{
		$permissions = $this->getTagPermissions($tagid);
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
	public function unsetPlayerTag(Player $player)
	{
		$this->getPlayerTag($player, function (?int $tagid) use (
			$player,
		): void {
			if ($tagid == -1) {
				$player->sendMessage(
					$this->plugin->messages[
						"kygektagsshop.warning.playerhasnotag"
					],
				);
				return;
			}
			if ($this->economyEnabled) {
				$tagprice = $this->getTagPrice($tagid);
				(new TagSellEvent($player, $tagid))->call();
				BedrockEconomyAPI::getInstance()->addToPlayerBalance(
					$player->getName(),
					$tagprice,
				);
				$this->removeData($player);
				// TODO: Set player display name to original display name after new database has been implemented
				$player->setDisplayName($player->getName());
				if ($this->purePermsEnabled) {
					$this->unSetUperm($player, $tagid);
				}
				$player->sendMessage(
					str_replace(
						"{price}",
						"$" . $tagprice,
						$this->plugin->messages[
							"kygektagsshop.info.economyselltagsuccess"
						],
					),
				);
				return;
			}

			(new TagSellEvent($player, $tagid))->call();
			$this->removeData($player);
			$player->setDisplayName($player->getName());
			$player->sendMessage(
				$this->plugin->messages[
					"kygektagsshop.info.freeselltagsuccess"
				],
			);
		});
	}

	public function setUperm(Player $player, int $tagid)
	{
		$permissions = $this->getTagPermissions($tagid);
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
	public function setPlayerTag(Player $player, int $tagid)
	{
		$this->getPlayerTag($player, function (?int $currentid) use (
			$player,
			$tagid,
		): void {
			if ($currentid == $tagid) {
				$player->sendMessage(
					$this->plugin->messages[
						"kygektagsshop.warning.playerhastag"
					],
				);
				return;
			}
			if ($this->economyEnabled) {
				BedrockEconomyAPI::getInstance()->getPlayerBalance(
					$player->getName(),
					ClosureContext::create(function (?int $balance) use (
						$tagid,
						$player,
					): void {
						$tagprice = $this->getTagPrice($tagid);
						$money = "$" . ($tagprice - $balance);

						if ($balance < $tagprice) {
							$player->sendMessage(
								str_replace(
									"{price}",
									$money,
									$this->plugin->messages[
										"kygektagsshop.warning.notenoughmoney"
									],
								),
							);
							return;
						}

						(new TagBuyEvent($player, $tagid))->call();
						$this->setData($player, $tagid);
						BedrockEconomyAPI::getInstance()->subtractFromPlayerBalance(
							$player->getName(),
							$tagprice,
						);
						// TODO: Store original player display name in database after new database has been implemented (See line #178 for purpose)
						$displayName = $player->getDisplayName();
						$tag = $this->getTagName($tagid);
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
								$this->plugin->messages[
									"kygektagsshop.info.economybuytagsuccess"
								],
							),
						);
					}),
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
	public function getDisplayNameFormat(): string
	{
		return $this->config["display-name-format"] ?? "{displayname} {tag}" ?:
			"{displayname} {tag}";
	}

	/**
	 * Gets the tag ID of a player from KygekTagsShop database
	 */
	private function getData(Player $player, Closure $callback)
	{
		$this->data->executeSelect(
			"kygektagsshop.get",
			[
				"player" => strtolower($player->getName()),
			],
			function (array $data) use ($callback) {
				if (empty($data)) {
					$callback(-1);
				} else {
					$callback(isset($data[0]) ? $data[0]["tagid"] : -1);
				}
			},
		);
	}

	/**
	 * Sets tag ID to a player inside KygekTagsShop database
	 */
	private function setData(Player $player, int $tagid)
	{
		$this->data->executeSelect(
			"kygektagsshop.get",
			[
				"player" => strtolower($player->getName()),
			],
			function (array $data) use ($player, $tagid) {
				if (empty($data)) {
					$this->data->executeInsert("kygektagsshop.insert", [
						"player" => strtolower($player->getName()),
						"tagid" => $tagid,
					]);
				} else {
					$this->data->executeChange("kygektagsshop.update", [
						"player" => strtolower($player->getName()),
						"tagid" => $tagid,
					]);
					$this->data->waitAll();
				}
			},
		);
	}

	/**
	 * Removes player tag ID from KygekTagsShop database
	 */
	private function removeData(Player $player)
	{
		$this->data->executeChange("kygektagsshop.remove", [
			"player" => strtolower($player->getName()),
		]);
	}

	/**
	 * Gets all KygekTagsShop database contents
	 */
	public function getAllData(Closure $callback)
	{
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
	public function getDataLocation(): string
	{
		return $this->plugin->getDataFolder() . "data.json";
	}
}
