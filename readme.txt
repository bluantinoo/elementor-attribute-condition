=== Elementor Attribute Condition ===
Contributors: bluantinoo
Tags: elementor, woocommerce, product attributes, theme builder, display conditions
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: elementor, elementor-pro, woocommerce
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Makes WooCommerce product attribute archives available to the Elementor Pro Theme Builder: display conditions per attribute or per term, and editor preview.

== Description ==

Elementor Pro lets you target a Product Archive template at product categories and tags, but not at attribute archives such as /color/red/, and does not offer them in "Preview Dynamic Content as".

The reason is a single flag: WooCommerce registers attribute taxonomies with `show_in_nav_menus` off, and Elementor Pro only lists taxonomies that have it on. This plugin turns it on, through the WooCommerce filter made for it, for attributes with archives enabled. Elementor Pro then handles them with its own code.

What you get, under Theme Builder > Product Archive > Display Conditions > Include > Product Archive:

* Each attribute by name (e.g. Color), with Elementor's own term picker: all Color archives, or only Red.
* Product Attribute Archive: the archive of any attribute, or of one attribute.

In the editor, "Preview Dynamic Content as" lists each attribute archive, and the plugin adds the field to pick the term to preview, which Elementor Pro shows only for Loop templates (it works for other taxonomy archives too, such as product categories). Archive Title, Archive Description and ACF fields on terms show that term's data. Attributes also appear in the Product Terms dynamic tag and in the taxonomy filters of the Posts widget.

There are no settings.

= Side effect =

Attributes with archives become available as boxes in Appearance > Menus (hidden by default, see Screen Options). To leave an attribute out of everything, return false from the `elementor_attribute_condition_expose_attribute` filter for its taxonomy.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it. WordPress requires Elementor, Elementor Pro and WooCommerce to be active first.
3. In Products > Attributes, make sure "Enable archives" is ticked for the attributes you want to template.
4. In Templates > Theme Builder, edit a Product Archive template and set its display conditions.

== Frequently Asked Questions ==

= Do I need Elementor Pro? =

Yes. Display conditions and archive templates are Elementor Pro features.

= The term field disappeared from the preview settings =

The field is added by wrapping an internal function of the Elementor Pro editor. If an Elementor Pro release changes it, the field disappears and archive templates preview the first term of the taxonomy, as Elementor Pro does on its own. Nothing else breaks.

= My attribute does not appear =

Edit the attribute in Products > Attributes and tick "Enable archives". Without archives there is no archive page to template.

== Upgrade Notice ==

= 2.0.0 =
Rebuilt on Elementor Pro's native handling. Saved display conditions and preview settings carry over; the helper functions get_current_loop_term() and get_loop_term_meta() are removed.

== Changelog ==

= 2.0.0 =
* Rewritten on top of Elementor Pro's native taxonomy handling: attribute archives get per-term display conditions and the native preview.
* Term field in the preview settings of every taxonomy archive template.
* The "Product Attribute Archive" condition keeps its internal name, so conditions saved with 1.x keep working; it now also offers "All attributes".
* Removed the custom Product Archive document class, the editor query rewrites and the filter on `get_post_meta` that rerouted post meta reads to term meta.
* Removed the scan of all templates on every condition check.
* No fatal error when Elementor or WooCommerce is inactive; dependencies declared with Requires Plugins.
* One text domain, translations loaded, Italian translation included.
* Preview settings saved with 1.x are converted to the native format on the first admin page load by an administrator after the update.
* Stores its version in an option, removed on uninstall.
* Removed the helper functions `get_current_loop_term()` and `get_loop_term_meta()`.

= 1.1.0 =
* Loop Builder additions and condition matching on the saved attribute.

= 1.0.0 =
* Initial release.
