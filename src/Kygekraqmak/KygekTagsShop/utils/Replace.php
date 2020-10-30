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

namespace Kygekraqmak\KygekTagsShop\utils;

use pocketmine\Player;
use Kygekraqmak\KygekTagsShop\TagsShop;

class Replace implements Instanceable {

    private static $instance;

    public function __construct() {
        self::$instance = $this;
    }

    public static function replaceGeneric(Player $player, string $text) : string {
        $replace = [
            "{player}" => $player->getName(),
            "&" => "§"
        ];

        return strtr($text, $replace);
    }

    public static function replaceTag(Player $player, int $tagid, string $text) : string {
        $tagsshop = TagsShop::getPlugin();
        $currency = $tagsshop->economyAPI->getMonetaryUnit();
        $price = $tagsshop->economyEnabled ? $currency . $tagsshop->getAPI()->getTagPrice($tagid) : "free";
        $name = $tagsshop->getAPI()->getTagName($tagid);

        $replace = [
            "{player}" => $player->getName(),
            "&" => "§",
            "{price}" => $price,
            "{tag}" => $name
        ];

        return strtr($text, $replace);
    }

    public static function getInstance() : self {
        return self::$instance;
    }

}
