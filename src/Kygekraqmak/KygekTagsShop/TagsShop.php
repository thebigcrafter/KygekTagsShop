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

namespace Kygekraqmak\KygekTagsShop;

use cooldogedev\BedrockEconomy\BedrockEconomy;
use KygekTeam\KtpmplCfs\KtpmplCfs;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class TagsShop extends PluginBase implements Listener
{
	private const ROOT = "kygektagsshop";
	public const INFO = TF::GREEN;
	public const WARNING = TF::RED;

	/** @var array */
	public $config;

	/** @var DataConnector */
	public $data;
	/** @var string */
	private $prefix = TF::YELLOW . "[KygekTagsShop] " . TF::RESET;

	/** @var bool */
	public $economyEnabled = false;

	/** @var TagsActions */
	private static $api;
	/** @var TagsShop */
	private static $instance = null;

	/** @var string[] */
	private $lang = ["de", "en", "es", "fr", "id", "ro", "tr", "vi"];
	/** @var string[] */
	public $messages = [];
	/** @var string[] */
	private $defaultMessages = [];

	/**
	 * Returns an instance of KygekTagsShop API
	 */
	public static function getAPI(): TagsActions
	{
		return self::$api;
	}

	public static function getInstance()
	{
		return self::$instance;
	}

	protected function onEnable(): void
	{
		self::$instance = $this;

		$this->saveResource("config.yml");
		$this->config = $this->getConfig()->getAll();
		$db = libasynql::create($this, $this->config["database"], [
			"mysql" => "mysql.sql",
			"sqlite" => "sqlite.sql",
		]);
		$db->executeGeneric("kygektagsshop.init");
		$db->waitAll();
		$this->data = $db;

		$this->prefix = empty($this->config["prefix"])
			? TF::YELLOW . "[KygekTagsShop] " . TF::RESET
			: $this->config["prefix"];

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
			".error.notags" => "Tags cannot be empty, disabling plugin...",
		];

		$this->initializeLangs();
		$this->checkConfig();

		if (!class_exists(BedrockEconomy::class)) {
			if ($this->config["notify-no-economyapi"] === true) {
				$this->getLogger()->notice(
					$this->messages["kygektagsshop.notice.noeconomyapi"],
				);
			}
		} else {
			$this->economyEnabled = true;
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

		$cmddesc = empty($this->config)
			? "Buy tags here!"
			: $this->config["command-desc"];
		$cmdalias = $this->config["command-aliases"];
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
		);
		(new KtpmplCfs($this))->checkUpdates();
	}

	private function initializeLangs()
	{
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
			array_walk($langc["info"], function (&$value) {
				$value = $this->prefix . self::INFO . $value;
			});
			array_walk($langc["warning"], function (&$value) {
				$value = $this->prefix . self::WARNING . $value;
			});

			$this->messages = array_merge($langc["info"], $langc["warning"]);
			foreach ($langc as $key => $value) {
				if (!is_array($value)) {
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

	public function onJoin(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();

		if (!$this->fileExists()) {
			$player->sendMessage(
				$this->messages["kygektagsshop.warning.filemissing"],
			);
			return;
		}
		self::getAPI()->getPlayerTag($player, function (?int $tagid) use (
			$player,
		): void {
			if ($tagid !== -1 && self::getAPI()->tagExists($tagid)) {
				$player->setDisplayName(
					$player->getName() .
						" " .
						self::getAPI()->getTagName($tagid),
				);
			}
		});
	}

	private function checkConfig()
	{
		if ($this->config["config-version"] !== "1.6") {
			$this->getLogger()->notice(
				$this->messages["kygektagsshop.notice.outdatedconfig"],
			);
			$this->getLogger()->notice(
				$this->messages["kygektagsshop.notice.oldconfiginfo"],
			);
			rename(
				$this->getDataFolder() . "config.yml",
				$this->getDataFolder() . "config_old.yml",
			);
			$this->saveResource("config.yml");
			$this->getConfig()->reload();
		}
	}

	public function fileExists(): bool
	{
		$config = $this->getDataFolder() . "config.yml";
		$data = self::getAPI()->getDataLocation();
		return file_exists($config) or file_exists($data);
	}
}
