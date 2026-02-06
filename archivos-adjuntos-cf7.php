<?php
/**
 * Plugin Name: Archivos Adjuntos CF7
 * Plugin URI: https://tu-sitio.com/archivos-adjuntos-cf7
 * Description: Agrega funcionalidad avanzada de carga de archivos a Contact Form 7 con drag & drop, validaciones personalizadas y gestión de almacenamiento
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tu-sitio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: archivos-adjuntos-cf7
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('AACF7_VERSION', '1.0.0');
define('AACF7_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AACF7_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AACF7_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class Archivos_Adjuntos_CF7 {
    
    private static $instance = null;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Verificar si Contact Form 7 está activo
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        
        // Hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Cargar archivos necesarios
        add_action('plugins_loaded', array($this, 'load_includes'));
        
        // Cargar assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Verificar dependencias
     */
    public function check_dependencies() {
        if (!class_exists('WPCF7')) {
            add_action('admin_notices', array($this, 'dependency_notice'));
            deactivate_plugins(AACF7_PLUGIN_BASENAME);
            return;
        }
    }
    
    /**
     * Aviso de dependencias
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Archivos Adjuntos CF7 requiere que Contact Form 7 esté instalado y activo.', 'archivos-adjuntos-cf7'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Configurar cron job
        if (!wp_next_scheduled('aacf7_cleanup_files')) {
            wp_schedule_event(time(), 'daily', 'aacf7_cleanup_files');
        }
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Remover cron job
        $timestamp = wp_next_scheduled('aacf7_cleanup_files');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aacf7_cleanup_files');
        }
    }
    
    /**
     * Cargar archivos del plugin
     */
    public function load_includes() {
        if (!class_exists('WPCF7')) {
            return;
        }
        
        require_once AACF7_PLUGIN_DIR . 'includes/class-file-field.php';
        require_once AACF7_PLUGIN_DIR . 'includes/class-admin-panel.php';
        require_once AACF7_PLUGIN_DIR . 'includes/class-file-handler.php';
        require_once AACF7_PLUGIN_DIR . 'includes/class-cron.php';
        require_once AACF7_PLUGIN_DIR . 'includes/class-tag-generator.php';
        
        // Inicializar clases
        new AACF7_File_Field();
        new AACF7_Admin_Panel();
        new AACF7_File_Handler();
        new AACF7_Cron();
        new AACF7_Tag_Generator();
    }
    
    /**
     * Cargar assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_cf7_page()) {
            return;
        }
        
        wp_enqueue_style(
            'aacf7-frontend',
            AACF7_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            AACF7_VERSION
        );
        
        wp_enqueue_script(
            'aacf7-frontend',
            AACF7_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            AACF7_VERSION,
            true
        );
        
        wp_localize_script('aacf7-frontend', 'aacf7Data', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aacf7_nonce')
        ));
    }
    
    /**
     * Cargar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wpcf7') === false) {
            return;
        }
        
        wp_enqueue_style(
            'aacf7-admin',
            AACF7_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            AACF7_VERSION
        );
        
        wp_enqueue_script(
            'aacf7-admin',
            AACF7_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            AACF7_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
    }
    
    /**
     * Verificar si es una página con CF7
     */
    private function is_cf7_page() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'contact-form-7')) {
            return true;
        }
        
        return false;
    }
}

// Inicializar el plugin
function aacf7_init() {
    return Archivos_Adjuntos_CF7::get_instance();
}

// Ejecutar
aacf7_init();
