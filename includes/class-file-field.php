<?php
/**
 * Clase para el campo personalizado de archivos
 */

if (!defined('ABSPATH')) {
    exit;
}

class AACF7_File_Field {
    
    public function __construct() {
        // Registrar el tag personalizado
        add_action('wpcf7_init', array($this, 'register_tag'));
        
        // Validación
        add_filter('wpcf7_validate_file_advanced', array($this, 'validate_field'), 10, 2);
        add_filter('wpcf7_validate_file_advanced*', array($this, 'validate_field'), 10, 2);
        
        // Procesamiento antes de enviar
        add_filter('wpcf7_before_send_mail', array($this, 'process_upload'));
        
        // Agregar archivos al mail
        add_filter('wpcf7_mail_components', array($this, 'attach_files_to_mail'), 10, 3);
        
        // Agregar estilos inline
        add_action('wp_head', array($this, 'add_inline_styles'));
    }
    
    /**
     * Registrar el tag personalizado
     */
    public function register_tag() {
        wpcf7_add_form_tag(
            array('file_advanced', 'file_advanced*'),
            array($this, 'tag_handler'),
            array('name-attr' => true)
        );
    }
    
    /**
     * Generar el HTML del campo
     */
    public function tag_handler($tag) {
        if (empty($tag->name)) {
            return '';
        }
        
        $validation_error = wpcf7_get_validation_error($tag->name);
        $class = wpcf7_form_controls_class($tag->type);
        
        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }
        
        // Obtener configuración del formulario
        $form = WPCF7_ContactForm::get_current();
        $form_id = $form->id();
        $settings = get_post_meta($form_id, '_aacf7_settings', true);
        
        if (!is_array($settings)) {
            $settings = array();
        }
        
        // Valores por defecto
        $defaults = array(
            'max_file_size' => 1024,
            'max_files' => 1,
            'allowed_types' => 'jpg,jpeg,png,pdf',
            'title_text' => __('Adjuntar Archivo', 'archivos-adjuntos-cf7'),
            'separator_text' => __('Arrastra o examina aquí tu archivo...', 'archivos-adjuntos-cf7'),
            'button_text' => __('Clic para adjuntar', 'archivos-adjuntos-cf7'),
            'note_text' => __('Solo puedes subir archivos en formato .jpg | .jpeg | .png | .pdf y con un peso máximo de 1024KB', 'archivos-adjuntos-cf7')
        );
        
        $config = array_merge($defaults, $settings);
        $field_id = $tag->name;
        $required = $tag->is_required();
        
        // HTML del campo
        ob_start();
        ?>
        <div class="aacf7-container aacf7-form-<?php echo esc_attr($form_id); ?> <?php echo esc_attr($class); ?>" 
             data-field-name="<?php echo esc_attr($field_id); ?>"
             data-max-size="<?php echo esc_attr($config['max_file_size']); ?>"
             data-max-files="<?php echo esc_attr($config['max_files']); ?>"
             data-allowed-types="<?php echo esc_attr($config['allowed_types']); ?>"
             data-required="<?php echo $required ? '1' : '0'; ?>">
            
            <!-- Título -->
            <div class="aacf7-title">
                <?php echo esc_html($config['title_text']); ?>
                <?php if ($required) : ?>
                    <span class="aacf7-required">*</span>
                <?php endif; ?>
            </div>
            
            <!-- Área de drag & drop -->
            <div class="aacf7-dropzone">
                <input type="file" 
                       name="<?php echo esc_attr($field_id); ?>[]" 
                       id="<?php echo esc_attr($field_id); ?>"
                       class="aacf7-input"
                       multiple="<?php echo $config['max_files'] > 1 ? 'multiple' : ''; ?>"
                       accept="<?php echo esc_attr($this->get_accept_attribute($config['allowed_types'])); ?>"
                       style="display: none;">
                
                <div class="aacf7-drop-text">
                    <?php echo esc_html($config['separator_text']); ?>
                </div>
                
                <!-- Botón -->
                <button type="button" 
                        class="aacf7-button"
                        onclick="document.getElementById('<?php echo esc_attr($field_id); ?>').click();">
                    <?php echo esc_html($config['button_text']); ?>
                </button>
            </div>
            
            <!-- Nota -->
            <div class="aacf7-note">
                <?php echo esc_html($config['note_text']); ?>
            </div>
            
            <!-- Contenedor para archivos seleccionados -->
            <div class="aacf7-files"></div>
            
            <!-- Mensajes de error -->
            <div class="aacf7-errors"></div>
            
            <?php echo $validation_error; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Generar atributo accept para tipos de archivo
     */
    private function get_accept_attribute($allowed_types) {
        $types = explode(',', $allowed_types);
        $mime_types = array();
        
        $mime_map = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        
        foreach ($types as $type) {
            $type = trim($type);
            if (isset($mime_map[$type])) {
                $mime_types[] = $mime_map[$type];
            }
            $mime_types[] = '.' . $type;
        }
        
        return implode(',', array_unique($mime_types));
    }
    
    /**
     * Validar el campo
     */
    public function validate_field($result, $tag) {
        $name = $tag->name;
        $form = WPCF7_ContactForm::get_current();
        $form_id = $form->id();
        $settings = get_post_meta($form_id, '_aacf7_settings', true);
        
        if (!is_array($settings)) {
            $settings = array();
        }
        
        // Mensajes de validación
        $msg_required = isset($settings['msg_required']) ? $settings['msg_required'] : __('Este campo es obligatorio.', 'archivos-adjuntos-cf7');
        $msg_max_size = isset($settings['msg_max_size']) ? $settings['msg_max_size'] : __('El archivo excede el tamaño máximo permitido.', 'archivos-adjuntos-cf7');
        $msg_max_files = isset($settings['msg_max_files']) ? $settings['msg_max_files'] : __('Has excedido la cantidad máxima de archivos permitidos.', 'archivos-adjuntos-cf7');
        $msg_invalid_type = isset($settings['msg_invalid_type']) ? $settings['msg_invalid_type'] : __('El tipo de archivo no está permitido.', 'archivos-adjuntos-cf7');
        
        if (!isset($_FILES[$name])) {
            if ($tag->is_required()) {
                $result->invalidate($tag, $msg_required);
            }
            return $result;
        }
        
        $files = $_FILES[$name];
        
        // Si es requerido y no hay archivos
        if ($tag->is_required() && empty($files['name'][0])) {
            $result->invalidate($tag, $msg_required);
            return $result;
        }
        
        // Si no hay archivos, no validar más
        if (empty($files['name'][0])) {
            return $result;
        }
        
        // Obtener configuración
        $max_size = isset($settings['max_file_size']) ? intval($settings['max_file_size']) * 1024 : 1024 * 1024;
        $max_files = isset($settings['max_files']) ? intval($settings['max_files']) : 1;
        $allowed_types = isset($settings['allowed_types']) ? explode(',', $settings['allowed_types']) : array();
        $allowed_types = array_map('trim', $allowed_types);
        
        $file_count = count(array_filter($files['name']));
        
        // Validar cantidad de archivos
        if ($file_count > $max_files) {
            $result->invalidate($tag, $msg_max_files);
            return $result;
        }
        
        // Validar cada archivo
        foreach ($files['name'] as $key => $filename) {
            if (empty($filename)) {
                continue;
            }
            
            $file_size = $files['size'][$key];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Validar tamaño
            if ($file_size > $max_size) {
                $result->invalidate($tag, $msg_max_size);
                return $result;
            }
            
            // Validar tipo
            if (!empty($allowed_types) && !in_array($file_ext, $allowed_types)) {
                $result->invalidate($tag, $msg_invalid_type);
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * Procesar la subida de archivos
     */
    public function process_upload($contact_form) {
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return $contact_form;
        }
        
        $uploaded_files = $submission->uploaded_files();
        
        if (empty($uploaded_files)) {
            return $contact_form;
        }
        
        $form_id = $contact_form->id();
        $settings = get_post_meta($form_id, '_aacf7_settings', true);
        
        if (!is_array($settings)) {
            $settings = array();
        }
        
        // Determinar directorio de destino
        $upload_location = isset($settings['upload_location']) ? $settings['upload_location'] : 'internal';
        
        if ($upload_location === 'external') {
            $target_dir = isset($settings['external_path']) ? $settings['external_path'] : '';
            $base_url = isset($settings['external_url']) ? $settings['external_url'] : '';
            
            if (empty($target_dir) || !file_exists($target_dir)) {
                // Fallback a interno si hay problema
                $upload_dir = wp_upload_dir();
                $target_dir = $upload_dir['basedir'] . '/cf7-uploads';
                $base_url = $upload_dir['baseurl'] . '/cf7-uploads';
            }
        } else {
            $upload_dir = wp_upload_dir();
            $custom_dir = isset($settings['internal_dir']) ? $settings['internal_dir'] : 'cf7-uploads';
            $target_dir = $upload_dir['basedir'] . '/' . $custom_dir;
            $base_url = $upload_dir['baseurl'] . '/' . $custom_dir;
        }
        
        // Crear directorio si no existe
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $processed_files = array();
        
        foreach ($uploaded_files as $field_name => $files) {
            if (!is_array($files)) {
                $files = array($files);
            }
            
            foreach ($files as $file_path) {
                if (empty($file_path) || !file_exists($file_path)) {
                    continue;
                }
                
                // Mantener el nombre original del archivo
                $original_filename = basename($file_path);
                
                // Asegurar nombre único si ya existe
                $target_file = $target_dir . '/' . $original_filename;
                $counter = 1;
                $file_info = pathinfo($original_filename);
                
                while (file_exists($target_file)) {
                    $new_filename = $file_info['filename'] . '-' . $counter . '.' . $file_info['extension'];
                    $target_file = $target_dir . '/' . $new_filename;
                    $counter++;
                }
                
                // Copiar archivo
                if (copy($file_path, $target_file)) {
                    $processed_files[$field_name][] = array(
                        'path' => $target_file,
                        'url' => $base_url . '/' . basename($target_file),
                        'name' => basename($target_file),
                        'timestamp' => time()
                    );
                    
                    // Guardar metadatos para el cron
                    $this->save_file_metadata($target_file);
                }
            }
        }
        
        // Guardar información de archivos procesados para el email
        update_option('aacf7_last_upload_' . $form_id, $processed_files, false);
        
        return $contact_form;
    }
    
    /**
     * Adjuntar archivos al email
     */
    public function attach_files_to_mail($components, $cf7, $instance) {
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return $components;
        }
        
        $form_id = $cf7->id();
        $settings = get_post_meta($form_id, '_aacf7_settings', true);
        $attach_to_email = isset($settings['attach_to_email']) ? $settings['attach_to_email'] : true;
        
        $processed_files = get_option('aacf7_last_upload_' . $form_id, array());
        
        if (empty($processed_files)) {
            return $components;
        }
        
        $attachments = isset($components['attachments']) ? $components['attachments'] : array();
        
        // Agregar URLs al cuerpo del mensaje
        $file_links = "\n\n--- Archivos Adjuntos ---\n";
        
        foreach ($processed_files as $field_name => $files) {
            foreach ($files as $file) {
                $file_links .= $file['name'] . ": " . $file['url'] . "\n";
                
                // Agregar archivo físico al email si está habilitado
                if ($attach_to_email && file_exists($file['path'])) {
                    $attachments[] = $file['path'];
                }
            }
        }
        
        $components['body'] .= $file_links;
        $components['attachments'] = $attachments;
        
        // Limpiar opción temporal
        delete_option('aacf7_last_upload_' . $form_id);
        
        return $components;
    }
    
    /**
     * Guardar metadatos del archivo para el cron
     */
    private function save_file_metadata($file_path) {
        $metadata = get_option('aacf7_file_metadata', array());
        
        $metadata[] = array(
            'path' => $file_path,
            'uploaded_at' => time()
        );
        
        update_option('aacf7_file_metadata', $metadata);
    }
    
    /**
     * Agregar estilos inline basados en la configuración
     */
    public function add_inline_styles() {
        global $post;
        
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'contact-form-7')) {
            return;
        }
        
        // Obtener todos los formularios de CF7 en la página
        preg_match_all('/\[contact-form-7[^\]]*id="(\d+)"[^\]]*\]/', $post->post_content, $matches);
        
        if (empty($matches[1])) {
            return;
        }
        
        foreach ($matches[1] as $form_id) {
            $settings = get_post_meta($form_id, '_aacf7_settings', true);
            
            if (!is_array($settings)) {
                continue;
            }
            
            $this->output_form_styles($form_id, $settings);
        }
    }
    
    /**
     * Generar estilos CSS para el formulario
     */
    private function output_form_styles($form_id, $settings) {
        ?>
        <style>
            .aacf7-form-<?php echo esc_attr($form_id); ?> {
                background: <?php echo esc_attr($settings['container_bg'] ?? '#ffffff'); ?>;
                padding: <?php echo esc_attr($settings['container_padding'] ?? '20px'); ?>;
                border: <?php echo esc_attr($settings['container_border'] ?? '1px solid #ddd'); ?>;
                border-radius: <?php echo esc_attr($settings['container_border_radius'] ?? '8px'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-title {
                color: <?php echo esc_attr($settings['title_color'] ?? '#333333'); ?>;
                font-size: <?php echo esc_attr($settings['title_size'] ?? '18px'); ?>;
                font-weight: <?php echo esc_attr($settings['title_weight'] ?? 'bold'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-dropzone {
                background: <?php echo esc_attr($settings['separator_bg'] ?? '#f9f9f9'); ?>;
                border: <?php echo esc_attr($settings['separator_border'] ?? '2px dashed #ccc'); ?>;
                padding: <?php echo esc_attr($settings['separator_padding'] ?? '30px 20px'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-drop-text {
                color: <?php echo esc_attr($settings['separator_text_color'] ?? '#666666'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-button {
                background: <?php echo esc_attr($settings['button_bg'] ?? '#0073aa'); ?>;
                color: <?php echo esc_attr($settings['button_color'] ?? '#ffffff'); ?>;
                padding: <?php echo esc_attr($settings['button_padding'] ?? '10px 20px'); ?>;
                border-radius: <?php echo esc_attr($settings['button_border_radius'] ?? '4px'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-button:hover {
                background: <?php echo esc_attr($settings['button_hover_bg'] ?? '#005a87'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-note {
                color: <?php echo esc_attr($settings['note_color'] ?? '#666666'); ?>;
                font-size: <?php echo esc_attr($settings['note_size'] ?? '12px'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-progress-bar {
                background: <?php echo esc_attr($settings['progress_bg'] ?? '#e9ecef'); ?>;
                height: <?php echo esc_attr($settings['progress_height'] ?? '6px'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-progress-fill {
                background: <?php echo esc_attr($settings['progress_fill'] ?? '#0073aa'); ?>;
            }
            
            .aacf7-form-<?php echo esc_attr($form_id); ?> .aacf7-file-item {
                background: <?php echo esc_attr($settings['file_item_bg'] ?? '#f5f5f5'); ?>;
                border: <?php echo esc_attr($settings['file_item_border'] ?? '1px solid #ddd'); ?>;
                padding: <?php echo esc_attr($settings['file_item_padding'] ?? '12px'); ?>;
            }
        </style>
        <?php
    }
}
