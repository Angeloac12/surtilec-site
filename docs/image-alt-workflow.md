# Flujo de alt text para imágenes

## Regla editorial

El alt text debe describir la imagen o el producto con datos reales. No usar
relleno de keywords, textos genéricos ni especificaciones que no existan en la
ficha del producto.

Formato para imagen destacada de producto:

```text
{nombre del producto} marca {marca}, referencia {sku} - Surtilec
```

Si no hay marca:

```text
{nombre del producto}, referencia {sku} - Surtilec
```

Casos especiales:

```text
Logo de Surtilec
Imagen de producto no disponible - Surtilec
Categoría {nombre de categoría} en Surtilec
```

## Flujo en WordPress Admin

1. Subir la imagen a Medios.
2. Abrir el producto correcto.
3. Asignarla como imagen destacada.
4. Guardar/actualizar el producto.
5. Si el alt estaba vacío, el mu-plugin `surtilec-image-alt.php` lo completa
   automáticamente usando nombre, marca y SKU reales.
6. Si el equipo escribe un alt manual antes o después, el sistema no lo pisa.

## Auditoría

Ejecutar antes de conectar dominio, después de cada lote de imágenes y antes de
activar indexación:

```bash
scripts/wp.sh eval-file - strict < scripts/audit-image-alt.php
```

Salida JSON para reportes:

```bash
scripts/wp.sh eval-file - format=json < scripts/audit-image-alt.php
```

## Reparación segura

Vista previa sin modificar datos:

```bash
scripts/wp.sh eval-file - dry-run < scripts/fix-image-alt.php
```

Aplicar alt solo donde está vacío y hay contexto seguro:

```bash
scripts/wp.sh eval-file - < scripts/fix-image-alt.php
```

Reemplazar valores genéricos conocidos como `image`, `foto` o `producto`:

```bash
scripts/wp.sh eval-file - include-generic < scripts/fix-image-alt.php
```

## Criterio de listo

- `image_attachments_missing_alt = 0`
- `product_featured_images_missing_alt = 0`
- `product_featured_images_generic_alt = 0`
- Las imágenes nuevas quedan con alt antes de purgar caché y revisar la ficha.
