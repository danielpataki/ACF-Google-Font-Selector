# ACF - Google Font Selector Field

Welcome to the Google font selector for Advanced custom fields. For information about the base plugin which is required for the role selector to work, take a look at [ACF on Github](https://github.com/elliotcondon/acf) or the official [ACF Homepage](http://www.advancedcustomfields.com/).

The plugin allows you to create a Google font selector field with different options. The plugin also created the font request in the theme header (unless disabled). Font variants and charsets can be selected separately to make font loading more flexible and optimized.

-----------------------

Once installed you should define a constant for the Google API key used to retrieve fronts from the Google API. By default the request will use my API key but if many people start using the plugin it may get rate limited. Plese define `ACF_GOOGLE_FONTS_API_KEY` somewhere. It's super easy to get an API key, just head on over to the [Google API Console](http://cloud.google.com/console), create a new project and get a browser api key.

```php
define( 'ACF_GOOGLE_FONTS_API_KEY', 'your_google_api_key' );
```

If the `ACF_GOOGLE_FONTS_DISABLE_HEADER` constant is defined as true the Google font request will not be added to the theme header. If it is defined as false or is not defined the font request will be added

```php
define( 'ACF_GOOGLE_FONTS_DISABLE_HEADER', true );
```

-----------------------

* Readme : https://github.com/danielpataki/acf-role_selector/blob/master/readme.txt
* WordPress repository: http://wordpress.org/plugins/acf-role-selector-field/

-----------------------

Special thanks to [Elliot Condon](http://elliotcondon.com) for making the wonderful [ACF plugin](advancedcustomfields.com) in the first place.