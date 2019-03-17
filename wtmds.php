<?php
/*
Plugin Name: wshbr-wordpress-themoviedb-sidebar
Plugin URI: https://github.com/Machigatta/wshbr-wordpress-themoviedb-sidebar
Description: wshbr.de - Provides a sidebar with information from https://www.themoviedb.org
Author: Machigatta
Author URI: https://machigatta.com/
Version: 1.2
Stable Tag: 1.2
*/

class wtmds
{
	public function __construct() {
		add_action('plugins_loaded', 'wtmds_init');
		add_action('admin_init', 'wtmds_config_init');
		add_shortcode('tmdb_sidebar', array($this, 'shortCode'));
		add_action('wp_enqueue_scripts', array($this, 'addStylesAndScripts'));
	}

	/* Load translation, if it exists */
	function wtmds_init() {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'wtmds', null, $plugin_dir.'/languages/' );
	}



	//Retrieve the Post Object
	function getPostObject($post_id) {
		$post_url = get_permalink($post_id);
		$title = strip_tags(get_the_title($post_id));
		$tagObjects = get_the_tags($post_id);
		$single = is_single();
		$tags = "";
		if (!empty($tagObjects)) {
			$tags .= $tagObjects[0]->name;
			for ($i = 1; $i < count($tagObjects); $i++) {
				$tags .=  ",".$tagObjects[$i]->name;
			}
		}
		$category = get_the_category($post_id);
		$categories = "";
		if (!empty($category)) {
			$categories .= $category[0]->name;
			for ($i=1; $i<count($category); $i++) {
				$categories .= ",".$category[$i]->name;
			}
		}
		$author = get_the_author();
		$date = get_the_date('U', $post_id) * 1000;
		$comments = get_comments_number($post_id);

		$tmdb = get_post_meta($post_id,'imdb_id');

		$post_object = array(
			'id' => $post_id,
			'url' => $post_url,
			'title' => $title,
			'tags' => $tags,
			'categories' => $categories,
			'comments' => $comments,
			'date' => $date,
			'author' => $author,
			'single' => $single,
			'img' => get_the_post_thumbnail_url($post_id),
			'tmdb' => $tmdb
		);
		return $post_object;
	}

	function wtmds_config_init(){
		wp_enqueue_style('tmbd-sidebar-admin-style',  trailingslashit(plugin_dir_url(__FILE__)) . 'assets/css/admin-style.css',array(),'0.0.1');
	}

	function addStylesAndScripts(){
		wp_enqueue_style('tmbd-sidebar-style',  trailingslashit(plugin_dir_url(__FILE__)) . 'assets/css/style.css',array(),'0.0.4');
		wp_enqueue_script('tmdb-sidebar-script',  trailingslashit(plugin_dir_url(__FILE__)) . 'assets/js/main.js',array('jquery'),'0.0.6');
	}

	public function renderSidebar($post_object){
		$tmdb->type = null;
		$tmdb->id = null;
		if (is_single($post_object["id"])) {
			if($post_object["tmdb"] != null){
				$tmdburl = $post_object["tmdb"][0];
				$tmdburl = str_replace("https://www.themoviedb.org/","",$tmdburl);
                if($tmdburl != ""){
                    $splits = explode("/",$tmdburl);
                    $tmdb->type = $splits[0];
                    $extraSplits = explode("-",$splits[1]);
                    $tmdb->id = $extraSplits[0];
                }
			}
		}

		if($tmdb->id != null){
			$loader = '<div class="loader">
							<h4>Lade Details...</h4>
							<div></div>
						</div>
						
						<script>var TMDB_DATA = {id: '.$tmdb->id.',type:"'.$tmdb->type.'"}</script> ';

			return $loader;
		}
	}

	//[tmdb_sidebar]
	function shortCode() {
		$post_id = get_the_ID();
		$post_object = $this->getPostObject($post_id);
		return $this->renderSidebar($post_object);
	}
}

function tmdb_sidebar()
{
	// if (function_exists('tmdb_sidebar')) { tmdb_sidebar(); }
	$wtmds = new wtmds();
	$post_id = get_the_ID();
	$post_object = $wtmds->getPostObject($post_id);
	echo $wtmds->renderSidebar($post_object);
}

function is_tmdb_ready()
{
	// if (function_exists('tmdb_sidebar')) { tmdb_sidebar(); }
	$wtmds = new wtmds();
	$post_id = get_the_ID();
	return $wtmds->getPostObject($post_id)["tmdb"][0] != null;
}

new wtmds();


//Register MetaBox
function wtmds_meta_custom() {
	$custom_post_types = get_post_types();
	array_push($custom_post_types,'page');
	foreach ($custom_post_types as $t) {
		$defaults = get_option('hotStuffDefaults'.ucfirst($t));
		if (!isset($defaults['activeMetaBox']) || $defaults['activeMetaBox'] == 'active') {
			add_meta_box('wtmds_div_imdb', __('TMDB','post-expirator'), 'wtmds_imdb_box', $t, 'side', 'core');
		}
	}	
}

//Draw Meta-Box
function wtmds_imdb_box($post) { 
	// Get Current_Value from Post-Meta
	$imdbvalue = get_post_meta($post->ID,"imdb_id",true);

	// security check
	wp_nonce_field( plugin_basename( __FILE__ ), 'wtmds_nonce' );

	//Draw Box
	echo "<div style='width:100%'>
			<p>
				Hier den Movie-DB-Link eintragen:
			</p>
			<input type='text' name='imdb_id' id='imdb_id' style='width:100%' value='".$imdbvalue."'>
			<p>
				Bsp: - https://www.themoviedb.org/tv/61555-the-missing
			</p>
		</div>";
}

add_action ('add_meta_boxes','wtmds_meta_custom');

//After save -> save the id
function wtmds_imdb_data($post_id){
	// check if this isn't an auto save
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;
	// security check
	if ( !wp_verify_nonce( $_POST['wtmds_nonce'], plugin_basename( __FILE__ ) ) )
		return;
	
	if ( isset( $_POST['imdb_id'] ) ) :
		update_post_meta( $post_id, 'imdb_id', $_POST['imdb_id'] );
	endif;        
}

add_action( 'save_post', 'wtmds_imdb_data' );