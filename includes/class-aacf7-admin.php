<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AACF7_Admin {
    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post_wpcf7_contact_form', array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets( $hook ) {
        // Only on Contact Form 7 edit screen
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) return;
        global $post;
        if ( ! $post || $post->post_type !== 'wpcf7_contact_form' ) return;

        wp_enqueue_style( 'aacf7-admin', AACF7_PLUGIN_URL . 'assets/css/admin.css', array(), AACF7_VERSION );
        wp_enqueue_script( 'aacf7-admin', AACF7_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AACF7_VERSION, true );
        wp_localize_script( 'aacf7-admin', 'aacf7_admin', array(
            'nonce' => wp_create_nonce( 'aacf7_admin_nonce' )
        ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'aacf7_meta',
            __( 'Adjuntos CF7', 'aacf7' ),
            array( $this, 'render_meta_box' ),
            'wpcf7_contact_form',
            'normal',
            'default'
        );
    }

    public function render_meta_box( $post ) {
        // Load existing settings or defaults
        $defaults = array(
            'storage' => array(
                'type' => 'uploads_subdir', // uploads_subdir|custom_path|external_url
                'subdir' => 'adjuntoscf7',
                'custom_path' => '',
                'external_url' => '',
                'attach_to_mail' => true,
                'delete_after_days' => 0,
            ),
            'options' => array(
                'max_size_kb' => 2048,
                'max_files' => 3,
            ),
            'types' => array( 'jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx' ),
            'texts' => array(
                'title' => 'Archivos Adjuntos',
                'drop_text' => 'Arrastra los archivos aquí o haz clic',
                'button_text' => 'Adjuntar archivos',
                'note' => 'Puedes enviar varios archivos.',
            ),
            'validations' => array(
                'required' => 'Este campo es obligatorio.',
                'size_exceeded' => 'Tamaño máximo excedido.',
                'count_exceeded' => 'Cantidad máxima de archivos excedida.',
                'type_not_allowed' => 'Tipo de archivo no permitido.',
            ),
            'styles' => array() // leave empty; admin UI allows adding many style options
        );

        $settings = get_post_meta( $post->ID, '_aacf7_settings', true );
        if ( ! $settings || ! is_array( $settings ) ) $settings = $defaults;

        wp_nonce_field( 'aacf7_save_meta', 'aacf7_meta_nonce' );

        // Render tabs and panels (HTML will be enhanced by admin.js)
        include AACF7_PLUGIN_DIR . 'includes/views/admin-meta-box.php';
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['aacf7_meta_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['aacf7_meta_nonce'], 'aacf7_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        $settings = array();

        // Storage
        $settings['storage'] = array(
            'type' => sanitize_text_field( $_POST['aacf7_storage_type'] ?? 'uploads_subdir' ),
            'subdir' => sanitize_text_field( $_POST['aacf7_subdir'] ?? 'adjuntoscf7' ),
            'custom_path' => sanitize_text_field( $_POST['aacf7_custom_path'] ?? '' ),
            'external_url' => esc_url_raw( $_POST['aacf7_external_url'] ?? '' ),
            'attach_to_mail' => isset( $_POST['aacf7_attach_to_mail'] ) ? true : false,
            'delete_after_days' => intval( $_POST['aacf7_delete_after_days'] ?? 0 ),
        );

        // Options
        $settings['options'] = array(
            'max_size_kb' => intval( $_POST['aacf7_max_size_kb'] ?? 2048 ),
            'max_files' => intval( $_POST['aacf7_max_files'] ?? 3 ),
        );

        // Types
        $allowed_types = array('jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx');
        $types = array();
        foreach ( $allowed_types as $t ) {
            $field = 'aacf7_type_' . $t;
            if ( isset( $_POST[ $field ] ) ) $types[] = $t;
        }
        $settings['types'] = $types;

        // Texts
        $settings['texts'] = array(
            'title' => sanitize_text_field( $_POST['aacf7_title'] ?? 'Archivos Adjuntos' ),
            'drop_text' => sanitize_text_field( $_POST['aacf7_drop_text'] ?? 'Arrastra los archivos aquí o haz clic' ),
            'button_text' => sanitize_text_field( $_POST['aacf7_button_text'] ?? 'Adjuntar archivos' ),
            'note' => sanitize_text_field( $_POST['aacf7_note'] ?? 'Puedes enviar varios archivos.' ),
        );

        // Validations
        $settings['validations'] = array(
            'required' => sanitize_text_field( $_POST['aacf7_required_msg'] ?? 'Este campo es obligatorio.' ),
            'size_exceeded' => sanitize_text_field( $_POST['aacf7_size_msg'] ?? 'Tamaño máximo excedido.' ),
            'count_exceeded' => sanitize_text_field( $_POST['aacf7_count_msg'] ?? 'Cantidad máxima de archivos excedida.' ),
            'type_not_allowed' => sanitize_text_field( $_POST['aacf7_type_msg'] ?? 'Tipo de archivo no permitido.' ),
        );

        // Styles: a simple json field for now
        $styles_json = wp_kses_post( $_POST['aacf7_styles_json'] ?? '' );
        $settings['styles'] = $styles_json ? json_decode( $styles_json, true ) : array();

        update_post_meta( $post_id, '_aacf7_settings', $settings );
    }
}
?>