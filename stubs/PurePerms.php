<?php

declare(strict_types=1);

namespace _64FF00\PurePerms;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

final class PurePerms extends PluginBase {
	public function getUserDataMgr() : UserDataManager { throw new \LogicException(); }
}

final class UserDataManager {
	public function setPermission(Player $player, string $permission) : void {}

	public function unsetPermission(Player $player, string $permission) : void {}
}
