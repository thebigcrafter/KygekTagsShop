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
 * Copyright (C) 2020-2023 Kygekraqmak
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop;

use _64FF00\PurePerms\PurePerms;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\poggit\libasynql\DataConnector;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\poggit\libasynql\libasynql;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\HConfig;
use Kygekraqmak\KygekTagsShop\libs\_61868a5d93acbee8\thebigcrafter\Hydrogen\Hydrogen;
use function array_merge;
use function array_walk;
use function class_exists;
use function file_exists;
use function is_array;
use function rename;

class TagsShop extends PluginBase implements Listener {
	private const ROOT = "kygektagsshop";
	public const INFO = TF::GREEN;
	public const WARNING = TF::RED;

	/** @var array<string, mixed> */
	public array $config;

	public DataConnector $data;
	private string $prefix = TF::YELLOW . "[KygekTagsShop] " . TF::RESET;

	public bool $economyEnabled = false;

	public bool $purePermsEnabled = false;
	private ?PurePerms $purePerms = null;

	private static ?TagsActions $api = null;
	private static ?self $instance = null;

	/** @var list<string> */
	private array $lang = ["de", "en", "es", "fr", "id", "ro", "tr", "vi", "ru"];
	/** @var array<string, string> */
	public array $messages = [];
	/** @var array<string, string> */
	private array $defaultMessages = [];

	/**
	 * Returns an instance of KygekTagsShop API
	 */
	public static function getAPI() : TagsActions {
		if (self::$api === null) {
			throw new \LogicException("KygekTagsShop API is not initialized");
		}
		return self::$api;
	}

	public static function getInstance() : self {
		if (self::$instance === null) {
			throw new \LogicException("KygekTagsShop is not initialized");
		}
		return self::$instance;
	}

	protected function onEnable() : void {
		self::$instance = $this;

		$this->config = [];
		foreach ($this->getConfig()->getAll() as $key => $value) {
			if (is_string($key)) {
				$this->config[$key] = $value;
			}
		}
		$db = libasynql::create($this, $this->config["database"], [
			"mysql" => "mysql.sql",
			"sqlite" => "sqlite.sql",
		]);
		$db->executeGeneric("kygektagsshop.init");
		$db->waitAll();
		$this->data = $db;

		$configPrefix = $this->config["prefix"] ?? null;
		$this->prefix = !is_string($configPrefix) || $configPrefix === ""
			? TF::YELLOW . "[KygekTagsShop] " . TF::RESET
			: $configPrefix;

		$this->defaultMessages = [
			self::ROOT . ".warning.filemissing" =>
			$this->prefix .
				self::WARNING .
				"Config and/or data file cannot be found, please restart the server!",
			self::ROOT . ".warning.notplayer" =>
			$this->prefix .
				self::WARNING .
				"You can only execute this command in-game!",
			self::ROOT . ".warning.nopermission" =>
			$this->prefix .
				self::WARNING .
				"You do not have permission to use this command!",
			self::ROOT . ".warning.playerhastag" =>
			$this->prefix .
				self::WARNING .
				"You cannot buy tags because you have owned a tag!",
			self::ROOT . ".warning.playerhasnotag" =>
			$this->prefix .
				self::WARNING .
				"You cannot buy tags because you haven't owned a tag!",
			self::ROOT . ".warning.notenoughmoney" =>
			$this->prefix .
				self::WARNING .
				"You need {price} more to buy this tag!",
			self::ROOT . ".info.economybuytagsuccess" =>
			$this->prefix .
				self::INFO .
				"Successfully set your tag for {price}",
			self::ROOT . ".info.freebuytagsuccess" =>
			$this->prefix . self::INFO . "Successfully set your tag",
			self::ROOT . ".info.economyselltagsuccess" =>
			$this->prefix .
				self::INFO .
				"Successfully sold your tag for {price}",
			self::ROOT . ".info.freeselltagsuccess" =>
			$this->prefix . self::INFO . "Successfully sold your tag",
			self::ROOT .
				".notice.outdatedconfig" => "Your configuration file is outdated, updating the config.yml...",
			self::ROOT .
				".notice.oldconfiginfo" => "The old configuration file can be found at config_old.yml",
			self::ROOT .
				".notice.noeconomyapi" => "EconomyAPI plugin is not installed or enabled, all tags will be free",
			self::ROOT .
				"notice.pureperms" => "PurePerms plugin not installed or activated, some features will be disabled",
			self::ROOT .
				".error.notags" => "Tags cannot be empty, disabling plugin...",
		];

		$this->initializeLangs();
		HConfig::verifyConfigVersion($this->getConfig(), "1.8");

		if (!class_exists(BedrockEconomy::class)) {
			if ($this->config["notify-no-economyapi"] === true) {
				$this->getLogger()->notice(
					$this->messages["kygektagsshop.notice.noeconomyapi"],
				);
			}
		} else {
			$this->economyEnabled = true;
		}

		$purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		if (!$purePerms instanceof PurePerms) {
			if ($this->config["notify-no-pureperms"] === true) {
				$this->getLogger()->notice(
					$this->messages["kygektagsshop.notice.pureperms"],
				);
			}
		} else {
			$this->purePermsEnabled = true;
			$this->purePerms = $purePerms;
		}

		if (empty($this->config["tags"])) {
			$this->getLogger()->error(
				$this->messages["kygektagsshop.error.notags"],
			);
			$this->getServer()
				->getPluginManager()
				->disablePlugin($this);
			return;
		}

		$commandDescription = $this->config["command-desc"] ?? null;
		$cmddesc = is_string($commandDescription) && $commandDescription !== ""
			? $commandDescription
			: "Buy tags here!";
		$rawAliases = $this->config["command-aliases"] ?? [];
		$cmdalias = is_array($rawAliases)
			? array_values(array_filter($rawAliases, static fn(mixed $alias): bool => is_string($alias)))
			: [];
		$this->getServer()
			->getPluginManager()
			->registerEvents($this, $this);
		$this->getServer()
			->getCommandMap()
			->register(
				"KygekTagsShop",
				new Commands($this, $cmddesc, $cmdalias),
			);
		self::$api = new TagsActions(
			$this,
			$this->config,
			$this->data,
			$this->economyEnabled,
			$this->purePermsEnabled,
		);
		Hydrogen::checkForUpdates($this);
	}

	private function initializeLangs() : void {
		foreach ($this->lang as $lang) {
			$this->saveResource("lang/" . $lang . ".yml", true);
			$langf = new Config(
				$this->getDataFolder() . "lang/" . $lang . ".yml",
				Config::YAML,
			);
			if ($this->config["language"] !== $lang) {
				continue;
			}

			$langc = $langf->getAll();
			$info = $langc["info"] ?? [];
			$warning = $langc["warning"] ?? [];
			if (is_array($info)) {
				foreach ($info as $key => $value) {
					if (is_string($key) && is_string($value)) {
						$this->messages[$key] = $this->prefix . self::INFO . $value;
					}
				}
			}
			if (is_array($warning)) {
				foreach ($warning as $key => $value) {
					if (is_string($key) && is_string($value)) {
						$this->messages[$key] = $this->prefix . self::WARNING . $value;
					}
				}
			}
			foreach ($langc as $key => $value) {
				if (is_string($key) && !is_array($value) && is_string($value)) {
					$this->messages[$key] = $value;
				}
			}

			// Fixes language if some keys are missing
			foreach ($this->defaultMessages as $key => $value) {
				if (!isset($this->messages[$key])) {
					$this->messages[$key] = $value;
				}
			}
		}

		if (!empty($this->messages)) {
			return;
		}
		$this->getLogger()->warning(
			"Unknown language in configuration file, using English...",
		);
		$this->messages = $this->defaultMessages;
	}

	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();

		if (!$this->fileExists()) {
			$player->sendMessage(
				$this->messages["kygektagsshop.warning.filemissing"],
			);
			return;
		}
		self::getAPI()->getPlayerTag($player, function (int $tagid) use (
			$player,
		) : void {
			if ($tagid >= 0 && self::getAPI()->tagExists($tagid)) {
				$player->setDisplayName(
					$player->getName() .
						" " .
						self::getAPI()->getTagName($tagid),
				);
			}
		});
	}

	public function fileExists() : bool {
		$config = $this->getDataFolder() . "config.yml";
		$data = self::getAPI()->getDataLocation();
		return file_exists($config) || file_exists($data);
	}

	public function getPurePerms() : PurePerms {
		if ($this->purePerms === null) {
			throw new \LogicException("PurePerms is not enabled");
		}
		return $this->purePerms;
	}
}