<?php
/**
 * Wordpress ACF importer
 *
 * @package   Wordpress ACF importer
 * @author    Maciej Gurban <maciej.gurban@gmail.com>
 * @license   GPL-2.0+
 * @link      https://github.com/maciej-gurban/acf_create_field/
 * @copyright 2013 Maciej Gurban
 */


class WP_ACF_Importer {

	/**
	 * Plugin version
	 *
	 * @since   0.1.1
	 *
	 * @var     string
	 */
	const VERSION = '0.1.1';

	/**
	 * @since   0.1.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wp_acf_importer';

	/**
	 * Instance of this class.
	 *
	 * @since   0.1.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since   0.1.1
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since   0.1.1
	 *
	 *@return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since   0.1.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since   0.1.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since   0.1.1
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since   0.1.1
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since   0.1.1
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since   0.1.1
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since   0.1.1
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since   0.1.1
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}



	public function insert_acf_field( $xml_string, $allow_duplicates = false ) {

	    // Parse ACF post's XML
	    $content = simplexml_load_string( $xml_string, 'SimpleXMLElement', LIBXML_NOCDATA); 

	    // @TODO: add a check on $content

	    // Parse XML post attributes containing fields
	    $wp_post_attributes = $content->channel->item->children('wp', true);

	    # Copy basic properties from the exported field
	    $wp_post_data = array(
	        'post_type'   => 'acf',
	        'post_title'  => $content->channel->item->title,
	        'post_name'   => $wp_post_attributes->post_name,
	        'post_status' => 'publish',
	        'post_author' => 1

	    );

	    $the_post = get_page_by_title( $content->channel->item->title, 'OBJECT', 'acf' );

	    # Execute only if doesn't exist already
	    if ( !$the_post || $allow_duplicates == true ) {
	        $post_id = wp_insert_post( $wp_post_data );
	    }
	    else {
	        $post_id = $the_post->ID;
	    }

	    $wp_post_meta = $content->channel->item->children( 'wp', true );

	    if( $wp_post_meta ) {
	        foreach ( $wp_post_meta as $row ) {

	            // Choose only arrays (postmeta)
	            if( count($row) > 0) {
	                // using addlashes on meta values to compensate for stripslashes() that will be run upon import
	                update_post_meta( $post_id, $row->meta_key, addslashes( $row->meta_value ) );
	            }

	        }
	    }
	}


} // end of class
