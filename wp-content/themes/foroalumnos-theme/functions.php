<?php
defined( 'ABSPATH' ) || exit;

define( 'FOROALUMNOS_VERSION', '1.0.5' );

add_action( 'widgets_init', 'foroalumnos_register_sidebars' );
function foroalumnos_register_sidebars(): void {
	register_sidebar( [
		'name'          => __( 'Sidebar del Foro', 'foroalumnos' ),
		'id'            => 'foroalumnos-forum-sidebar',
		'description'   => __( 'Widgets que aparecen en la columna derecha de la página del foro.', 'foroalumnos' ),
		'before_widget' => '<div class="foro-sidebar-card" id="%1$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="foro-sidebar-card__title">',
		'after_title'   => '</h3>',
	] );
}

// Preconnect a Google Fonts para carga anticipada
add_action( 'wp_head', 'foroalumnos_preconnect_fonts', 1 );
function foroalumnos_preconnect_fonts(): void {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}

add_action( 'wp_enqueue_scripts', 'foroalumnos_enqueue_assets' );
function foroalumnos_enqueue_assets() {
	// Google Fonts — Manrope (titulares) + Inter (cuerpo)
	wp_enqueue_style(
		'foroalumnos-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700&display=swap',
		[],
		null
	);

	// Estilos del tema padre (Astra)
	wp_enqueue_style(
		'astra-theme-css',
		get_template_directory_uri() . '/style.css',
		[ 'foroalumnos-fonts' ],
		wp_get_theme( 'astra' )->get( 'Version' )
	);

	// Sobrescribir variables de Astra con los tokens de Figma
	wp_add_inline_style( 'astra-theme-css', foroalumnos_astra_overrides() );

	// Tokens de diseño
	wp_enqueue_style(
		'foroalumnos-tokens',
		get_stylesheet_directory_uri() . '/assets/css/tokens.css',
		[ 'astra-theme-css' ],
		FOROALUMNOS_VERSION
	);

	// Estilos principales
	wp_enqueue_style(
		'foroalumnos-main',
		get_stylesheet_directory_uri() . '/assets/css/main.css',
		[ 'foroalumnos-tokens' ],
		FOROALUMNOS_VERSION
	);

	// Overrides de wpForo (solo en páginas del foro)
	if ( function_exists( 'is_wpforo_url' ) && ( is_wpforo_url() || is_page( get_option( 'wpforo_pageid', 0 ) ) ) ) {
		wp_enqueue_style(
			'foroalumnos-wpforo',
			get_stylesheet_directory_uri() . '/assets/css/wpforo-theme.css',
			[ 'foroalumnos-main' ],
			FOROALUMNOS_VERSION
		);
	}
}

/**
 * CSS inline que sobreescribe los valores por defecto de Astra
 * con la paleta y tipografía exactas del diseño en Figma.
 */
function foroalumnos_astra_overrides(): string {
	return '
:root {
  /* Paleta global de Astra → tokens Figma */
  --ast-global-color-0: #004ac6;
  --ast-global-color-1: #003da8;
  --ast-global-color-2: #191c1e;
  --ast-global-color-3: #54647a;
  --ast-global-color-4: #ffffff;
  --ast-global-color-5: #eceef0;
  --ast-global-color-6: #191c1e;
  --ast-global-color-7: #c3c6d7;
  --ast-global-color-8: #191c1e;

  /* Ancho de contenedor → 1604 px (sincronizado con astra-settings[site-content-width]) */
  --ast-container-width:        1604px;
  --ast-normal-container-width: 1604px;
  --ast-content-width-size:     1604px;
  --ast-narrow-container-width: 900px;
}

/* Tipografía: cuerpo → Inter */
body,
.ast-separate-container .ast-article-post,
.entry-content {
  font-family: \'Inter\', system-ui, sans-serif;
}

/* Tipografía: titulares → Manrope SemiBold */
h1, h2, h3, h4, h5, h6,
.site-title,
.ast-header-break-point .main-navigation {
  font-family: \'Manrope\', system-ui, sans-serif;
  font-weight: 600;
}

/* Color de enlaces */
a,
.ast-builder-grid-row a {
  color: #004ac6;
}
a:hover {
  color: #003da8;
}
';
}

/**
 * Renderiza el icono SVG inline de cada categoría.
 */
function foroalumnos_category_icon( string $slug ): void {
	$icons = [
		'eso' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
		'fp'  => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>',
		'bachillerato' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
	];
	echo $icons[ $slug ] ?? $icons['eso'];
}

/**
 * Devuelve la clase CSS del badge según el título del foro.
 *
 * @return array{ class: string }
 */
function foroalumnos_get_forum_badge( string $forum_title ): array {
	$title = mb_strtolower( $forum_title );
	if ( str_contains( $title, 'fp' ) || str_contains( $title, 'formación' ) || str_contains( $title, 'formacion' ) ) {
		return [ 'class' => 'foro-badge--fp' ];
	}
	if ( str_contains( $title, 'bachillerato' ) ) {
		return [ 'class' => 'foro-badge--bachillerato' ];
	}
	return [ 'class' => 'foro-badge--eso' ];
}

/**
 * Devuelve etiqueta y clase CSS legibles para el rol del usuario.
 *
 * @param  string[] $roles  Array de roles WP del usuario.
 * @return array{ label: string, class: string }
 */
function foroalumnos_get_display_role( array $roles ): array {
	if ( in_array( 'administrator', $roles, true ) ) {
		return [ 'label' => __( 'Admin', 'foroalumnos' ), 'class' => 'foro-member-item__role--admin' ];
	}
	if ( in_array( 'editor', $roles, true ) ) {
		return [ 'label' => __( 'Editor', 'foroalumnos' ), 'class' => 'foro-member-item__role--editor' ];
	}
	if ( in_array( 'wpforo_moderator', $roles, true ) || in_array( 'moderator', $roles, true ) ) {
		return [ 'label' => __( 'Moderador', 'foroalumnos' ), 'class' => 'foro-member-item__role--mod' ];
	}
	return [ 'label' => __( 'Miembro', 'foroalumnos' ), 'class' => 'foro-member-item__role--member' ];
}
