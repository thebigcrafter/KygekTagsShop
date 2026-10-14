# KygekTagsShop

KygekTagsShop is an Axolotl-PM plugin that lets players buy, sell and display configurable name tags through forms.

## Features

- Buy and sell tags through an in-game form interface.
- Configure tag prices, required permissions and display-name formatting.
- Support optional SimpleEconomy balances through [EcoAPI](https://github.com/NhanAZ-Libraries/EcoAPI) and PurePerms tag permissions.
- Store tag ownership in SQLite or MySQL through libasynql.
- Customize messages, language, command aliases and form text.
- Provide an API and `TagBuyEvent`/`TagSellEvent` events for addons.
- Support the following bundled languages: English, Indonesian, Spanish, Romanian, French, German, Turkish, Vietnamese and Russian.

## Requirements

- Axolotl-PM API 5.
- SimpleEconomy is optional. When it is unavailable, the plugin follows the configured no-economy behavior and tags are free.
- PurePerms is optional. When it is available, tag permissions are managed through it.

## Installation

1. Download a release from [GitHub Releases](https://github.com/thebigcrafter/KygekTagsShop/releases) or a verified development artifact.
2. Copy `KygekTagsShop.phar` into the server's `plugins` directory.
3. Start or restart Axolotl-PM.
4. Edit `plugin_data/KygekTagsShop/config.yml` when you need to change tags, forms, language or storage.

Development builds may contain unfinished changes and should be tested on a staging server before being used on a production server.

## Commands and permissions

| Command | Permission | Description |
| --- | --- | --- |
| `/tagsshop` | `kygektagsshop.tags` | Open the tag shop. |

The command is player-only. Its description and aliases can be changed with `command-desc` and `command-aliases` in `config.yml`.

## Configuration

The generated `config.yml` controls:

- Language, message prefix and form text.
- Command description and aliases.
- Whether the plugin returns to a previous form after closing one.
- The tag list, prices, required permissions and player display-name format.
- SQLite or MySQL connection settings and the asynchronous query worker limit.

The default database is SQLite. Configure the `database.mysql` section only when `database.type` is set to `mysql`. Keep database credentials outside public repositories and avoid using a root database account.

## Developer API

Addons can access the API through `Kygekraqmak\KygekTagsShop\TagsShop::getAPI()`.

`getDataLocation()` returns the configured SQLite database path, resolved relative to the plugin data folder when necessary. It returns an empty string when the configured backend does not use a local SQLite file.

The plugin dispatches:

- `Kygekraqmak\KygekTagsShop\event\TagBuyEvent` after a tag purchase.
- `Kygekraqmak\KygekTagsShop\event\TagSellEvent` after a tag sale.

Check the source and event classes in this repository for the current method and event contracts.

## Development

Install the locked development dependencies and run the static analysis command:

```shell
composer install --no-interaction
composer analyse
```

The GitHub Actions workflow builds and uploads the plugin PHAR with the repository's pinned build action. A successful packaging run does not replace runtime, client or gameplay testing on a staging server.

## Contributing

Contributions and translations are welcome. For a new translation, copy `resources/lang/en.yml`, use the appropriate language code and open a [pull request](https://github.com/thebigcrafter/KygekTagsShop/pulls).

## License

KygekTagsShop is licensed under the [GNU General Public License v3.0](https://github.com/thebigcrafter/KygekTagsShop/blob/main/LICENSE).
