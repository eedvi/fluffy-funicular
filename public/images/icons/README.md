# PWA Icons

Esta carpeta debe contener los íconos para la Progressive Web App (PWA).

## Íconos Requeridos

Necesitas crear los siguientes íconos PNG con fondo transparente o de color sólido:

- `icon-72x72.png` - 72x72 píxeles
- `icon-96x96.png` - 96x96 píxeles
- `icon-128x128.png` - 128x128 píxeles
- `icon-144x144.png` - 144x144 píxeles
- `icon-152x152.png` - 152x152 píxeles
- `icon-192x192.png` - 192x192 píxeles
- `icon-384x384.png` - 384x384 píxeles
- `icon-512x512.png` - 512x512 píxeles

## Cómo Generar los Íconos

### Opción 1: Usar un Generador Online (Recomendado)

1. **PWA Asset Generator**: https://www.pwabuilder.com/imageGenerator
   - Sube tu logo (mínimo 512x512px)
   - Descarga el paquete de íconos
   - Copia los archivos a esta carpeta

2. **Favicon Generator**: https://realfavicongenerator.net/
   - Sube tu logo
   - Selecciona las opciones para PWA
   - Descarga y extrae los íconos necesarios

### Opción 2: Usar Herramientas de Diseño

1. **Adobe Photoshop/Illustrator**
   - Crea o abre tu logo
   - Exporta en los tamaños especificados
   - Guarda como PNG con transparencia

2. **GIMP (Gratuito)**
   - Abre tu logo
   - Escala a cada tamaño requerido
   - Exporta como PNG

3. **Canva (Online)**
   - Crea diseños de los tamaños especificados
   - Descarga como PNG

### Opción 3: Usar ImageMagick (Línea de Comandos)

Si tienes ImageMagick instalado:

```bash
# Desde un logo de 512x512px
convert logo.png -resize 72x72 icon-72x72.png
convert logo.png -resize 96x96 icon-96x96.png
convert logo.png -resize 128x128 icon-128x128.png
convert logo.png -resize 144x144 icon-144x144.png
convert logo.png -resize 152x152 icon-152x152.png
convert logo.png -resize 192x192 icon-192x192.png
convert logo.png -resize 384x384 icon-384x384.png
convert logo.png -resize 512x512 icon-512x512.png
```

## Recomendaciones de Diseño

- ✅ Usa colores que representen tu marca
- ✅ Asegúrate de que el logo sea visible en tamaños pequeños
- ✅ Usa un diseño simple y reconocible
- ✅ Considera usar el color primario de tu app (#f59e0b - Ámbar)
- ✅ Prueba los íconos en diferentes dispositivos

## Verificación

Después de agregar los íconos:

1. Abre la aplicación en Chrome
2. Ve a DevTools > Application > Manifest
3. Verifica que todos los íconos se carguen correctamente
4. Prueba la instalación de la PWA

## Nota Temporal

⚠️ **IMPORTANTE**: Actualmente esta carpeta está vacía. Los íconos son OBLIGATORIOS para que la PWA funcione correctamente.

Por favor, agrega los íconos lo antes posible usando alguna de las opciones mencionadas arriba.
