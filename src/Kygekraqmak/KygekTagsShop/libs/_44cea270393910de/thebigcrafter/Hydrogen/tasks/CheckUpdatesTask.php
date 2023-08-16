<?php

/*
 * This file is part of Hydrogen.
 * (c) thebigcrafter <hello@thebigcrafter.xyz>
 * This source file is subject to the Apache-2.0 license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Kygekraqmak\KygekTagsShop\libs\_44cea270393910de\thebigcrafter\Hydrogen\tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use function json_decode;
use function version_compare;

class CheckUpdatesTask extends AsyncTask {
	public function __construct(private readonly string $name, private readonly string $version) {
	}

	public function onRun() : void {
		$res = Internet::getURL("https://poggit.pmmp.io/releases.min.json?name=" . $this->name, 10, [], $err);

		$highestVersion = $this->version;
		$artifactUrl = "";

		if ($res !== null) {
			$releases = (array) json_decode($res->getBody(), true);
			if ($releases !== null) {
				/**
				 * @var array{'version': string, 'artifact_url': string} $release
				 */
				foreach ($releases as $release) {
					if (version_compare($highestVersion, $release["version"], ">=")) {
						continue;
					}

					$highestVersion = $release["version"];
					$artifactUrl = $release["artifact_url"];
				}
			}
		}

		$this->setResult([$highestVersion, $artifactUrl, $err]);
	}

	public function onCompletion() : void {
		/**
		 * @var string $highestVersion
		 * @var string $artifactUrl
		 * @var string|null $err
		 */
		[$highestVersion, $artifactUrl, $err] = (array) $this->getResult();

		$logger = Server::getInstance()->getLogger();

		if ($err) {
			$logger->debug($err);
			return;
		}

		if ($highestVersion !== $this->version) {
			$artifactUrl .= "/{$this->name}_{$highestVersion}.phar";
			$logger->notice("{$this->name} v{$highestVersion} is available for download at {$artifactUrl}");
			return;
		}
	}
}