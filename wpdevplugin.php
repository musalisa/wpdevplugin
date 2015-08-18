<?php
/*
Plugin Name: WP Dev Plugin
Plugin URI: 
Description: WP Dev Plugin
Version: 1.0
Author: SB
Author URI: http://webdevstudios.com
License: GPLv2
*/


// Action hook to initialize the plugin
add_action( 'init', 'wpdevplugin_init' );

add_filter( 'jetpack_development_mode', '__return_true' );

if ( !class_exists( 'WPDev' ) ) :

class WPDev {

	public static function instance() {
	
		// Store the instance locally to avoid private static replication
		static $instance = null;
	
		// Only run these methods if they haven't been run previously
		if ( null === $instance ) {
			$instance = new WPDev;
			$instance->constants();
			//$instance->setup_globals();
			//$instance->includes();
		}
	
		// Always return the instance
		return $instance;
	}
	
	private function __construct() { /* Do nothing here */ 
	}
	
	private function constants() {
	
		// Path and URL
		if ( ! defined( 'WPDEV_PLUGIN_DIR' ) ) {
			define( 'WPDEV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
	
		if ( ! defined( 'WPDEV_PLUGIN_URL' ) ) {
			define( 'WPDEV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
		
		// Only applicable to those running trunk
		if ( ! defined( 'WPDEV_SOURCE_SUBDIRECTORY' ) ) {
			define( 'WPDEV_SOURCE_SUBDIRECTORY', '' );
		}
	}
	
	private function setup_globals() {
	
		// WPDev root directory
		$this->file           = constant( 'WPDEV_PLUGIN_DIR' ) . 'wpdevplugin.php';
		$this->basename       = basename( constant( 'WPDEV_PLUGIN_DIR' ) ) . '/bp-loader.php';
		$this->plugin_dir     = trailingslashit( constant( 'WPDEV_PLUGIN_DIR' ) . constant( 'WPDEV_SOURCE_SUBDIRECTORY' ) );
		$this->plugin_url     = trailingslashit( constant( 'WPDEV_PLUGIN_DIR' ) . constant( 'WPDEV_SOURCE_SUBDIRECTORY' ) );
	
	}
	
	private function includes() {
	
		require( $this->plugin_dir . 'includes/wpdev-classes.php' );
	}	
}

function wpdev() {
	return WPDev::instance();
}

$GLOBALS['wpdev'] = wpdev();

$wpdev = $GLOBALS['wpdev'];

endif;

function wpdevplugin_init() {
	// Set up some labels for the post type
		$labels_file = array(
			'name'	             => __( 'BuddyFiles', 'buddydrive' ),
			'singular'           => __( 'BuddyFile', 'buddydrive' ),
			'menu_name'          => __( 'BuddyDrive Files', 'buddydrive' ),
			'all_items'          => __( 'All BuddyFiles', 'buddydrive' ),
			'singular_name'      => __( 'BuddyFile', 'buddydrive' ),
			'add_new'            => __( 'Add New BuddyFile', 'buddydrive' ),
			'add_new_item'       => __( 'Add New BuddyFile', 'buddydrive' ),
			'edit_item'          => __( 'Edit BuddyFile', 'buddydrive' ),
			'new_item'           => __( 'New BuddyFile', 'buddydrive' ),
			'view_item'          => __( 'View BuddyFile', 'buddydrive' ),
			'search_items'       => __( 'Search BuddyFiles', 'buddydrive' ),
			'not_found'          => __( 'No BuddyFiles Found', 'buddydrive' ),
			'not_found_in_trash' => __( 'No BuddyFiles Found in Trash', 'buddydrive' )
		);
		
		$args_file = array(
			'label'	            => __( 'BuddyFile', 'buddydrive' ),
			'labels'            => $labels_file,
			'public'            => true,
			'rewrite'           => false,
			'show_ui'           => true,
			'show_in_admin_bar' => true,
			'supports'          => array( 'title', 'editor', 'author' )
		);
	register_post_type ('buddydrive-file', $args_file);
	
	// Register custom taxonomies
	register_taxonomy( 'media', 'buddydrive-file', array('hierarchical' => true, 'label' => 'Media', 'query_var' => true, 'rewrite' => true));
	add_post_type_support( 'buddydrive-file', 'custom-fields' ); 

}

//Stampa la form dei custosm fields di buddypress espandendo la classe walker
function tvox_bd_uplaoder_custom_fields( $item_id ) {
	// Stampo gli imput fields
	echo( '<label for="tvox_bd_title">Title</label>' );
	echo( '<input type="text" name="tvox_bd_title" class="buddydrive-customs" value="' . esc_attr( get_post_meta( $item_id, 'tvox_bd_title', TRUE ) ) . '" />' );
	echo( '<label for="tvox_bd_artist">Artist</label>' );
	echo( '<input type="text" name="tvox_bd_artist" class="buddydrive-customs" value="' . esc_attr( get_post_meta( $item_id, 'tvox_bd_artist', TRUE ) ) . '" />' );
	echo( '<label for="tvox_bd_media_terms">Media Category</label> ');
	
	// Estraggo l'alberatura della taxonomy media
	$args = array(
			'type'                     => 'buddydrivefile',
			'child_of'                 => 0,
			'parent'                   => '',
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 0,
			'hierarchical'             => 1,
			'exclude'                  => '',
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => 'media',
			'pad_counts'               => flase
	);
	$media_terms = get_categories( $args );
	
	
	// Estraggo i terms dell'oggetto e preparo il valore della variabile hidden
	$object_terms = wp_get_object_terms( $item_id, 'media' );
	$object_term_ids = array();
	
	foreach ( $object_terms as $term ) {
		$object_term_ids[] = $term->term_id;
	}	
	$hidden_term_ids = serialize( $object_term_ids );
	
	// Stampo il walker per la selezione dei terms, con i valori già checked
	$walker = new Tvox_BD_Media_Walker();
	print_r( $walker->walk( $media_terms, 10, array ('checked' => $object_term_ids) ));

?>

<input type=hidden name="tvox_bd_media_terms"   id="tvox_bd_media_terms" class="buddydrive-customs" value="<?php echo $hidden_term_ids; ?>" />
<script type="text/javascript">
function tvox_set_hidden_terms() {
	
	var tvox_bd_terms = [];
	jQuery('input.tvox_bd_term_check:checkbox:checked').each(function() {
		tvox_bd_terms.push( parseInt(jQuery(this).val()) );
		});
	jQuery('input#tvox_bd_media_terms').val(serialize( tvox_bd_terms));
	}
	<?php if ( $item_id ) : ?>
		tvox_set_hidden_terms();	
 	<?php endif; ?>
 jQuery('textarea#buddyfile-desc').attr('maxlength', '500');
 jQuery('textarea#buddyfile-desc').attr('placeholder', '500 characters to do so'); 
 jQuery('textarea#buddydrive-item-content').attr('maxlength', '500');
 jQuery('input.tvox_bd_term_check').click(tvox_set_hidden_terms);

</script>
	
<?php 
}
add_action( 'buddydrive_uploader_custom_fields', 'tvox_bd_uplaoder_custom_fields' );

// Salva i terms del custom field dei terms
function buddydrive_tvox_set_object_terms( $bd_item_id, $bd_params ) { 
	$cat_ids = unserialize( get_post_meta( $bd_item_id, 'tvox_bd_media_terms', true ) );
	$cat_ids = array_map( 'intval', $cat_ids );
	$cat_ids = array_unique( $cat_ids );
	wp_set_object_terms( $bd_item_id, $cat_ids, 'media' );	
}
add_action( 'buddydrive_save_item', 'buddydrive_tvox_set_object_terms', 10, 2 );

// Aggiungo il menu della taxonomy meia
function tvox_bd_register_media_page() {
	add_menu_page( __( 'BuddyDrive Media', 'buddydrivemedia' ),
			__( 'BuddyDrive Media', 'buddydrivemedia' ),
			'manage_options',
			'edit-tags.php?taxonomy=media&post_type=buddydrive-file',
			'',
			'',
			61 );
}
add_action( 'admin_menu', 'tvox_bd_register_media_page' );

//Carico le classi nessarie a questo plugin - Togliere pathassoluto
function wpdev_load_classes() {
	//$wpdev_classes = plugin_dir_path( __FILE__ ). ’includes/wpdev-classes.php’;
	//include_once( $wpdev_classes );
	require( constant( 'WPDEV_PLUGIN_DIR' ) . 'includes/wpdev-classes.php'  );
	//require '/home/viridiana/local/var/www/wpdev/wp-content/plugins/wpdevplugin/includes/wpdev-classes.php';	
}
add_action( 'bp_buddydrive_includes', 'wpdev_load_classes' );
