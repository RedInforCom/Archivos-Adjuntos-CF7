<?php
/**
 * Plugin Name: Archivos Adjuntos CF7
 * Plugin URI:  https://example.com/archivos-adjuntos-cf7
 * Description: Añade un campo de subida Drag & Drop y botón a Contact Form 7 con configuración por formulario, almacenamiento configurable y opciones de estilos.
 * Version:     1.0.0
 * Author:      Tu Nombre
 * Text Domain: archivos-adjuntos-cf7
 * Domain Path: /languages
 * License:     GPLv2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AACF7_VERSION', '1.0.0' );
define( 'AACF7_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AACF7_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AACF7_PLUGIN_DIR . 'includes/class-adjuntos-cf7.php';

function aacf7_init_plugin() {
	$plugin = new Adjuntos_CF7();
	$plugin->run();
}
add_action( 'plugins_loaded', 'aacf7_init_plugin' );