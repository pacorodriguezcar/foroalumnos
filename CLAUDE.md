# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Contexto del proyecto

Sitio WordPress migrado desde Hostinger a entorno local con DDEV. El objetivo es desarrollar y ajustar contenido en local y luego sincronizar los cambios a producción.

**URL local:** `https://foroalumnos.ddev.site`
**URL producción:** `https://lightslategrey-rook-836799.hostingersite.com`
**Usuario SSH Hostinger:** `u853007583`
**Host SSH Hostinger:** `141.136.39.83`
**Puerto SSH Hostinger:** `65002`
**WordPress:** `6.9.4` | **PHP:** `8.3` | **MariaDB:** `11.8.6`
**Prefijo tablas:** `wp_`

---

## Entorno local

- **Sistema:** Ubuntu 24.04 sobre WSL2
- **Docker:** 29.2.1
- **DDEV:** v1.24.4
- **WP-CLI:** disponible dentro del contenedor via `ddev wp` (no instalar en host)
- **mkcert CAROOT:** `/mnt/c/Users/Paco/AppData/Local/mkcert`

---

## Comandos de uso diario

```bash
ddev start                              # Arrancar entorno
ddev stop                               # Parar entorno
ddev restart                            # Reiniciar contenedores
ddev launch                             # Abrir https://foroalumnos.ddev.site en navegador
ddev launch /wp-admin/                  # Abrir panel de administración
ddev ssh                                # Shell dentro del contenedor web
ddev logs -f                            # Ver logs en tiempo real
ddev describe                           # Info del proyecto (URLs, credenciales BD)
ddev poweroff                           # Parar TODOS los proyectos DDEV del sistema
```

### WP-CLI (siempre via `ddev wp`)

```bash
ddev wp plugin list                     # Listar plugins
ddev wp plugin update --all             # Actualizar todos los plugins
ddev wp theme list                      # Listar temas
ddev wp cache flush                     # Limpiar caché
ddev wp rewrite flush                   # Regenerar reglas de reescritura
ddev wp option get siteurl              # Ver URL del sitio en BD
ddev wp option get home                 # Ver URL home en BD
ddev wp user list                       # Listar usuarios
ddev wp db size                         # Ver tamaño de la base de datos
ddev wp core verify-checksums           # Verificar integridad de WordPress core
```

### Base de datos

```bash
ddev mysql                              # Cliente MySQL interactivo
ddev import-db --file=archivo.sql.gz   # Importar BD
ddev export-db --file=backup.sql.gz    # Exportar BD
ddev snapshot --name="nombre"          # Crear snapshot de la BD
ddev snapshot restore --name="nombre"  # Restaurar snapshot
```

---

## Plan de migración desde Hostinger

### FASE 0 — Información a recopilar en Hostinger antes de empezar

| Dato | Dónde encontrarlo |
|---|---|
| URL de producción | Hostinger → Websites → Domain |
| Versión PHP activa | Hostinger → Hosting → PHP Configuration |
| Versión WordPress | WP Admin → Dashboard → At a Glance |
| Versión MySQL/MariaDB | phpMyAdmin → página principal |
| Plugins activos | WP Admin → Plugins → Installed Plugins |
| Credenciales BD | `wp-config.php` en servidor o Hostinger → Databases |

### FASE 1 — Exportar desde Hostinger

**Exportar base de datos (Opción A — phpMyAdmin):**
```
Hostinger Panel → Databases → phpMyAdmin → Export → Quick → SQL → Go
```

**Exportar base de datos (Opción B — SSH, planes Business o superior):**
```bash
ssh -p 65002 u853007583@141.136.39.83
mysqldump -u db_usuario -p db_nombre | gzip > /tmp/foroalumnos_db.sql.gz
exit
scp -P 65002 u853007583@141.136.39.83:/tmp/foroalumnos_db.sql.gz /home/paco/proyectos/foroalumnos/
```

**Descargar archivos del sitio (rsync via SSH):**
```bash
rsync -avz --progress \
    -e "ssh -p 65002" \
    u853007583@141.136.39.83:/home/u853007583/domains/lightslategrey-rook-836799.hostingersite.com/public_html/ \
    /home/paco/proyectos/foroalumnos/
```

**Descargar archivos (SFTP con FileZilla):**
```
Host: tudominio.com | Puerto: 65002 | Usuario/Contraseña: credenciales SSH
Directorio remoto: /home/u853007583/domains/lightslategrey-rook-836799.hostingersite.com/public_html/
Directorio local: /home/paco/proyectos/foroalumnos/
```

### FASE 2 — Configurar DDEV

```bash
cd /home/paco/proyectos/foroalumnos

# Ajustar --php-version para que coincida con la versión usada en Hostinger
ddev config \
    --project-name="foroalumnos" \
    --project-type=wordpress \
    --docroot="." \
    --php-version="8.3" \
    --database="mariadb:11.4"

ddev start
```

### FASE 3 — Importar base de datos

```bash
ddev import-db --file=foroalumnos_db.sql.gz
ddev snapshot --name="import-inicial-$(date +%Y%m%d)"   # Snapshot de seguridad
```

### FASE 4 — Configurar wp-config.php

Editar `wp-config.php` para que detecte automáticamente si está en DDEV o en producción. DDEV expone la variable de entorno `IS_DDEV_PROJECT=true` dentro del contenedor:

```php
<?php
$is_ddev = getenv('IS_DDEV_PROJECT') === 'true';

if ($is_ddev) {
    define('DB_NAME',     'db');
    define('DB_USER',     'db');
    define('DB_PASSWORD', 'db');
    define('DB_HOST',     'db');
    define('WP_SITEURL',  'https://foroalumnos.ddev.site');
    define('WP_HOME',     'https://foroalumnos.ddev.site');
    define('WP_DEBUG',      true);
    define('WP_DEBUG_LOG',  true);
    define('WP_DEBUG_DISPLAY', false);
} else {
    // Credenciales reales de Hostinger
    define('DB_NAME',     'nombre_bd_produccion');
    define('DB_USER',     'usuario_bd_produccion');
    define('DB_PASSWORD', 'contrasena_bd_produccion');
    define('DB_HOST',     'localhost');
    define('WP_DEBUG',    false);
}

define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Pegar las claves de seguridad originales del wp-config.php de producción
define('AUTH_KEY',         '...');
// ... resto de claves ...

$table_prefix = 'wp_'; // Verificar el prefijo real en producción

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
require_once ABSPATH . 'wp-settings.php';
```

### FASE 5 — Sustituir URLs en la base de datos

```bash
# Reemplazar URL de producción por la URL local (ajustar tudominio.com con la real)
ddev wp search-replace 'https://lightslategrey-rook-836799.hostingersite.com' 'https://foroalumnos.ddev.site' \
    --all-tables --precise --verbose --report-changed-only

ddev wp cache flush
ddev wp rewrite flush

# Verificar resultado
ddev wp option get siteurl
ddev wp option get home
# Ambos deben mostrar: https://foroalumnos.ddev.site

ddev launch
```

### FASE 6 — Inicializar Git

```bash
git init
git branch -M main
# Crear .gitignore (ver sección siguiente)
git add .gitignore .ddev/config.yaml wp-config.php
git add wp-content/themes/ wp-content/plugins/
git commit -m "chore: migración inicial WordPress desde Hostinger"
git checkout -b develop
```

---

## .gitignore para este proyecto

```gitignore
# Credenciales locales
wp-config-local.php

# Uploads (gestionar por separado con rsync)
/wp-content/uploads/

# Caché y archivos generados por plugins
/wp-content/cache/
/wp-content/upgrade/
/wp-content/backup-db/
/wp-content/advanced-cache.php
/wp-content/wp-cache-config.php
/wp-content/object-cache.php
/wp-content/debug.log

# Node.js
node_modules/
/wp-content/themes/*/dist/
/wp-content/themes/*/build/

# DDEV (solo versionar config.yaml)
.ddev/.downloads/
.ddev/db_snapshots/
.ddev/sequelpro.spf
.ddev/.global_commands

# IDEs
.idea/
.vscode/
*.swp
*~
.DS_Store

# Dumps de BD
*.sql
*.sql.gz
*.sql.bz2

# Entorno
.env
.env.local
```

---

## Estrategia de ramas

```
main      ← código en producción
develop   ← integración de desarrollo
feature/* ← funcionalidades nuevas (branch desde develop)
fix/*     ← correcciones (branch desde develop o main)
```

```bash
# Nueva funcionalidad
git checkout -b feature/nombre develop
# ... desarrollar, hacer commits ...
git checkout develop
git merge --no-ff feature/nombre
git branch -d feature/nombre
```

---

## Sincronización con producción

### Subir cambios de tema/plugins a Hostinger

```bash
# Solo archivos del tema — NUNCA la BD completa hacia producción
# NUNCA sincronizar wp-config.php al servidor (Hostinger tiene su propio wp-config.php con credenciales)
rsync -avz --progress \
    --exclude="node_modules/" --exclude=".git/" --exclude="*.map" \
    -e "ssh -p 65002" \
    /home/paco/proyectos/foroalumnos/wp-content/themes/nombre-del-tema/ \
    u853007583@141.136.39.83:/home/u853007583/domains/lightslategrey-rook-836799.hostingersite.com/public_html/wp-content/themes/nombre-del-tema/
```

### Traer contenido de producción a local

```bash
# 1. Exportar BD en producción
ssh -p 65002 u853007583@141.136.39.83 \
    "mysqldump -u db_user -pdb_pass db_name | gzip > /tmp/sync_$(date +%Y%m%d).sql.gz"

# 2. Descargar
scp -P 65002 u853007583@141.136.39.83:/tmp/sync_$(date +%Y%m%d).sql.gz .

# 3. Snapshot de seguridad antes de importar
ddev snapshot --name="antes-sync-$(date +%Y%m%d)"

# 4. Importar y reemplazar URLs
ddev import-db --file=sync_$(date +%Y%m%d).sql.gz
ddev wp search-replace 'https://lightslategrey-rook-836799.hostingersite.com' 'https://foroalumnos.ddev.site' \
    --all-tables --precise
ddev wp cache flush
```

---

## Buenas prácticas de desarrollo

### PHP / WordPress

- **Escapar siempre la salida:** `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Sanitizar siempre la entrada:** `sanitize_text_field()`, `sanitize_email()`, `esc_url_raw()`
- **Nonces en formularios:** `wp_nonce_field()` / `wp_verify_nonce()`
- **Nunca SQL directo sin `$wpdb->prepare()`**
- **Nunca modificar `wp-includes/` ni `wp-admin/`** — usar hooks y filtros
- **Enqueue de assets:** siempre via `wp_enqueue_style()` / `wp_enqueue_script()` en `functions.php`, nunca con `<link>` o `<script>` directos en plantillas
- **Prefijo en todas las funciones y clases** del tema: `foroalumnos_`
- **Funciones de traducción siempre:** `__()`, `_e()` con text-domain `foroalumnos`
- **Queries via `WP_Query`**, no via `query_posts()`

```php
// Ejemplo de enqueue correcto
add_action('wp_enqueue_scripts', 'foroalumnos_enqueue_assets');
function foroalumnos_enqueue_assets() {
    wp_enqueue_style('foroalumnos-main',
        get_template_directory_uri() . '/assets/css/main.css', [], FOROALUMNOS_VERSION);
    wp_enqueue_script('foroalumnos-main',
        get_template_directory_uri() . '/assets/js/main.js', ['jquery'], FOROALUMNOS_VERSION, true);
}
```

### CSS

- **Variables CSS nativas** en `:root` para colores, tipografía y espaciado
- **BEM** para nomenclatura de clases: `.foro-card__title`, `.foro-card--featured`
- **Prefijo `foro-`** en todas las clases del tema para evitar conflictos con plugins
- **Mobile-first:** estilos base para móvil, `@media (min-width: ...)` para pantallas mayores
- No usar `!important` salvo para sobrescribir estilos inline de plugins de terceros (documentar el motivo)
- El archivo `style.css` del tema solo contiene la cabecera obligatoria de WordPress — el CSS real se carga via enqueue

### Seguridad (añadir en wp-config.php)

```php
define('DISALLOW_FILE_EDIT', true);     // Desactivar editor de archivos en admin
define('FORCE_SSL_ADMIN', true);        // HTTPS obligatorio en admin
define('WP_AUTO_UPDATE_CORE', 'minor'); // Solo actualizaciones menores automáticas
```

---

## Estructura del tema personalizado

```
wp-content/themes/foroalumnos-theme/
├── style.css              # Solo cabecera del tema (Theme Name, Version, etc.)
├── functions.php          # Enqueue, hooks, registro de menús/sidebars/CPTs
├── index.php
├── header.php / footer.php / sidebar.php
├── single.php / page.php / archive.php / 404.php
├── template-parts/        # Partials reutilizables (get_template_part())
├── inc/                   # Includes PHP del tema (separar por responsabilidad)
├── assets/
│   ├── css/main.css
│   └── js/main.js
└── package.json           # Si tiene proceso de build (SCSS, Webpack, etc.)
```

---

## Archivos clave

| Archivo | Propósito |
|---|---|
| `.ddev/config.yaml` | Configuración DDEV del proyecto (versionar) |
| `wp-config.php` | Configuración multi-entorno WordPress (versionar sin credenciales reales) |
| `.gitignore` | Reglas de exclusión Git |
| `.ddev/providers/hostinger.yaml` | Proveedor para `ddev pull hostinger` (opcional, ver Fase 2) |
| `wp-content/themes/[tema]/functions.php` | Punto de entrada del tema: hooks, enqueue, configuración |
| `wp-content/debug.log` | Log de errores WordPress (solo en local con WP_DEBUG_LOG=true) |
