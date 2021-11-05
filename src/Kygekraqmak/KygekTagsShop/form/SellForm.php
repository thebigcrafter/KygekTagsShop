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

namespace Kygekraqmak\KygekTagsShop\form;

use jojoe77777\FormAPI\SimpleForm;
use Kygekraqmak\KygekTagsShop\utils\Replace;
use pocketmine\Player;

class SellForm extends MenuForm {

    public static function sellTagForm(Player $player, int $tagid) {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-when-closed"]) parent::menuForm($player);
                return true;
            }
            switch ($data) {
                case 0:
                    parent::getMain()->getAPI()->unsetPlayerTag($player);
                    break;
                case 1:
                    parent::menuForm($player);
                    break;
            }
        });

        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["sell-title"]));
        $form->setContent(Replace::replaceTag($player, $tagid, parent::getMain()->config["sell-content"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["sell-agree-button"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["sell-disagree-button"]));
        $player->sendForm($form);
    }

    public static function noTagForm(Player $player) {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-when-closed"]) parent::menuForm($player);
                return true;
            }
            if ($data === 0) parent::menuForm($player);
        });
        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["no-tag-title"]));
        $form->setContent(Replace::replaceGeneric($player, parent::getMain()->config["no-tag-content"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["no-tag-button"]));
        $player->sendForm($form);
    }

}
