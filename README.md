# ACF - Google Font Selector Field

A field for Advanced Custom Fields which allows users to select Google fonts with advanced options


## Description

This plugin allows you to create a Google font selector field with different options. The plugin also creates the font request in the theme header to autoload the fonts if you'd like. Font variants and charsets can be selected separately to make font loading more flexible and optimized.

Font options added to any options page will always be enqueued. Any fonts added to post pages will only be enqueued when that specific post is displayed.

#### Thanks

- [Advanced Custom Fields](http://www.advancedcustomfields.com/) for the awesome base plugin.
- [Iconjam](https://www.iconfinder.com/Icojam) for the T icon.

#### Translations

The plugin is currently available in English and Hungarian. Please feel free to submit any new languages via a pull request, I'd be mighty thankful.

#### Useful Links

This Github repository is for the development of this plugin. If you would like to read installation and in-depth usage instructions you might want to look at the WordPress plugin page instead.

- [Plugin Page](https://wordpress.org/plugins/acf-google-font-selector-field/installation/)
- [SVN Repository](http://plugins.svn.wordpress.org/acf-google-font-selector-field/)
- [ACF Plugin](https://wordpress.org/plugins/advanced-custom-fields/)
- [ACF Home Page](http://www.advancedcustomfields.com/)
- [ACF Documentation](http://www.advancedcustomfields.com/resources/)
- [ACF Field Template](https://github.com/elliotcondon/acf-field-type-template)
- [ACF on Github](https://github.com/elliotcondon/acf)


# For Developers

There are a few more advanced controls you can set to make the plugin do your bidding. If you would like to hard-code the API key and disable users from seeing the nag screen and setting panel you can define the `ACFGFS_API_KEY` constant.

`define( 'ACFGFS_API_KEY', 'your_google_api_key' );`


The `ACFGFS_REFRESH` constant can controls how frequently the plugin checks the Google API for updates. The value is in seconds, 86400 would be a day. The default is set to 7 days.

`define( 'ACFGFS_REFRESH', 259200 );`

If you would like to disable the automatic enqueueing of fonts you can use the `ACFGFS_NOENQUEUE` constant. The fonts are only enqueued automatically when this constant is not defined. Define the constant to disable enqueueing.

`define( 'ACFGFS_NOENQUEUE', true );`

If you want to modify the fonts that are loaded on a page you can use the `acfgfs/enqueued_fonts` filter. This should return an array of fonts with variants and subsets needed, something like this:

`array(
    'font' => 'Open Sans',
    'variants' => array( 'regular', '700' ),
    'subsets' => array( 'latin' )
)`

New in 3.0.1 is the ability to control the fonts displayed in the dropdown. If you only want to give your users access to a smaller portion of Google fonts you can use the `acfgfs/font_dropdown_array` filter to modify the array that is used to generate the dropdown. Please return an array where the key and the value are both the names of the font.

`add_filter( 'acfgfs/font_dropdown_array', 'my_font_list' )
function my_font_list( $fonts ) {
    $fonts = array(
        'Raleway' => 'Raleway',
        'Lato' => 'Lato'
    );
    return $fonts;
}
`


# Want To Help?

If you like the plugin and you like helping others out there are a few things you can do:

- **[Buy ACF Pro](http://www.advancedcustomfields.com/pro/)**
- **[Review the plugin](https://wordpress.org/support/view/plugin-reviews/acf-google-font-selector-field)**
- **Submit a translation** If you speak another language goodly, you can submit a language file, I'd be mighty thankful! Take a look at the lang directory to see what languages we already have. If a language isn't there create one and submit a pull request. If you have no idea what I'm talking about drop me a line and I'll help you out
- **[Tip me on Gratipay](https://gratipay.com/danielpataki/)**
