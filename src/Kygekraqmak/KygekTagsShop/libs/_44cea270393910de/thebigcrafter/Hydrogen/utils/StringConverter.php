<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_44cea270393910de\thebigcrafter\Hydrogen\utils;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\StringToItemParser;
use Kygekraqmak\KygekTagsShop\libs\_44cea270393910de\thebigcrafter\Hydrogen\exceptions\HException;

class StringConverter {
	/**
	 * @throws HException
	 */
	public static function stringToItem(int $id, int $meta) : Item
	{
		$item = StringToItemParser::getInstance()->parse($id . ":" . $meta);

		if ($item === null) {
			throw new HException("Item not found!");
		}

		if(!$item instanceof Item) {
			throw new HException("Not an item!");
		}

		return $item;
	}

	/**
	 * @throws HException
	 */
	public static function stringToBlock(int $id, int $meta) : Block
	{
		$item = StringToItemParser::getInstance()->parse($id . ":" . $meta);

		if ($item === null) {
			throw new HException("Item not found!");
		}

		if(!$item instanceof ItemBlock) {
			throw new HException("Not an block!");
		}

		return $item->getBlock();
	}
}