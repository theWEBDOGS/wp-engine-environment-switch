<?php
/**
 * Plugin Name: WP Engine Environment Switch
 * Plugin URI: https://WEBDOGS.COM/
 * Version: 1.0
 * Description: Easily switch between staging and production environments on your WP Engine installs.
 * Author: WEBDOGS Support Team
 * Author URI: https://WEBDOGS.COM/
 * Text Domain: wpe-env-switch
 * Domain Path: /languages/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * WP Engine Environment Switch
 * Copyright (C) 2016, Jacob Vega/Canote - jacob@webdogs.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) :
    exit;
elseif ( ! function_exists( 'is_wpe' ) ) :
    return;
elseif ( ! defined( 'PWP_NAME' ) ) :
    return;
elseif ( PWP_NAME == "not in use currently" ) :
    return;
elseif ( ! class_exists( 'WPE_Environment_Switch' ) ) :


add_action( 'plugins_loaded', function() { 

    /**
     * Add a quicklink in the Admin Bar to switch between
     *      the different environments of your WP Engine WordPress install.
     *
     * @param string $install WPE install name (reguired).
     * @param  array $arguments Custom args (optional).
     *
     */
     new WPE_Environment_Switch( PWP_NAME ); 

} );

class WPE_Environment_Switch {

    /**
     * WPE install name
     *
     * @var string
     */
    private $install;


    ///////////////////
    //
    // MENU COMPOSSED
    //
    ///////////////////

    /**
     * Compossed title
     *
     * @var string
     */
    private $title;

    /**
     * Link URL
     *
     * @var string
     */
    private $href;


    //////////////////
    //
    // MENU DEFAULTS
    //
    //////////////////

    /**
     * Menu ID
     *
     * @var string
     */
    private $id;

    /**
     * Admin bar node
     *
     * @var array
     */
    private $parent;

    /**
     * Link parameters 
     *
     * @var array
     */
    private $meta = array();


    ////////////////////
    //
    // FORMAT DEFAULTS
    //
    ////////////////////

    /**
     * Dashicon class  
     *      for menu_icon
     *
     * @var string
     */
    private $icon;

    /**
     * Format string 
     *      for title wrap
     *
     * @var string
     */
    private $wrap;

    /**
     * Format string 
     *      for menu_title
     *
     * @var string
     */
    private $format;

    /**
     * Formatting string 
     *      for staging URL
     *
     * @var string
     */
    private $staging;

    /**
     * Formatting string 
     *      for production URL
     *
     * @var string
     */
    private $production;


    /////////////////
    //
    // HOOK DEFAULT
    //
    /////////////////

    /**
     * Hook tag
     *
     * @var string
     */
    private $hook;

    /**
     * Hook priority
     *
     * @var integer
     */
    private $priority = 0;


    /////////////////
    //
    // CAPS DEFAULT
    //
    /////////////////

    /**
     * Menu caps
     *
     * @var string
     */
    private $capability;


    //////////////////////////
    //
    // ENVIRONMENT SELECTORS
    //
    //////////////////////////

    /**
     * Index for external link 
     *      data in $environments
     *
     * @var string
     */
    private $index;

    /**
     * Index for current domain
     *      data in $environments
     *
     * @var string
     */
    private $context;


    ////////////////////////
    //
    // ENVIRONMENT CONTEXT
    //
    ////////////////////////

    /**
     * External environment URLs
     *
     * @var array
     */
    private $environment = array();

    /**
     * Current install 
     *      environment domain data
     *
     * @var array
     */
    private $environments = array();


    ///////////////////
    //
    // DOMAIN MAPPING
    //
    ///////////////////

    /**
     * Using domain mapping from 
     *      WordPress MU Domain Mapping or Mulitsite
     *
     * @var bool
     */
    private $domain_mapping = FALSE;


    ///////////////////////
    //
    // DEFAULT PARAMETERS
    //
    ///////////////////////

    /**
     * Constructor arguments
     *
     * @var array
     */
    private $arguments = array();

    /**
     * Default paramaters
     *
     * @var array
     */
    private $defaults = array(

        'id'         => 'wpe_environment',
        'parent'     => 'top-secondary',
        'meta'       => array( 'target' => '_blank' ),

        'icon'       => 'dashicons-external',
        'wrap'       => '<span class="ab-icon %1$s"></span><span class="ab-label">%2$s</span>',
        'format'     => 'Go to %1$s',

        'staging'    => 'http://%1$s.staging.wpengine.com',
        'production' => 'http://%1$s.wpengine.com',  /*
        'hook'       => 'admin_bar_menu',             */
        'hook'       => 'wp_before_admin_bar_render', 
        'priority'   =>  10,

        'capability' => 'edit_posts'
    );

    /**
     * Constructor
     *
     * @param string $install WPE install name (reguired).
     * @param  array $arguments Custom args (optional).
     */
    public function __construct( $install, $arguments = array() ) {
        global $wpdb;
        $this->load_translation();

        $this->install   = $install;
        $this->arguments = $arguments;

        //Check if domain_mapping is active.
        if ( $wpdb->dmtable === $wpdb->base_prefix . 'domain_mapping' )
            $this->domain_mapping = TRUE;

        // init vars 
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Return default domain and environment data.
     *
     * @return array 
     */
    private function default_environments() {
        return array(

            'production' => array(
                'current' => (bool) is_wpe(),
                'url' => sprintf( $this->production, $this->install ) ),

            'staging'    => array(
                'current' => (bool) is_wpe_snapshot(),
                'url' => sprintf( $this->staging, $this->install ) ) );
    }

    /**
     * Populate vars and register action hooks
     *
     * @return void
     */
    public function init() {
        $this->populate();

        $environments = $this->default_environments();
        $index        = $this->get_environment_slug( $environments, FALSE );
        $context      = $this->get_environment_slug( $environments, TRUE  );
        
        if ( $this->domain_mapping )
            $environments = $this->get_domain_mapping( $environments );

        $this->environments = $environments;
        
        if ( ! empty( $environments[ $index ] ) ) {
            $this->index = $index;
            $this->environment = $environments[ $index ];
            $this->environments[ $index ] =& $this->environment;
        }
        $this->set_context( $context );

        // This feature is by default only for contributors or better.
        $capability = apply_filters( 'wpees-quicklink-capability', $this->capability );
        if ( ! current_user_can( $capability ) ) 
            return;
        

        //////////////////////////////////////
        //                                  //
        // Hook Quicklink to the Admin Bar  //
        //                                  //
        //////////////////////////////////////

        add_action( $this->hook, array( $this, 'add_quicklink' ), $this->priority );

        //////////////////////////////////////
    }

    /**
     * Configure vars for current context
     *
     * @param string $context current evnironment context.
     *
     */
    private function set_context( $context ) {
        global $wpe_all_domains;
        $environment =& $this->environment;
        $url = ( $this->domain_mapping && isset( $environment['mapping_url'] ) ) ? $environment['mapping_url'] : $environment['url'];
        $domains = array( $environment['url'] );

        if ( isset( $environment['mapping_url'] ) )
            $domains[] = $environment['mapping_url'];

        switch ( $context ) {

            case 'staging':
                $domains = array_map( array( $this, 'trim_http_transports' ),  
                    ( ( is_array( $wpe_all_domains ) && ! empty( $wpe_all_domains ) ) ? $wpe_all_domains + $domains : $domains ) ); 
                foreach ( $domains as $domain ) {
                    if ( $domain && false == strpos( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $domain ) ) {
                        $url = $domain; break; } }
                $environment['url'] = $url;
                if ( isset( $environment['mapping_url'] ) )
                    $environment['mapping_url'] = $url;
            break;

            case 'production':
            default:
                $domains = array_map( array( $this, 'trim_http_transports' ), $domains );
                foreach ( $domains as $domain ) {
                    if ( $domain && false == strpos( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $domain ) ) {
                        $url = $domain; break; } }
                $environment['url'] = $url;
                if ( isset( $environment['mapping_url'] ) )
                    $environment['mapping_url'] = $url;
            break;
        }
        $this->context = $context;

        // Check if there is a URL.
        if ( ! empty( $environment['url'] ) ) {
            $href = $environment['url'];
            if ( $this->domain_mapping && ! empty( $environment['mapping_url'] ) )
                $href = $environment['mapping_url'];

            $this->href  = $href . $this->get_request();
            $format      = sprintf( $this->format, ucfirst( $this->index ) );
            $this->title = sprintf( $this->wrap, $this->icon, $format );
        }
    }

    /**
     * Returns the Request URI, that is not a subfolder of the WPE installation.
     *
     * @return string REQUEST_URI
     */
    public function get_request() {
        if ( ! isset( $this->context ) )
            return;

        $request = $this->trim_http_transports( esc_url_raw( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );
        $url_types = array( 'url', 'mapping_url' );

        foreach ( $url_types as $url_type ) {
            $url = $this->trim_http_transports( $this->environments[ $this->context ][ $url_type ] );
            if ( 0 === strpos( $request, $url ) )
                return substr( $request, strlen( $url ) );
        }

        if ( is_multisite() )
            $base_url = get_site_url( get_current_blog_id() );
        else
            $base_url = get_site_url();
        
        $base_url = $this->trim_http_transports( $base_url );
        if ( 0 === strpos( $request, $base_url ) )
            return substr( $request, strlen( $base_url ) );

        return esc_url_raw( $_SERVER['REQUEST_URI'] );
    }

    /**
     * Filters the Admin Bar to add the links between the different environments.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP AdminBar.
     *
     * @return void
     */
    public function add_quicklink( $wp_admin_bar ) {
        global $wp_admin_bar;

        // Don't show for logged out users.
        // Show only when the user is a member of this site, or they're a super admin.
        if ( ! is_user_logged_in() )
            return;
        elseif ( ! is_user_member_of_blog() && ! is_super_admin() )
            return;
        elseif ( ! isset( $this->context ) )
            return;

        $args = array(
            'id'        => esc_attr( $this->id ),
            'title'     => wp_kses_normalize_entities( $this->title ),
            'parent'    => esc_html( $this->parent ),
            'href'      =>  esc_url( $this->href ),
            'meta'      =>           $this->meta 
        );
        $wpees_quicklink_args = apply_filters( 'wpees-quicklink-args', $args );

        if ( ! empty( $wpees_quicklink_args ) )
            $wp_admin_bar->add_menu( $wpees_quicklink_args );
    }

    /**
     * Populate class with default and custom arguments.
     */
    private function populate() {
        $arguments = wp_parse_args( $this->arguments, apply_filters( 'wpees-quicklink-defaults', $this->defaults ) );

        if ( ! empty( $arguments ) ) {
            $arguments = array_map( function( $argument ) {
                    return is_string( $argument ) ? trim( $argument ) : $argument ; 
                }, $arguments );

            foreach ( $arguments as $property => $argument ) {
                if ( is_null( $argument ) )
                    continue;

                $this->{$property} = apply_filters( 'wpees-quicklink-{$property}', $argument );
            }
        }
    }

    /**
     * Get environment index.
     *
     * @param array $environments environments data.
     * @param bool $is_current TRUE to return current index.
     *
     * @return mixed bool/string
     */
    private function get_environment_slug( $environments = array(), $is_current = FALSE ) {
        $method = ( $is_current ) ? 'is_current' : 'is_not_current' ;
        $environment_array = array_filter( $environments, array( $this, $method ) );
        return ( empty( $environment_array ) ) ? FALSE : array_shift( array_keys( $environment_array ) );
    }

    
    /**
     * Get non current environment.
     *
     * @param array $env environment data.
     *
     * @return bool
     */
    public function is_current( $env = array() ) {
        return (bool) ( empty( $env ) ) ? FALSE : ( TRUE === $env['current'] );
    }

    /**
     * Get non current environment.
     *
     * @param array $env environment data.
     *
     * @return bool
     */
    public function is_not_current( $env = array() ) {
        return (bool) ( empty( $env ) ) ? FALSE : ( FALSE === $env['current'] );
    }
    
    /**
     * Add domain mapping args to environment data. 
     *
     * @param array $environments environments data.
     *
     * @return array 
     */
    private function get_domain_mapping( $environments = array() ) {
        return array_map( array( $this, 'push_domain_mapping' ), $environments );
    }

    /**
     * Callback to push domain mapping into environment data. 
     *
     * @param array $env environment data.
     *
     * @return array
     */
    public function push_domain_mapping( $env = array() ) {
        return array_merge( $env, array( 'mapping_url' => $env['url'] ) );
    }

    /**
     * Maybe convert URL transport to SSL. 
     *
     * @param string $url
     *
     * @return string
     */
    private function maybe_ssl( $url ) {
        if ( is_ssl() )
            $url = preg_replace( '#^http://#', 'https://', $url );
        return $url;
    }

    /**
     * Trim the transport from a URL
     *
     * @param string $url
     *
     * @return string
     */
    public function trim_http_transports( $url ) {
        return trim( substr( $url, strpos( $url, '://' ) + 3 ), '/' );
    }

    /**
     * Load textdomain
     */
    public function load_translation() {
        load_plugin_textdomain( 'wpe-env-switch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}

endif;