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

use Kygekraqmak\KygekTagsShop\TagsShop;
use Kygekraqmak\KygekTagsShop\utils\Replace;
use pocketmine\player\Player;
use Kygekraqmak\KygekTagsShop\libs\_ff0c2de982ac1602\Vecnavium\FormsUI\SimpleForm;

class MenuForm {
	protected static function getMain() : TagsShop {
		return TagsShop::getInstance();
	}

	public static function menuForm(Player $player) {
		$form = new SimpleForm(function (Player $player, $data = null) {
			if ($data === null) {
				return true;
			}
			switch ($data) {
				case 0:
					TagsShop::getAPI()->getPlayerTag($player, function (
						?int $tagid,
					) use ($player) : void {
						if ($tagid !== -1) {
							BuyForm::tagExistsForm($player);
						} else {
							BuyForm::tagsListForm($player);
						}
					});
					break;
				case 1:
					TagsShop::getAPI()->getPlayerTag($player, function (
						?int $tagid,
					) use ($player) : void {
						if ($tagid === -1) {
							SellForm::noTagForm($player);
						} else {
							SellForm::sellTagForm($player, $tagid);
						}
					});
					break;
			}
		});

		$form->setTitle(
			Replace::replaceGeneric(
				$player,
				self::getMain()->config["main-title"],
			),
		);
		$form->setContent(
			Replace::replaceGeneric(
				$player,
				self::getMain()->config["main-content"],
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				self::getMain()->config["main-buy-button"],
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				self::getMain()->config["main-sell-button"],
			),
		);
		$form->addButton(
			Replace::replaceGeneric(
				$player,
				self::getMain()->config["main-exit-button"],
			),
		);
		$player->sendForm($form);
	}
}