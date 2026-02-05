<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AACF7_Frontend {
    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wpcf7_init', array( $this, 'register_form_tag' ) );
        add_action( 'wp_ajax_aacf7_upload', array( $this, 'ajax_upload' ) );
        add_action( 'wp_ajax_nopriv_aacf7_upload', array( $this, 'ajax_upload' ) );
        add_filter( 'wpcf7_mail_components', array( $this, 'attach_files_to_mail' ), 10, 3 );
        // Cleanup cron hook
        add_action( 'aacf7_cleanup_files', array( $this, 'cleanup_files_cron' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'aacf7-frontend', AACF7_PLUGIN_URL . 'assets/css/frontend.css', array(), AACF7_VERSION );
        wp_enqueue_script( 'aacf7-frontend', AACF7_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), AACF7_VERSION, true );
        wp_localize_script( 'aacf7-frontend', 'aacf7_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'aacf7_upload_nonce' ),
            )
        );
    }

    public function register_form_tag() {
        if ( function_exists( 'wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( 'adjuntos_cf7', array( $this, 'form_tag_handler' ), true );
        }
    }

    public function form_tag_handler( $tag ) {
        $tag = new WPCF7_FormTag( $tag );
        $name = $tag->name;
        $post_id = $this->get_current_cf7_post_id();
        $settings = get_post_meta( $post_id, '_aacf7_settings', true );
        if ( ! $settings ) $settings = array();

        $id_attr = 'aacf7-' . esc_attr( uniqid() );
        $html = '<div class="aacf7-container" data-setting-post="' . esc_attr( $post_id ) . '" id="'.esc_attr($id_attr).'">';
        $html .= '<div class="aacf7-title">' . esc_html( $settings['texts']['title'] ?? 'Archivos Adjuntos' ) .'</div>';
        $html .= '<div class="aacf7-dropzone" tabindex="0">';
        $html .= '<div class="aacf7-icon">📁</div>';
        $html .= '<div class="aacf7-droptext">' . esc_html( $settings['texts']['drop_text'] ?? 'Arrastra los archivos aquí o haz clic' ) . '</div>';
        $html .= '<button type="button" class="aacf7-button">' . esc_html( $settings['texts']['button_text'] ?? 'Adjuntar archivos' ) . '</button>';
        $html .= '<input type="file" class="aacf7-file-input" multiple style="display:none" />';
        $html .= '</div>'; // dropzone
        $html .= '<div class="aacf7-note">' . esc_html( $settings['texts']['note'] ?? '' ) . '</div>';
        $html .= '<div class="aacf7-list"></div>';
        // Hidden input to pass uploaded file tokens to server on submit (JSON)
        $html .= '<input type="hidden" name="aacf7_uploaded_files_' . esc_attr( $name ) . '" class="aacf7-hidden" value="" />';
        $html .= '</div>';

        return $html;
    }

    private function get_current_cf7_post_id() {
        // Try to get ID from wpcf7 contact form global
        if ( function_exists( 'wpcf7_contact_form' ) ) {
            $form = wpcf7_contact_form();
            if ( $form ) return $form->id();
        }
        // Fallback: 0
        return 0;
    }

    public function ajax_upload() {
        check_ajax_referer( 'aacf7_upload_nonce', 'nonce' );

        if ( empty( $_FILES['file'] ) || empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => 'No hay archivo o formulario' ) );
        }

        $post_id = intval( $_POST['post_id'] );
        $settings = get_post_meta( $post_id, '_aacf7_settings', true );
        $options = $settings['options'] ?? array( 'max_size_kb' => 2048, 'max_files' => 3 );
        $types = $settings['types'] ?? array('jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx');

        $file = $_FILES['file'];

        // Validate size
        $max_kb = intval( $options['max_size_kb'] ?? 0 );
        if ( $max_kb > 0 && ($file['size'] / 1024) > $max_kb ) {
            wp_send_json_error( array( 'message' => $settings['validations']['size_exceeded'] ?? 'Tamaño excedido.' ) );
        }

        // Validate type by extension
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, $types ) ) {
            wp_send_json_error( array( 'message' => $settings['validations']['type_not_allowed'] ?? 'Tipo no permitido.' ) );
        }

        // Build destination using storage settings
        $storage = $settings['storage'] ?? array( 'type' => 'uploads_subdir', 'subdir' => 'adjuntoscf7' );
        $upload_overrides = array( 'test_form' => false );

        // Custom upload_dir filter to route file to desired location
        add_filter( 'upload_dir', function( $dirs ) use ( $storage ) {
            if ( $storage['type'] === 'uploads_subdir' ) {
                $base = $dirs['basedir'];
                $sub = trim( $storage['subdir'], '/\\' );
                $dirs['path'] = $base . '/' . $sub;
                $dirs['url'] = $dirs['baseurl'] . '/' . $sub;
                $dirs['basedir'] = $base;
            } elseif ( $storage['type'] === 'custom_path' ) {
                // store path directly (must be inside server)
                $custom = rtrim( $storage['custom_path'], '/\\' );
                $dirs['path'] = $custom;
                // URL may not be available; keep existing url
            } elseif ( $storage['type'] === 'external_url' ) {
                // For external url, we still upload to temp and return external URL field only (advanced: push via FTP/remote)
                // Here, upload to uploads but return external_url as reference.
            }
            return $dirs;
        });

        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $movefile = wp_handle_upload( $file, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            // Save info to transient or option to later attach
            $saved = array(
                'file' => $movefile['file'],
                'url' => $movefile['url'],
                'name' => basename( $movefile['file'] ),
                'uploaded_at' => time(),
                'post_id' => $post_id,
            );
            // store as user transient to be picked up on submit, but better: return info to frontend and include in hidden input
            wp_send_json_success( $saved );
        } else {
            wp_send_json_error( array( 'message' => $movefile['error'] ?? 'Error al subir' ) );
        }
    }

    public function attach_files_to_mail( $mail_components, $contact_form, $instance ) {
        // Find hidden inputs with uploaded file JSON in POST
        foreach ( $_POST as $key => $value ) {
            if ( strpos( $key, 'aacf7_uploaded_files_' ) === 0 ) {
                $json = stripslashes( $value );
                if ( $json ) {
                    $files = json_decode( $json, true );
                    if ( is_array( $files ) ) {
                        foreach ( $files as $f ) {
                            // If attach_to_mail is true for this form, we add the full path
                            $post_id = $this->get_current_cf7_post_id(); // might not work here; use contact_form->id()
                            $post_id = $contact_form->id();
                            $settings = get_post_meta( $post_id, '_aacf7_settings', true );
                            $attach = $settings['storage']['attach_to_mail'] ?? true;
                            if ( $attach && ! empty( $f['file'] ) ) {
                                if ( empty( $mail_components['attachments'] ) ) $mail_components['attachments'] = array();
                                $mail_components['attachments'][] = $f['file'];
                            }
                        }
                    }
                }
            }
        }
        return $mail_components;
    }

    public function cleanup_files_cron() {
        // Iterate all CF7 forms, check delete_after_days and remove old files recorded inside uploads folder
        $forms = get_posts( array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1 ) );
        foreach ( $forms as $f ) {
            $settings = get_post_meta( $f->ID, '_aacf7_settings', true );
            if ( empty( $settings['storage']['delete_after_days'] ) ) continue;
            $days = intval( $settings['storage']['delete_after_days'] );
            $cut = time() - ( $days * DAY_IN_SECONDS );
            // For simplicity, scan the configured folder and unlink older files
            if ( $settings['storage']['type'] === 'uploads_subdir' ) {
                $upload_dir = wp_upload_dir();
                $dir = trailingslashit( $upload_dir['basedir'] ) . trim( $settings['storage']['subdir'], '/\\' );
                if ( is_dir( $dir ) ) {
                    $files = glob( $dir . '/*' );
                    foreach ( $files as $file ) {
                        if ( filemtime( $file ) < $cut ) {
                            @unlink( $file );
                        }
                    }
                }
            }
            // custom_path and external_url require different handling (skipped)
        }
    }
}
?>