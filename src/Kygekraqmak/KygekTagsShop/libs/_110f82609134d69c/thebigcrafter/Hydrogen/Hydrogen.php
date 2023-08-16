<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_110f82609134d69c\thebigcrafter\Hydrogen;

use pocketmine\plugin\Plugin;
use pocketmine\Server;
use Kygekraqmak\KygekTagsShop\libs\_110f82609134d69c\thebigcrafter\Hydrogen\tasks\CheckUpdatesTask;

class Hydrogen {
	/**
	 * Notify if an update is available on Poggit.
	 */
	public static function checkForUpdates(Plugin $plugin) : void {
		Server::getInstance()->getAsyncPool()->submitTask(new CheckUpdatesTask($plugin->getName(), $plugin->getDescription()->getVersion()));
	}
}