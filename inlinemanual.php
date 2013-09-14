<?php
/*
Plugin Name: Inlinemanual
Plugin URI: http://2046.cz
Description: Inlinemanual for Wordpress.
Author: 2046
Version: 0.2
Author URI: http://2046.cz
*/

// get rid of header already sent after redirects
function app_output_buffer() {
	ob_start();
} // soi_output_buffer
add_action('init', 'app_output_buffer');

// define the table prefix
$schema_DB_table = 'inlinemanual_settings';

// 
// 
// FRONT END FEED
// 
// 
// ignite feed
// ?feed=inlinemanual
// /feed/inlinemanual
function f2046_feed() {
	add_feed('inlinemanual','inlinemanual_feed');
}
add_action('init','f2046_feed');

// Add variable to wordpress URL
// &inm_topic=
function f2046_feed_variables( $qvars ){
	// $qvars[] = 'inm_IDs';
	$qvars[] = 'inm_topic';
	return $qvars;
}
add_filter('query_vars', 'f2046_feed_variables' );

// list of playlist IDs
// JSON

function inlinemanual_feed() {
	header('Content-Type: text/html; charset=utf-8');
	global $wp;
	$output = '';
	if(array_key_exists ("inm_topic", $wp->query_vars)) {
		$raw_topic_id = $wp->query_vars["inm_topic"];
		// filter the variable to raw numbers
		$tid = f2046_filter_number($raw_topic_id);
		// get requested topic details
		$output = inm_feedback_topic($tid);
	}else{
		// get all Ids of all topics
		$output = inm_feedback_ids();
	}
	echo $output;
}

function inm_feedback_ids() {
	global $schema_DB_table;
	// get the options from DB
	$data = get_option($schema_DB_table);
	$user_ID = get_current_user_id();
	$user_info = get_userdata( $user_ID );
	$user_capability = isset($user_info->user_level) ? $user_info->user_level : -1;  // -1 is a nonamer
	$topics = array();
	
	foreach( $data['topics'] as $key => $val) {
	
		if ((int)$user_capability >= (int)$val['permissions'] && (int)$val['permissions'] != -2){ 
		
			$topics[$val['id']] = array(
				'tid' => $val['id'],
				'title' => $val['title'],
				'description' => $val['description'],
				'version' => $val['version'],
				'steps' => $val['steps']
			);
		}
	}
	return json_encode($topics);
}

function inm_feedback_topic($tid) {
	global $schema_DB_table;
	// get the options from DB
	$data = get_option($schema_DB_table);
	$user_ID = get_current_user_id();
	$user_info = get_userdata( $user_ID );
	$user_capability = isset($user_info->user_level) ? $user_info->user_level : -1;  // -1 is a nonamer
	$output = '';

	foreach( $data['topics'] as $key => $val) {
		// show only those with the right capability
		// check if the current user is granted enough capabilities to see current topic
		if ((int)$user_capability >= (int)$val['permissions'] && (int)$val['permissions'] != -2){ 	
			// process steps
			if(!empty($val['steps'])){
				$output = json_encode($val['steps']);
			}
		}
	}
	return $output;
}

// 
// 
// Inject player 
// 
// 
function inm_player_injector(){
	global $schema_DB_table;
	$data = get_option($schema_DB_table);
	$user_ID = get_current_user_id();
	$user_info = get_userdata( $user_ID );
	$user_capability = isset($user_info->user_level) ? $user_info->user_level : -1;  // -1 is a nonamer
	foreach( $data['topics'] as $key => $val) {
		// show only those with the right capability
		// check if the current user is granted enough capabilities to see current topic
		if ((int)$user_capability >= (int)$val['permissions'] && (int)$val['permissions'] != -2){ 

			wp_register_script ( 'inm_player', 'http://inlinemanual.com/inm/player2/js/player.min.js', array('jquery'));
			wp_register_style ( 'inm_styles','http://inlinemanual.com/inm/player2/css/player.min.css' );

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'inm_player' );
			wp_enqueue_style( 'inm_styles' );
			// include html to the frontend of page
			add_filter( 'wp_footer' , 'inm_player_html' );
			
			// inject html to admin footer
			add_action( 'admin_footer', 'inm_player_html' );
			

		}
	}
}
// call the function on frontend
add_action('wp_enqueue_scripts', 'inm_player_injector');
// call the action in Admin
add_action('admin_enqueue_scripts', 'inm_player_injector');


function inm_player_html() {
   echo '<div id="inm-progress"><div class="inm-progress"></div></div><div id="inline-manual" data-topic-title="" data-steps=""><a id="inm-trigger" href="#"><i class="inm-icon"></i></a></div>';
	$site_url = parse_url(get_site_url());
	$site_path = $site_url['path'].'/';
	global $schema_DB_table;
	// get the options from DB
	$data = get_option($schema_DB_table);
	$settings = $data['primary_key'];
	$widget_title = 'Get support';
	if (!empty($settings[1])) { $widget_title = $settings[1]; }
	$config = json_encode(
		array(
			'basePath' => $site_path,
			'topicsUrl' => '?feed=inlinemanual',
			'mode' => 'tour',
			'l10n' => array(
				'title' => $widget_title,
				'refresh' => 'Refresh',
				'backToTopics' => '&laquo; Back to topics',
				'scrollUp' => 'Scroll up',
				'scrollDown' => 'Scroll down',
				'progress' => 'of',
				'poweredBy' => 'Powered by'
			)
 		)
    );

     echo '<script>jQuery(document).ready( function() { IMP.init( ' . $config . ' ); })</script>';
};


// 
// 
// ADMIN BACKEND
// 
// 
// connect to Inlinemanual.com
// get the user data
/**
 * Featch all topics from through the InlineManual API
 */
function inm_wordpress_topics_fetch_all() {
	require(dirname(__FILE__) . '/lib/InlineManual.php');
	// get actual data form DB
	// define the table prefix
	global $schema_DB_table;
	// get the options from DB
	$data = get_option($schema_DB_table);
	$api_key = $data['primary_key'];
	$test_key = '407eea6c384cf0717b4930b357ea660f'; // 407eea6c384cf0717b4930b357ea660f  // test me Yo
	InlineManual::$site_api_key = $api_key[0]; 
	InlineManual::$verify_ssl_certs = FALSE;
	try {
		// connect to inlinemanual.com
		//  fetch all remote topics 
		$topics = InlineManual_Site::fetchAllTopics();
		// var_dump($topics);
		// get the dummy topic .. 

		$i = 0;
		
		// walk through received data
		foreach ($topics as $topic => $value) {
			// fetch just one ID
			// $topic_detail = InlineManual_Player::fetchTopic($value->id);
			// var_dump($topic_detail);
			$tmp_topics[$i] = array(
				'id' => $value->id,
				'title' => $value->title,
				'description' => $value->description,
				'version' => $value->version,
				'tags' => $value->tags, //  array {serialize}
				'timestamp' => 0,
				'steps' => '',
				'permissions' => 'hidden'
				// do not refresh status
				// 'id' => '1'
			 );		
			// fetch the steps for each topic separatelly .. 
			// Man, dont ask me why.. It is not my decision
			$topic_detail = InlineManual_Site::fetchTopic($value->id);
			$tmp_topics[$i]['steps'] = is_array($topic_detail->steps) ? $topic_detail->steps : '';
			// walk through actual DB
			foreach($data['topics'] as $s_key => $s_val){
				// if a topic with a sameid exists in the DB already
				// write its perrmission settings to the incoming topic
				if( $s_val['id'] == $value->id && !empty($s_val['permissions']) ){
					$tmp_topics[$i]['permissions'] = $s_val['permissions'];
				}
			}
			$i++;
		}

		echo '<div class="updated"><p>Data loaded and up to date.</p></div>';
	}
	catch (InlineManual_Error $e) {
	  echo '<div class="updated"><p>Response from inlinemanual.com: <span style="color:red">'.$e->getMessage().'</span></p></div>'; 
	}

	// DUMMY TOPIC scheme  for TESTING in this case.. if server is slugish or what ever
	// $tmp_topics = dummy_topic();

	// compare what you get whit what you got
	// * associate just for now
	if(isset($tmp_topics)){
		$data['topics'] = $tmp_topics;
	}
	// save the result to DB
	if(!empty($data)){
		// update the options
		update_option($schema_DB_table, $data);
	}
}
/*
Create WP DB storage
 */

add_action('init', 'inm_DB_options', 99);

function inm_DB_options() {
	$schema = inm_wp_schema();
	// define the table prefix
	global $schema_DB_table;
	// get the options from DB
	$data = get_option($schema_DB_table);
	// write the default data to DB
	if(empty($data)){
		// update the options
		update_option($schema_DB_table, $schema);
	}
}
// Add admin menu
function inm_admin_UI () {
	// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	$icon_url = plugins_url( 'inlinemanual.png' , __FILE__ );
	add_menu_page('Inlinemanual','Inlinemanual','manage_options','inm_settings', 'inm_settings_page', $icon_url);
}
add_action('admin_menu','inm_admin_UI');
 
/**
 * inm Tabbed Settings Page
 */

function inm_load_settings_page() {
	// check if the user clicked the submit button
	$submit = isset($_POST["inm-settings-submit"]) ? $_POST["inm-settings-submit"] : '';
	if ( $submit == 'Y' ) {
		// nonce security

		check_admin_referer( 'inm-settings-page', 'lnc97wrtouwyvo87r' );
		// call function that add write the data to DB
		inm_save_plugin_settings();
	
		// a small HACK for url variable
		// Don't know why, but _GET never sees the "tab" variable.. but _POST does :)
		$referer = parse_url($_POST['_wp_http_referer']);
		$query = array();
		parse_str($referer['query'], $query);
		$tab_query = isset($query['tab']) ? $query['tab'] : '';
		if(isset($_GET['tab'])){
			$url_parameters = 'tab='.$_GET['tab'].'&updated=true';
		}elseif($tab_query != ''){
			$url_parameters = 'tab='.$tab_query.'&updated=true';
		}else{
			$url_parameters = 'updated=true';
		}
		// $url_parameters = isset($_GET['tab']) ? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
		// admin.php?page=inm_settings&tab=settings

		// BETTER DO NOT redirect.. it just makes it all more clumsy
		// wp_redirect(admin_url('?page=inm_settings&'.$url_parameters));
		// exit;
	}
}

function inm_save_plugin_settings() {
	global $pagenow, $schema_DB_table;
	if ( $pagenow == 'admin.php' && $_GET['updated'] == 'true' ){ 
		// get data from DB
		$settings = get_option( $schema_DB_table );
		
		// get the irl variables 
		$referer = $_POST['_wp_http_referer'];
		$tab = get_variable_from_POST($referer, 'tab', 'topics');
		// if ( isset ( $_GET['tab'] ) )
		// 	$tab = $_GET['tab']; 
		// else
		// 	$tab = 'topics'; 

		// save options of the last opend tab only
		switch ( $tab ){ 
			case 'topics' :
				//  DO THE MAGIC WITH TOPIC SETTiNGS
				$a_id = 0;
				foreach ($_POST['topic'] as $key => $value) {
					// key = id
					// var_dump($value); // =  permission
					foreach($settings['topics'] as $s_key => $s_val){
						// var_dump($s_val);
						if($s_val['id'] == $key){
							$settings['topics'][$a_id]['permissions'] = $value['permissions'];
							// var_dump($settings['topics'][$a_id]['permissions']);
						}
					}
					$a_id++;
				}
				// var_dump($settings['topics']);
			break; 
			case 'settings' : 
				$settings['primary_key'][0]  = isset($_POST['api_key']) ? $_POST['api_key'] : '';
				$settings['primary_key'][1]  = isset($_POST['widget_title']) ? $_POST['widget_title'] : '';
			break;
		}
	}
	// clean the topics on user reuest
	$delete_all_topics = (isset($_POST['delete_all_topics']) && $_POST['delete_all_topics'] == true) ? true : false;

	if($delete_all_topics == true){
		$settings['topics'] = array();
	}

	// clean up the user given data
	// if( !current_user_can( 'unfiltered_html' ) ){
	// $settings['primary_key'][0]  = isset($_POST['api_key']) ? esc_attr($_POST['api_key']) : '';
	// write the changes to DB
	// we need to save possibly new api_key first before the we connect to the remote server
	$updated = update_option( $schema_DB_table, $settings );
	// fetch or not the data from server

	$do_fetch = (isset($_POST['do_fetch']) && $_POST['do_fetch'] == true) ? true : false;
	if($do_fetch == true){
		// fetch the data from inlinemanual.com
		inm_wordpress_topics_fetch_all();
	}
	
}

function inm_admin_tabs( $current = 'topics' ) { 
	$tabs = array( 'topics' => 'Topics', 'settings' => 'Account'); 
	$links = array();
	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ){
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='?page=inm_settings&tab=$tab'>$name</a>";
		
	}
	echo '</h2>';
}

function inm_settings_page() {
	global $pagenow, $schema_DB_table;
	?>
	
	<div class="wrap">
		<h2><?php _e('Inlinemanual'); ?></h2>
		<p><?php // _e($description); ?></p>
		<?php
			// inform user about the update
			$updated_msg = (isset($_GET['updated'])) ? $_GET['updated'] : '';
			// Process POST data
			inm_load_settings_page();
			if ( 'true' == esc_attr( $updated_msg ) ) echo '<div class="updated" ><p>Settings updated.</p></div>';
			// switch between tabs based on url variable
			// // get the irl variables 
			// $referer = isset($_POST['_wp_http_referer']) ? $_POST['_wp_http_referer'] : '';
			// $tab = get_variable_from_POST($referer, 'tab', 'topics');
			// inm_admin_tabs($tab);
			// var_dump($tab);
			if ( isset ( $_GET['tab'] ) ) inm_admin_tabs($_GET['tab']); else inm_admin_tabs('topics');
		?>

		<div id="poststuff">
			<form method="post" action="<?php echo admin_url( 'admin.php?page=inm_settings&updated=true'  ); ?>">
				<?php
				wp_nonce_field( 'inm-settings-page', 'lnc97wrtouwyvo87r' ); 
				// if ( $pagenow == 'admin.php' && $_GET['page'] == 'inm_settings' ){ 
				//  TODO check user priviliges
				if ( $_GET['page'] == 'inm_settings' ){ 
					// get data from DB
					$data = get_option($schema_DB_table);
					$description = $data['description'];
					$api_key = $data['primary_key'];
					$topics = $data['topics'];

					// if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; 
					// else $tab = 'topics'; 
					// $referer = isset($_POST['_wp_http_referer']) ? $_POST['_wp_http_referer'] : '' ;
					// $tab = get_variable_from_POST($referer, 'tab', 'topics');
					$tab = isset($_GET['tab']) ? $_GET['tab'] : 'topics';
					echo '<table class="form-table">';
					switch ( $tab ){
						case 'topics' : 
							//Prepare Table of elements
							$wp_list_table = new Topic_List_Table();
							$wp_list_table->prepare_items();
							//Table of elements
							$wp_list_table->display();
							if(!empty($data['topics'])){
								echo '<input type="checkbox" id="delete_all_topics" name="delete_all_topics"> '. __('Delete all topics');
							}
						break;
						case 'settings' : 
							// print in the message from remote server
							?>

							<tr>
								<td>
									<p>
									<input type="text" id="api_key" name="api_key" value="<?php echo $api_key[0]; ?>">
									<span class="description"><?php _e('Enter the site API key (Get it from site detail at <a href="https://inlinemanual.com/sites">Inlinemanual.com - sites</a>)'); ?></span>
									</p>
									<p>
									<input type="text" id="widget_title" name="widget_title" value="<?php echo $api_key[1]; ?>">
									<span class="description"><?php _e('The title of the widget the end-user will see'); ?></span>
									</p>
									<p>
									<input type="checkbox" id="do_fetch" name="do_fetch"> Re/fetch data from Inlinemanual.com
									</p>
								</td>
							</tr>

							
							<?php
						break;	
					}
					echo '</table>';
				}
				?>
				<p class="submit" style="clear: both;">
					<input type="submit" name="Submit"  class="button-primary" value="Update Settings" />
					<input type="hidden" name="inm-settings-submit" value="Y" />
				</p>
			</form>
		</div>

	</div>
<?php
}

// SCAFOLDING - TABLE CLASS
// //Our class extends the WP_List_Table class, so we need to make sure that it's there
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Topic_List_Table extends WP_List_Table {

	/**Re/
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	 function __construct() {
		 parent::__construct( array(
		'singular'=> 'topic', //Singular label
		'plural' => 'topics', //plural label, also this well be one of the table css class
		'ajax'	=> false //We won't support Ajax for this table
		) );
	 }
	 /**
	 * Add extra markup in the toolbars before or after the list
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//The code that goes before the table is here
			echo ""; // if we need any text before the  table
		}
		if ( $which == "bottom" ){
			//The code that goes after the table is there
			echo ""; // if we need any text after the table
		}
	}
	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		return $columns= array(
			'col_link_id'=>__('ID'),
			'col_link_title'=>__('Title'),
			'col_link_version'=>__('Version'),
			'tags'=>__('tags'),
			'col_link_permissions'=>__('Permissions')
		);
	}
	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'id'=>'id',
			'title'=>'title'
		);
	}
	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
		global $schema_DB_table;
		/*
		
		//
		// sorting for the data if they are written in separate table
		//


		global $wpdb, $_wp_column_headers,;
		$screen = get_current_screen();
		
		// 
		//
		// -- Preparing your query -- 
			// $query = "SELECT * FROM $wpdb->options";
		   $query = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE option_name ='inlinemanual_settings'" ) ;
			
		// -- Ordering parameters --
			//Parameters that are going to be used to order the result
			$orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
			$order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
			if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

		// -- Pagination parameters -- 
			//Number of elements in your table?
			$totalitems = $wpdb->query($query); //return the total number of affected rows
			//How many to display per page?
			$perpage = 5;
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
			//Page Number
			if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
			//How many pages do we have in total?
			$totalpages = ceil($totalitems/$perpage);
			//adjust the query to take pagination into account
			if(!empty($paged) && !empty($perpage)){
				$offset=($paged-1)*$perpage;
				$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
			}

		// -- Register the pagination -- 
			$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );
			//The pagination links are automatically built according to those parameters

		// -- Register the Columns -- 
			$columns = $this->get_columns();
			$_wp_column_headers[$screen->id]=$columns;

		// -- Fetch the items -- 
			$this->items = $wpdb->get_results($query);
*/
			$schema = get_option($schema_DB_table);
			$this->items = $schema['topics'];
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {

		//Get the records registered in the prepare_items method
		$records = $this->items;

// var_dump($records);
		// create table head
		echo '<tr>';
		foreach ($records[0] as $key => $val){
			echo '<th>'.$key.'</th>';
		}
		echo '</tr>';
		//Get the columns registered in the get_columns and get_sortable_columns methods
		// list( $columns, $hidden ) = $this->get_column_info();
		//Loop for each record
		if(!empty($records)){
			$i = 0;
			foreach($records as $rec){
				if($i % 2 === 0){
					$class = 'alternate';
				}else{
					$class = '';
				}
				//Open the line
				// var_dump($rec);
				echo '<tr id="record_'.$rec['id'].'" class="'.$class.'">';
				// remove steps we do not need them inhere 

				foreach ( $rec as $rec_name => $rec_val ) {
					$output = '';
					if($rec_name == 'steps'){
						if(!empty($rec_val)){
							// $output .= count((array)$rec_val);	
							$output .= count((array)$rec_val);	
						}
						else{
							$output .= '';	
						}
					}
					// render the select box for permissions
					elseif($rec_name == 'permissions'){
						$current_capability = !empty($rec_val) ? $rec_val : '';
						$capabilities = list_of_capablities();
						$output .= '<select name="topic['.$rec['id'].']['.$rec_name.']">';
							foreach($capabilities as $k => $v){
								if($current_capability == $k){
									$selected = ' selected="selected"';
								}else{
									$selected = '';
								}
								$output .= '<option'.$selected.' value="'.$k.'">'.$v.'</option>';
							}
						$output .= '</select>';
					}
					// print out tags
					elseif($rec_name == 'tags'){
						foreach ($rec_val as $tag) {
							$output .= '<span>'.$tag.'</span>';
							if($tag != end($rec_val)){
								$output .= ', ';
							}
						}
					}		
					else{
						$output = $rec_val;
					}
					echo '<td>'.$output.'</td>';
				}
				//Close the line
				echo'</tr>';
				$i++;
			}
		}
	}
}

// 
function get_variable_from_POST($url, $variable, $default){
		$referer = parse_url($url); // $_POST['_wp_http_referer']
		
		$tab = isset($default) ? $default : 'topics';
		$query = array();
		if(!empty($url)){
			parse_str($referer['query'], $query);
		}
		$tab_query = isset($query[$variable]) ? $query[$variable] : '';
		if(isset($_GET[$variable])){
			$tab = $_GET[$variable];
		}elseif($tab_query != ''){
			$tab = $tab_query;
		}else{
			$tab = $default;
		}
		return $tab;
}

/**
 * Implements hook_schema().
 */
function inm_wp_schema() {
  $schema = array(
	'description' => 'Stores the individual topics imported from inm portal.',
	'fields' => array(
	  '0' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'int',
		// 'not null' => TRUE,
		'default' => 0,
		'description' => 'The topic id.',
	  ),
	  'title' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'varchar',
		// 'length' => 255,
		// 'not null' => TRUE,
		'default' => '',
		'description' => 'Title of the topic.',
	  ),
	  'description' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'text',
		// 'not null' => TRUE,
		// 'size' => 'big',
		'default' => '',
		'description' => 'Description of the topic.',
	  ),
	  'tags' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'text',
		// 'not null' => TRUE,
		// 'size' => 'big',
		'default' => '',
		'description' => 'Topic tags.',
	  ),
	  'timestamp' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'int',
		// 'not null' => FALSE,
		'default' => '',
		'description' => 'Timestamp.',
	  ),
	  'status' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'int',
		// 'not null' => TRUE,
		'default' => 1,
		'description' => 'Boolean indicating whether the topic is enabled.',
	  ),
	  'version' => array(
		'id' => '',
		'value' => '',
		// 'type' => 'varchar',
		// 'length' => 255,
		// 'not null' => TRUE,
		'default' => '',
		'description' => 'Version of the topic.',
	  ),
	),
	'topics' => array(),
	'primary_key' => array('id')
  );
  return $schema;
}
function dummy_topic(){
	$topics = array(
		'0' => array(
			'id' => '1',
			'title' => 'wp',
			'description' => 'descr',
			'version' => '0.1',
			'tags' => array(), //  array {serialize}
			'steps' => '',
			'timestamp' => 0,
			'permissions' => '', // 0 = subscriber / for subscribers http://codex.wordpress.org/Roles_and_Capabilities#User_Levels
			// do not refresh status
		)
	);
	return $topics;
}
function list_of_capablities(){
	$out = array(
		'-2' => __('Hidden'),
		'-1' => __('Public'),
		'0' => __('Subcriber'),
		'1' => __('Contributor'),
		'2' => __('Author'),
		'7' => __('Editor'),
		'10' => __('Administrator'),
	);
	return $out;
}

function f2046_filter_number($string){
	$output = '';
	$output = preg_replace("/[^0-9]/", "", $string );
	return $output;
}
 
