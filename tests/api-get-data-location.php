<?php

declare(strict_types=1);

use Kygekraqmak\KygekTagsShop\TagsActions;
use Kygekraqmak\KygekTagsShop\TagsShop;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\base\DataConnectorImpl;

require __DIR__ . "/../vendor/autoload.php";

/** @param bool $condition */
function expectApi(bool $condition, string $message) : void{
	if(!$condition){
		throw new RuntimeException($message);
	}
}

$plugin = (new ReflectionClass(TagsShop::class))->newInstanceWithoutConstructor();
$dataFolder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "kygektagsshop-api-" . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
$dataFolderProperty = new ReflectionProperty(PluginBase::class, "dataFolder");
$dataFolderProperty->setValue($plugin, $dataFolder);

$connector = (new ReflectionClass(DataConnectorImpl::class))->newInstanceWithoutConstructor();
expectApi($connector instanceof DataConnector, "The test connector must satisfy the DataConnector contract");

$cases = [
	[
		["database" => ["type" => "sqlite", "sqlite" => ["file" => "tags.sqlite"]]],
		$dataFolder . "tags.sqlite",
	],
	[
		["database" => ["type" => "sqlite", "sqlite" => ["file" => "nested/tags.sqlite"]]],
		$dataFolder . "nested/tags.sqlite",
	],
	[
		["database" => ["type" => "sqlite", "sqlite" => ["file" => "/var/lib/kygektagsshop/tags.sqlite"]]],
		"/var/lib/kygektagsshop/tags.sqlite",
	],
	[
		["database" => ["type" => "sqlite", "sqlite" => ["file" => "C:\\servers\\tags.sqlite"]]],
		"C:\\servers\\tags.sqlite",
	],
	[
		["database" => ["type" => "sqlite", "sqlite" => ["file" => ":memory:"]]],
		"",
	],
	[
		["database" => ["type" => "mysql", "mysql" => ["database" => "tags"]]],
		"",
	],
	[
		[],
		"",
	],
];

foreach($cases as [$config, $expected]){
	$api = new TagsActions($plugin, $config, $connector, false, false);
	expectApi($api->getDataLocation() === $expected, "Unexpected database location for " . json_encode($config, JSON_THROW_ON_ERROR));
}

echo "PASS: getDataLocation resolves SQLite paths and rejects non-file backends\n";
