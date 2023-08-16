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

use Kygekraqmak\KygekTagsShop\form\MenuForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;

class Commands extends Command implements PluginOwned {
	/** @var TagsShop */
	private $main;

	public function __construct(TagsShop $main, string $desc, array $aliases) {
		$this->main = $main;
		$desc = empty($desc) ? "Buy and sell your tags using money" : $desc;

		parent::__construct("tagsshop");
		$this->setPermission("kygektagsshop.tags");
		$this->setAliases($aliases);
		$this->setUsage("/tagsshop");
		$this->setDescription($desc);
	}

	public function execute(
		CommandSender $sender,
		string $commandLabel,
		array $args,
	) : bool {
		if (!$sender instanceof Player) {
			$sender->sendMessage(
				$this->getOwningPlugin()->messages["kygektagsshop.warning.notplayer"],
			);
			return true;
		}

		if (!$sender->hasPermission("kygektagsshop.tags")) {
			$sender->sendMessage(
				$this->getOwningPlugin()->messages["kygektagsshop.warning.nopermission"],
			);
			return true;
		}

		if (!$this->getOwningPlugin()->fileExists()) {
			$sender->sendMessage(
				$this->getOwningPlugin()->messages["kygektagsshop.warning.filemissing"],
			);
			return true;
		}

		MenuForm::menuForm($sender);
		return true;
	}

	public function getOwningPlugin() : TagsShop {
		return $this->main;
	}
}