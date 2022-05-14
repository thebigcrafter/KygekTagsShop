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
 * Copyright (C) 2020-2021 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop;

use Kygekraqmak\KygekTagsShop\event\TagBuyEvent;
use Kygekraqmak\KygekTagsShop\event\TagSellEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;
use pocketmine\utils\Config;

/**
 * KygekTagsShop API class
 *
 * @package Kygekraqmak\KygekTagsShop
 */
class TagsActions {

    public const API_VERSION = 1.2;

    /** @var TagsShop */
    private $plugin;

    /** @var array */
    private $config;
    /** @var Config */
    private $data;

    /** @var bool */
    private $economyEnabled;
    /** @var EconomyAPI|null */
    private $economyAPI;

    public function __construct(TagsShop $plugin, array $config, Config $data, bool $economyEnabled, ?EconomyAPI $economyAPI) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->data = $data;
        $this->economyEnabled = $economyEnabled;
        $this->economyAPI = $economyAPI;
    }


    /**
     * Get tags in config file
     *
     * Returns an multidimensional associative array (ID => [tag => price]) or null if there are no tags
     * ID always starts from 0 and is ordered as that of in config file
     *
     * @return null|array
     */
    public function getAllTags() : ?array {
        $alltags = [];
        if (empty($this->config["tags"])) return null;

        foreach ($this->config["tags"] as $tag) {
            $tag = explode(":", $tag);
            $alltags[][str_replace("&", "§", $tag[0] . "&r")] = $tag[1];
        }

        return $alltags;
    }


    /**
     * Get price of a tag
     *
     * Returns null if:
     * - EconomyAPI plugin is not installed or enabled, and/or
     * - tag ID doesn't exists
     *
     * @param int $tagid
     * @return null|int
     */
    public function getTagPrice(int $tagid) : ?int {
        if (!$this->economyEnabled or !$this->tagExists($tagid)) return null;

        return (int) array_values($this->getAllTags()[$tagid])[0];
    }


    /**
     * Get tag display
     *
     * Returns null if tag ID doesn't exists
     *
     * @param int $tagid
     * @return null|string
     */
    public function getTagName(int $tagid) : ?string {
        if (!$this->tagExists($tagid)) return null;

        return array_keys($this->getAllTags()[$tagid])[0];
    }


    /**
     * Checks if tag exists in config
     *
     * @param int $tagid
     * @return bool
     */
    public function tagExists(int $tagid) : bool {
        return isset($this->getAllTags()[$tagid]);
    }


    /**
     * Checks if player has tag
     *
     * @param Player $player
     * @return bool
     */
    public function playerHasTag(Player $player) : bool {
        return isset($this->getAllData()[$player->getLowercaseName()]);
    }


    /**
     * Gets player's tag ID from database
     *
     * Returns null if player doesn't have tag
     *
     * @param Player $player
     * @return null|int
     */
    public function getPlayerTag(Player $player) : ?int {
        if (!$this->playerHasTag($player)) return null;

        return $this->getData($player);
    }


    /**
     * Removes tag from player
     *
     * Sends a warning message if player doesn't have a tag
     *
     * @param Player $player
     */
    public function unsetPlayerTag(Player $player) {
        if (!$this->playerHasTag($player)) {
            $player->sendMessage($this->plugin->messages["kygektagsshop.warning.playerhasnotag"]);
            return;
        }

        $tagid = $this->getPlayerTag($player);

        if ($this->economyEnabled) {
            $tagprice = $this->getTagPrice($this->getPlayerTag($player));
            $currency = $this->economyAPI->getMonetaryUnit();
            (new TagSellEvent($player, $tagid))->call();
            $this->economyAPI->addMoney($player, $tagprice);
            $this->removeData($player);
            // TODO: Set player display name to original display name after new database has been implemented
            $player->setDisplayName($player->getName());
            $player->sendMessage(str_replace("{price}", $currency . $tagprice, $this->plugin->messages["kygektagsshop.info.economyselltagsuccess"]));
            return;
        }

        (new TagSellEvent($player, $tagid))->call();
        $this->removeData($player);
        $player->setDisplayName($player->getName());
        $player->sendMessage($this->plugin->messages["kygektagsshop.info.freeselltagsuccess"]);
    }


    /**
     * Sets a tag to player
     *
     * Sends a warning message if player have a tag or player doesn't have enough money
     *
     * @param Player $player
     * @param int $tagid
     */
    public function setPlayerTag(Player $player, int $tagid) {

        if ($this->playerHasTag($player)) {
            $player->sendMessage($this->plugin->messages["kygektagsshop.warning.playerhastag"]);
            return;
        }

        if ($this->economyEnabled) {
            $playermoney = $this->economyAPI->myMoney($player);
            $tagprice = $this->getTagPrice($tagid);
            $currency = $this->economyAPI->getMonetaryUnit();
            $money = $currency . ($tagprice - $playermoney);

            if ($playermoney < $tagprice) {
                $player->sendMessage(str_replace("{price}", $money, $this->plugin->messages["kygektagsshop.warning.notenoughmoney"]));
                return;
            }

            (new TagBuyEvent($player, $tagid))->call();
            $this->setData($player, $tagid);
            $this->economyAPI->reduceMoney($player, $tagprice);
            // TODO: Store original player display name in database after new database has been implemented (See line #178 for purpose)
            $displayName = $player->getDisplayName();
            $tag = $this->getTagName($tagid);
            $player->setDisplayName(str_replace(["{displayname}", "{tag}"], [$displayName, $tag], $this->getDisplayNameFormat()));
            $player->sendMessage(str_replace("{price}", $currency . $tagprice, $this->plugin->messages["kygektagsshop.info.economybuytagsuccess"]));
            return;
        }

        (new TagBuyEvent($player, $tagid))->call();
        $this->setData($player, $tagid);
        $player->setDisplayName($player->getName() . " " . $this->getTagName($tagid));
        $player->sendMessage($this->plugin->messages["kygektagsshop.info.freebuytagsuccess"]);
    }


    /**
     * Gets the display name format from the KygekTagsShop configuration file
     *
     * @return string
     */
    public function getDisplayNameFormat() : string {
        return ($this->config["display-name-format"] ?? "{displayname} {tag}") ?: "{displayname} {tag}";
    }


    /**
     * Gets the tag ID of a player from KygekTagsShop database
     *
     * @param Player $player
     * @return int
     */
    private function getData(Player $player) : int {
        return $this->data->get($player->getLowercaseName());
    }


    /**
     * Sets tag ID to a player inside KygekTagsShop database
     *
     * @param Player $player
     * @param int $tagid
     */
    private function setData(Player $player, int $tagid) {
        $this->data->set($player->getLowercaseName(), $tagid);
        $this->saveData();
        $this->reloadData();
    }


    /**
     * Removes player tag ID from KygekTagsShop database
     *
     * @param Player $player
     */
    private function removeData(Player $player) {
        $this->data->remove($player->getLowercaseName());
        $this->saveData();
        $this->reloadData();
    }


    /**
     * Gets all KygekTagsShop database contents
     *
     * @param bool $keys
     * @return array
     */
    public function getAllData(bool $keys = false) : array {
        return $this->data->getAll($keys);
    }


    /**
     * Reloads the KygekTagsShop database
     */
    public function reloadData() {
        $this->data->reload();
    }


    /**
     * Saves the KygekTagsShop database
     */
    public function saveData() {
        $this->data->save();
    }


    /**
     * Gets the KygekTagsShop database location
     *
     * @return string
     */
    public function getDataLocation() : string {
        return $this->plugin->getDataFolder() . "data.json";
    }

}
