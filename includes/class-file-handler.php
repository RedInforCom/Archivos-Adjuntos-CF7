<?php
if (!defined('ABSPATH')) exit;

class AACF7_File_Handler {
    
    public function __construct() {
        // Este es el ÚNICO lugar donde procesaremos archivos
        add_action('wpcf7_before_send_mail', array($this, 'handle_file_upload'), 5);
    }
    
    /**
     * Procesar archivos ANTES de que CF7 intente validar
     */
    public function handle_file_upload($contact_form) {
        $submission = WPCF7_Submission::get_instance();
        
        if (!$submission) {
            return;
        }
        
        $form_id = $contact_form->id();
        $settings = get_post_meta($form_id, '_aacf7_settings', true);
        
        if (!is_array($settings)) {
            $settings = array();
        }
        
        // Buscar campos file_advanced en $_FILES
        $uploaded_files = array();
        
        foreach ($_FILES as $field_name => $file_data) {
            // Verificar si es un campo nuestro
            $form_tags = $contact_form->scan_form_tags(array('name' => $field_name));
            
            if (empty($form_tags)) {
                continue;
            }
            
            foreach ($form_tags as $tag) {
                if (strpos($tag->type, 'file_advanced') !== 0) {
                    continue;
                }
                
                // Procesar archivos de este campo
                if (!empty($file_data['name'][0])) {
                    $processed = $this->process_files($file_data, $field_name, $form_id, $settings);
                    
                    if (!empty($processed)) {
                        $uploaded_files[$field_name] = $processed;
                    }
                }
            }
        }
        
        // Guardar archivos procesados para uso posterior
        if (!empty($uploaded_files)) {
            update_option('aacf7_temp_files_' . $form_id, $uploaded_files, false);
        }
    }
    
    /**
     * Procesar array de archivos
     */
    private function process_files($file_data, $field_name, $form_id, $settings) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        // Determinar directorio de destino
        $upload_location = isset($settings['upload_location']) ? $settings['upload_location'] : 'internal';
        
        if ($upload_location === 'external') {
            $target_dir = isset($settings['external_path']) ? $settings['external_path'] : '';
            $base_url = isset($settings['external_url']) ? $settings['external_url'] : '';
            
            if (empty($target_dir) || !file_exists($target_dir)) {
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
        
        // Procesar cada archivo
        foreach ($file_data['name'] as $key => $filename) {
            if (empty($filename) || $file_data['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $tmp_name = $file_data['tmp_name'][$key];
            $file_size = $file_data['size'][$key];
            
            // Generar nombre único
            $file_info = pathinfo($filename);
            $base_name = sanitize_file_name($file_info['filename']);
            $extension = strtolower($file_info['extension']);
            $target_filename = $base_name . '.' . $extension;
            $target_file = $target_dir . '/' . $target_filename;
            
            // Asegurar nombre único
            $counter = 1;
            while (file_exists($target_file)) {
                $target_filename = $base_name . '-' . $counter . '.' . $extension;
                $target_file = $target_dir . '/' . $target_filename;
                $counter++;
            }
            
            // Mover archivo
            if (move_uploaded_file($tmp_name, $target_file)) {
                $processed_files[] = array(
                    'path' => $target_file,
                    'url' => $base_url . '/' . $target_filename,
                    'name' => $target_filename,
                    'size' => $file_size,
                    'timestamp' => time()
                );
                
                // Guardar metadata para cron
                $this->save_file_metadata($target_file, $form_id);
            }
        }
        
        return $processed_files;
    }
    
    /**
     * Guardar metadata para limpieza automática
     */
    private function save_file_metadata($file_path, $form_id) {
        $metadata = get_option('aacf7_file_metadata', array());
        
        $metadata[] = array(
            'path' => $file_path,
            'form_id' => $form_id,
            'uploaded_at' => time()
        );
        
        update_option('aacf7_file_metadata', $metadata);
    }
}