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

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

use Kygekraqmak\KygekTagsShop\form\MenuForm;

class Commands extends PluginCommand {

    /** @var TagsShop */
    private $plugin;

    public function __construct(TagsShop $plugin, string $desc, array $aliases) {
        $this->plugin = $plugin;
        $desc = ($desc == null) ? "Buy and sell your tags using money" : $desc;

        parent::__construct("tagsshop", $plugin);
        $this->setPermission("kygektagsshop.tags");
        $this->setAliases($aliases);
        $this->setUsage("/tagsshop");
        $this->setDescription($desc);
    }

    public function getPlugin() : TagsShop {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->warning["kygektagsshop.warning.notplayer"]);
            return true;
        }

        if (!$sender->hasPermission("kygektagsshop.tags")) {
            $sender->sendMessage($this->getPlugin()->warning["kygektagsshop.warning.nopermission"]);
            return true;
        }

        if (!$this->getPlugin()->fileExists()) {
            $sender->sendMessage($this->getPlugin()->warning["kygektagsshop.warning.filemissing"]);
            return true;
        }

        MenuForm::menuForm($sender);
        return true;
    }

}
