<?php
defined( 'ABSPATH' ) || exit;

define( 'FOROALUMNOS_VERSION', '1.0.0' );

add_action( 'wp_enqueue_scripts', 'foroalumnos_enqueue_assets' );
function foroalumnos_enqueue_assets() {
	// Estilos del tema padre (Astra)
	wp_enqueue_style(
		'astra-theme-css',
		get_template_directory_uri() . '/style.css',
		[],
		wp_get_theme( 'astra' )->get( 'Version' )
	);

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
