# KygekTagsShop

[![Poggit](https://poggit.pmmp.io/shield.dl.total/KygekTagsShop)](https://poggit.pmmp.io/p/KygekTagsShop)
[![Discord](https://img.shields.io/discord/970294579372912700.svg?label=&logo=discord&logoColor=ffffff&color=7389D8&labelColor=6A7EC2)](https://discord.gg/cEXW8uK6QA)

![KygekTagsShop](https://github.com/thebigcrafter/KygekTagsShop/blob/master/images/KygekTagsShop.png?raw=true)

Allow players to use tags that they can show to other players by using this plugin! 

# Features

- EconomyAPI support for tags prices
    - Shows warning if EconomyAPI plugin is not installed or enabled (Can be disabled in `config.yml`)
- Compatible with PureChat
- Unlimited tags
- Forms to buy and sell tags
- Highly customizeable forms
    - Supports `{player}` to display the player name in all forms
    - Supports `&` as formatting codes in all forms
    - Supports `\n` to break new line in all forms
    - Supports `{tagname}` to display tag name and `{tagprice}` to display tag price in some forms
- Command descrption can be changed
- Customizeable message prefix
- Supports command aliases
- Enable/disable return to previous form when the (X) button is pressed
- Automatic plugin updates checker
- Missing config file detection
- Empty tags detection
- Configurable player display name format for tag placement
- Multiple languages support (English, Indonesian, Spanish, German, French, Romanian, Turkish)
- API for developers (see **For Developers** tab)
- Events for developers (`TagBuyEvent` and `TagSellEvent`)

# How to Install

**WARNING: This plugin is in beta stage. It may contain bugs including its API.
Please report [here](https://github.com/thebigcrafter/KygekTagsShop/issues) if you found any bugs.**

1. Download the latest version from [Poggit Releases](https://poggit.pmmp.io/p/KygekTagsShop) (It is recommended to always download the latest version for the best experience, except you're having compatibility issues).
2. Place the `KygekTagsShop.phar` file into the plugins folder.
3. Restart or start your server.
4. Done!

If you want to download the latest development build, which may contain the latest features that are currently in development, head over to [Poggit CI](https://poggit.pmmp.io/ci/thebigcrafter/KygekTagsShop/~) and download the latest build. **Please note that development builds may be unstable because it may contain untested issues or bugs.**

# Commands & Permissions

| Command | Default Description | Permission | Default |
| --- | --- | --- | --- |
| `/tagsshop` | Allows player to use KygekTagsShop to buy and sell tags | `kygektagsshop.tags` | true |

Command description can be changed in `config.yml`. You can also add command aliases in `config.yml`.

Use `-kygektagsshop.tags` to blacklist the `/tagsshop` command permission to groups/users in PurePerms.

# For Developers

We provide API for developers to write addons/plugins that depends with KygekTagsShop.\
To access KygekTagsShop API class, you can use `Kygekraqmak\KygekTagsShop\TagsShop::getAPI()`.

Example:
```php
$tags = Kygekraqmak\KygekTagsShop\TagsShop::getAPI()->getAllTags(); // Get all tags from KygekTagsShop
```

API code can be seen [here](https://github.com/thebigcrafter/KygekTagsShop/blob/master/src/Kygekraqmak/KygekTagsShop/TagsActions.php).\
Please regularly check the changelogs for any changes in the API in future versions.

# Contributing

Help us by contributing or translating KygekTagsShop plugin. To add translation, fork the KygekTagsShop plugin repo and copy the `en.yml` file inside `resources/lang` directory to the language code.

Don't forget to create a [pull request](https://github.com/thebigcrafter/KygekTagsShop/pulls)!

# Upcoming Features

- Commands to buy and sell tags and more
- And much more...

# Additional Notes

- See **For Developers** tab for API guide.
- Join our [Discord server](https://discord.gg/cEXW8uK6QA) for latest updates from thebigcrafter.
- If you found bugs or want to give suggestions, please [create an issue](https://github.com/thebigcrafter/KygekTagsShop/issues) or join our Discord server.
- We accept all contributions! If you want to contribute, please [make a pull request](https://github.com/thebigcrafter/KygekTagsShop/pulls).

<!-- Icons made by <a href="https://www.flaticon.com/authors/kirill-kazachek" title="Kirill Kazachek">Kirill Kazachek</a> from <a href="https://www.flaticon.com/" title="Flaticon"> www.flaticon.com</a> -->
