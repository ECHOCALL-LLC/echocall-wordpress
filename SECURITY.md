# Security policy

## Supported versions

Only the latest release receives fixes. Please update before you report a problem.

| Version | Supported |
| ------- | --------- |
| 2.x     | Yes       |
| 1.x     | No        |

## Reporting a vulnerability

Please do not open a public issue for a security problem. Write to **team@echocall.de** or use [private vulnerability reporting](https://github.com/ECHOCALL-LLC/echocall-wordpress/security/advisories/new) on GitHub.

Include the plugin version, the WordPress and PHP versions, the steps that show the problem and what an attacker could do with it. We confirm receipt and keep you posted until a fix is released.

## What the plugin stores

- The API key is sent once to the EchoCall API to load the chatbot list and is never saved in WordPress.
- Saved are the id, name and status of each chatbot, the chosen chatbot and the display settings.
- Deleting the plugin removes all of its options.
