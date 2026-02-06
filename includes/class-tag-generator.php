<?php
/**
 * Generador de etiquetas para CF7
 */

if (!defined('ABSPATH')) {
    exit;
}

class AACF7_Tag_Generator {
    
    public function __construct() {
        add_action('wpcf7_admin_init', array($this, 'add_tag_generator'), 100);
    }
    
    /**
     * Agregar botón generador de etiquetas
     */
    public function add_tag_generator() {
        if (!class_exists('WPCF7_TagGenerator')) {
            return;
        }
        
        $tag_generator = WPCF7_TagGenerator::get_instance();
        
        $tag_generator->add(
            'file_advanced',
            __('Archivo Adjunto', 'archivos-adjuntos-cf7'),
            array($this, 'tag_generator_dialog')
        );
    }
    
    /**
     * Diálogo del generador de etiquetas
     */
    public function tag_generator_dialog($contact_form, $args = '') {
        $args = wp_parse_args($args, array());
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php _e('Genera una etiqueta de archivo adjunto para insertar en el formulario', 'archivos-adjuntos-cf7'); ?></legend>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-name'); ?>">
                                    <?php _e('Nombre', 'archivos-adjuntos-cf7'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" />
                                <p class="description"><?php _e('Nombre único para este campo (ej: mi-archivo, curriculum, fotos)', 'archivos-adjuntos-cf7'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Campo obligatorio', 'archivos-adjuntos-cf7'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="required" class="option" />
                                    <?php _e('Hacer este campo obligatorio', 'archivos-adjuntos-cf7'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-id'); ?>">
                                    <?php _e('ID (opcional)', 'archivos-adjuntos-cf7'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-class'); ?>">
                                    <?php _e('Clase CSS (opcional)', 'archivos-adjuntos-cf7'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="description" style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                    <strong><?php _e('Nota:', 'archivos-adjuntos-cf7'); ?></strong> 
                    <?php _e('Las opciones de configuración (título, tipos permitidos, validaciones, etc.) se configuran en la pestaña "Estilos y Opciones" del formulario.', 'archivos-adjuntos-cf7'); ?>
                </p>
            </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="file_advanced" class="tag code" readonly="readonly" onfocus="this.select()" />
            
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php esc_attr_e('Insertar etiqueta', 'archivos-adjuntos-cf7'); ?>" />
            </div>
        </div>
        <?php
    }
}
