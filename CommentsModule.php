<?php
/**
 * Comments Module Plugin
 *
 * @package   comments-module
 * @author    Team Ajency <wordpress@ajency.in>
 * @license   GPL-2.0+
 * @link      http://ajency.in
 * @copyright 10-22-2014 Ajency.in
 */

/**
 * Comments Module class.
 *
 * @package CommentsModule
 * @author  Team Ajency <wordpress@ajency.in>
 */
class CommentsModule{
	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1.0
	 *
	 * @var     string
	 */
	protected $version = "0.1.0";

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = "comments-module";
        
	/**
	 * Instance of this class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of the plugin api class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $api_instance = null;
        
	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = '';

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action("init", array($this, "load_plugin_textdomain"));
                
                // hook function to register plugin defined and theme defined document types
                // custom added
                add_action("init", array($this, "reg_comments_types"));
                
                // hook function initialize plugin api methods
                // custom added
                add_action( "wp_json_server_before_serve", array($this, "plugin_api_init"),10,1 );
                
                // hook function to check if depended plugin 'JSON REST API' is active if not display notice 
                add_action("admin_notices", array($this, "add_plugin_dashboard_notices") );
                
                // hook function to send api error response incase of a duplicate comment
                add_action("comment_duplicate_trigger",array($this,"send_duplicate_comment_error"),10,1);
                
		// Add the options page and menu item.
                // custom added
		add_action("admin_menu", array($this, "add_plugin_admin_menu"));  

		// Load admin style sheet and JavaScript.
		add_action("admin_enqueue_scripts", array($this, "enqueue_admin_styles"));
		add_action("admin_enqueue_scripts", array($this, "enqueue_admin_scripts"));

		// Load public-facing style sheet and JavaScript.
		add_action("wp_enqueue_scripts", array($this, "enqueue_styles"));
		add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));
                
		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action("TODO", array($this, "action_method_name"));
		add_filter("TODO", array($this, "filter_method_name"));
                
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn"t been set, set it now.
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
         * custom code logic for table creation on plugin activation
         * 
	 * @since    0.1.0
	 *
	 * @param    boolean $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate($network_wide) {
            // create plugin document uploads directory

            
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate($network_wide) {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters("plugin_locale", get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR . "/" . $domain . "/" . $domain . "-" . $locale . ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . "/lang/");
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		$screen = get_current_screen();
		if ($screen->id == $this->plugin_screen_hook_suffix) {
			wp_enqueue_style($this->plugin_slug . "-admin-styles", plugins_url("css/admin.css", __FILE__), array(),
				$this->version);
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		$screen = get_current_screen();
		if ($screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script($this->plugin_slug . "-admin-script", plugins_url("js/comments-module-admin.js", __FILE__),
				array("jquery"), $this->version);
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style($this->plugin_slug . "-plugin-styles", plugins_url("css/public.css", __FILE__), array(),
			$this->version);
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script($this->plugin_slug . "-plugin-script", plugins_url("js/comments-module.js", __FILE__), array("jquery"),
			$this->version);
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_plugins_page(__("Comments Module - Administration", $this->plugin_slug),
			__("Comments Module", $this->plugin_slug), "read", $this->plugin_slug, array($this, "display_plugin_admin_page"));
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1.0
	 */
	public function display_plugin_admin_page() {
		include_once("views/admin.php");
	}
        

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    0.1.0
	 */
	public function action_method_name() {
		// TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    0.1.0
	 */
	public function filter_method_name() {
		// TODO: Define your filter hook callback here
	}
        
        /*
         * function to register the comments types
         * custom added function
         * 
         * @since    0.1.0
         * 
         */        
        public function reg_comments_types(){

            register_comments_types('post');
        }  
        
        /*
         * function to register the api routes and the api functionality
         * custom added function
         * 
         * @since    0.1.0
         * 
         */
        public function plugin_api_init($server){
            
            if(class_exists('CommentsModuleAPI')){
                   if (null == self::$api_instance) {
                       self::$api_instance = new CommentsModuleAPI($server);
                   }
               add_filter( 'json_endpoints', array( self::$api_instance, 'register_routes' ) );
            }
           
        }
        
        /*
         * Check if a comment type is registered in theme code
         * @param string $comment_type
         * 
         * return bool key true|false
         */
        public function is_registered_comment_type($comment_type){
            global $ajcm_comment_types;
                        
            if(is_null($ajcm_comment_types)){
                    return false;
            }
          
            if(!in_array($comment_type, $ajcm_comment_types))
                    return false;
                       
            return true;
            
        }        
        
        /*
         * function to display notice if depended plugin 'JSON REST API' is active if not display notice 
         */
        public function add_plugin_dashboard_notices(){
           include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
           if (!is_plugin_active('json-rest-api/plugin.php')){ ?>
           <div class="error">
               <p>Comments Module has dependencies. <strong>Please install/activate Plugin JSON REST API. </strong></p>
           </div>
           <?php }      
        } 
        
        /*
         * function to send api error response in case of a duplicated comment
         * Hooks to action 'comment_duplicate_trigger'
         */
        public function send_duplicate_comment_error($comment_data){
            wp_send_json_error(array('msg'=>'Duplicate comment detected; it looks as though you&#8217;ve already said that!'));
        }
}