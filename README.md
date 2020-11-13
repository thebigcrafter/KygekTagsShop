# KygekTagsShop

[![Discord](https://img.shields.io/discord/735439472992321587.svg?label=&logo=discord&logoColor=ffffff&color=7389D8&labelColor=6A7EC2)](https://discord.gg/CXtqUZv)

![KygekTagsShop](https://github.com/Kygekraqmak/KygekTagsShop/blob/master/images/KygekTagsShop.png?raw=true)

Allow players to use tags that they can show to other players by using this plugin! 

# Features

- EconomyAPI support for tags prices
- Compatible with PureChat
- Unlimited tags
- Forms to buy and sell tags
- Highly customizeable forms
    - Supports `{player}` to display the player name in all forms
    - Supports `&` as formatting codes in all forms
    - Supports `\n` to break new line in all forms
    - Supports `{tagname}` to display tag name and `{tagprice}` to display tag price in some forms
- Command descrption can be changed
- Supports command aliases
- Enable/disable return to previous form when the (X) button is pressed
- Automatic plugin updates checker
- Missing config file detection
- Empty tags detection
- API for developers (see **For Developers** tab)

# How to Install

**WARNING: This plugin is in beta stage. It may contain bugs including its API.
Please report [here](https://github.com/Kygekraqmak/KygekTagsShop/issues) if you found any bugs.**

1. Download the latest version (It is recommended to always download the latest version for the best experience, except you're having compatibility issues).
2. Place the `KygekTagsShop.phar` file into the plugins folder.
3. Restart or start your server.
4. Done!

# Commands & Permissions

| Command | Default Description | Permission | Default |
| --- | --- | --- | --- |
| `/tagsshop` | Allows player to use KygekTagsShop to buy and sell tags | `kygektagsshop.tags` | true |

Command description can be changed in `config.yml`. You can also add command aliases in `config.yml`.

Use `-kygektagsshop.tags` to blacklist the `/tagsshop` command permission to groups/users in PurePerms.

# For Developers

We provide API for developers to write addons/plugins that depends with KygekTagsShop.\
To access KygekTagsShop API class, you can use `Kygekraqmak\KygekTagsShop\TagsShop::getAPI()`.\
API code can be seen [here](https://github.com/Kygekraqmak/KygekTagsShop/blob/master/src/Kygekraqmak/KygekTagsShop/TagsActions.php).\
Please regularly check the changelogs for any changes in the API in future versions.

# Upcoming Features

- Multiple languages
- Commands to buy and sell tags and more
- And much more...

# Additional Notes

- See **For Developers** tab for API guide.
- Join the Discord [here](https://discord.gg/CXtqUZv) for latest updates from Kygekraqmak.
- If you found bugs or want to give suggestions, please visit [here](https://github.com/Kygekraqmak/KygekTagsShop/issues) or DM KygekDev#6415 via Discord.
- We accept any contributions! If you want to contribute please make a pull request in [here](https://github.com/Kygekraqmak/KygekTagsShop/pulls).

<!-- Icons made by <a href="https://www.flaticon.com/authors/kirill-kazachek" title="Kirill Kazachek">Kirill Kazachek</a> from <a href="https://www.flaticon.com/" title="Flaticon"> www.flaticon.com</a> -->