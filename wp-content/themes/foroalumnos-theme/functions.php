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
