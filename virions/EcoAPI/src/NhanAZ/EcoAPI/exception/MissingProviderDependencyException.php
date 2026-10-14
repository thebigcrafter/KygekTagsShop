<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI\exception;

use RuntimeException;

/**
 * Thrown when the required economy plugin for a provider is not installed.
 *
 * The provider name is recognized, but the actual economy plugin
 * (e.g., EconomyAPI, BedrockEconomy) is not installed or enabled on the server.
 */
class MissingProviderDependencyException extends RuntimeException {
}
