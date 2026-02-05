<?php
// This view is included from Admin::render_meta_box and expects $settings and $post to be available.
?>
<div class="aacf7-tabs">
    <ul class="aacf7-tabs-nav">
        <li data-tab="storage" class="active">Almacenamiento</li>
        <li data-tab="options">Opciones</li>
        <li data-tab="texts">Textos</li>
        <li data-tab="validations">Validaciones</li>
        <li data-tab="styles">Estilos</li>
    </ul>

    <div class="aacf7-tab-panels">
        <div class="aacf7-tab-panel active" id="aacf7-tab-storage">
            <h4>Ubicación de Archivos</h4>
            <p>
                <label><input type="radio" name="aacf7_storage_type" value="uploads_subdir" <?php checked( $settings['storage']['type'], 'uploads_subdir' ); ?>> Guardar en wp-content/uploads/<input type="text" name="aacf7_subdir" value="<?php echo esc_attr( $settings['storage']['subdir'] ); ?>" style="width:200px" /></label>
            </p>
            <p>
                <label><input type="radio" name="aacf7_storage_type" value="custom_path" <?php checked( $settings['storage']['type'], 'custom_path' ); ?>> Ruta completa en el hosting: <input type="text" name="aacf7_custom_path" value="<?php echo esc_attr( $settings['storage']['custom_path'] ); ?>" style="width:100%" /></label>
            </p>
            <p>
                <label><input type="radio" name="aacf7_storage_type" value="external_url" <?php checked( $settings['storage']['type'], 'external_url' ); ?>> URL externa: <input type="url" name="aacf7_external_url" value="<?php echo esc_attr( $settings['storage']['external_url'] ); ?>" style="width:100%" /></label>
            </p>

            <p>
                <label><input type="checkbox" name="aacf7_attach_to_mail" <?php checked( $settings['storage']['attach_to_mail'], true ); ?>> Adjuntar archivo al correo además de guardarlo</label>
            </p>

            <p>
                <label>Eliminar archivos después de (días): <input type="number" name="aacf7_delete_after_days" value="<?php echo intval( $settings['storage']['delete_after_days'] ); ?>" min="0" /></label>
                <br><small>0 = Nunca eliminar automáticamente</small>
            </p>
        </div>

        <div class="aacf7-tab-panel" id="aacf7-tab-options">
            <h4>Límites de archivos</h4>
            <p><label>Tamaño máximo por archivo (KB): <input type="number" name="aacf7_max_size_kb" value="<?php echo intval( $settings['options']['max_size_kb'] ); ?>" /></label></p>
            <p><label>Cantidad máxima de archivos: <input type="number" name="aacf7_max_files" value="<?php echo intval( $settings['options']['max_files'] ); ?>" /></label></p>

            <h4>Tipos permitidos</h4>
            <p>
                <?php
                $exts = array('jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx');
                foreach ( $exts as $e ) {
                    $checked = in_array( $e, $settings['types'] ) ? 'checked' : '';
                    echo '<label style="margin-right:8px"><input type="checkbox" name="aacf7_type_' . esc_attr($e) . '" ' . $checked . '> .' . esc_html($e) . '</label>';
                }
                ?>
            </p>
        </div>

        <div class="aacf7-tab-panel" id="aacf7-tab-texts">
            <h4>Textos de Campos</h4>
            <p><label>Título del campo: <input type="text" name="aacf7_title" value="<?php echo esc_attr( $settings['texts']['title'] ); ?>" style="width:100%" /></label></p>
            <p><label>Texto del área de arrastre: <input type="text" name="aacf7_drop_text" value="<?php echo esc_attr( $settings['texts']['drop_text'] ); ?>" style="width:100%" /></label></p>
            <p><label>Texto del botón: <input type="text" name="aacf7_button_text" value="<?php echo esc_attr( $settings['texts']['button_text'] ); ?>" style="width:100%" /></label></p>
            <p><label>Nota informativa: <input type="text" name="aacf7_note" value="<?php echo esc_attr( $settings['texts']['note'] ); ?>" style="width:100%" /></label></p>
        </div>

        <div class="aacf7-tab-panel" id="aacf7-tab-validations">
            <h4>Mensajes de validación</h4>
            <p><label>Campo Obligatorio: <input type="text" name="aacf7_required_msg" value="<?php echo esc_attr( $settings['validations']['required'] ); ?>" style="width:100%" /></label></p>
            <p><label>Tamaño excedido: <input type="text" name="aacf7_size_msg" value="<?php echo esc_attr( $settings['validations']['size_exceeded'] ); ?>" style="width:100%" /></label></p>
            <p><label>Cantidad excedida: <input type="text" name="aacf7_count_msg" value="<?php echo esc_attr( $settings['validations']['count_exceeded'] ); ?>" style="width:100%" /></label></p>
            <p><label>Tipo no permitido: <input type="text" name="aacf7_type_msg" value="<?php echo esc_attr( $settings['validations']['type_not_allowed'] ); ?>" style="width:100%" /></label></p>
        </div>

        <div class="aacf7-tab-panel" id="aacf7-tab-styles">
            <h4>Estilos</h4>
            <p>Para mantener la UI compacta, los estilos se guardan como JSON estructurado. Aquí tienes un editor simple (puedes integrar un diseñador visual más adelante).</p>
            <textarea name="aacf7_styles_json" style="width:100%;height:150px;"><?php echo esc_textarea( is_array( $settings['styles'] ) ? json_encode( $settings['styles'] ) : $settings['styles'] ); ?></textarea>
            <p><small>Ejemplo de JSON (simplificado): {"container":{"background":"#fff","border_radius":"8px"},"button":{"background":"#0a74da","color":"#fff"}}</small></p>
        </div>
    </div>
</div>
<style>
.aacf7-tabs{border:1px solid #e1e1e1;padding:10px;background:#fff}
.aacf7-tabs-nav{list-style:none;padding:0;margin:0 0 10px;display:flex;gap:8px}
.aacf7-tabs-nav li{padding:6px 10px;background:#f7f7f7;border-radius:4px;cursor:pointer}
.aacf7-tabs-nav li.active{background:#0a74da;color:#fff}
.aacf7-tab-panel{display:none}
.aacf7-tab-panel.active{display:block}
</style>