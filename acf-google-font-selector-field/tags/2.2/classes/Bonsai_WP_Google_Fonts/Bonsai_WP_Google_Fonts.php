<?php
/**
 * Class BonsaiGoogleFonts
 */
class Bonsai_WP_Google_Fonts {

	/**
	 * @var null Google API Key
	 */
	private $api_key;

	/**
	 * @var int Refresh Period
	 */
	private $refresh;
	/**
	 * @var string Option Name
	 */
	private $option_name;
	/**
	 * @var array font data
	 */
	private $data;

	/**
	 * @param null $api_key
	 * @param int $refresh
	 */
	function __construct( $api_key = null, $refresh = 259200, $option_name = 'bonsai_wp_google_fonts' ) {
		$this->api_key     = $api_key;
		$this->refresh     = $refresh;
		$this->option_name = $option_name;

		if ( ! $this->is_installed() ) {
			$this->install();
		}

		$this->set_data();

		if ( $this->should_update() ) {
			$this->update();
		}

	}

	function is_installed() {
		$data = get_option( $this->option_name );

		return ! empty( $data ) ? true : false;
	}

	function install() {
		$data = json_decode( include( 'resources/fontlist.php' ), true );
		update_option( $this->option_name, $data );
	}

	function should_update() {
		if( time() - $this->data['generated'] >= $this->refresh ) {
			return true;
		}

		return false;
	}

	function update() {
		$fonts = $this->retrieve_raw_data();
		$data = $this->format_raw_data( $fonts );
		if( !empty( $data['error'] ) ) {
			$this->data['generated'] = time();
		}
		else {
			$this->data = $data;
		}

		$this->save_data();

	}

	function retrieve_raw_data() {
		$fonts = wp_remote_get( 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $this->api_key );
		return $fonts;
	}

	function format_raw_data( $fonts ) {
		$fonts = json_decode( $fonts['body'], true );
		if( !empty( $fonts['error'] ) ) {
			return $fonts;
		}

		unset( $fonts['kind']);
		$fonts = $fonts['items'];
		$i = 0;
		foreach( $fonts as $item ) {
			unset( $fonts[$i]['kind'] );
			unset( $fonts[$i]['category'] );
			unset( $fonts[$i]['version'] );
			unset( $fonts[$i]['lastModified'] );
			unset( $fonts[$i]['files'] );
			$i++;
		}


		$data['fonts'] = $fonts;
		$data['generated'] = time();

		foreach( $data['fonts'] as $i => $font ) {
			$data['fonts'][$font['family']] = $font;
			unset( $data['fonts'][$i] );
		}

		return $data;
	}

	function set_data() {
		$this->data = get_option( $this->option_name );
		if( empty( $this->data ) || empty( $this->data['generated'] ) ) {
			$this->data = array( 'generated' => 0, 'fonts' => array() );
		}
	}

	function save_data() {
		update_option( $this->option_name, $this->data );
	}

	function get_font_dropdown_array() {
		$options = array();
		foreach( $this->data['fonts'] as $font ) {
			$options[$font['family']] = $font['family'];
		}

		return $options;
	}

	function get_font_variant_array( $font ) {
		$variants = array();
		if( !empty( $this->data['fonts'][$font]['variants'] ) ) {
			$variants = $this->data['fonts'][$font]['variants'];
		}
		return $variants;
	}

	function get_font_subset_array( $font ) {
		$subsets = array();
		if( !empty( $this->data['fonts'][$font]['subsets'] ) ) { 		
			$subsets = $this->data['fonts'][$font]['subsets'];
		}
		return $subsets;
	}


}
?>
