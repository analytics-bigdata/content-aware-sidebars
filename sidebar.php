<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2017 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	exit;
}

final class CAS_Sidebar_Manager {

	/**
	 * Sidebar metadata
	 * @var WPCAObjectManager
	 */
	protected $metadata;

	/**
	 * Custom sidebars
	 * @var array
	 */
	public $sidebars = array();

	/**
	 * @var array
	 * Constructor
	 *
	 * @since 3.1
	 */
	public function __construct() {

		add_action('wpca/loaded',
			array($this,'late_init'));
		add_action('wp_head',
			array($this,'sidebar_notify_theme_customizer'));
		add_action('init',
			array($this,'init_sidebar_type'),99);
		add_action('widgets_init',
			array($this,'create_sidebars'),99);
		add_action('wp_loaded',
			array($this,'update_sidebars'),99);

		add_shortcode( 'ca-sidebar',
			array($this,'sidebar_shortcode'));


	}

	/**
	 * Initialize after WPCA has been loaded
	 * Makes sure the SDK can be used in actions/filters
	 * forcefully called earlier
	 *
	 * @since  3.4
	 * @return void
	 */
	public function late_init() {
		add_action('sidebars_widgets',
			array($this,'replace_sidebar'));
	}

	/**
	 * Get instance of metadata manager
	 *
	 * @since  3.0
	 * @return WPCAObjectManager
	 */
	public function metadata() {
		if(!$this->metadata) {
			$this->init_metadata();
		}
		return $this->metadata;
	}

	/**
	 * Create post meta fields
	 * @global array $wp_registered_sidebars 
	 * @return void 
	 */
	private function init_metadata() {

		$this->metadata = new WPCAObjectManager();
		$this->metadata
		->add(new WPCAMeta(
			'visibility',
			__('Visibility','content-aware-sidebars'),
			array(),
			'multi',
			array(
				-1 => __('Logged-in Users', 'content-aware-sidebars')
			)
		),'visibility')
		->add(new WPCAMeta(
			'handle',
			_x('Action','option', 'content-aware-sidebars'),
			0,
			'select',
			array(
				0 => __('Replace', 'content-aware-sidebars'),
				1 => __('Merge', 'content-aware-sidebars'),
				2 => __('Shortcode / Template Tag', 'content-aware-sidebars'),
				3 => __('Forced replace','content-aware-sidebars')
			),
			__('Replace host sidebar, merge with it or add sidebar manually.', 'content-aware-sidebars')
		),'handle')
		->add(new WPCAMeta(
			'host',
			__('Target Sidebar', 'content-aware-sidebars'),
			'sidebar-1',
			'select',
			array()
		),'host')
		->add(new WPCAMeta(
			'merge_pos',
			__('Merge Position', 'content-aware-sidebars'),
			1,
			'select',
			array(
				__('Top', 'content-aware-sidebars'),
				__('Bottom', 'content-aware-sidebars')
			),
			__('Place sidebar on top or bottom of host when merging.', 'content-aware-sidebars')
		),'merge_pos');
		apply_filters('cas/metadata/init',$this->metadata);
	}

	/**
	 * Populate metadata with dynamic content
	 * for use in admin
	 *
	 * @since  3.2
	 * @return void
	 */
	public function populate_metadata() {
		if($this->metadata) {

			global $wp_registered_sidebars;

			// List of sidebars
			$sidebar_list = array();
			foreach($wp_registered_sidebars as $sidebar) {
				$sidebar_list[$sidebar['id']] = $sidebar['name'];
			}

			// Remove ability to set self to host
			if(get_the_ID()) {
				unset($sidebar_list[CAS_App::SIDEBAR_PREFIX.get_the_ID()]);
			}
			$this->metadata->get('host')->set_input_list($sidebar_list);

			apply_filters('cas/metadata/populate',$this->metadata);
		}
		
	}

	/**
	 * Create sidebar post type
	 * Add it to content aware engine
	 * 
	 * @return void 
	 */
	public function init_sidebar_type() {
		
		// Register the sidebar type
		register_post_type(CAS_App::TYPE_SIDEBAR,array(
			'labels'        => array(
				'name'               => __('Sidebars', 'content-aware-sidebars'),
				'singular_name'      => __('Sidebar', 'content-aware-sidebars'),
				'add_new'            => _x('Add New', 'sidebar', 'content-aware-sidebars'),
				'add_new_item'       => __('Add New Sidebar', 'content-aware-sidebars'),
				'edit_item'          => __('Edit Sidebar', 'content-aware-sidebars'),
				'new_item'           => __('New Sidebar', 'content-aware-sidebars'),
				'all_items'          => __('All Sidebars', 'content-aware-sidebars'),
				'view_item'          => __('View Sidebar', 'content-aware-sidebars'),
				'search_items'       => __('Search Sidebars', 'content-aware-sidebars'),
				'not_found'          => __('No sidebars found', 'content-aware-sidebars'),
				'not_found_in_trash' => __('No sidebars found in Trash', 'content-aware-sidebars'),
				//wp-content-aware-engine specific
				'ca_title'           => __('Sidebar Conditions','content-aware-sidebars'),
				'ca_not_found'       => __('No content. Please add at least one condition group to make the sidebar content aware.','content-aware-sidebars')
			),
			'capabilities'  => array(
				'edit_post'          => CAS_App::CAPABILITY,
				'read_post'          => CAS_App::CAPABILITY,
				'delete_post'        => CAS_App::CAPABILITY,
				'edit_posts'         => CAS_App::CAPABILITY,
				'delete_posts'       => CAS_App::CAPABILITY,
				'edit_others_posts'  => CAS_App::CAPABILITY,
				'publish_posts'      => CAS_App::CAPABILITY,
				'read_private_posts' => CAS_App::CAPABILITY
			),
			'public'              => false,
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array('title','page-attributes'),
			'menu_icon'           => 'dashicons-welcome-widgets-menus',
			'can_export'          => false,
			'delete_with_user'    => false
		));

		WPCACore::post_types()->add(CAS_App::TYPE_SIDEBAR);
	}

	/**
	 * Add sidebars to widgets area
	 * Triggered in widgets_init to save location for each theme
	 * @return void
	 */
	public function create_sidebars() {
		$sidebars = get_posts(array(
			'numberposts' => -1,
			'post_type'   => CAS_App::TYPE_SIDEBAR,
			'post_status' => array(
				CAS_App::STATUS_ACTIVE,
				CAS_App::STATUS_INACTIVE,
				CAS_App::STATUS_SCHEDULED
			),
			'orderby'     => 'title',
			'order'       => 'ASC'
		));

		//Register sidebars to add them to the list
		foreach($sidebars as $post) {
			$this->sidebars[CAS_App::SIDEBAR_PREFIX.$post->ID] = $post;
			register_sidebar( array(
				'name' => $post->post_title ? $post->post_title : __('(no title)'),
				'id'   => CAS_App::SIDEBAR_PREFIX.$post->ID
			));
		}
	}
	
	/**
	 * Update the created sidebars with metadata
	 * 
	 * @since  [since]
	 * @return void
	 */
	public function update_sidebars() {

		//TODO: check if this is necessary anymore or merge to 1 method

		//Now reregister sidebars with proper content
		foreach($this->sidebars as $post) {

			$handle_meta = $this->metadata()->get('handle');

			$sidebar_args = array(
				'name'        => $post->post_title ? $post->post_title : __('(no title)'),
				'description' => $handle_meta->get_list_data($post->ID,true),
				'id'          => CAS_App::SIDEBAR_PREFIX.$post->ID
			);

			if(!$sidebar_args['description']) {
				continue;
			}

			$sidebar_args['before_widget'] = '<li id="%1$s" class="widget-container %2$s">';
			$sidebar_args['after_widget'] = '</li>';
			$sidebar_args['before_title'] = '<h4 class="widget-title">';
			$sidebar_args['after_title'] = '</h4>';

			if ($handle_meta->get_data($post->ID) != 2) {
				$host_meta = $this->metadata()->get('host');
				$host = $host_meta->get_list_data($post->ID,false);
				$sidebar_args['description'] .= ': ' . ($host ? $host :  __('Please update Host Sidebar', 'content-aware-sidebars') );

				//Set style from host to fix when content aware sidebar
				//is called directly by other sidebar managers
				global $wp_registered_sidebars;
				$host_id = $host_meta->get_data($post->ID);
				if(isset($wp_registered_sidebars[$host_id])) {
					$sidebar_args['before_widget'] = $wp_registered_sidebars[$host_id]['before_widget'];
					$sidebar_args['after_widget'] = $wp_registered_sidebars[$host_id]['after_widget'];
					$sidebar_args['before_title'] = $wp_registered_sidebars[$host_id]['before_title'];
					$sidebar_args['after_title'] = $wp_registered_sidebars[$host_id]['after_title'];
				}
			}
			register_sidebar($sidebar_args);
		}
	}

	/**
	 * Replace or merge a sidebar with content aware sidebars.
	 * @since  .
	 * @param  array    $sidebars_widgets
	 * @return array
	 */
	public function replace_sidebar($sidebars_widgets) {

		$posts = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);

		if ($posts) {
			//temporary filter until WPCACore allows filtering
			$user_visibility = is_user_logged_in() ? array(-1) : array();
			$user_visibility = apply_filters('cas/user_visibility',$user_visibility);
			foreach ($posts as $post) {

				$id = CAS_App::SIDEBAR_PREFIX . $post->ID;
				$visibility = $this->metadata()->get('visibility')->get_data($post->ID,true,false);
				$host = $this->metadata()->get('host')->get_data($post->ID);

				// Check visibility
				if($visibility && !array_intersect($visibility,$user_visibility))
					continue;

				// Check for correct handling and if host exist
				if ( $post->handle == 2 || !isset($sidebars_widgets[$host]))
					continue;

				// Sidebar might not have any widgets. Get it anyway!
				if (!isset($sidebars_widgets[$id]))
					$sidebars_widgets[$id] = array();

				// If handle is merge or if handle is replace and host has already been replaced
				if ($post->handle == 1 || ($post->handle == 0 && isset($handled_already[$host]))) {
					if ($this->metadata()->get('merge_pos')->get_data($post->ID))
						$sidebars_widgets[$host] = array_merge($sidebars_widgets[$host], $sidebars_widgets[$id]);
					else
						$sidebars_widgets[$host] = array_merge($sidebars_widgets[$id], $sidebars_widgets[$host]);
				} else {
					$sidebars_widgets[$host] = $sidebars_widgets[$id];
					$handled_already[$host] = 1;
				}
				
			}
		}
		return $sidebars_widgets;
	}

	/**
	 * Show manually handled content aware sidebars
	 * @global array $_wp_sidebars_widgets
	 * @param  string|array $args 
	 * @return void 
	 */
	public function manual_sidebar($args) {
		global $_wp_sidebars_widgets;

		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'include' => '',
			'before'  => '<div id="sidebar" class="widget-area"><ul class="xoxo">',
			'after'   => '</ul></div>'
		));
		extract($args, EXTR_SKIP);

		// Get sidebars
		$posts = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);
		if (!$posts)
			return;

		// Handle include argument
		if (!empty($include)) {
			if (!is_array($include))
				$include = explode(',', $include);
			// Fast lookup
			$include = array_flip($include);
		}

		$i = $host = 0;
		foreach ($posts as $post) {

			$id = CAS_App::SIDEBAR_PREFIX . $post->ID;

			// Check for manual handling, if sidebar exists and if id should be included
			if ($post->handle != 2 || !isset($_wp_sidebars_widgets[$id]) || (!empty($include) && !isset($include[$post->ID])))
				continue;

			// Merge if more than one. First one is host.
			if ($i > 0) {
				if ($this->metadata()->get('merge_pos')->get_data($post->ID))
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host], $_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id], $_wp_sidebars_widgets[$host]);
			} else {
				$host = $id;
			}
			$i++;
		}

		if ($host) {
			echo $before;
			dynamic_sidebar($host);
			echo $after;
		}
	}

	/**
	 * Display sidebar with shortcode
	 * @version 2.5
	 * @param   array     $atts
	 * @param   string    $content
	 * @return  string
	 */
	public function sidebar_shortcode( $atts, $content = null ) {
		$a = shortcode_atts( array(
			'id' => 0,
		), $atts );
		
		$id = CAS_App::SIDEBAR_PREFIX.esc_attr($a['id']);
		ob_start();
		dynamic_sidebar($id);
		return ob_get_clean();
	}

	/**
	 * Runs is_active_sidebar for sidebars
	 * Widget management in Theme Customizer
	 * expects this
	 * 
	 * @global type $wp_customize
	 * @since  2.2
	 * @return void
	 */
	public function sidebar_notify_theme_customizer() {
		global $wp_customize;
		if(!empty($wp_customize)) {
			$sidebars = WPCACore::get_posts(CAS_App::TYPE_SIDEBAR);
			if($sidebars) {
				foreach($sidebars as $sidebar) {
					is_active_sidebar(CAS_App::SIDEBAR_PREFIX . $sidebar->ID);
				}
			}
		}
	}

}

//eol