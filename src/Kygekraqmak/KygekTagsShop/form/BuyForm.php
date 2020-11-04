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

namespace Kygekraqmak\KygekTagsShop\form;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use Kygekraqmak\KygekTagsShop\TagsShop;
use Kygekraqmak\KygekTagsShop\utils\Replace;
use pocketmine\Player;

class BuyForm extends MenuForm {

    public static function tagsListForm(Player $player) {
        $tagdisplay = [];
        foreach (TagsShop::getAPI()->getAllTags() as $tags) {
            $tagdisplay[] = array_keys($tags)[0];
        }

        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-when-closed"]) parent::menuForm($player);
                return true;
            }
            self::confirmBuyForm($player, $data[1]);
        });

        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["buy-title"]));
        $form->addLabel(Replace::replaceGeneric($player, parent::getMain()->config["buy-content"]));
        $form->addDropdown(Replace::replaceGeneric($player, parent::getMain()->config["buy-dropdown"]), $tagdisplay);
        $player->sendForm($form);
    }

    public static function tagExistsForm(Player $player) {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-when-closed"]) self::tagsListForm($player);
                return true;
            }
            if ($data === 0) parent::menuForm($player);
        });
        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["tag-exists-title"]));
        $form->setContent(Replace::replaceGeneric($player, parent::getMain()->config["tag-exists-content"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["tag-exists-button"]));
        $player->sendForm($form);
    }

    public static function confirmBuyForm(Player $player, int $tagid) {
        $form = new SimpleForm(function (Player $player, $data = null) use ($tagid) {
            if ($data === null) {
                if (parent::getMain()->config["return-when-closed"]) self::tagsListForm($player);
                return true;
            }
            switch ($data) {
                case 0:
                    TagsShop::getAPI()->setPlayerTag($player, $tagid);
                    break;
                case 1:
                    self::tagsListForm($player);
                    break;
            }
        });

        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-title"]));
        $form->setContent(Replace::replaceTag($player, $tagid, parent::getMain()->config["buy-confirm-content"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-agree-button"]));
        $form->addButton(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-disagree-button"]));
        $player->sendForm($form);
    }

}
