<?php

declare(strict_types=1);

namespace cooldogedev\BedrockEconomy;

use pocketmine\plugin\PluginBase;

final class BedrockEconomy extends PluginBase {}

namespace cooldogedev\BedrockEconomy\api;

use Closure;

final class BedrockEconomyAPI {
	public static function getInstance() : self { throw new \LogicException(); }

	public function getPlayerBalance(string $player, Closure $callback) : void {}

	public function addToPlayerBalance(string $player, ?int $amount) : void {}

	public function subtractFromPlayerBalance(string $player, ?int $amount) : void {}
}

namespace cooldogedev\BedrockEconomy\api\legacy;

use Closure;

final class ClosureContext {
	public static function create(Closure $closure) : Closure { return $closure; }
}
