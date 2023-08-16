<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_ff0c2de982ac1602\thebigcrafter\Hydrogen;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use function rename;
use function unlink;
use function version_compare;

class HConfig {
	/**
	 * Check config file version
	 */
	public static function verifyConfigVersion(Config $config, string $version, string $configKey = "config-version") : bool {
		/** @var string $currentVersion */
		$currentVersion = $config->get($configKey);

		if (version_compare($currentVersion, $version, "<>")) {
			return true;
		}
		return false;
	}

	/**
	 * Reset config file by using a template file
	 */
	public static function resetConfig(PluginBase $plugin, bool $hardReset = false) : bool {
		if ($hardReset) {
			if (unlink($plugin->getDataFolder() . "config.yml")) {
				$plugin->saveDefaultConfig();
				$plugin->getConfig()->reload();
				return true;
			} else {
				return false;
			}
		} else {
			if (rename($plugin->getDataFolder() . "config.yml", $plugin->getDataFolder() . "config_old.yml")) {
				$plugin->saveDefaultConfig();
				$plugin->getConfig()->reload();
				return true;
			}
			return false;
		}
	}
}