<?php
/**
 * Removes the stored plugin version when the plugin is deleted.
 *
 * @package Elementor_Attribute_Condition
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'elementor_attribute_condition_version' );
