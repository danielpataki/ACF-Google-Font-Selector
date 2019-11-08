<?php

class acf_field_google_font_selector extends acf_Field
{

	// vars
	var $settings,
		$defaults,
		$api_key,
		$fonts,
		$web_safe;


	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*	- This function is called when the field class is initalized on each page.
	*	- Here you can add filters / actions and setup any other functionality for your field
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	*
	*-------------------------------------------------------------------------------------*/

	function __construct($parent)
	{

		parent::__construct($parent);

		// vars
		$this->name = 'google_font_selector';
		$this->label = __( 'Google Font Selector', 'acf' );
		$this->category = __( "Choice",'acf' );
		$this->defaults = array(
			'include_web_safe' => true,
			'default_font' => 'Droid Sans',
			'interface' => 'advanced'
		);

		parent::__construct();

		$api_key = ( defined( 'ACF_GOOGLE_FONTS_API_KEY' ) ) ? ACF_GOOGLE_FONTS_API_KEY : 'AIzaSyDprvtcGk0jQhIAIr0CdW7g57A5eRyesrc';
		$this->api_key = $api_key;
		$this->fonts = $this->get_google_fonts();
		$this->web_safe = $this->set_web_safe();

		$this->settings = array(
			'path' => apply_filters('acf/helpers/get_path', __FILE__),
			'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
			'version' => '1.0.0'
		);

		add_action( 'wp_ajax_acfgfs_get_font_data', array( $this, 'action_get_font_data' ) );

		if( !( defined( 'ACF_GOOGLE_FONTS_DISABLE_HEADER' ) && true == ACF_GOOGLE_FONTS_DISABLE_HEADER ) ) {
			add_action( 'wp_head', array( $this, 'google_font_request' ) );
		}

  }


	/*
	*  Build Google Font Request
	*
	*  Retrieves all font settings and builds a Google Font query
	*
	*/
	function google_font_request(){
		global $wpdb;

		$font_string = '%:{s:4:"font"%';
		$postmeta_fonts = $wpdb->get_col($wpdb->prepare(
			"SELECT DISTINCT(meta_value) FROM $wpdb->postmeta WHERE meta_value LIKE %s",
			$font_string
		));

		$usermeta_fonts = $wpdb->get_col($wpdb->prepare(
			"SELECT DISTINCT(meta_value) FROM $wpdb->usermeta WHERE meta_value LIKE %s",
			$font_string
		));

		$option_fonts = $wpdb->get_col($wpdb->prepare(
			"SELECT DISTINCT(meta_value) FROM $wpdb->options WHERE option_value LIKE %s",
			$font_string
		));

		$fontmeta = array_merge( $postmeta_fonts, $usermeta_fonts, $option_fonts );

		$fonts = array();
		foreach( $fontmeta as $font ) {
			$fonts[] = unserialize( $font );
		}

		$fontlist = array();
		foreach( $fonts as $font ) {
			if( !empty( $fontlist[$font['font']] ) ) {
				$fontlist[$font['font']]['variants'] = ( empty( $fontlist[$font['font']]['variants'] ) ) ? array() : $fontlist[$font['font']]['variants'];
				$fontlist[$font['font']]['subsets'] = ( empty( $fontlist[$font['font']]['subsets'] ) ) ? array() : $fontlist[$font['font']]['subsets'];
				$fontlist[$font['font']]['variants'] = array_merge( $fontlist[$font['font']]['variants'], $font['variants'] );
				$fontlist[$font['font']]['subsets'] = array_merge( $fontlist[$font['font']]['subsets'], $font['subsets'] );
				$fontlist[$font['font']]['variants'] = array_unique( $fontlist[$font['font']]['variants'] );
				$fontlist[$font['font']]['subsets'] = array_unique( $fontlist[$font['font']]['subsets'] );

				foreach( $fontlist[$font['font']]['variants'] as $key => $variant ) {
					if( 'regular' == $variant ) {
						$fontlist[$font['font']]['variants'][$key] = '400';
					}
					if( 'italic' == $variant ) {
						$fontlist[$font['font']]['variants'][$key] = '400italic';
					}
				}

				if( !in_array( 'regular', $fontlist[$font['font']]['variants'] ) ) {
					$fontlist[$font['font']]['variants'][] = '400';
				}

			}
			else {
				$fontlist[$font['font']] = $font;
			}
		}

		$fonts = array();
		$subsets = array();
		foreach( $fontlist as $name => $data ) {
			if( !in_array( $name, $this->web_safe ) ) {
				$name = str_replace( ' ', '+', $name );
				if( empty( $data['variants'] ) ) {
					$variants = ':300,400,400italic,700';
				}
				else {
					$variants = ':' . implode( ',', $data['variants'] );
				}
				$subsets[] = array_merge( $data['subsets'] );
				$fonts[] = $name . $variants;
			}
		}

		$subsets = array_unique( $subsets );
		$subsets = ( !empty( $subsets ) ) ? '?subset=' . implode( ', ', $subsets ) : '';
		$request = "//fonts.googleapis.com/css?family=" . implode( '|', $fonts ) . $subsets;

		echo "<link href='" . $request . "' rel='stylesheet' type='text/css'>";


	}

	/*
	*  Retrieve Font Data
	*
	*  Used when a user selects a new font to retrieve variants and sets
	*
	*/
	function action_get_font_data() {
		$results['variants'] = $this->get_font_variant_options( $_POST['field_name'], $_POST['font'] );
		$results['charsets'] = $this->get_font_charset_options( $_POST['field_name'], $_POST['font'] );

		echo json_encode( $results );
		die();
	}

	/*
	*  Get Google Font Data
	*
	*  Loads all Google Font Data from transient or grabs it from the api
	*
	*/
	function get_google_fonts() {
		$fonts = get_transient( 'acf_google_font_selector_fonts' );
		if( !$fonts ) {
			$fonts = $this->set_google_fonts();
		}
		return $fonts;
	}

	/*
	*  Retrieve Google Fonts From API
	*
	*  Sets a transient based on a request to the Google API
	*
	*/
	function set_google_fonts() {
		$data = wp_remote_get( 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $this->api_key );
		$data = json_decode( $data['body'], true );
		$data = $data['items'];
		foreach( $data as $key => $item ) {
			unset( $data[$key] );
			$data[$item['family']] = $item;
		}

		set_transient( 'acf_google_font_selector_fonts', $data, WEEK_IN_SECONDS );
	}

	/*
	*  Build Font Select List
	*
	*  Builts a list of fonts either in array or ready to go '<option>' form
	*
	*/
	function get_font_select_list( $field, $output = 'array' ) {
		$list = array();
		foreach( $this->fonts as $font => $data ) {
			$list[$font] = $font;
		}

		if( !empty( $field['include_web_safe' ] ) ) {
			foreach( $this->web_safe as $font ) {
				$list[$font] = $font;
			}
		}

		asort( $list );

		if( $output == 'options' ) {
			$options = array();
			$current = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :  $field['value']['font'];
			foreach( $list as $item ) {
				$selected = ( $current == $item ) ? 'selected="selected"' : '';
				$options[] = '<option ' . $selected . ' value="' . $item . '">' . $item . '</option>';
			}
			$list = implode( '', $options );
		}

		return $list;
	}


	/*
	*  Get Font Variants
	*
	*  Finds the variants available for a specific font
	*
	*/
	function get_font_variant_options( $field, $font = '' ) {
		if( is_array( $field ) ) {
			$font = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :  $field['value']['font'];
			$name = $field['name'];
			$selected_variants = ( empty( $field['value']['variants'] ) ) ? '' : $field['value']['variants'];
		}
		else {
			$name = $field;
		}

		$font = ( !empty( $this->fonts[$font] ) ) ? $this->fonts[$font] : 'web-safe';
		if( !is_array( $font ) ) {
			return;
		}


		$output = array();
		foreach( $font['variants'] as $variant ) {
			$checked = ( !empty( $selected_variants ) && in_array( $variant, $selected_variants ) ) ? 'checked="checked"' : '';
			$output[] = '<li><label><input ' . $checked . ' type="checkbox" class="checkbox" name="' . $name . '[variants][]" value="' . $variant . '">' . $variant . '</label></li>';
		}

		return implode( '', $output );
	}

	/*
	*  Get Font Charsets
	*
	*  Finds the charsets available for a specific font
	*
	*/
	function get_font_charset_options( $field, $font = '' ) {
		if( is_array( $field ) ) {
			$font = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :  $field['value']['font'];
			$name = $field['name'];
			$selected_subsets = ( empty( $field['value']['subsets'] ) ) ? '' : $field['value']['subsets'];
		}
		else {
			$name = $field;
		}

		$font = ( !empty( $this->fonts[$font] ) ) ? $this->fonts[$font] : 'web-safe';
		if( !is_array( $font ) ) {
			return;
		}

		$output = array();
		foreach( $font['subsets'] as $subset ) {
			$checked = ( !empty( $selected_subsets ) && in_array( $subset, $selected_subsets ) ) ? 'checked="checked"' : '';
			$output[] = '<li><label><input ' . $checked . ' type="checkbox" class="checkbox" name="' . $field['name'] . '[subsets][]" value="' . $subset . '">' . $subset . '</label></li>';
		}

		return implode( '', $output );

	}

	/*
	*  Set web safe fonts
	*
	*  Sets all fonts which do not need to be pulled from Google
	*
	*/
	function set_web_safe() {
		$web_safe = array( 'Georgia', 'Palatino Linotype', 'Book Antiqua', 'Palatino', 'Times New Roman', 'Times', 'Arial', 'Helvetica', 'Arial Black', 'Gadget', 'Impact', 'Charcoal', 'Lucida Sans Unicode', 'Lucida Grande', 'Tahoma', 'Geneva', 'Trebuchet MS', 'Helvetica', 'Verdana', 'Geneva', 'Courier New', 'Courier', 'Lucida Console', 'Monaco' );
		return $web_safe;
	}


 	/*
  *  helpers_get_path
  *
  *  @description: calculates the path (works for plugin / theme folders)
  *  @since: 3.6
  *  @created: 30/01/13
  */

  function helpers_get_path($file)
  {
    return trailingslashit(dirname($file));
  }


  /*
  *  helpers_get_dir
  *
  *  @description: calculates the directory (works for plugin / theme folders)
  *  @since: 3.6
  *  @created: 30/01/13
  */

  function helpers_get_dir($file)
  {
    $dir = trailingslashit(dirname($file));
    $count = 0;


    // sanitize for Win32 installs
    $dir = str_replace('\\', '/', $dir);


    // if file is in plugins folder
    $wp_plugin_dir = str_replace('\\', '/', WP_PLUGIN_DIR);
    $dir = str_replace($wp_plugin_dir, WP_PLUGIN_URL, $dir, $count);


    if($count < 1)
    {
      // if file is in wp-content folder
      $wp_content_dir = str_replace('\\', '/', WP_CONTENT_DIR);
      $dir = str_replace($wp_content_dir, WP_CONTENT_URL, $dir, $count);
    }


    if($count < 1)
    {
      // if file is in ??? folder
      $wp_dir = str_replace('\\', '/', ABSPATH);
      $dir = str_replace($wp_dir, site_url('/'), $dir);
    }

    return $dir;
  }


	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*	- this function is called from core/field_meta_box.php to create extra options
	*	for your field
	*
	*	@params
	*	- $key (int) - the $_POST obejct key required to save the options to the field
	*	- $field (array) - the field object
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	*
	*-------------------------------------------------------------------------------------*/

	function create_options($key, $field)
	{
		$field = array_merge($this->defaults, $field);


		// key is needed in the field names to correctly save the data
		$key = $field['name'];


		// Create Field Options HTML
		?>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Web Safe Fonts", 'acf'); ?></label>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'    =>  'true_false',
			'name'    =>  'fields[' . $key . '][include_web_safe]',
			'value'   =>  $field['include_web_safe'],
			'layout'  =>  'horizontal',
			'message'   =>  __( 'Include Web Safe Fonts?', 'acf' )
		));

		?>
	</td>
</tr>

<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Default Font", 'acf'); ?></label>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'    =>  'select',
			'name'    =>  'fields[' . $key . '][default_font]',
			'value'   =>  $field['default_font'],
			'layout'  =>  'horizontal',
			'choices' =>  $this->get_font_select_list( $field )
		));

		?>
	</td>
</tr>

<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("Interface", 'acf'); ?></label>
		<p class="description"><?php _e("The advanced interface allows users to set allowed font variants and character sets. If set to simple the most common options are loaded.", 'acf'); ?></p>
	</td>
	<td>
		<?php

		do_action('acf/create_field', array(
			'type'    =>  'radio',
			'name'    =>  'fields[' . $key . '][interface]',
			'value'   =>  $field['interface'],
			'layout'  =>  'horizontal',
			'choices' =>  array(
				'advanced' => 'Advanced',
				'simple'   => 'Simple'
			)
		));

		?>
	</td>
</tr>

		<?php
	}



	/*--------------------------------------------------------------------------------------
	*
	*	create_field
	*	- this function is called on edit screens to produce the html for this field
	*
	*	@author Elliot Condon
	*	@since 2.2.0
	*
	*-------------------------------------------------------------------------------------*/

	function create_field( $field )
	{
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/

		// perhaps use $field['preview_size'] to alter the markup?


		// create Field HTML

		$current = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :  $field['value']['font'];
		?>
		<div>
			<table class='acf-google-font-table'>
				<tr>
					<td>
						<div class='label'><?php _e( 'Font Family', 'acf' ) ?></div>
						<select data-field_name='<?php echo $field['name'] ?>' class='acfgfs-font-select' name='<?php echo $field['name'] ?>[font]'>
							<?php echo $this->get_font_select_list( $field, 'options'  ) ?>
						</select>
					</td>

					<?php if( !empty( $field['interface'] ) && 'advanced' == $field['interface'] ) : ?>

					<td class='acfgfs-font-variants'>
						<div class='label'><?php _e( 'Font Variants', 'acf' ) ?></div>
						<ul class="acf-checkbox-list checkbox vertical">
							<?php echo $this->get_font_variant_options( $field ) ?>
						</ul>
					</td>
					<td class='acfgfs-font-charsets'>
						<div class='label'><?php _e( 'Character Sets', 'acf' ) ?></div>
						<ul class="acf-checkbox-list checkbox vertical">
							<?php echo $this->get_font_charset_options( $field ) ?>
						</ul>
					</td>
					<?php endif ?>
				</tr>
			</table>

		</div>
		<?php
	}


	/*--------------------------------------------------------------------------------------
	*
	*	admin_print_scripts / admin_print_styles
	*	- this function is called in the admin_print_scripts / admin_print_styles where
	*	your field is created. Use this function to register css and javascript to assist
	*	your create_field() function.
	*
	*	@author Elliot Condon
	*	@since 3.0.0
	*
	*-------------------------------------------------------------------------------------*/

	function admin_print_scripts()
	{
		// Note: This function can be removed if not used


		// register acf scripts
		wp_register_script('acf-input-google_font_selector', $this->settings['dir'] . 'js/input.js', array('acf-input'), $this->settings['version']);

		// scripts
		wp_enqueue_script(array(
			'acf-input-google_font_selector',
		));

		wp_localize_script( 'acf-input-google_font_selector', 'acfgfs', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'loading' => 'LOADING...' ) );


	}

	function admin_print_styles()
	{
		wp_register_style('acf-input-google_font_selector', $this->settings['dir'] . 'css/input.css', array('acf-input'), $this->settings['version']);

		wp_enqueue_style(array(
			'acf-input-google_font_selector',
		));

	}

}

?>
