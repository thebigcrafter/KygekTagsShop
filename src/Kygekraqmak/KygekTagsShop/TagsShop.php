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
 * A PocketMine-MP plugin to buy tags with money
 * Copyright (C) 2020 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

class TagsShop extends PluginBase implements Listener {

    private const ROOT = "kygektagsshop";
    private const PREFIX = TF::YELLOW . "[KygekTagsShop] " . TF::RESET;
    public const INFO = TF::GREEN;
    public const WARNING = TF::RED;

    /** @var array */
    public $config;
    /** @var Config */
    public $data;

    /** @var bool */
    public $economyEnabled = true;

    /** @var Plugin|null */
    public $economyAPI;

    /** @var TagsActions */
    private static $api;
    /** @var TagsShop */
    private static $instance = null;

    // TODO: Add languages support in future version(s)
    /** @var string[] */
    public $messages = [
        self::ROOT . ".warning.filemissing" => self::PREFIX . self::WARNING . "Config and/or data file cannot be found, please restart the server!",
        self::ROOT . ".warning.notplayer" => self::PREFIX . self::WARNING . "You can only execute this command in-game!",
        self::ROOT . ".warning.nopermission" => self::PREFIX . self::WARNING . "You do not have permission to use this command!",
        self::ROOT . ".warning.playerhastag" => self::PREFIX . self::WARNING . "You cannot buy tags because you have owned a tag!",
        self::ROOT . ".warning.playerhasnotag" => self::PREFIX . self::WARNING . "You cannot buy tags because you haven't owned a tag!",
        self::ROOT . ".warning.notenoughmoney" => self::PREFIX . self::WARNING . "You need {price} more to buy this tag!",
        self::ROOT . ".info.economybuytagsuccess" => self::PREFIX . self::INFO . "Successfully set your tag for {price}",
        self::ROOT . ".info.freebuytagsuccess" => self::PREFIX . self::INFO . "Successfully set your tag",
        self::ROOT . ".info.economyselltagsuccess" => self::PREFIX . self::INFO . "Successfully sold your tag for {price}",
        self::ROOT . ".info.freeselltagsuccess" => self::PREFIX . self::INFO . "Successfully sold your tag",
        self::ROOT . ".notice.outdatedconfig" => "Your configuration file is outdated, updating the config.yml...",
        self::ROOT . ".notice.oldconfiginfo" => "The old configuration file can be found at config_old.yml",
        self::ROOT . ".notice.noeconomyapi" => "EconomyAPI plugin is not installed or enabled, all tags will be free",
        self::ROOT . ".error.notags" => "Tags cannot be empty, disabling plugin..."
    ];

    /**
     * Returns an instance of KygekTagsShop API
     *
     * @return TagsActions
     */
    public static function getAPI() : TagsActions {
        return self::$api;
    }

    public static function getInstance() {
        return self::$instance;
    }

    public function onEnable() {
        self::$instance = $this;
        $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($economyapi === null) {
            $this->economyEnabled = false;
            $this->getLogger()->notice($this->messages["kygektagsshop.notice.noeconomyapi"]);
            $this->economyAPI = null;
        } else {
            $this->economyAPI = $economyapi;
        }

        $this->saveResource("config.yml");
        $this->config = $this->getConfig()->getAll();
        $this->checkConfig();
        $this->data = new Config($this->getDataFolder()."data.json", Config::JSON);

        if (empty($this->config["tags"])) {
            $this->getLogger()->error($this->messages["kygektagsshop.error.notags"]);
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $cmddesc = (empty($this->config)) ? "Buy tags here!" : $this->config["command-desc"];
        $cmdalias = $this->config["command-aliases"];
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("KygekTagsShop", new Commands($this, $cmddesc, $cmdalias));
        self::$api = new TagsActions();

        if ($this->getConfig()->get("check-updates", true)) {
            UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
        }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();

        if (!$this->fileExists()) {
            $player->sendMessage($this->messages["kygektagsshop.warning.filemissing"]);
            return;
        }

        if (self::getAPI()->playerHasTag($player)) {
            $tagid = self::getAPI()->getPlayerTag($player);
            if (self::getAPI()->tagExists($tagid)) {
                $player->setDisplayName($player->getName() . " " . $this->getAPI()->getTagName($tagid));
            }
        }
    }

    private function checkConfig() {
        if ($this->config["config-version"] !== "1.0") {
            $this->getLogger()->notice($this->messages["kygektagsshop.notice.outdatedconfig"]);
            $this->getLogger()->notice($this->messages["kygektagsshop.notice.oldconfiginfo"]);
            rename($this->getDataFolder()."config.yml", $this->getDataFolder()."config_old.yml");
            $this->saveResource("config.yml");
            $this->getConfig()->reload();
        }
    }

    public function fileExists() : bool {
        $config = $this->getDataFolder() . "config.yml";
        $data = self::getAPI()->getDataLocation();
        return file_exists($config) or file_exists($data);
    }

}
