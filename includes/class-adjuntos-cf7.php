<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 */
class Adjuntos_CF7 {

	/**
	 * Constructor
	 */
	public function __construct() {
		// load textdomain
		load_plugin_textdomain( 'archivos-adjuntos-cf7', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Hook everything
	 */
	public function run() {
		// Only proceed if Contact Form 7 is active
		add_action( 'admin_init', array( $this, 'check_cf7' ) );

		// Admin assets and CF7 editor panel
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'wpcf7_editor_panels', array( $this, 'add_cf7_editor_panel' ) );

		// Add form-tag handler and tag generator
		add_action( 'wpcf7_init', array( $this, 'register_form_tag_and_generator' ) );

		// Frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

		// AJAX upload handlers
		add_action( 'wp_ajax_aacf7_upload', array( $this, 'ajax_upload' ) );
		add_action( 'wp_ajax_nopriv_aacf7_upload', array( $this, 'ajax_upload' ) );
		add_action( 'wp_ajax_aacf7_delete', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_nopriv_aacf7_delete', array( $this, 'ajax_delete' ) );

		// Attach files to mail if configured
		add_action( 'wpcf7_before_send_mail', array( $this, 'maybe_attach_files_to_mail' ), 10, 1 );

		// Retention cron
		add_action( 'aacf7_retention_cron', array( $this, 'run_retention' ) );
		register_activation_hook( AACF7_PLUGIN_DIR . '/../archivos-adjuntos-cf7.php', array( $this, 'activation' ) );
		register_deactivation_hook( AACF7_PLUGIN_DIR . '/../archivos-adjuntos-cf7.php', array( $this, 'deactivation' ) );
	}

	public function check_cf7() {
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			// CF7 not active — show admin notice
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Archivos Adjuntos CF7 requiere Contact Form 7 activo.', 'archivos-adjuntos-cf7' );
				echo '</p></div>';
			} );
		}
	}

	/* ------------------------
	 * Assets
	 * ------------------------ */

	public function admin_assets( $hook ) {
		// Load only on contact form editor
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}
		$post_id = intval( $_GET['post'] );
		$post_type = get_post_type( $post_id );
		if ( 'wpcf7_contact_form' !== $post_type ) {
			return;
		}
		wp_enqueue_style( 'aacf7-admin', AACF7_PLUGIN_URL . 'assets/css/admin.css', array(), AACF7_VERSION );
		wp_enqueue_script( 'aacf7-admin', AACF7_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-util' ), AACF7_VERSION, true );
		wp_localize_script( 'aacf7-admin', 'aacf7_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aacf7_admin_nonce' ),
			'lang'     => array(
				'saving' => __( 'Guardando...', 'archivos-adjuntos-cf7' ),
			),
		) );
	}

	public function frontend_assets() {
		wp_enqueue_style( 'aacf7-frontend', AACF7_PLUGIN_URL . 'assets/css/frontend.css', array(), AACF7_VERSION );
		wp_enqueue_script( 'aacf7-frontend', AACF7_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), AACF7_VERSION, true );
		wp_localize_script( 'aacf7-frontend', 'aacf7_frontend', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aacf7_front_nonce' ),
		) );
	}

	/* ------------------------
	 * CF7 Editor Panel
	 * ------------------------ */

	public function add_cf7_editor_panel( $panels ) {
		$panels['aacf7-panel'] = array(
			'title' => __( 'Adjuntos CF7', 'archivos-adjuntos-cf7' ),
			'callback' => array( $this, 'render_cf7_panel' ),
		);
		return $panels;
	}

	public function render_cf7_panel( $form ) {
		$form_id = $form->id();

		// Load saved config (stored as post meta)
		$config = get_post_meta( $form_id, '_aacf7_config', true );
		if ( ! is_array( $config ) ) {
			$config = $this->default_config();
		}

		// Panels UI: We'll output tabs and groups. Admin JS will handle switching.
		?>
		<div class="aacf7-wrap" data-form-id="<?php echo esc_attr( $form_id ); ?>">
			<nav class="aacf7-tabs">
				<button data-tab="storage" class="aacf7-tab active"><?php esc_html_e( 'Almacenamiento', 'archivos-adjuntos-cf7' ); ?></button>
				<button data-tab="options" class="aacf7-tab"><?php esc_html_e( 'Opciones', 'archivos-adjuntos-cf7' ); ?></button>
				<button data-tab="texts" class="aacf7-tab"><?php esc_html_e( 'Textos', 'archivos-adjuntos-cf7' ); ?></button>
				<button data-tab="validations" class="aacf7-tab"><?php esc_html_e( 'Validaciones', 'archivos-adjuntos-cf7' ); ?></button>
				<button data-tab="styles" class="aacf7-tab"><?php esc_html_e( 'Estilos', 'archivos-adjuntos-cf7' ); ?></button>
			</nav>

			<div class="aacf7-panels">
				<!-- STORAGE -->
				<section class="aacf7-panel aacf7-panel--active" data-panel="storage">
					<h3><?php esc_html_e( 'Ubicación de Archivos', 'archivos-adjuntos-cf7' ); ?></h3>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Tipo de almacenamiento', 'archivos-adjuntos-cf7' ); ?></label>
						<select name="storage[type]" class="aacf7-input">
							<option value="uploads" <?php selected( $config['storage']['type'], 'uploads' ); ?>><?php esc_html_e( 'Carpeta en uploads (wp-content/uploads/adjuntoscf7)', 'archivos-adjuntos-cf7' ); ?></option>
							<option value="wpcontent_subpath" <?php selected( $config['storage']['type'], 'wpcontent_subpath' ); ?>><?php esc_html_e( 'Ruta bajo wp-content/ (ej: wp-content/mi-carpeta/adjuntos)', 'archivos-adjuntos-cf7' ); ?></option>
							<option value="full_path" <?php selected( $config['storage']['type'], 'full_path' ); ?>><?php esc_html_e( 'Ruta absoluta del servidor (ej: /home/usuario/public_html/uploads/adjuntos)', 'archivos-adjuntos-cf7' ); ?></option>
							<option value="external_url" <?php selected( $config['storage']['type'], 'external_url' ); ?>><?php esc_html_e( 'URL externa (sube por HTTP)', 'archivos-adjuntos-cf7' ); ?></option>
						</select>
					</div>

					<div class="aacf7-field aacf7-subfield" data-when="wpcontent_subpath,full_path,external_url">
						<label><?php esc_html_e( 'Ruta / URL', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="storage[path]" class="aacf7-input" value="<?php echo esc_attr( $config['storage']['path'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Si eliges wpcontent_subpath escribe la ruta después de wp-content/ (ej: uploads/mi-carpeta). Si eliges external_url indica la URL completa del endpoint que aceptará uploads.', 'archivos-adjuntos-cf7' ); ?></p>
					</div>

					<div class="aacf7-field">
						<label><input type="checkbox" name="storage[attach_to_mail]" value="1" <?php checked( $config['storage']['attach_to_mail'], 1 ); ?> /> <?php esc_html_e( 'Adjuntar archivo al correo enviado por CF7 (si está activado)', 'archivos-adjuntos-cf7' ); ?></label>
					</div>

					<div class="aacf7-field">
						<label><?php esc_html_e( 'Eliminar archivo después de (días). Dejar vacío o 0 para no eliminar automáticamente.', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="number" min="0" name="storage[retention_days]" class="aacf7-input" value="<?php echo esc_attr( $config['storage']['retention_days'] ); ?>" />
					</div>
				</section>

				<!-- OPTIONS -->
				<section class="aacf7-panel" data-panel="options">
					<h3><?php esc_html_e( 'Límites de Archivos', 'archivos-adjuntos-cf7' ); ?></h3>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Tamaño máximo por archivo (MB)', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="number" min="1" name="options[max_size_mb]" class="aacf7-input" value="<?php echo esc_attr( $config['options']['max_size_mb'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Cantidad máxima de archivos', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="number" min="1" name="options[max_files]" class="aacf7-input" value="<?php echo esc_attr( $config['options']['max_files'] ); ?>" />
					</div>

					<h3><?php esc_html_e( 'Tipos de archivos permitidos', 'archivos-adjuntos-cf7' ); ?></h3>
					<div class="aacf7-field">
						<p class="description"><?php esc_html_e( 'Selecciona los tipos permitidos. Sólo se permitirán los que estén marcados.', 'archivos-adjuntos-cf7' ); ?></p>
						<?php
						$types = array( 'jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx' );
						foreach ( $types as $t ) {
							$checked = in_array( $t, (array) $config['options']['allowed_types'], true );
							printf(
								'<label><input type="checkbox" name="options[allowed_types][]" value="%1$s" %2$s /> %1$s</label> ',
								esc_html( $t ),
								checked( $checked, true, false )
							);
						}
						?>
					</div>
				</section>

				<!-- TEXTS -->
				<section class="aacf7-panel" data-panel="texts">
					<h3><?php esc_html_e( 'Textos de Campos', 'archivos-adjuntos-cf7' ); ?></h3>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Título del campo', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="texts[title]" class="aacf7-input" value="<?php echo esc_attr( $config['texts']['title'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Texto del área de arrastre', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="texts[drop_text]" class="aacf7-input" value="<?php echo esc_attr( $config['texts']['drop_text'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Texto del botón', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="texts[button_text]" class="aacf7-input" value="<?php echo esc_attr( $config['texts']['button_text'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Nota informativa', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="texts[note]" class="aacf7-input" value="<?php echo esc_attr( $config['texts']['note'] ); ?>" />
					</div>
				</section>

				<!-- VALIDATIONS -->
				<section class="aacf7-panel" data-panel="validations">
					<h3><?php esc_html_e( 'Mensajes de validación', 'archivos-adjuntos-cf7' ); ?></h3>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Campo Obligatorio', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="validations[required]" class="aacf7-input" value="<?php echo esc_attr( $config['validations']['required'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Tamaño excedido', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="validations[size_exceeded]" class="aacf7-input" value="<?php echo esc_attr( $config['validations']['size_exceeded'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Cantidad excedida', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="validations[count_exceeded]" class="aacf7-input" value="<?php echo esc_attr( $config['validations']['count_exceeded'] ); ?>" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Tipo no permitido', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="validations[type_not_allowed]" class="aacf7-input" value="<?php echo esc_attr( $config['validations']['type_not_allowed'] ); ?>" />
					</div>
				</section>

				<!-- STYLES -->
				<section class="aacf7-panel" data-panel="styles">
					<h3><?php esc_html_e( 'Estilos', 'archivos-adjuntos-cf7' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Opciones básicas de estilo. Los valores se almacenan como JSON y se aplican inline en el frontend. Para personalizaciones avanzadas, añade CSS personalizado.', 'archivos-adjuntos-cf7' ); ?></p>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Contenedor principal - Color de fondo', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="styles[container_bg]" class="aacf7-input" value="<?php echo esc_attr( $config['styles']['container_bg'] ); ?>" placeholder="#ffffff" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'DropZone - Color de fondo', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="styles[dropzone_bg]" class="aacf7-input" value="<?php echo esc_attr( $config['styles']['dropzone_bg'] ); ?>" placeholder="#fafafa" />
					</div>
					<div class="aacf7-field">
						<label><?php esc_html_e( 'Botón - Color de fondo', 'archivos-adjuntos-cf7' ); ?></label>
						<input type="text" name="styles[button_bg]" class="aacf7-input" value="<?php echo esc_attr( $config['styles']['button_bg'] ); ?>" placeholder="#0073aa" />
					</div>

					<!-- Nota: puedes ampliar con muchos más controles -->
				</section>
			</div>

			<div class="aacf7-actions">
				<button class="button button-primary aacf7-save"><?php esc_html_e( 'Guardar configuración', 'archivos-adjuntos-cf7' ); ?></button>
				<span class="aacf7-status"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Default config
	 */
	public function default_config() {
		return array(
			'storage' => array(
				'type' => 'uploads',
				'path' => '',
				'attach_to_mail' => 1,
				'retention_days' => 0,
			),
			'options' => array(
				'max_size_mb' => 8,
				'max_files' => 3,
				'allowed_types' => array( 'jpg','jpeg','png','webp','bmp','pdf','xlsx','xls','doc','docx' ),
			),
			'texts' => array(
				'title' => 'Adjunta tus archivos',
				'drop_text' => 'Arrastra los archivos aquí o haz clic para seleccionar',
				'button_text' => 'Adjuntar archivo',
				'note' => 'Tipos permitidos: jpg, png, pdf, docx. Tamaño máximo por archivo: 8MB',
			),
			'validations' => array(
				'required' => 'Este campo es obligatorio.',
				'size_exceeded' => 'Uno o varios archivos exceden el tamaño permitido.',
				'count_exceeded' => 'Se excedió la cantidad máxima de archivos.',
				'type_not_allowed' => 'Tipo de archivo no permitido.',
			),
			'styles' => array(
				'container_bg' => '#ffffff',
				'dropzone_bg' => '#f9f9f9',
				'button_bg' => '#0073aa',
			),
		);
	}

	/* ------------------------
	 * Save settings via AJAX
	 * ------------------------ */
	public function ajax_save_settings() {
		check_ajax_referer( 'aacf7_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'No tienes permisos', 'archivos-adjuntos-cf7' ) );
		}
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		$config = $this->sanitize_config( $config );
		update_post_meta( $form_id, '_aacf7_config', $config );
		wp_send_json_success();
	}

	/* ------------------------
	 * Register tag and generator
	 * ------------------------ */
	public function register_form_tag_and_generator() {
		if ( function_exists( 'wpcf7_add_form_tag' ) ) {
			wpcf7_add_form_tag( 'adjuntos_cf7', array( $this, 'render_form_tag' ) );
		}

		// Add tag generator button in CF7 form editor
		if ( function_exists( 'wpcf7_add_tag_generator' ) ) {
			// Nota: CF7 requiere al menos 4 argumentos en versiones recientes.
			// Pasamos un array (puede contener opciones) como cuarto argumento.
			wpcf7_add_tag_generator(
				'adjuntos_cf7',
				__( 'Archivos Adjuntos', 'archivos-adjuntos-cf7' ),
				array( $this, 'tag_generator_panel' ),
				array() // <-- cuarto argumento requerido; dejar array vacío o definir opciones aquí
			);
		}
	}

	public function tag_generator_panel( $contact_form ) {
		?>
		<div id="wpcf7-tag-generator-adjuntos_cf7" class="tag-generator">
			<form>
				<table class="form-table">
					<tr>
						<td><label><?php esc_html_e( 'Nombre', 'archivos-adjuntos-cf7' ); ?></label></td>
						<td><input type="text" id="tag-name" class="tg-text" /></td>
					</tr>
				</table>
				<div class="tg-tag"><?php esc_html_e( 'Etiqueta:', 'archivos-adjuntos-cf7' ); ?> <span id="generated-tag"></span></div>
				<div class="tg-submit">
					<input type="button" class="button button-primary insert-tag" value="<?php esc_attr_e( 'Insertar etiqueta', 'archivos-adjuntos-cf7' ); ?>" />
				</div>
			</form>
		</div>
		<script>
		(function($){
			function updateTag(){
				var name = $('#tag-name').val() || 'adjuntos';
				$('#generated-tag').text('[adjuntos_cf7 ' + name + ']');
			}
			$('#tag-name').on('input', updateTag);
			updateTag();

			$('.insert-tag').on('click', function(){
				var tag = $('#generated-tag').text();
				window.wpcf7TagInsertion(tag);
			});
		})(jQuery);
		</script>
		<?php
	}

	/* ------------------------
	 * Render form tag (shortcode)
	 * ------------------------ */
	public function render_form_tag( $tag ) {
		// $tag is WPCF7_FormTag
		$name = $tag->name;
		$form_id = $tag->get_option( 'form-id', '', true );
		$wpcf7 = WPCF7_ContactForm::get_current();
		if ( ! $wpcf7 ) {
			// fallback
			$form_id = '';
		} else {
			$form_id = $wpcf7->id();
		}

		// Load config for this form id
		$config = get_post_meta( $form_id, '_aacf7_config', true );
		if ( ! is_array( $config ) ) {
			$config = $this->default_config();
		}

		// Build HTML output
		$uniq = uniqid( 'aacf7_' );
		$wrapper_style = '';
		if ( ! empty( $config['styles']['container_bg'] ) ) {
			$wrapper_style .= 'background:' . esc_attr( $config['styles']['container_bg'] ) . ';';
		}
		ob_start();
		?>
		<div class="aacf7-field-wrapper" id="<?php echo esc_attr( $uniq ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>" data-field-name="<?php echo esc_attr( $name ); ?>">
			<?php if ( ! empty( $config['texts']['title'] ) ) : ?>
				<div class="aacf7-title"><?php echo esc_html( $config['texts']['title'] ); ?></div>
			<?php endif; ?>

			<div class="aacf7-dropzone" tabindex="0" role="button" aria-label="<?php echo esc_attr( $config['texts']['drop_text'] ); ?>" style="<?php echo 'background:' . esc_attr( $config['styles']['dropzone_bg'] ) . ';'; ?>">
				<div class="aacf7-icon" aria-hidden="true">
					<!-- simple svg upload icon -->
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3v9" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 7l4-4 4 4" stroke="#666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="13" width="18" height="8" rx="2" stroke="#666" stroke-width="1.5"/></svg>
				</div>
				<div class="aacf7-drop-text"><?php echo esc_html( $config['texts']['drop_text'] ); ?></div>
				<button type="button" class="aacf7-btn-attach"><?php echo esc_html( $config['texts']['button_text'] ); ?></button>
				<input type="file" class="aacf7-file-input" multiple style="display:none;" />
			</div>

			<?php if ( ! empty( $config['texts']['note'] ) ) : ?>
				<div class="aacf7-note"><?php echo esc_html( $config['texts']['note'] ); ?></div>
			<?php endif; ?>

			<div class="aacf7-files-list"></div>

			<!-- Hidden input to store uploaded file references (comma separated) -->
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="" class="aacf7-hidden-input" />
		</div>
		<?php
		return ob_get_clean();
	}

	/* ------------------------
	 * AJAX: upload
	 * ------------------------ */
	public function ajax_upload() {
		check_ajax_referer( 'aacf7_front_nonce', 'nonce' );
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$config = get_post_meta( $form_id, '_aacf7_config', true );
		if ( ! is_array( $config ) ) {
			$config = $this->default_config();
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No se recibió archivo', 'archivos-adjuntos-cf7' ) ) );
		}

		$file = $_FILES['file'];

		// Validate type
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, (array) $config['options']['allowed_types'], true ) ) {
			wp_send_json_error( array( 'message' => $config['validations']['type_not_allowed'] ) );
		}

		// Validate size
		$max_bytes = intval( $config['options']['max_size_mb'] ) * 1024 * 1024;
		if ( $file['size'] > $max_bytes ) {
			wp_send_json_error( array( 'message' => $config['validations']['size_exceeded'] ) );
		}

		// Decide storage
		$storage = $config['storage']['type'];

		if ( 'external_url' === $storage && ! empty( $config['storage']['path'] ) ) {
			// Send to external URL via multipart POST
			$body = array(
				'file' => curl_file_create( $file['tmp_name'], $file['type'], $file['name'] ),
			);
			// Use wp_remote_post cannot send real file easily - fallback to PHP stream if curl available
			if ( function_exists( 'curl_file_create' ) ) {
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $config['storage']['path'] );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
				$response = curl_exec( $ch );
				$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				curl_close( $ch );
				if ( 200 !== intval( $http_code ) ) {
					wp_send_json_error( array( 'message' => __( 'Error subiendo a URL externa', 'archivos-adjuntos-cf7' ) ) );
				}
				// Assume response contains URL or ID. We store the returned body as reference.
				$ref = wp_json_encode( array( 'external_response' => $response ) );
				wp_send_json_success( array( 'id' => uniqid( 'aacf7_ext_' ), 'name' => $file['name'], 'size' => $file['size'], 'ref' => $ref ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'El servidor no permite subir a URL externa (curl no disponible).', 'archivos-adjuntos-cf7' ) ) );
			}
		}

		// Default: store in WP uploads (or custom)
		$upload_dir = wp_upload_dir();
		$subdir = '/adjuntoscf7';
		if ( 'wpcontent_subpath' === $storage && ! empty( $config['storage']['path'] ) ) {
			$path = $config['storage']['path'];
			// if path starts with 'uploads/' assume relative to wp-content/
			if ( 0 === strpos( $path, 'uploads' ) ) {
				$subdir = '/' . trim( str_replace( 'uploads', '', $path ), '/' );
				$upload_base = WP_CONTENT_DIR . '/uploads';
				$target_dir = untrailingslashit( $upload_base ) . $subdir;
			} else {
				$target_dir = WP_CONTENT_DIR . '/' . ltrim( $path, '/' );
			}
		} elseif ( 'full_path' === $storage && ! empty( $config['storage']['path'] ) ) {
			$target_dir = untrailingslashit( $config['storage']['path'] );
		} else {
			$target_dir = $upload_dir['basedir'] . $subdir;
		}

		// Ensure dir exists
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		$filename = wp_unique_filename( $target_dir, $file['name'] );
		$target = trailingslashit( $target_dir ) . $filename;

		if ( move_uploaded_file( $file['tmp_name'], $target ) ) {
			// Build accessible URL if using uploads
			$public_url = '';
			if ( strpos( $target_dir, $upload_dir['basedir'] ) !== false ) {
				$public_url = trailingslashit( $upload_dir['baseurl'] ) . ltrim( str_replace( $upload_dir['basedir'], '', $target ), '/' );
			} elseif ( strpos( $target_dir, WP_CONTENT_DIR ) !== false ) {
				// if under wp-content but not uploads base
				$public_url = content_url( str_replace( WP_CONTENT_DIR, '', $target ) );
			}

			$attachment = array(
				'id' => uniqid( 'aacf7_' ),
				'name' => $filename,
				'original_name' => $file['name'],
				'size' => $file['size'],
				'path' => $target,
				'url' => $public_url,
				'uploaded_at' => current_time( 'mysql' ),
			);

			// Save reference in transient or option per form — here we'll save in post meta list
			$files = get_post_meta( $form_id, '_aacf7_files', true );
			if ( ! is_array( $files ) ) {
				$files = array();
			}
			$files[ $attachment['id'] ] = $attachment;
			update_post_meta( $form_id, '_aacf7_files', $files );

			wp_send_json_success( $attachment );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error moviendo archivo.', 'archivos-adjuntos-cf7' ) ) );
		}
	}

	/* ------------------------
	 * AJAX: delete uploaded file
	 * ------------------------ */
	public function ajax_delete() {
		check_ajax_referer( 'aacf7_front_nonce', 'nonce' );
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$file_id = isset( $_POST['file_id'] ) ? sanitize_text_field( $_POST['file_id'] ) : '';
		$files = get_post_meta( $form_id, '_aacf7_files', true );
		if ( isset( $files[ $file_id ] ) ) {
			$file = $files[ $file_id ];
			if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
				@unlink( $file['path'] );
			}
			unset( $files[ $file_id ] );
			update_post_meta( $form_id, '_aacf7_files', $files );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	/* ------------------------
	 * Attach files to CF7 mail
	 * ------------------------ */
	public function maybe_attach_files_to_mail( $contact_form ) {
		$form_id = $contact_form->id();
		$config = get_post_meta( $form_id, '_aacf7_config', true );
		if ( ! is_array( $config ) ) {
			return;
		}
		if ( empty( $config['storage']['attach_to_mail'] ) ) {
			return;
		}
		// Find posted field values that contain our identifiers (they are stored as comma separated ids)
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}
		$data = $submission->get_posted_data();
		if ( ! is_array( $data ) ) {
			return;
		}
		$attachments = array();
		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && strpos( $value, 'aacf7_' ) !== false ) {
				$ids = array_filter( array_map( 'trim', explode( ',', $value ) ) );
				foreach ( $ids as $id ) {
					$files = get_post_meta( $form_id, '_aacf7_files', true );
					if ( isset( $files[ $id ] ) ) {
						$file = $files[ $id ];
						if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
							$attachments[] = $file['path'];
						} elseif ( ! empty( $file['url'] ) ) {
							// if only URL available, we can try to download to tmp and attach
							$tmp = download_url( $file['url'] );
							if ( ! is_wp_error( $tmp ) ) {
								$attachments[] = $tmp;
							}
						}
					}
				}
			}
		}
		if ( ! empty( $attachments ) ) {
			$mail = $contact_form->prop( 'mail' );
			$existing = isset( $mail['attachments'] ) ? (array) $mail['attachments'] : array();
			$mail['attachments'] = array_merge( $existing, $attachments );
			$contact_form->set_properties( array( 'mail' => $mail ) );
		}
	}

	/* ------------------------
	 * Retention
	 * ------------------------ */
	public function activation() {
		if ( ! wp_next_scheduled( 'aacf7_retention_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'aacf7_retention_cron' );
		}
	}

	public function deactivation() {
		wp_clear_scheduled_hook( 'aacf7_retention_cron' );
	}

	public function run_retention() {
		// Loop through all forms and remove files older than retention_days
		$args = array(
			'post_type' => 'wpcf7_contact_form',
			'post_status' => 'publish',
			'numberposts' => -1,
		);
		$forms = get_posts( $args );
		foreach ( $forms as $f ) {
			$config = get_post_meta( $f->ID, '_aacf7_config', true );
			$retention = isset( $config['storage']['retention_days'] ) ? intval( $config['storage']['retention_days'] ) : 0;
			if ( $retention <= 0 ) {
				continue;
			}
			$files = get_post_meta( $f->ID, '_aacf7_files', true );
			if ( ! is_array( $files ) ) {
				continue;
			}
			$changed = false;
			foreach ( $files as $id => $file ) {
				$uploaded = isset( $file['uploaded_at'] ) ? strtotime( $file['uploaded_at'] ) : 0;
				if ( $uploaded && ( time() - $uploaded ) > ( $retention * DAY_IN_SECONDS ) ) {
					if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
						@unlink( $file['path'] );
					}
					unset( $files[ $id ] );
					$changed = true;
				}
			}
			if ( $changed ) {
				update_post_meta( $f->ID, '_aacf7_files', $files );
			}
		}
	}

	/* ------------------------
	 * Helpers
	 * ------------------------ */
	private function sanitize_config( $config ) {
		$default = $this->default_config();
		$merged = wp_parse_args( $config, $default );
		// sanitize nested
		$merged['storage']['type'] = sanitize_text_field( $merged['storage']['type'] );
		$merged['storage']['path'] = sanitize_text_field( $merged['storage']['path'] );
		$merged['storage']['attach_to_mail'] = ! empty( $merged['storage']['attach_to_mail'] ) ? 1 : 0;
		$merged['storage']['retention_days'] = intval( $merged['storage']['retention_days'] );
		$merged['options']['max_size_mb'] = intval( $merged['options']['max_size_mb'] );
		$merged['options']['max_files'] = intval( $merged['options']['max_files'] );
		$merged['options']['allowed_types'] = array_map( 'sanitize_text_field', (array) $merged['options']['allowed_types'] );
		$merged['texts'] = array_map( 'sanitize_text_field', (array) $merged['texts'] );
		$merged['validations'] = array_map( 'sanitize_text_field', (array) $merged['validations'] );
		$merged['styles'] = array_map( 'sanitize_text_field', (array) $merged['styles'] );
		return $merged;
	}
}

/* Bind AJAX save endpoint separately to keep class file focused */
add_action( 'wp_ajax_aacf7_save_settings', function() {
	check_ajax_referer( 'aacf7_admin_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'No tienes permisos', 'archivos-adjuntos-cf7' ) );
	}
	$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
	$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
	$adj = new Adjuntos_CF7();
	$config = $adj->sanitize_config( $config );
	update_post_meta( $form_id, '_aacf7_config', $config );
	wp_send_json_success();
} );