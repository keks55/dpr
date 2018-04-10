<?php
/*
Plugin Name: DPR
Plugin URI:  http://keksus.com/dpr.html
Description: Delete post revisions and disable revisions
Version:     1.0
Author:      Keksus
Author URI:  http://keksus.com/
License:     GPLv3

Copyright 2018-2019 Keksus.com (email : alexbalance@gmail.com)
*/ 

if(!defined('ABSPATH')) die;

global $dpr_option_name, $dpr_options;
$dpr_option_name = 'dpr';
$dpr_options = get_option($dpr_option_name);

register_activation_hook( __FILE__, 'dpr_activate' );
register_deactivation_hook( __FILE__, 'dpr_deactivate' );

function dpr_activate() {
    global $dpr_option_name, $dpr_options;

    $data = array(
        'disable' => null
    );
    update_option( $dpr_option_name, $data );

    $notice =  __( 'Thank you for installing this plugin! Plugin settings are available on the page', 'dpr_activate_notice' ) .
                      ' <a href="' . admin_url( 'options-general.php?page=dpr_settings' ). '">Settings - DPR</a>';
    update_option( 'dpr_activate_notice', $notice );
}

function dpr_activate_notice() {
    if( $notice = get_option( 'dpr_activate_notice' ) ) {
        echo "<div class='updated'><p>$notice</p></div>";
    }
    delete_option( 'dpr_activate_notice' );
}
add_action( 'admin_notices', 'dpr_activate_notice' );

function dpr_deactivate() {
    global $dpr_option_name;
    delete_option( $dpr_option_name );
}

// show plugin footer text
function dpr_this_screen() {
    if( get_current_screen()->id === "settings_page_dpr_settings" ) {
        add_filter( 'update_footer', 'dpr_right_admin_footer_text', 11); 
        function dpr_right_admin_footer_text( $text ) {
            $text = current_time( 'Y-m-d' );
            return $text;
        }
        add_filter( 'admin_footer_text', 'dpr_left_admin_footer_text' ); 
        function dpr_left_admin_footer_text( $text ) {
            $text = __( 'Thank you for installing this plugin! Created by', 'dpr' ).' <a class="created" href="http://keksus.com">Keksus</a>';
            return $text;
        }
    }
}
add_action( 'current_screen', 'dpr_this_screen' );

function dpr_scripts_admin() {
    if ( get_current_screen()->id === "settings_page_dpr_settings" ) {
        wp_enqueue_style( 'admin-css',   plugins_url( 'css/admin.css',__FILE__ ) );
        wp_enqueue_style( 'admin-icons', plugins_url( 'css/ionicons.min.css',__FILE__ ) );
        wp_enqueue_script( 'admin-js',   plugins_url( 'js/admin.js',__FILE__ ), array(jquery) );
    }
}
add_action( 'admin_enqueue_scripts', 'dpr_scripts_admin' );

function dpr_admin_page_settings() {
    add_options_page('DPR', 'DPR', 'edit_pages', 'dpr_settings', 'dpr_admin_page');
}
add_action('admin_menu', 'dpr_admin_page_settings');

// register plugin settings
function dpr_plugin_settings() {
    global $dpr_option_name, $dpr_options;
    register_setting( 'dpr_settings', $dpr_option_name, 'dpr_sanitize' );
}
add_action( 'admin_init', 'dpr_plugin_settings' );

function dpr_admin_page() {
    global $wpdb, $dpr_option_name, $dpr_options;
    $checked = (is_array($dpr_options) && $dpr_options['disable'] == '1') ? 'checked="checked"' : null;
    $revisions = $wpdb->get_results("SELECT count(*) as count FROM $wpdb->posts WHERE post_type = 'revision' ");
    if($revisions[0]->count > 0) {
        $wpdb->query("
            DELETE p,tr,pm 
            FROM $wpdb->posts p 
            LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id 
            LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'revision'
        ");        
    }
    $file = ABSPATH ."wp-config.php";
    chmod($file, 0777);
        $f = fopen($file, 'r+') or die('Error: Can`t open wp-config.php file.');
        if(is_array($dpr_options) && $dpr_options['disable'] == '1'){ 
            while(($buffer = fgets($f)) !== false){
                $needle = "define('DB_COLLATE', '');";
                if(strpos($buffer, $needle) !== false){
                    $cursor = ftell($f);
                    fseek($f, $cursor, SEEK_SET);
                    fputs($f, "\n// Disable post revisions, added by DPR plugin, http://keksus.com/dpr.html");
                    fputs($f, "\ndefine('WP_POST_REVISIONS', false);");
                    fputs($f, "\n");
                    fputs($f, "\n/* Authentication Keys and Salts, @link https://api.wordpress.org/secret-key/1.1/salt/   ");
                }
            }
        }
        else{
            while(($buffer = fgets($f)) !== false){
                $needle = "define('WP_POST_REVISIONS', false);";
                if(strpos($buffer, $needle) !== false){
                    $cursor = ftell($f);
                    fseek($f, $cursor-36, SEEK_SET);
                    fputs($f, "define('WP_POST_REVISIONS', true); ");
                }
            }
        }
        fclose($f);
    chmod($file, 0644);

?>
<div class='options'>
    <h1><?php _e( 'DPR Settings', 'dpr' ); ?></h1>
    <form class='dpr-form' method='POST' action="options.php" >
    <?php settings_fields( 'dpr_settings' ); ?>
        <div class='q12'>
            <div class='tabs'>
                <ul class='tab-links'>
                    <li class='active'><a href='#tab1'><?php _e( 'Settings', 'dpr' ); ?></a></li>
                    <li><a href='#tab2'><?php _e( 'wp-config.php', 'dpr' ); ?></a></li>
                    <li><a href='#tab3'><?php _e( 'Info', 'dpr' ); ?></a></li>
                </ul>
            </div>
            <div class='tab-content active'>
                <div id='tab1' class='tab active'>
                    <div class='clear'>
                        <div class='q8'>
                            <div class='post'>
                                <h5>You have <strong><?php echo $revisions[0]->count; ?></strong> post revisions</h5>
                                <?php print_r($dpr_options); ?>

                                <input type="checkbox" name="<?php echo $dpr_option_name?>[disable]" value="1"<?php echo $checked; ?> />
                                <label for="text_field"><strong>Disable post revisions</strong> </label> 
                                 <p>If field "Disable post revisions" checked, to file <strong>wp-config.php</strong> after line <strong>define('DB_COLLATE', '');</strong> the following lines will be added </p> 
                                    <pre>// Disable post revisions, added by DPR plugin, http://keksus.com/dpr.html
define('WP_POST_REVISIONS', false);</pre>
                                        <p>If you aren't using the original <strong>wp-config.php</strong> or if you added or deleted rows earlier, check on wp-config.php tab that the lines you need were not deleted.</p>
                                <br><br>
                                <?php 
                                //
                                ?>                           
                            </div>
                        </div>
                    </div>
                    <input type="submit" class='button-primary' name="dpr_submit" value="<?php echo ($revisions[0]->count > 0 ) ? 'Delete revisions and' : null; ?> Save" />
                </div>
                 <div id='tab2' class='tab'>
                    <div class='clear'>
                        <div class='q8'>
                            <div class='post'>
                                <h5>Current contents of the wp-config.php file</h5>
                                <?php 
                                    $f = fopen($file, 'r') or die('Error: Can`t open wp-config.php file.');
                                    $lines = file($file);
                                    if(is_array($lines)){
                                        echo '<pre>';
                                        $i = 1;
                                        foreach($lines as $line){
                                            echo $i." ".$line."<br>";
                                            $i++;
                                        }                                       
                                        echo '</pre>';
                                    }
                                    fclose($f);
                                ?>                           
                            </div>
                        </div>
                    </div>
                </div>
                <div id='tab3' class='tab'>
                    <div class='clear'>
                        <div class='q8'>
                            <div class='post'>
                                <h2><?php _e( 'License:', 'dpr' ); ?></h2>
                                    <p><?php _e( 'This plugin is licensed under the', 'dpr' );?>
                                        <a target="_blank" href="https://www.gnu.org/licenses/gpl-3.0.html">GPL v3 license</a>
                                    <?php _e( 'This means you can use it for anything you like as long as it remains GPL v3.', 'dpr' ); ?></p>

                                <h2><?php _e( 'Links:', 'dpr' ); ?></h2>
                                    <p><?php _e( 'This plugin was created by', 'dpr' );?>
                                        <a target="_blank" href="http://keksus.com/">Keksus.com</a> <?php _e( 'You can follow us via our social media!', 'dpr' ); ?>
                                    </p>
                                    <p class='links'>
                                        <a target="_blank" href="https://twitter.com/keks5588" class="button button-secondary">Twitter</a>
                                        <a target="_blank" href="https://www.facebook.com/keks5588" class="button button-secondary">Facebook</a>
                                        <a target="_blank" href="https://plus.google.com/110925729980114845157" class="button button-secondary">Google +</a>
                                        <a target="_blank" href="https://www.instagram.com/keksus55/" class="button button-secondary">Instagram</a>
                                    </p>

                                <h2><?php _e( 'Donation:', 'dpr' ); ?></h2>
                                <p><?php _e( 'If you would like this plugin, you can donate any amount for other plugins development.', 'dpr' ); ?></p>
                                <p class='links'><a href="http://keksus.com/donate.html" target="_blank" class="button button-secondary">
                                    <?php _e( 'Donate', 'dpr' ); ?>
                                    </a>
                                </p>
                            </div><!-- end post -->
                        </div>
                    </div>      
                </div>
                
            </div>
            
        </div>
    </form>
</div>
<?php
} // dpr_admin_page

// clean options and show message
function dpr_sanitize($dpr_options) {  
    $dpr_type = 'updated';
    $dpr_message = __( 'Settings saved!', 'dpr' );
    add_settings_error( $dpr_option_name, 'settings_updated', $dpr_message, $dpr_type );

    foreach( $dpr_options as $name => $val ) {
        if( $name == 'disable') {
            $val = intval($val);
        }
        $val = sanitize_text_field($val);
    }
    return $dpr_options; //die(print_r( $dpr_options ) );
}
