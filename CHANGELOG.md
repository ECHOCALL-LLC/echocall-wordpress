# Changelog

All notable changes to the EchoCall Chat Widget are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the plugin uses [Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-11-24

### Added

- Connect with an API key and pick the chatbot from the list of your account. The key is used for that one request and is not saved.
- Translations for English, German (informal and formal) and French.
- The source code is public on GitHub and new versions are published as GitHub releases.
- `Update URI` in the plugin header, so WordPress never replaces the plugin with another one of the same name.

### Changed

- The shortcode and the Elementor widget show only chatbots from the loaded list.
- Position is taken from the chatbot in the EchoCall dashboard.
- Requires WordPress 6.8 and PHP 7.4, tested up to WordPress 7.1.

### Removed

- The style reset that changed theme elements. The chat now sits above the page without it.

### Upgrading

Load your chatbots once under Settings, EchoCall Chat. A chatbot ID set in version 1 keeps working until you change it.

## [1.0.0] - 2026-03-23

### Added

- First version.

[2.0.0]: https://github.com/ECHOCALL-LLC/echocall-wordpress/releases/tag/v2.0.0
