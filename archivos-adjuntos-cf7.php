<?php
/**
 * Plugin Name: Archivos Adjuntos CF7
 * Description: Campo Drag & Drop para Contact Form 7 con almacenamiento configurable, validaciones y estilos por formulario.
 * Version: 1.0.0
 * Author: RedInforCom
 * Text Domain: aacf7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AACF7_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AACF7_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AACF7_VERSION', '1.0.0' );

require_once AACF7_PLUGIN_DIR . 'includes/class-aacf7-admin.php';
require_once AACF7_PLUGIN_DIR . 'includes/class-aacf7-frontend.php';

function aacf7_init_plugin() {
    // Init admin and frontend singletons
    if ( is_admin() ) {
        AACF7_Admin::instance();
    }
    AACF7_Frontend::instance();
}
add_action( 'plugins_loaded', 'aacf7_init_plugin' );

// Activation: ensure uploads folder exists
function aacf7_activate() {
    $upload_dir = wp_upload_dir();
    $default_dir = trailingslashit( $upload_dir['basedir'] ) . 'adjuntoscf7';
    if ( ! file_exists( $default_dir ) ) {
        wp_mkdir_p( $default_dir );
    }
}
register_activation_hook( __FILE__, 'aacf7_activate' );
?>