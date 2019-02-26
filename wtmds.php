<?php
/*
Plugin Name: wshbr-wordpress-themoviedb-sidebar
Plugin URI: https://github.com/Machigatta/wshbr-wordpress-themoviedb-sidebar
Description: wshbr.de - Provides a sidebar with information from https://www.themoviedb.org
Author: Machigatta
Author URI: https://machigatta.com/
Version: 0.1
Stable Tag: 0.1
*/

/* Load translation, if it exists */
function wtmds_init() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'wtmds', null, $plugin_dir.'/languages/' );
}
add_action('plugins_loaded', 'wtmds_init');

//Register MetaBox
function wtmds_meta_custom() {
	$custom_post_types = get_post_types();
	array_push($custom_post_types,'page');
	foreach ($custom_post_types as $t) {
		$defaults = get_option('hotStuffDefaults'.ucfirst($t));
		if (!isset($defaults['activeMetaBox']) || $defaults['activeMetaBox'] == 'active') {
			add_meta_box('wtmds_div_imdb', __('IMDB','post-expirator'), 'wtmds_imdb_box', $t, 'side', 'core');
		}
	}	
}
add_action ('add_meta_boxes','wtmds_meta_custom');

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


//Add Style to admin-panel
function wtmds_config_init(){
	wp_register_style('mainCss', plugins_url('/assets/style.css',__FILE__ ));
	wp_enqueue_style('mainCss');
}
add_action('admin_init', 'wtmds_config_init');