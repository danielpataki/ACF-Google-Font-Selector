<?php

/*
*  Functionality common to both versions
*/
class acf_google_font_selector_common {

    /*
    *  Reference to the font handling class
    */
    var $bonsai_WP_Google_Fonts;

    /*
    *  Constructor function
    *
    *  Pulls the font class in for reference
    *
    *  @param array $args Contains setup parameters
    *  @return void
    *
    */
    function __construct( $args ) {
        $this->bonsai_WP_Google_Fonts = $args['bonsai_WP_Google_Fonts'];
    }

    /*
    *  Gets the fonts to enqueue
    *
    *  Pulls all fonts set in options pages and looks for post specific
    *  ones if on a singular page.
    *
    *  @return array Font fields to enqueue
    *
    */
    function get_fonts_to_enqueue() {
        if( is_singular() ) {
            global $post;
            $post_fields = get_field_objects( $post->ID );
        }

        $post_fields = ( empty( $post_fields ) ) ? array() : $post_fields;

        $option_fields = get_field_objects( 'options' );
        $option_fields = ( empty( $option_fields ) ) ? array() : $option_fields;

        $fields = array_merge( $post_fields, $option_fields );
        $font_fields = array();

        foreach( $fields as $field ) {
            if( !empty( $field['type'] ) && 'google_font_selector' == $field['type'] && !empty( $field['value'] ) ) {
                $font_fields[] = $field['value'];
            }
        }

        return $font_fields;
    }

    /*
    *  Enqueue Fonts
    *
    *  Builds the Google Font query and adds it to the header.
    *
    *  @uses $this->get_fonts_to_enqueue();
    *  @return void
    *
    */
	function google_font_enqueue(){
        $fonts = $this->get_fonts_to_enqueue();

        if( empty( $fonts ) ) {
            return;
        }

		$subsets = array();
		$font_element = array();
		foreach( $fonts as $font ) {
			$subsets = array_merge( $subsets, $font['subsets'] );
			$font_name = str_replace( ' ', '+', $font['font'] );
			if( $font['variants'] == array( 'regular' ) ) {
				$font_element[] = $font_name;
			}
			else {
				$regular_variant = array_search( 'regular', $font['variants'] );
				if( $regular_variant !== false ) {
					$font['variants'][$regular_variant] = '400';
				}
				$font_element[] = $font_name . ':' . implode( ',', $font['variants'] );
			}
		}
		$subsets = ( empty( $subsets ) ) ? array('latin') : array_unique( $subsets );
		$subset_string = implode( ',', $subsets );

		$font_string = implode( '|', $font_element );

		$subset_string = '';
		$request = '//fonts.googleapis.com/css?family=' . $font_string . '&subset=' . $subset_string;

		wp_enqueue_style( 'acfgfs-enqueue-fonts', $request );


	}


	/**
	 * Display a variant list for a font
	 *
	 * @param array $variants variant list
	 * @param array $field field to display for
     *
     * @return void
	 */
	function display_variant_list( $variants = array(), $field ) {
		$i = 1;
		foreach( $variants as $variant ) :
			$checked = ( empty( $field['value'] ) || ( !empty( $field['value'] ) && in_array( $variant, $field['value']['variants'] ) ) ) ? 'checked="checked"' : '';
			?>

			<input <?php echo $checked ?> type="checkbox" id="<?php echo $field['key'] ?>_variants_<?php echo $i ?>" name="<?php echo $field['key'] ?>_variants[]" value="<?php echo $variant ?>"><label for="<?php echo $field['key'] ?>_variants_<?php echo $i ?>"><?php echo $variant ?></label> <br>

			<?php $i++; endforeach;

	}


	/**
	 * Displays a list of subsets for a font
	 *
	 * @param array $subsets array of subsets
	 * @param array $field field to display subsets for
     *
     * @return void
	 */
	function display_subset_list( $subsets = array(), $field ) {
		$i = 1;
		foreach( $subsets as $subset ) :
			$checked = ( empty( $field['value'] ) || ( !empty( $field['value'] ) && in_array( $subset, $field['value']['subsets'] ) ) ) ? 'checked="checked"' : '';
			?>
			<input <?php echo $checked ?> type="checkbox" id="<?php echo $field['key'] ?>_subsets_<?php echo $i ?>" name="<?php echo $field['key'] ?>_subsets[]" value="<?php echo $subset ?>"><label for="<?php echo $field['key'] ?>_subsets_<?php echo $i ?>"><?php echo $subset ?></label> <br>

			<?php $i++; endforeach;

	}


    /*
    *  Get Font Detais
    *
    *  Displays selectable font details to the user.
    *
    *  @return void
    *
    */
	function action_get_font_details() {
		$details = array();
		$field = json_decode( stripslashes( $_POST['data'] ), true );
		unset( $field['value'] );
		$subsets = $this->bonsai_WP_Google_Fonts->get_font_subset_array( $_POST['font_family'] );
		$variants = $this->bonsai_WP_Google_Fonts->get_font_variant_array( $_POST['font_family'] );
		ob_start();
		$this->display_subset_list( $subsets, $field );
		$details['subsets'] = ob_get_clean();

		ob_start();
		$this->display_variant_list( $variants, $field );
		$details['variants'] = ob_get_clean();

		echo json_encode( $details );

		die();
	}



}

?>
