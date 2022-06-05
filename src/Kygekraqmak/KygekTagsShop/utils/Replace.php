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
 * Copyright (C) 2020-2022 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\utils;

use pocketmine\player\Player;
use Kygekraqmak\KygekTagsShop\TagsShop;

class Replace {

    public static function replaceGeneric(Player $player, string $text) : string {
        $replace = [
            "{player}" => $player->getName(),
            "&" => "§"
        ];

        return strtr($text, $replace);
    }

    public static function replaceTag(Player $player, int $tagid, string $text) : string {
        $tagsshop = TagsShop::getInstance();
        $api = TagsShop::getAPI();
        $currency = $tagsshop->economyEnabled ? "$" : "";
        $price = ($tagsshop->economyEnabled and $api->tagExists($tagid)) ? $currency . $api->getTagPrice($tagid) : "free";
        $name = $api->getTagName($tagid) ?? "Unknown tag";

        $replace = [
            "{player}" => $player->getName(),
            "&" => "§",
            "{tagprice}" => $price,
            "{tagname}" => $name
        ];

        return strtr($text, $replace);
    }
}
