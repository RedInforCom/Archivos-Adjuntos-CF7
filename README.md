# Archivos Adjuntos CF7

Plugin avanzado de carga de archivos para Contact Form 7 con funcionalidades extendidas.

## Características

- ✅ Drag & Drop para subir archivos
- ✅ Botón integrado en el editor de CF7
- ✅ Configuración completa por formulario en pestaña "Estilos y Opciones"
- ✅ Soporte para directorios internos y externos
- ✅ Textos completamente personalizables
- ✅ Mensajes de validación editables
- ✅ Estilos CSS totalmente configurables
- ✅ Mantiene el nombre original del archivo
- ✅ Limpieza automática con cron job
- ✅ Compatible con Elementor

## Instalación

1. Sube el archivo ZIP desde WordPress Admin → Plugins → Añadir nuevo
2. Activa el plugin
3. Listo para usar

## Uso

### 1. Agregar Campo al Formulario

1. Edita tu formulario en Contact Form 7
2. Verás un nuevo botón "Archivo Adjunto" junto a los demás botones
3. Haz clic en "Archivo Adjunto"
4. Ingresa un nombre para el campo (ej: mi-archivo)
5. Marca como obligatorio si lo necesitas
6. Haz clic en "Insertar etiqueta"

### 2. Configurar Opciones

1. Ve a la pestaña **"Estilos y Opciones"** en el editor del formulario
2. Configura:
   - **Almacenamiento**: Directorio interno o externo
   - **Opciones de Archivo**: Tamaño máximo, cantidad, tipos permitidos
   - **Textos**: Personaliza título, botón, notas
   - **Validación**: Mensajes de error personalizados
   - **Estilos CSS**: Colores, tamaños, bordes

### 3. Guardar

Guarda el formulario y todos los campos de archivo adjunto usarán esta configuración.

## Ejemplo de Uso

```
<label>Nombre*
    [text* nombre]
</label>

<label>Email*
    [email* email]
</label>

<label>Adjunta tu documento*
    [file_advanced* documento]
</label>

[submit "Enviar"]
```

## Configuración de Almacenamiento

### Directorio Interno
- Carpeta dentro de `wp-content/uploads/`
- Ejemplo: `wp-content/uploads/cf7-uploads/`

### Directorio Externo
- Ruta absoluta del servidor
- Ejemplo: `/home/usuario/public_html/archivos/`
- Requiere URL pública para acceso

## Personalización Avanzada

Toda la personalización se hace desde la pestaña **"Estilos y Opciones"**:

- Textos del campo
- Límites de tamaño y cantidad
- Tipos de archivo permitidos
- Mensajes de validación
- Colores y estilos CSS

## Tipos de Archivo Soportados

- Imágenes: jpg, jpeg, png, webp, bmp
- Documentos: pdf, doc, docx
- Hojas de cálculo: xlsx, xls

## Soporte

Para soporte, visita la página del plugin o contacta al desarrollador.

## Licencia

GPL v2 or later
