<?php

/**
 * Class acf_field_google_font_selector
 */
class acf_field_google_font_selector extends acf_field {

	public $enqueue_fonts_option;
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	function __construct() {
		$api_key = ( defined( 'ACFGFS_API_KEY' ) ) ? ACFGFS_API_KEY : null;
		$refresh = ( defined( 'ACFGFS_REFRESH' ) ) ? ACFGFS_REFRESH : 259200;
		$option_name = ( defined( 'ACFGFS_OPTION_NAME' ) ) ? ACFGFS_OPTION_NAME : 'bonsai_wp_google_fonts';

		$this->bonsai_WP_Google_Fonts = new Bonsai_WP_Google_Fonts( $api_key, $refresh, $option_name );
		$this->common = new acf_google_font_selector_common(array(
			'bonsai_WP_Google_Fonts' => $this->bonsai_WP_Google_Fonts
		));

		$this->name = 'google_font_selector';
		$this->label = __('Google Font Selector', 'acf-google_font_selector');
		$this->category = 'choice';

		$this->defaults = array(
			'include_web_safe_fonts' => true,
			'enqueue_font'           => true,
			'default_font'           => 'Droid Sans',
		);

    	parent::__construct();

		add_action( 'wp_ajax_acfgfs_get_font_details', array( $this->common, 'action_get_font_details' ) );
		if( !defined( 'ACFGFS_NOENQUEUE' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this->common, 'google_font_enqueue' ) );
		}

	}


	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	function render_field_settings( $field ) {


		acf_render_field_setting( $field, array(
			'label'			=> __('Web Safe Fonts?','acf-google_font_selector'),
			'message'    	=> __('Include web safe fonts?','acf-google_font_selector'),
			'type'			=> 'true_false',
			'name'			=> 'include_web_safe_fonts',
			'layout'		=> 'horizontal',
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Enqueue Font?','acf-google_font_selector'),
			'message'    	=> __('Automaticallty load font?','acf-google_font_selector'),
			'type'			=> 'true_false',
			'name'			=> 'enqueue_font',
			'layout'		=> 'horizontal',
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Default Font','acf-google_font_selector'),
			'type'			=> 'select',
			'name'			=> 'default_font',
			'choices'       => $this->bonsai_WP_Google_Fonts->get_font_dropdown_array()
		));


	}


	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	function render_field( $field ) {
		/*
		*  Create a simple text input using the 'font_size' setting.
		*/
		$current_font_family = ( empty( $field['value'] ) ) ? $field['default_font'] : $field['value']['font'];


		?>
		<div class="acfgfs-font-selector">
			<div class="acfgfs-loader"></div>
			<div class="acfgfs-form-control acfgfs-font-family">
			<div class="acfgfs-form-control-title"><?php _e('Font Family', 'acf-google_font_selector') ?></div>

				<select name="<?php echo esc_attr($field['name']) ?>">
					<?php
						$options = $this->bonsai_WP_Google_Fonts->get_font_dropdown_array();
						foreach( $options as $option ) {
							echo '<option ' . selected( $option, $current_font_family ) . ' value="' . $option . '">' . $option . '</option>';
						}
					?>
				</select>
		</div>

		<div class="acfgfs-form-control acfgfs-font-variants">
				<div class="acfgfs-form-control-title"><?php _e('Variants', 'acf-google_font_selector') ?></div>
				<div class="acfgfs-list">
				<?php
					$font_variants = $this->bonsai_WP_Google_Fonts->get_font_variant_array( $current_font_family );
				$this->common->display_variant_list( $font_variants, $field );
				?>
				</div>

		</div>

		<div class="acfgfs-form-control acfgfs-font-subsets">
			<div class="acfgfs-form-control-title"><?php _e('Subsets', 'acf-google_font_selector') ?></div>
			<div class="acfgfs-list">

			<?php
			$font_subsets = $this->bonsai_WP_Google_Fonts->get_font_subset_array( $current_font_family );
			$this->common->display_subset_list( $font_subsets, $field );
			?>

			</div>

		</div>

			<textarea name="acfgfs-font-data" class="acfgfs-font-data"><?php echo json_encode( $field ) ?></textarea>

		</div>


		<?php
	}

	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/
	function input_admin_enqueue_scripts() {

		$dir = plugin_dir_url( __FILE__ );

		// register & include JS
		wp_register_script( 'acf-input-google_font_selector', "{$dir}js/input.js" );
		wp_enqueue_script('acf-input-google_font_selector');


		// register & include CSS
		wp_register_style( 'acf-input-google_font_selector', "{$dir}css/input.css" );
		wp_enqueue_style('acf-input-google_font_selector');


	}

	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	function update_value( $value, $post_id, $field ) {
		$new_value = array();
		$new_value['font'] = $value;

		if( empty( $_POST[$field['key'] . '_variants'] ) ) {
			$_POST[$field['key'] . '_variants'] = $this->bonsai_WP_Google_Fonts->get_font_variant_array( $new_value['font'] );
		}

		if( empty( $_POST[$field['key'] . '_subsets'] ) ) {
			$_POST[$field['key'] . '_subsets'] = $this->bonsai_WP_Google_Fonts->get_font_subset_array( $new_value['font'] );
		}

		$new_value['variants'] = $_POST[$field['key'] . '_variants'];
		$new_value['subsets'] = $_POST[$field['key'] . '_subsets'];
		return $new_value;
	}

}


// create field
new acf_field_google_font_selector();

?>
