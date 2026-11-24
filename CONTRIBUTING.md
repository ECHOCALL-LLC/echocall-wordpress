# Contributing

Thanks for helping. Issues and pull requests are welcome.

## Before you open a pull request

- Keep the code working on PHP 7.4 and WordPress 6.8. CI checks the syntax on PHP 7.4 to 8.5.
- Follow the [WordPress coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/): tabs, Yoda conditions, `esc_*` on every output, nonces and capability checks on every admin action, `sanitize_*` on every input.
- Every user-facing string goes through the text domain `echocall-chat-widget`. Add new strings to all three files in `languages/` (`de_DE`, `de_DE_formal`, `fr_FR`).
- The plugin adds no dependencies and no build step. Please keep it that way.
- Run the checks from the README before you push.

## Versions

A release changes the version in four places: the plugin header, `ECHOCALL_CHAT_VERSION`, the `Stable tag` in `readme.txt` and a new section at the top of `CHANGELOG.md`. Pull requests leave the version alone; the maintainers set it when they release.

Describe your change under `## [Unreleased]` in `CHANGELOG.md` if you like, in plain words a site owner understands.

## Security problems

Please do not open a public issue. See [SECURITY.md](SECURITY.md).

## License

By contributing you agree that your contribution is licensed under GPL-2.0-or-later, like the rest of the plugin.
