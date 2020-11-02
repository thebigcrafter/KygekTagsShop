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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

class TagsShop extends PluginBase implements Listener {

    public const INFO = TF::GREEN;
    public const WARNING = TF::RED;

    /** @var array */
    public $config;
    /** @var Config */
    public $data;

    /** @var bool */
    public $economyEnabled = true;

    /** @var onebone\economyapi\EconomyAPI|null */
    public $economyAPI;

    /** @var TagsActions */
    private static $api;
    /** @var TagsShop */
    private static $plugin;

    // TODO: Add languages support in future version(s)
    /** @var string[] */
    public $messages = [
        "kygektagsshop.warning.filemissing" => self::WARNING . "Config and/or data file cannot be found, please restart the server!",
        "kygektagsshop.warning.notplayer" => self::WARNING . "You can only execute this command in-game!",
        "kygektagsshop.warning.nopermission" => self::WARNING . "You do not have permission to use this command!",
        "kygektagsshop.notice.outdatedconfig" => "Your configuration file is outdated, updating the config.yml...",
        "kygektagsshop.notice.oldconfiginfo" => "The old configuration file can be found at config_old.yml"
    ];

    /**
     * Returns an instance of KygekTagsShop API
     *
     * @return TagsActions
     */
    public static function getAPI() : TagsActions {
        return self::$api;
    }

    public static function getPlugin() : self {
        return self::$plugin;
    }

    public function onLoad() {
        self::$api = new TagsActions();
        self::$plugin = $this;
    }

    public function onEnable() {
        $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($economyapi === null xor !$economyapi->isEnabled()) {
            $this->economyEnabled = false;
            $this->getLogger()->notice("EconomyAPI plugin is not installed or enabled, all tags will be free");
            $this->economyAPI = null;
        } else {
            $this->economyAPI = EconomyAPI::getInstance();
        }

        $this->saveResource("config.yml");
        $this->checkConfig();
        $this->config = $this->getConfig()->getAll();
        $this->data = new Config($this->getDataFolder()."data.yml", Config::YAML);

        if (empty($this->config["tags"])) {
            $this->getLogger()->error("Tags cannot be empty, disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        $cmddesc = (empty($this->config)) ? "Buy tags here!" : $this->config["command-desc"];
        $cmdalias = $this->config["command-aliases"];
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("KygekTagsShop", new Commands($this, $cmddesc, $cmdalias));
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();

        if (!$this->fileExists()) {
            $player->sendMessage($this->messages["kygektagsshop.warning.filemissing"]);
            return;
        }

        if (self::getAPI()->playerHasTag($player)) {
            $tagid = $this->getAPI()->getPlayerTag($player);
            $player->setNameTag($player->getName() . $this->getAPI()->getTagName($tagid));
        }
    }

    private function checkConfig() {
        if ($this->config["config-version"] !== "1.0") {
            $this->getLogger()->notice($this->messages["kygektagsshop.notice.outdatedconfig"]);
            $this->getLogger()->notice($this->messages["kygektagsshop.notice.oldconfiginfo"]);
            rename($this->getDataFolder()."config.yml", $this->getDataFolder()."config_old.yml");
            $this->saveResource("config.yml");
        }
    }

    public function fileExists() : bool {
        $config = $this->getDataFolder() . "config.yml";
        $data = $this->getDataFolder() . "data.yml";
        return file_exists($config) or file_exists($data);
    }


}
