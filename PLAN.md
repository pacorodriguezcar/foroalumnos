# Plan de Desarrollo — ForoAlumnos

> Estado: Design system completado. Pendiente: implementación de plantillas y plugin de foro.

---

## Contexto

Sitio WordPress migrado de Hostinger a DDEV local. Se ha creado un child theme (`foroalumnos-theme`) con un design system completo extraído de Figma (3 pantallas: Portal de Inicio, Índice del Foro, Categoría Bachillerato).

**Stack:** WordPress 6.9.4 · PHP 8.3 · MariaDB 11.4 · Astra (padre) · foroalumnos-theme (hijo)  
**Local:** `https://foroalumnos.ddev.site`  
**Producción:** `https://lightslategrey-rook-836799.hostingersite.com`

---

## Paso 1 — Verificar estado del foro `[ ]`

Antes de tocar plantillas, confirmar qué plugin gestiona el foro y qué páginas existen.

```bash
ddev wp plugin list --status=active
ddev wp post list --post_type=page --fields=ID,post_title,post_status
```

- Si no hay plugin de foro activo → instalarlo (bbPress es la opción recomendada para WordPress)
- Si hay bbPress → anotar la versión y las taxonomías/post types que genera

---

## Paso 2 — Conectar el design system con Astra `[ ]`

Los tokens CSS existen pero Astra tiene sus propios estilos por encima.

- Sobrescribir en `functions.php` las variables de Astra con los valores de Figma (colores, fuentes, radios)
- Confirmar que Manrope + Inter cargan correctamente vía Google Fonts en el `<head>`
- Verificar visualmente en `https://foroalumnos.ddev.site`

**Archivos a tocar:**
- `wp-content/themes/foroalumnos-theme/functions.php`
- `wp-content/themes/foroalumnos-theme/assets/css/main.css`

---

## Paso 3 — Construir las plantillas PHP del child theme `[ ]`

Traducir los 3 diseños de Figma a plantillas WordPress usando las clases CSS ya creadas.

| Plantilla | Archivo | Diseño Figma |
|---|---|---|
| Home / Portal de Inicio | `front-page.php` | Portal de Inicio |
| Índice del foro | depende del plugin | Community Forums |
| Categoría (ej. Bachillerato) | depende del plugin | Categoría Bachillerato |

Componentes CSS disponibles:
- `.foro-navbar` · `.foro-hero` · `.foro-category-grid` · `.foro-cat-card`
- `.foro-subcat-group` · `.foro-subcat-row` (con barra de acento lateral 4px)
- `.foro-topic-card` · `.foro-stats-bar` · `.foro-bento-grid`
- `.foro-sidebar-card` · `.foro-member-item` · `.foro-stat-row`
- `.foro-btn` (primary, outline, ghost, lg, icon)
- `.foro-breadcrumbs` · `.foro-badge` · `.foro-tag`

---

## Paso 4 — Probar en el navegador con contenido real `[ ]`

Abrir `https://foroalumnos.ddev.site` y revisar:

- [ ] Fuentes Manrope/Inter cargan correctamente
- [ ] Colores correctos (`#004ac6`, `#191c1e`, `#eceef0`…)
- [ ] Layout responsive en móvil
- [ ] Componentes del plugin de foro integrados con el design system
- [ ] Sin errores en consola ni en `wp-content/debug.log`

---

## Paso 5 — Sincronizar a producción `[ ]`

Una vez validado en local:

```bash
# Solo el tema — NUNCA wp-config.php
rsync -avz --progress \
    --exclude="node_modules/" --exclude=".git/" --exclude="*.map" \
    -e "ssh -p 65002" \
    /home/paco/proyectos/foroalumnos/wp-content/themes/foroalumnos-theme/ \
    u853007583@141.136.39.83:/home/u853007583/domains/lightslategrey-rook-836799.hostingersite.com/public_html/wp-content/themes/foroalumnos-theme/
```

- Verificar que Google Fonts carga en producción
- Activar el tema en el panel de WordPress de Hostinger si no lo está

---

## Archivos clave del design system

| Archivo | Descripción |
|---|---|
| `wp-content/themes/foroalumnos-theme/style.css` | Cabecera del child theme |
| `wp-content/themes/foroalumnos-theme/functions.php` | Enqueue de assets |
| `wp-content/themes/foroalumnos-theme/assets/css/tokens.css` | Variables CSS — paleta, tipografía, espaciado, layout |
| `wp-content/themes/foroalumnos-theme/assets/css/main.css` | Componentes CSS del design system |

### Paleta principal (de Figma)

| Color | Hex | Uso |
|---|---|---|
| Science Blue | `#004ac6` | Primary, links, descripciones |
| Orient | `#005a82` | Barras de acento de categoría |
| Shark | `#191c1e` | Texto principal / titulares |
| Blue Bayoux | `#54647a` | Texto secundario |
| Porcelain | `#eceef0` | Surface container (cabeceras de sección) |
| Ghost | `#c3c6d7` | Bordes |
| Hawkes Blue | `#d0e1fb` | Fondos de iconos |

### Tipografía

- **Titulares:** Manrope SemiBold 600
- **Cuerpo:** Inter Regular / Medium / SemiBold

---

## Decisiones tomadas

- Child theme sobre Astra (no reemplazar Astra, aprovechar su base)
- CSS custom properties nativas para todos los tokens (sin Tailwind, sin SASS)
- Prefijo `foro-` en todas las clases para evitar conflictos con plugins
- Colores y tipografía extraídos directamente del código generado por Figma MCP
- `wp-config.php` usa `getenv()` para credenciales de producción — nunca hardcoded
