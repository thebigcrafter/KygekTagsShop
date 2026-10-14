<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI\Plugin;

use pocketmine\plugin\PluginBase;

/**
 * Companion plugin for EcoAPI virion library.
 *
 * This is an empty plugin that allows EcoAPI to be loaded
 * as a standalone plugin on the server. It does nothing by itself.
 *
 * You can use EcoAPI in two ways:
 * 1. As a virion (shade it into your plugin) — recommended
 * 2. As a plugin (drop this .phar on the server)
 */
class Main extends PluginBase {
	// No-op — this plugin exists only to load the EcoAPI classes.
}
