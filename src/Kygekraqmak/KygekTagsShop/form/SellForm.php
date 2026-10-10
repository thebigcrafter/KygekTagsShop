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

namespace Kygekraqmak\KygekTagsShop\form;

use Kygekraqmak\KygekTagsShop\utils\Replace;
use pocketmine\player\Player;
use Kygekraqmak\KygekTagsShop\libs\_c20891dfa0c1366c\Vecnavium\FormsUI\SimpleForm;

class SellForm extends MenuForm {
	public static function sellTagForm(Player $player, int $tagid) : void {
		$form = new SimpleForm(function (Player $player, $data = null) {
			if ($data === null) {
				if (parent::configBool("return-when-closed")) {
					parent::menuForm($player);
				}
				return true;
			}
			switch ($data) {
				case 0:
					parent::getMain()
						->getAPI()
						->unsetPlayerTag($player);
					break;
				case 1:
					parent::menuForm($player);
					break;
			}
		});

		$form->setTitle(
			Replace::replaceGeneric(
				$player,
				parent::configString("sell-title"),
			),
		);
		$form->setContent(
			Replace::replaceTag(
				$player,
				$tagid,
				parent::configString("sell-content"),
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				parent::configString("sell-agree-button"),
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				parent::configString("sell-disagree-button"),
			),
		);
		$player->sendForm($form);
	}

	public static function noTagForm(Player $player) : void {
		$form = new SimpleForm(function (Player $player, $data = null) {
			if ($data === null) {
				if (parent::configBool("return-when-closed")) {
					parent::menuForm($player);
				}
				return true;
			}
			if ($data === 0) {
				parent::menuForm($player);
			}
		});
		$form->setTitle(
			Replace::replaceGeneric(
				$player,
				parent::configString("no-tag-title"),
			),
		);
		$form->setContent(
			Replace::replaceGeneric(
				$player,
				parent::configString("no-tag-content"),
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				parent::configString("no-tag-button"),
			),
		);
		$player->sendForm($form);
	}
}