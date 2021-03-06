<?php

namespace Limber;

use WP_Query;
use WP_Rewrite;
use WP;
use WP_Widget_Factory;
use WP_Roles;
use WP_Locale;
use WP_Error;

class Loader {
  public $wpconfig = '../../web/wp-config.php';
  public $plugins   = array();

  public function __construct( $options = array() ) {
    // tell wpconfig Limber is being used
    define( 'LIMBER', true );
    // get the wp-config
    define( 'SHORTINIT', true );

    if ( isset( $options['wpconfig'] ) ) {
      $this->wpconfig = $options['wpconfig'];
    }

    // requiring the wp-config makes sure
    require $this->wpconfig;

    // Define constants that rely on the API to obtain the default value.
    // Define must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
    wp_plugin_directory_constants();
  }

  public function load() {

    // Load the L10n library.
    require_once ABSPATH . WPINC . '/l10n.php';

    // Run the installer if WordPress is not installed.
    wp_not_installed();

    // Load most of WordPress.
    require ABSPATH . WPINC . '/class-wp-walker.php';
    require ABSPATH . WPINC . '/class-wp-ajax-response.php';
    require ABSPATH . WPINC . '/formatting.php';
    require ABSPATH . WPINC . '/capabilities.php';
    require ABSPATH . WPINC . '/query.php';
    require ABSPATH . WPINC . '/date.php';
    require ABSPATH . WPINC . '/theme.php';
    require ABSPATH . WPINC . '/class-wp-theme.php';
    require ABSPATH . WPINC . '/template.php';
    require ABSPATH . WPINC . '/user.php';
    require ABSPATH . WPINC . '/session.php';
    require ABSPATH . WPINC . '/meta.php';
    require ABSPATH . WPINC . '/general-template.php';
    require ABSPATH . WPINC . '/link-template.php';
    require ABSPATH . WPINC . '/author-template.php';
    require ABSPATH . WPINC . '/post.php';
    require ABSPATH . WPINC . '/post-template.php';
    require ABSPATH . WPINC . '/revision.php';
    require ABSPATH . WPINC . '/post-formats.php';
    require ABSPATH . WPINC . '/post-thumbnail-template.php';
    require ABSPATH . WPINC . '/category.php';
    require ABSPATH . WPINC . '/category-template.php';
    require ABSPATH . WPINC . '/comment.php';
    require ABSPATH . WPINC . '/comment-template.php';
    require ABSPATH . WPINC . '/rewrite.php';
    require ABSPATH . WPINC . '/feed.php';
    require ABSPATH . WPINC . '/bookmark.php';
    require ABSPATH . WPINC . '/bookmark-template.php';
    require ABSPATH . WPINC . '/kses.php';
    require ABSPATH . WPINC . '/cron.php';
    require ABSPATH . WPINC . '/deprecated.php';
    require ABSPATH . WPINC . '/script-loader.php';
    require ABSPATH . WPINC . '/taxonomy.php';
    require ABSPATH . WPINC . '/update.php';
    require ABSPATH . WPINC . '/canonical.php';
    require ABSPATH . WPINC . '/shortcodes.php';
    require ABSPATH . WPINC . '/class-wp-embed.php';
    require ABSPATH . WPINC . '/media.php';
    require ABSPATH . WPINC . '/http.php';
    require ABSPATH . WPINC . '/class-http.php';
    require ABSPATH . WPINC . '/widgets.php';
    require ABSPATH . WPINC . '/nav-menu.php';
    require ABSPATH . WPINC . '/nav-menu-template.php';
    require ABSPATH . WPINC . '/admin-bar.php';

    // Load multisite-specific files.
    if ( is_multisite() ) {
      require ABSPATH . WPINC . '/ms-functions.php';
      require ABSPATH . WPINC . '/ms-default-filters.php';
      require ABSPATH . WPINC . '/ms-deprecated.php';
    }

    $GLOBALS['wp_plugin_paths'] = array();

    // Load must-use plugins.
    foreach ( wp_get_mu_plugins() as $mu_plugin ) {
      include_once $mu_plugin;
    }
    unset( $mu_plugin );

    // Load network activated plugins.
    if ( is_multisite() ) {
      foreach ( wp_get_active_network_plugins() as $network_plugin ) {
        wp_register_plugin_realpath( $network_plugin );
        include_once $network_plugin;
      }
      unset( $network_plugin );
    }

    /**
     * Fires once all must-use and network-activated plugins have loaded.
     *
     * @since 2.8.0
     */
    do_action( 'muplugins_loaded' );

    if ( is_multisite() )
      ms_cookie_constants(  );

    // Define constants after multisite is loaded. Cookie-related constants may be overridden in ms_network_cookies().
    wp_cookie_constants();

    // Define and enforce our SSL constants
    wp_ssl_constants();

    // Create common globals.
    require ABSPATH . WPINC . '/vars.php';

    // Make taxonomies and posts available to plugins and themes.
    // @plugin authors: warning: these get registered again on the init hook.
    create_initial_taxonomies();
    create_initial_post_types();

    // Register the default theme directory root
    register_theme_directory( get_theme_root() );

    // [LIMBER START]
    // Load active plugins.
    // foreach ( wp_get_active_and_valid_plugins() as $plugin ) {
    //     wp_register_plugin_realpath( $plugin );
    //     include_once( $plugin );
    // }
    // unset( $plugin );
    $this->load_plugins();
    // [LIMBER END]

    // Load pluggable functions.
    require ABSPATH . WPINC . '/pluggable.php';
    require ABSPATH . WPINC . '/pluggable-deprecated.php';

    // Set internal encoding.
    wp_set_internal_encoding();

    // Run wp_cache_postload() if object cache is enabled and the function exists.
    if ( WP_CACHE && function_exists( 'wp_cache_postload' ) )
      wp_cache_postload();

    /**
     * Fires once activated plugins have loaded.
     *
     * Pluggable functions are also available at this point in the loading order.
     *
     * @since 1.5.0
     */
    do_action( 'plugins_loaded' );

    // Define constants which affect functionality if not already defined.
    wp_functionality_constants();

    // Add magic quotes and set up $_REQUEST ( $_GET + $_POST )
    wp_magic_quotes();

    /**
     * Fires when comment cookies are sanitized.
     *
     * @since 2.0.11
     */
    do_action( 'sanitize_comment_cookies' );

    /**
     * WordPress Query object
     *
     * @global object $wp_the_query
     * @since 2.0.0
     */
    $GLOBALS['wp_the_query'] = new WP_Query();

    /**
     * Holds the reference to @see $wp_the_query
     * Use this global for WordPress queries
     *
     * @global object $wp_query
     * @since 1.5.0
     */
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

    /**
     * Holds the WordPress Rewrite object for creating pretty URLs
     *
     * @global object $wp_rewrite
     * @since 1.5.0
     */
    $GLOBALS['wp_rewrite'] = new WP_Rewrite();

    /**
     * WordPress Object
     *
     * @global object $wp
     * @since 2.0.0
     */
    $GLOBALS['wp'] = new WP();

    /**
     * WordPress Widget Factory Object
     *
     * @global object $wp_widget_factory
     * @since 2.8.0
     */
    $GLOBALS['wp_widget_factory'] = new WP_Widget_Factory();

    /**
     * WordPress User Roles
     *
     * @global object $wp_roles
     * @since 2.0.0
     */
    $GLOBALS['wp_roles'] = new WP_Roles();

    /**
     * Fires before the theme is loaded.
     *
     * @since 2.6.0
     */
    do_action( 'setup_theme' );

    // Define the template related constants.
    wp_templating_constants(  );

    // Load the default text localization domain.
    load_default_textdomain();

    $locale = get_locale();
    $locale_file = WP_LANG_DIR . "/$locale.php";
    if ( ( 0 === validate_file( $locale ) ) && is_readable( $locale_file ) )
      require $locale_file;
    unset( $locale_file );

    // Pull in locale data after loading text domain.
    require_once ABSPATH . WPINC . '/locale.php';

    /**
     * WordPress Locale object for loading locale domain date and various strings.
     *
     * @global object $wp_locale
     * @since 2.1.0
     */
    $GLOBALS['wp_locale'] = new WP_Locale();

    // Load the functions for the active theme, for both parent and child theme if applicable.
    if ( ! defined( 'WP_INSTALLING' ) || 'wp-activate.php' === $pagenow ) {
      if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/functions.php' ) )
        include STYLESHEETPATH . '/functions.php';
      if ( file_exists( TEMPLATEPATH . '/functions.php' ) )
        include TEMPLATEPATH . '/functions.php';
    }

    /**
     * Fires after the theme is loaded.
     *
     * @since 3.0.0
     */
    do_action( 'after_setup_theme' );

    // Set up current user.
    $GLOBALS['wp']->init();

    /**
     * Fires after WordPress has finished loading but before any headers are sent.
     *
     * Most of WP is loaded at this stage, and the user is authenticated. WP continues
     * to load on the init hook that follows (e.g. widgets), and many plugins instantiate
     * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
     *
     * If you wish to plug an action once WP is loaded, use the wp_loaded hook below.
     *
     * @since 1.5.0
     */
    do_action( 'init' );

    // Check site status
    if ( is_multisite() ) {
      if ( true !== ( $file = ms_site_check() ) ) {
        require $file;
        die();
      }
      unset( $file );
    }

    /**
     * This hook is fired once WP, all plugins, and the theme are fully loaded and instantiated.
     *
     * AJAX requests should use wp-admin/admin-ajax.php. admin-ajax.php can handle requests for
     * users not logged in.
     *
     * @link http://codex.wordpress.org/AJAX_in_Plugins
     *
     * @since 3.0.0
     */
    do_action( 'wp_loaded' );

  }

  public function plugin( $plugin ) {
    $this->plugins[] = WP_PLUGIN_DIR . '/' . $plugin;
  }

  private function load_plugins() {
    foreach ( $this->plugins as $plugin ) {
      wp_register_plugin_realpath( $plugin );
      include_once $plugin;
    }
  }

}
