<?php
/**
 * "Product Attribute Archive" display condition.
 *
 * Loaded only from the elementor/theme/register_conditions hook, when the
 * Elementor Pro base class is guaranteed to exist.
 *
 * @package Elementor_Attribute_Condition
 */

namespace Elementor_Attribute_Condition;

use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Matches the archive of any public product attribute, or of one attribute.
 *
 * Saved as product_archive/product_attribute_archive[/pa_<name>]. The name is
 * kept from version 1.x so that existing templates keep their conditions.
 * Per-term targeting (e.g. only "Color: Red") is provided natively by
 * Elementor Pro once attributes are exposed, under each attribute's own name.
 */
class Attribute_Archive_Condition extends Condition_Base {

	/**
	 * Condition group.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'archive';
	}

	/**
	 * Same priority as Elementor Pro's taxonomy conditions, so that a template
	 * for one attribute and a template for one category weigh the same.
	 *
	 * @return int
	 */
	public static function get_priority() {
		return 70;
	}

	/**
	 * Internal name, stored in the template conditions.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'product_attribute_archive';
	}

	/**
	 * Label in the conditions screen.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Product Attribute Archive', 'elementor-attribute-condition' );
	}

	/**
	 * The attribute picker, shown by Elementor as the third select of the row.
	 */
	protected function register_controls() {
		$options = [
			'' => __( 'All attributes', 'elementor-attribute-condition' ),
		];

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
			if ( taxonomy_exists( $taxonomy ) && is_taxonomy_viewable( $taxonomy ) ) {
				$options[ $taxonomy ] = wc_attribute_label( $taxonomy );
			}
		}

		$this->add_control(
			'attribute',
			[
				'section' => 'settings',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
			]
		);
	}

	/**
	 * Whether the current request is the archive of the chosen attribute.
	 *
	 * @param array $args Elementor passes the saved attribute in $args['id'];
	 *                    empty means any attribute.
	 * @return bool
	 */
	public function check( $args ) {
		if ( ! is_tax() ) {
			return false;
		}

		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term || ! taxonomy_is_product_attribute( $term->taxonomy ) ) {
			return false;
		}

		$target = isset( $args['id'] ) && is_string( $args['id'] ) ? $args['id'] : '';

		return '' === $target || $term->taxonomy === $target;
	}
}
