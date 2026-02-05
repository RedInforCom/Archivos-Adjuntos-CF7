# Archivos Adjuntos CF7

Plugin que añade un campo de subida de archivos Drag & Drop y botón para Contact Form 7, con configuración por formulario.

Características principales:
- Campo [adjuntos_cf7 nombre] para insertar en formularios CF7
- Editor CF7: pestaña "Adjuntos CF7" con sub-pestañas de configuración (Almacenamiento, Opciones, Textos, Validaciones, Estilos)
- Subida vía AJAX y almacenamiento configurable
- Opción de adjuntar archivos al correo enviado por CF7
- Retención automática de archivos (configurable por formulario)
- Tipos permitidos, límites de tamaño y cantidad configurables
- Interfaz frontend compacta y profesional, extensible

Instalación:
1. Copia la carpeta `archivos-adjuntos-cf7` a `wp-content/plugins/`.
2. Activa el plugin desde el panel de plugins.
3. Abre un formulario de Contact Form 7 y verás la nueva pestaña "Adjuntos CF7".
4. Inserta el campo en el formulario usando el generador de etiquetas o añadiendo manualmente `[adjuntos_cf7 nombre]`.

Notas y recomendaciones:
- Para subir a URL externa el servidor debe tener libcurl habilitado (se usa curl para enviar multipart).
- Por seguridad, asegúrate de revisar los permisos y la ruta si decides usar rutas fuera de uploads.
- Se recomienda probar en un entorno de staging antes de poner en producción.
- Licencia: GPLv2+

Si quieres que añada:
- Más controles de estilo (paneles completos con sliders, colorpickers).
- Integración con almacenamiento cloud (S3, Google Cloud).
- Exportación/gestión avanzada de archivos desde el admin.
Dímelo y lo agrego en la siguiente iteración.