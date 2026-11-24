# EchoCall Chat Widget for WordPress

[![CI](https://github.com/ECHOCALL-LLC/echocall-wordpress/actions/workflows/ci.yml/badge.svg)](https://github.com/ECHOCALL-LLC/echocall-wordpress/actions/workflows/ci.yml)
[![Latest release](https://img.shields.io/github/v/release/ECHOCALL-LLC/echocall-wordpress)](https://github.com/ECHOCALL-LLC/echocall-wordpress/releases/latest)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)
![WordPress 6.8+](https://img.shields.io/badge/WordPress-6.8%2B-3858e9)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4)

Shows the chatbot you built in [EchoCall](https://echocall.de) on your WordPress site, as a chat in the corner of every page or inside a page. You connect once with an API key and pick the chatbot from the list of your own account.

## Install

1. Download [echocall-chat-widget.zip](https://github.com/ECHOCALL-LLC/echocall-wordpress/releases/latest/download/echocall-chat-widget.zip) from the latest release.
2. In WordPress, open Plugins, Add New, Upload Plugin, choose the ZIP and activate it.
3. In EchoCall, open the Developer Portal and create an API key.
4. In WordPress, open Settings, EchoCall Chat, paste the key and press Load chatbots.
5. Choose your chatbot and save. The chat now appears on your website.

An EchoCall account with an active package is needed. The trial counts. If your chatbot only allows certain websites, add the domain of your WordPress site to it in the EchoCall dashboard.

To update, upload the ZIP of the newer release the same way. WordPress asks whether to replace the installed version.

## Features

- Connect with an API key. The plugin loads the chatbots of your account once; the key is used for that request and is not saved.
- Pick the chatbot from that list. Chatbots of other accounts cannot be chosen.
- Show the chat on every page, hide it on phones or on single pages.
- Place the chat inside a page with the shortcode `[echocall_chat]` or the Elementor widget EchoCall Chat.
- Position, colors, language, light and dark look come from the chatbot in your EchoCall dashboard, so a change there needs nothing in WordPress.
- English, German (informal and formal) and French.

### Shortcode

```text
[echocall_chat]
[echocall_chat id="12" height="600px" width="100%"]
```

The first form shows the chatbot from the settings. The second shows another chatbot from the loaded list. Heights and widths take `px`, `%`, `vh` or `rem`.

### Consent tools

Return `false` from the filter `echocall_chat_widget_enabled` to skip the chat on a request. The script tag also passes through `wp_script_attributes`, which most consent tools use.

```php
add_filter( 'echocall_chat_widget_enabled', function ( $enabled ) {
	return $enabled && my_consent_given( 'echocall' );
} );
```

## External services and privacy

The plugin connects to the EchoCall service to show your chatbot.

- When an admin presses Load chatbots, the plugin sends the pasted API key and the address of the site to the EchoCall API at `hub.echocall.de` and receives the list of chatbots. This happens only on that click. Only the id, name and status of each chatbot are saved in WordPress.
- On every page where the chat is shown, the visitor's browser loads `widget.js` and the chat window from `cdn.echocall.de` (or the widget address set in the settings). Messages the visitor types are sent to EchoCall to be answered by the chatbot.

[Terms of service](https://echocall.de/terms-conditions) and [privacy policy](https://echocall.de/privacy-policy).

## Repository layout

```text
echocall-chat-widget.php    plugin header, constants, bootstrap
includes/                   settings page, chatbot list, frontend, Elementor widget
assets/                     admin styles
languages/                  translations as PHP files (de_DE, de_DE_formal, fr_FR)
readme.txt                  the readme WordPress shows for the plugin
uninstall.php               removes the plugin options on delete
.github/                    CI, release workflow and the scripts both use
```

The release ZIP holds the plugin folder `echocall-chat-widget/` and nothing from `.github/` or the Markdown files. `.gitattributes` decides what stays out.

## Development

No build step and no dependencies. Any PHP from 7.4 up runs the checks:

```sh
sh .github/scripts/lint-php.sh        # syntax check of every PHP file
sh .github/scripts/check-versions.sh  # the four version numbers agree
sh .github/scripts/build-zip.sh       # build/echocall-chat-widget.zip from HEAD
```

CI runs the syntax check on PHP 7.4 to 8.5, checks the versions and inspects the ZIP.

### Releasing

1. Raise the version in the plugin header, in `ECHOCALL_CHAT_VERSION`, in the `Stable tag` of `readme.txt` and as a new section in `CHANGELOG.md`.
2. Commit and push to `main`.
3. Tag the commit, for example `git tag v2.0.1 && git push origin v2.0.1`.

The release workflow checks that tag and versions agree, builds the ZIP, publishes the release with the changelog section as notes and records the release as live. The asset is always called `echocall-chat-widget.zip`, so the download link above always serves the newest version.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License

GPL-2.0-or-later, see [LICENSE](LICENSE). Copyright EchoCall LLC.
