<?php
/**
 * Removes the stored plugin version and the update checker state when the plugin is deleted.
 *
 * @package Elementor_Attribute_Condition
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'elementor_attribute_condition_version' );
delete_site_option( 'external_updates-elementor-attribute-condition' );
wp_clear_scheduled_hook( 'puc_cron_check_updates-elementor-attribute-condition' );
