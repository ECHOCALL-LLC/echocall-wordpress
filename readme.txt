=== EchoCall Chat Widget ===
Contributors: echocall
Tags: chat, chatbot, live chat, ai, customer service
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds your EchoCall chatbot to your website. Connect once with an API key and pick the chatbot from your own list.

== Description ==

EchoCall Chat Widget shows the chatbot you built in EchoCall on your WordPress site, as a chat in the corner of every page or inside a page.

* Connect with an API key from the Developer Portal. The plugin loads the chatbots of your account. The key is used for that one request and is not saved.
* Pick the chatbot from that list. Chatbots of other accounts cannot be chosen.
* Show the chat on every page, hide it on phones or on single pages.
* Place the chat inside a page with the shortcode `[echocall_chat]` or the Elementor widget EchoCall Chat.
* Position, colors, language, light and dark look are set per chatbot in your EchoCall dashboard and take effect without changing anything in WordPress.
* English, German (informal and formal) and French.

The source code is open under GPL-2.0-or-later: https://github.com/ECHOCALL-LLC/echocall-wordpress

An EchoCall account with an active package is needed. The trial counts.

== Installation ==

1. Upload the ZIP under Plugins, Add New, Upload Plugin, and activate it.
2. In EchoCall, open the Developer Portal and create an API key.
3. In WordPress, open Settings, EchoCall Chat, paste the key and press Load chatbots.
4. Choose your chatbot and save. The chat now appears on your website.

If your chatbot only allows certain websites, add the domain of your WordPress site to it in the EchoCall dashboard.

== Frequently Asked Questions ==

= Is my API key stored in WordPress? =

No. The key is sent once to the EchoCall API to read the list of your chatbots. Only the id, name and status of each chatbot are saved. Load the list again with a key whenever you add a chatbot.

= The key is not accepted. =

Copy the whole key, it starts with eck_live_. If the key is limited to certain IP addresses or domains, allow the address of your web server or the domain of your site for it, or use a key without that limit.

= How do I place the chat inside a page? =

Use `[echocall_chat]` for the chatbot from the settings, or `[echocall_chat id="12" height="600px" width="100%"]` for another one from the list. Heights and widths take px, %, vh or rem.

= Can a cookie tool block the chat until consent? =

Yes. Return false from the filter `echocall_chat_widget_enabled` to skip the chat on a request. The script tag also passes through `wp_script_attributes`, which most consent tools use.

= Does the plugin change my theme? =

No. The chat is added on top of the page. The plugin does not change the styles of your theme.

== External services ==

This plugin connects to the EchoCall service to show your chatbot.

* When an admin presses Load chatbots, the plugin sends the pasted API key and the address of the site to the EchoCall API at hub.echocall.de and receives the list of chatbots. This happens only on that click.
* On every page where the chat is shown, the visitor's browser loads widget.js and the chat window from cdn.echocall.de (or the widget address set in the settings). Messages the visitor types are sent to EchoCall to be answered by the chatbot.

Terms of service: https://echocall.de/terms-conditions
Privacy policy: https://echocall.de/privacy-policy

== Changelog ==

= 2.0.0 =
* Connect with an API key and pick the chatbot from the list of your account. The key is not saved.
* The shortcode and the Elementor widget show only chatbots from that list.
* Removed the style reset that changed theme elements, the chat now sits above the page without it.
* Position is now taken from the chatbot in the EchoCall dashboard.
* Translations for English, German (informal and formal) and French.
* Requires WordPress 6.8 and PHP 7.4, tested up to WordPress 7.1.
* New versions are published as GitHub releases. WordPress never replaces the plugin with another one of the same name.

= 1.0.0 =
* First version.

== Upgrade Notice ==

= 2.0.0 =
Load your chatbots once under Settings, EchoCall Chat. A chatbot ID set in version 1 keeps working until you change it.
