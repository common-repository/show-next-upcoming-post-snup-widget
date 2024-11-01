<?php
/**
 * * Plugin Name
 *
 * @package    Show Next Upcoming Post (SNUP Widget)
 * @author     Bjorn Inge Vaarvik
 * @copyright  2022 Bjorn Inge Vaarvik
 * @license    GPL-3.0-or-later
 * 
 * @Wordpress-plugin
 * 
 * Plugin Name: Show Next Upcoming Post (SNUP Widget)
 * Plugin URI: http://www.vaarvik.com/snup-widget
 * Description: Show a teaser of the next upcoming post
 * Version: 1.3.1
 * Author: Bjorn Inge Vaarvik
 * Author URI: http://www.vaarvik.com
 * Contributor:  Werner A. Bischler
 * Text domain:  snup-lang
 * Domain path:  /languages
 */






/**
 * Plugin version
 */
function get_snup_version() {
    if (!function_exists('get_plugin_data')) {      
        include_once( ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $pluginObject = get_plugin_data( __FILE__ );

    return $pluginObject['Version'];
}


/**
 * Load style CSS.
 */

function snupwidget_enqueue_scripts() {
   wp_enqueue_style('custom-style', plugins_url( '/assets/css/style.css', __FILE__ ), array(),'all');
}
add_action( 'wp_enqueue_scripts', 'snupwidget_enqueue_scripts' );



/**
 * Load plugin textdomain.
 */
function snupwidget_init() {
    load_plugin_textdomain( 'snup-lang', false, dirname(plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'snupwidget_init' );





/*
 * ---------------------------------- *
 * constants
 * ---------------------------------- *
*/

if ( ! defined( 'SNUP_PLUGIN_DIR' ) ) {
    define( 'SNUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SNUP_PLUGIN_URL' ) ) {
    define( 'SNUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}



/**
 * Add SNUP into WP admin dashboard widget
 */

function snup_add_dashboard_widgets() {
    wp_add_dashboard_widget( 
        'snup_dashboard_widget',        // Widget Slug
        'Show Next Upcoming Post',      // Title
        'snup_dashboard_widget_function'     // Display function
    );
}
add_action( 'wp_dashboard_setup', 'snup_add_dashboard_widgets' );
 
/*
 * Output the contents of the dashboard widget
 */
function snup_dashboard_widget_function( $post, $callback_args ) {
    echo wp_kses_post(snup_upcoming_posts_dashboard());
    $label3=__('Your current version of SNUP is:', 'snup-lang');
?>
    <p>
    <label><?php echo esc_html_e($label3), "\n\n\n", get_snup_version();?></label><br>
    </p>
<?php
}


/**
 * List next upcoming post at SNUP Dashboard Widget
 */

function snup_upcoming_posts_dashboard() { 
    // The query to fetch future posts
    $the_query = new WP_Query(array( 
        'post_status' => 'future',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'ASC'
    ));
 
// The loop to display posts
if ( $the_query->have_posts() ) {
    echo wp_kses_post('<ul>');
    while ( $the_query->have_posts() ) {
        $the_query->the_post();
        $output .= ''. ' <div class="snup_title_dash"> '. get_the_title() .' </div><div class="snup_published_dash"> '. __('Published', 'snup-lang') . '</div><div class="snup_time_dash"> '.  get_the_time('d.m.Y H:i') .') </div><br>';

    }
    echo wp_kses_post('<ul>');
 
} else {
    // Show this when no future posts are found
    $output .= '<div class="snup_noplan_dash"> '. __('No planed posts yet.', 'snup-lang') . '</div>';
}
 
// Reset post data
wp_reset_postdata();
 
// Return output
 
return $output; 
} 


// Add shortcode
add_shortcode('snup_dashboard()', 'snup_upcoming_posts_dashboard()'); 
// Enable shortcode execution inside text widgets
add_filter('widget_text', 'do_shortcode');




// Creating the widget 
class SNUP_widget extends WP_Widget {
  
function __construct() {
parent::__construct(
  
// Base ID of your widget
'snup_widget', 
  
// Widget name will appear in UI
__('SNUP Widget', 'snup-lang'), 
  
// Widget description
array( 'Show Next Upcoming Post with featured image, title, custom teaser text and published date' => __( 'Show Next Upcoming Post', 'snup-lang' ), ) 
);
}
  
// Creating widget front-end
  
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
  
// before and after widget arguments are defined by themes
echo wp_kses_post($args['before_widget']);
if ( ! empty( $title ) )
echo wp_kses_post($args['before_title'] . $title . $args['after_title']);

  
// This is where you run the code and display the output
echo wp_kses_post(snupwidget_upcoming_posts());

}
          
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'snup-lang' );
}

// Widget admin form
?>
<p>
<label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
}
      
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}
 
// Class snup_widget ends here
} 
 
 
// Register and load the widget
function snup_load_widget() {
    register_widget( 'snup_widget' );
}
add_action( 'widgets_init', 'snup_load_widget' );





/*
 * ---------------------------------- *
 * Add Custom Text Field 
 * ---------------------------------- *
 */

function snup_add_meta_box(){
    add_meta_box (
        'snup_id', //id
        'Show Next Upcoming Post', //title
        'snup_callback', //callback
        'post', //screen
        'normal', //context
        'default', //priority

    );
}
add_action('add_meta_boxes', 'snup_add_meta_box');


function snup_callback($post){ 
    wp_nonce_field('snup_meta_box', 'snup_meta_box_nonce');

$snuptext = get_post_meta($post->ID, 'snuptext', true);
$snup_custom_title = get_post_meta($post->ID, 'snup_custom_title', true);
$label1=__('Please type the teaser text here','snup-lang');
$label2=__('Max 100 characters.', 'snup-lang');
$label3=__('Custom teaser title.', 'snup-lang');
$label4=__('Show only in the SNUP Widget until the post is published.', 'snup-lang');

?>
    <p>
    <label><b><?php echo esc_html_e($label3);?></b></label><br>
    <label><?php echo esc_html_e($label4);?></label><br>
    <input type="text" name="snup_custom_title" size="50" value="<?php echo esc_textarea($snup_custom_title);?>"></textarea><br><br>
    <label><b><?php echo esc_html_e($label1);?></b></label><br>
    <label><?php echo esc_html_e($label2);?></label><br>
    <textarea style="resize:none" name="snuptext" rows="3" cols="75%" maxlength="100"><?php echo esc_textarea($snuptext);?></textarea>
    </p>
    

<?php }


/*
 * ---------------------------------- *
 * Save Custom Text Field 
 * ---------------------------------- *
 */


function snup_save_meta($post_id) {

// Check if out nonce is set.

    if ( ! isset( $_POST['snup_meta_box_nonce'] ) ) {
        return;
    }

// Verify that the nonce is valid.

    if ( ! wp_verify_nonce( $_POST['snup_meta_box_nonce'], 'snup_meta_box' ) ) {
        return;
    }


// Make sure that it(input) is set.
    if ( ! isset( $_POST['snuptext'] ) ) {
        return;
    }
    if ( ! isset( $_POST['snup_custom_title'] ) ) {
        return;
    }

    
//Sanitize user input.
    $snuptext = sanitize_textarea_field( $_POST['snuptext'] );
    $snup_custom_title = sanitize_textarea_field( $_POST['snup_custom_title'] );


//Update the meta field in the databse.
    update_post_meta( $post_id, 'snuptext', $snuptext );
    update_post_meta( $post_id, 'snup_custom_title', $snup_custom_title );
}


add_action( 'save_post', 'snup_save_meta' );


/*
 * ---------------------------------- *
 * Show the info in SNUP
 * ---------------------------------- *
 */
function snupwidget_upcoming_posts() { 

    $output = '';

    // The query to fetch future posts
    $the_query = new WP_Query(array(
        'post_status' => 'future',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'ASC'
      ));
 

// The loop to display posts
if ( ! $the_query->posts ) {
  return sprintf(
    '<div class="snup_noplan">%s</div>',
    esc_html__('No planned posts yet.', 'snup-lang')
  );
}

$items = array();
foreach ($the_query->posts as $post) {

  $snuptext = get_post_meta( $post->ID, 'snuptext', true );
  if ( $snuptext && is_string( $snuptext ) ) {
    $snuptext = str_replace(array("\r\n", "\r", "\n"), "<br />", $snuptext);
  } else {
    $snuptext = '';
  }
  
  $items[] = sprintf(
    '<ul class="snup_ul">
        <li>
        %s
        <div class="snup_custom_title">%s</div>
        <div class="snup_text">%s</div>
        <div class="snup_published">%s</div>
        <div class="snup_time">%s</div>
        </li>
    </ul>',
    get_the_post_thumbnail($post),
    esc_html( $post->snup_custom_title ),
    esc_html( $snuptext ),
    esc_html__('Published:', 'snup-lang'),
    get_the_time('l d.m.Y H:i', $post),
  );
}

return sprintf('<ul>%s</ul>', implode('', $items));
}
 



// Add shortcode
add_shortcode('snup-widget', 'snupwidget_upcoming_posts'); 
// Enable shortcode execution inside text widgets
add_filter('widget_text', 'do_shortcode');


?>