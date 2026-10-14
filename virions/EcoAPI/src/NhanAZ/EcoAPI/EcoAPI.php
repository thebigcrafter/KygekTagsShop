<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI;

use Closure;
use NhanAZ\EcoAPI\exception\MissingProviderDependencyException;
use NhanAZ\EcoAPI\exception\UnknownProviderException;
use NhanAZ\EcoAPI\provider\BedrockEconomyProvider;
use NhanAZ\EcoAPI\provider\EconomyAPIProvider;
use NhanAZ\EcoAPI\provider\SimpleEconomyProvider;
use NhanAZ\EcoAPI\provider\XPProvider;
use function spl_object_id;

/**
 * Main entry point for EcoAPI.
 *
 * Provides a unified interface to multiple economy plugins.
 * Call EcoAPI::init() once in your plugin's onEnable(), then use
 * detectProvider() or getProvider() to get an EconomyProvider instance.
 *
 * Usage:
 *   EcoAPI::init();
 *   $provider = EcoAPI::detectProvider();
 *   $provider->getMoney($player, function(float|int $balance): void { ... });
 */
final class EcoAPI {

	/**
	 * @var array<string, array{factory: Closure(): EconomyProvider, available: Closure(): bool}>
	 * Maps provider names (lowercase) to shared factory registrations.
	 */
	private static array $providers = [];

	/** Whether init() has been called. */
	private static bool $initialized = false;

	/** Prevent instantiation — all methods are static. */
	private function __construct() {
	}

	/**
	 * Initialize EcoAPI with built-in providers.
	 *
	 * Call this once in your plugin's onEnable().
	 * Safe to call multiple times — subsequent calls are ignored.
	 */
	public static function init(): void {
		if (self::$initialized) {
			return;
		}
		self::$initialized = true;

		self::registerProvider(
			static fn(): EconomyProvider => new EconomyAPIProvider(),
			static fn(): bool => EconomyAPIProvider::isAvailable(),
			"economyapi", "economy"
		);
		self::registerProvider(
			static fn(): EconomyProvider => new BedrockEconomyProvider(),
			static fn(): bool => BedrockEconomyProvider::isAvailable(),
			"bedrockeconomy", "bedrock"
		);
		self::registerProvider(
			static fn(): EconomyProvider => new SimpleEconomyProvider(),
			static fn(): bool => SimpleEconomyProvider::isAvailable(),
			"simpleeconomy", "simple"
		);
		self::registerProvider(
			static fn(): EconomyProvider => new XPProvider(),
			static fn(): bool => XPProvider::isAvailable(),
			"xp", "exp", "experience"
		);
	}

	/**
	 * Register a custom economy provider.
	 *
	 * You can register multiple names/aliases for the same provider.
	 * Names are case-insensitive.
	 *
	 * Example:
	 *   EcoAPI::registerProvider(
	 *       static fn(): EconomyProvider => new MyProvider(),
	 *       static fn(): bool => MyProvider::isAvailable(),
	 *       "myprovider", "myeco"
	 *   );
	 *
	 * @param Closure(): EconomyProvider $factory Creates a provider instance
	 * @param Closure(): bool $isAvailable Checks the provider dependency
	 * @param string ...$names One or more names/aliases for this provider
	 */
	public static function registerProvider(Closure $factory, Closure $isAvailable, string ...$names): void {
		$registration = ["factory" => $factory, "available" => $isAvailable];
		foreach ($names as $name) {
			self::$providers[strtolower($name)] = $registration;
		}
	}

	/**
	 * Get an economy provider by name.
	 *
	 * @param string $name The provider name (case-insensitive), e.g., "economyapi"
	 *
	 * @throws UnknownProviderException If the provider name is not registered
	 * @throws MissingProviderDependencyException If the provider's plugin is not installed
	 */
	public static function getProvider(string $name): EconomyProvider {
		$key = strtolower($name);

		if (!isset(self::$providers[$key])) {
			$available = implode(", ", array_keys(self::$providers));
			throw new UnknownProviderException(
				"Unknown economy provider: \"{$name}\". Registered providers: {$available}"
			);
		}

		$registration = self::$providers[$key];

		if (!$registration["available"]()) {
			throw new MissingProviderDependencyException(
				"Economy provider \"{$name}\" is not available. Is the required plugin installed and enabled?"
			);
		}

		return $registration["factory"]();
	}

	/**
	 * Auto-detect the first available economy provider.
	 *
	 * Checks each registered provider in order and returns the first one
	 * whose economy plugin is installed. Returns null if none found.
	 *
	 * @return EconomyProvider|null The first available provider, or null if none found
	 */
	public static function detectProvider(): ?EconomyProvider {
		$seen = [];
		foreach (self::$providers as $registration) {
			$factory = $registration["factory"];
			$id = spl_object_id($factory);
			if (isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;

			if ($registration["available"]()) {
				return $factory();
			}
		}
		return null;
	}

	/**
	 * Check if any economy provider is available on the server.
	 */
	public static function isAvailable(): bool {
		return self::detectProvider() !== null;
	}

	/**
	 * Get a list of all registered provider names.
	 *
	 * @return string[] e.g., ["economyapi", "economy", "bedrockeconomy", "bedrock", ...]
	 */
	public static function getRegisteredProviders(): array {
		return array_keys(self::$providers);
	}

	/**
	 * Get a list of provider names that are currently available (plugin installed).
	 *
	 * Only returns the primary name for each provider (not aliases).
	 *
	 * @return string[] e.g., ["economyapi", "xp"]
	 */
	public static function getAvailableProviders(): array {
		$available = [];
		$seen = [];
		foreach (self::$providers as $name => $registration) {
			$id = spl_object_id($registration["factory"]);
			if (isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;

			if ($registration["available"]()) {
				$available[] = $name;
			}
		}
		return $available;
	}
}
