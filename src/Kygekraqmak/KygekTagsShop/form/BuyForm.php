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

use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\CustomForm;
use Kygekraqmak\KygekTagsShop\utils\Replace;
use pocketmine\Player;

class BuyForm extends MenuForm {

    public static function tagsListForm(Player $player) {
        $tagdisplay = [];
        foreach (parent::getMain()->getAPI()->getAllTags() as $tags) {
            $tagdisplay[] = array_keys($tags)[0];
        }

        $form = new CustomForm(function (Player $player, int $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-to-main"]) parent::menuForm($player);
                return true;
            }
            // TODO: Pass the selected tag to comfirm buy form
        });

        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["buy-title"]));
        $form->addLabel(Replace::replaceGeneric($player, parent::getMain()->config["buy-content"]));
        $form->addDropdown(Replace::replaceGeneric($player, parent::getMain()->config["buy-dropdown"]), $tagdisplay);
        $player->sendForm($form);
    }

    public static function confimBuyForm(Player $player) {
        $form = new ModalForm(function (Player $player, bool $data = null) {
            if ($data === null) {
                if (parent::getMain()->config["return-to-main"]) self::tagsListForm($player);
                return true;
            }
            switch ($data) {
                case true:
                    // TODO: Set tag to player
                    break;
                case false:
                    self::tagsListForm($player);
                    break;
            }
        });

        $form->setTitle(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-title"]));
        $form->setContent(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-content"]));
        $form->setButton1(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-agree-button"]));
        $form->setButton2(Replace::replaceGeneric($player, parent::getMain()->config["buy-confirm-disagree-button"]));
        $player->sendForm($form);
    }

}
