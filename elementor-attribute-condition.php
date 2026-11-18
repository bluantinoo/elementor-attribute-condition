<?php
/**
 * Plugin Name:       Elementor Attribute Condition
 * Plugin URI:        https://github.com/bluantinoo/elementor-attribute-condition
 * Description:       Makes WooCommerce product attribute archives available to the Elementor Pro Theme Builder: display conditions per attribute or per term, and editor preview.
 * Version:           2.0.1
 * Author:            Diego Vagnoli
 * Author URI:        https://diegovagnoli.it
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elementor-attribute-condition
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Requires Plugins:  elementor, elementor-pro, woocommerce
 * Update URI:        https://github.com/bluantinoo/elementor-attribute-condition
 * WC requires at least: 8.0
 * WC tested up to:   11.2
 * Elementor tested up to: 4.4
 * Elementor Pro tested up to: 4.4
 *
 * @package Elementor_Attribute_Condition
 */

namespace Elementor_Attribute_Condition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '2.0.1';

require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

/**
 * Updates come from the GitHub releases of the plugin, not from wordpress.org
 * (the Update URI header keeps WordPress from looking there). Only the zip
 * attached to a release is installed, never the source archive of the tag.
 */
function register_update_checker() {
	$checker = \YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
		'https://github.com/bluantinoo/elementor-attribute-condition/',
		__FILE__,
		'elementor-attribute-condition'
	);
	$checker->getVcsApi()->enableReleaseAssets(
		'/^elementor-attribute-condition\.zip$/',
		\YahnisElsts\PluginUpdateChecker\v6p0\Vcs\Api::REQUIRE_RELEASE_ASSETS
	);

	// Releases only: without this the library falls back to the latest tag and
	// then to the main branch, whose code was never built or released.
	add_filter(
		'puc_vcs_update_detection_strategies-elementor-attribute-condition',
		static function ( $strategies ) {
			return array_intersect_key( $strategies, [ 'latest_release' => true ] );
		}
	);
}
register_update_checker();

/*
 * WooCommerce registers public attribute taxonomies with show_in_nav_menus off,
 * and Elementor Pro only lists taxonomies that have it on: that single flag is
 * why attribute archives are missing from display conditions, from the
 * "Preview Dynamic Content as" list and from the Product Terms tag.
 * WooCommerce reads this filter when it registers the taxonomies on init, so
 * it has to be in place before then. Only attributes with "Enable archives"
 * ticked are affected: WooCommerce ANDs the filter with attribute_public.
 */
add_filter( 'woocommerce_attribute_show_in_nav_menus', __NAMESPACE__ . '\\show_attribute_in_nav_menus', 10, 2 );

/**
 * Lets Elementor Pro see public attribute taxonomies.
 *
 * @param bool   $show     Whether the taxonomy is shown in nav menus.
 * @param string $taxonomy Attribute taxonomy name, e.g. pa_color.
 * @return bool
 */
function show_attribute_in_nav_menus( $show, $taxonomy ) {
	/**
	 * Filters whether an attribute archive is exposed to Elementor Pro.
	 *
	 * @param bool   $expose   Default true.
	 * @param string $taxonomy Attribute taxonomy name, e.g. pa_color.
	 */
	return (bool) apply_filters( 'elementor_attribute_condition_expose_attribute', true, $taxonomy );
}

add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );

/**
 * Loads translations shipped with the plugin.
 */
function load_textdomain() {
	load_plugin_textdomain( 'elementor-attribute-condition', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Fired by Elementor Pro only: if it is inactive, nothing below runs.
add_action( 'elementor/theme/register_conditions', __NAMESPACE__ . '\\register_conditions', 100 );

/**
 * Registers the "Product Attribute Archive" sub-condition under "Product Archive".
 *
 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Manager $conditions_manager Conditions manager.
 */
function register_conditions( $conditions_manager ) {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}

	$product_archive = $conditions_manager->get_condition( 'product_archive' );
	if ( ! $product_archive ) {
		return;
	}

	require_once __DIR__ . '/includes/class-attribute-archive-condition.php';

	$product_archive->register_sub_condition( new Attribute_Archive_Condition() );
}

add_action( 'elementor/editor/after_enqueue_scripts', __NAMESPACE__ . '\\enqueue_editor_script' );

/**
 * Adds the term picker for taxonomy archive previews to the Elementor editor.
 */
function enqueue_editor_script() {
	if ( ! wp_script_is( 'elementor-pro', 'registered' ) ) {
		return;
	}

	wp_enqueue_script(
		'elementor-attribute-condition-editor',
		plugins_url( 'assets/js/editor-preview-term.js', __FILE__ ),
		[ 'elementor-pro' ],
		VERSION,
		true
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\\maybe_upgrade' );

/**
 * Runs once per plugin version.
 */
function maybe_upgrade() {
	// Wait for a request that can see the attribute taxonomies.
	if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}

	$installed = get_option( 'elementor_attribute_condition_version', '' );
	if ( VERSION === $installed ) {
		return;
	}

	if ( '' === $installed || version_compare( $installed, '2.0.0', '<' ) ) {
		migrate_legacy_preview_settings();
	}

	update_option( 'elementor_attribute_condition_version', VERSION, false );
}

/**
 * Converts preview settings saved by 1.x to the native Elementor Pro format.
 *
 * 1.x stored the term inside the preview type ("taxonomy/pa_color::red");
 * Elementor Pro expects "taxonomy/pa_color" plus the term ID in preview_id.
 */
function migrate_legacy_preview_settings() {
	$template_ids = get_posts(
		[
			'post_type'      => 'elementor_library',
			'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => '_elementor_page_settings',
					'value'   => '::',
					'compare' => 'LIKE',
				],
			],
		]
	);

	foreach ( $template_ids as $template_id ) {
		$settings = get_post_meta( $template_id, '_elementor_page_settings', true );
		if ( ! is_array( $settings ) || empty( $settings['preview_type'] ) || ! is_string( $settings['preview_type'] ) ) {
			continue;
		}
		if ( ! preg_match( '#^taxonomy/([^/:]+)::(.+)$#u', $settings['preview_type'], $matches ) ) {
			continue;
		}

		// Leave the setting as it is if the term cannot be resolved: the
		// template then previews the first term, as Elementor Pro does.
		$term = taxonomy_exists( $matches[1] ) ? get_term_by( 'slug', $matches[2], $matches[1] ) : false;
		if ( ! $term instanceof \WP_Term ) {
			continue;
		}

		$settings['preview_type'] = 'taxonomy/' . $matches[1];
		$settings['preview_id']   = $term->term_id;

		update_post_meta( $template_id, '_elementor_page_settings', wp_slash( $settings ) );
	}
}

add_action( 'admin_notices', __NAMESPACE__ . '\\missing_dependencies_notice' );

/**
 * Warns administrators when a required plugin is not active.
 *
 * WordPress 6.5+ already refuses to activate this plugin without its
 * dependencies; this covers older setups and sites where a dependency was
 * removed from disk.
 */
function missing_dependencies_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$missing = [];
	if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
		$missing[] = 'Elementor Pro';
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		$missing[] = 'WooCommerce';
	}
	if ( ! $missing ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %s: comma-separated list of plugin names. */
				__( 'Elementor Attribute Condition does nothing until these plugins are active: %s.', 'elementor-attribute-condition' ),
				implode( ', ', $missing )
			)
		)
	);
}
