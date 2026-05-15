<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

use go2wpforo\Main;

if( ! function_exists( 'WPF_GO2' ) ) {
	function WPF_GO2() {
		return Main::instance();
	}
}

add_action( 'admin_notices', function() {
	if( ! function_exists( 'WPF' ) ) {
		$class   = 'notice notice-error';
		$message = __( 'Migrate2wpForo Notice: Please activate required <a href="https://wpforo.com">wpForo plugin</a> otherwise <b>Migrate2wpForo</b> will not work', 'mg2wpforo' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
} );

add_action( 'wpforo_core_inited', function() {
	if( wpforo_is_admin() && version_compare( WPFORO_VERSION, GO2WPFORO_WPFORO_REQUIRED_VERSION, '>=' ) ) {
		if( wpforo_get_option( 'go2_version', null, false ) !== GO2WPFORO_VERSION ) go2wpforo_activation();
		$GLOBALS['go2wpforo'] = WPF_GO2();
	}
} );

function go2wpforo_activation() {
	global $wpdb;
	$sql = "CREATE TABLE IF NOT EXISTS `" . WPF()->tables->mg2wpforo . "`(
		id SERIAL PRIMARY KEY,
		obj VARCHAR(20) NOT NULL,
		old_id BIGINT UNSIGNED NOT NULL,
		new_id BIGINT UNSIGNED NOT NULL,
		UNIQUE KEY unqdata(obj,old_id),
		KEY(obj,old_id,new_id)
	)ENGINE = MyISAM";
	$wpdb->query( $sql );
	$sql = "TRUNCATE " . WPF()->tables->mg2wpforo;
	$wpdb->query( $sql );
	/*$sql = "ALTER TABLE `" . WPF()->tables->topics . "` ENGINE = MyISAM";
	$wpdb->query($sql);
	$sql = "ALTER TABLE `" . WPF()->tables->posts . "` ENGINE = MyISAM";
	$wpdb->query($sql);*/

	$avatar_basedir = WPF()->folders['avatars']['dir'];
	$attach_basedir = WPF()->folders['attachments']['dir'];

	if( ! is_dir( $avatar_basedir ) ) wp_mkdir_p( $avatar_basedir );
	if( ! is_dir( $attach_basedir ) ) wp_mkdir_p( $attach_basedir );

	wpforo_update_option( 'go2_version', GO2WPFORO_VERSION );
}

add_filter( 'wpforo_init_tables', function( $tables ) {
	$tables[] = 'mg2wpforo';

	return $tables;
} );

function mg2wpforo_admin_notice__finish() {
	$class   = 'notice';
	$message = '';
	if( $_GET['mg2wpforo'] == 'finish' ) {
		$class   = 'notice notice-success ';
		$message = __( 'Migrate2wpForo Notice: Successfully Finished', 'mg2wpforo' );
	}
	printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
}

function mg2wpforo_admin_notice__no_forum_found() {
	$class   = 'notice notice-warning ';
	$message = __( 'Migrate2wpForo Notice: We can\'t find this board on this system', 'mg2wpforo' );
	printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
}

/**
 * @param string $dir
 *
 * @return string
 */
function go2wpf_fix_directory( $dir ) {
	$dir = str_replace( [ '/', '\\', '\\\\' ], DIRECTORY_SEPARATOR, $dir );

	return rtrim( trim( (string) $dir ), DIRECTORY_SEPARATOR );
}

/**
 * @param array $array
 *
 * @return bool
 */
function go2wpf_array_values_is_all_true( $array ) {
	foreach( $array as $a ) if( ! $a ) return false;

	return true;
}

/**
 * @param string $str
 *
 * @return int
 */
function go2wpf_str_to_int_id( $str ) {
	$id = 0;
	if( is_string( $str ) || is_numeric( $str ) ) foreach( str_split( md5( $str ) ) as $letter ) $id += ord( $letter );

	return $id;
}

/**
 * @param array $match
 *
 * @return string
 */
function go2wpf_style_bb2html( $match ) {
	$style = trim( str_replace( [ ' ', '=', "'", '"', 'size:' ], [ '; ', ':', '', '', 'font-size:' ], trim( (string) $match[1] ) ) );

	return '<span style="' . $style . '">';
}

/**
 * @param string $text
 *
 * @return string
 */
function go2wpf_bb2html( $text ) {
	$patterns     = [
		'#(src|href)=([^\"\'\]<>\s]+)#isu',
		'#\[(/?)quote[^\[\]]*?\]#isu',
		'#\[img[^\[\]]*?\][\r\n\t\s\0]*([^\[\]]*?)[\r\n\t\s\0]*\[/img\]#isu',
		'#\[url[\r\n\t\s\0]*?=[\r\n\t\s\0\'\"]*?([^\[\]\r\n\t\s\0\'\"]+?)[\r\n\t\s\0\'\"]*?\](.*?)\[/url\]#isu',
		'#\[url[^\[\]]*?\][\r\n\t\s\0]*([^\r\n\t\s\0]*?)[\r\n\t\s\0]*\[/url\]#isu',
		'#\[(/?)(u|center|marquee|table|tr|td|th|tt|sup|sub|s|ul|ol|li)(?:[\r\n\t\s\0=\'"]+[^\[\]]*|)\]#isu',
		'#\[(/?)(br|p|div)(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[(/?)(?:big|b|strong|h1|h2|h3|h4|h5|h6)(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[(/?)(?:i|em)(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[(/?)(?:code|php)(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[(/?)strike(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[(/?)list(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[\*\](.*?)(?:\[/\*\]|$)#ium',
		'#\[hr(?:[\r\n\t\s\0]+[^\[\]]*|)\]#isu',
		'#\[size[^\[\]]*?([\d\w-]+)[^\[\]]*?\]#isu',
		'#\[font=([^\[\]]*?)\]#isu',
		'#\[color[\r\n\t\s\0]*=[\r\n\t\s\0]*(\#?\w+?)[\r\n\t\s\0]*\]#isu',
		'#\[/(?:size|color|style|font)\]#isu',
		'#\[(?:align[\r\n\s\t\0]*=[\r\n\s\t\0]*)?(left|right|center)[^\[\]]*?\]#isu',
		'#\[/(?:left|right|align)\]#isu',
		'#\[youtube[^\[\]]*?\][\r\n\t\s\0]*(https?://[^\[\]]+)[\r\n\t\s\0]*\[/youtube\]#isu',
		'#\[youtube[^\[\]]*?\][\r\n\t\s\0]*([^\[\]]+)[\r\n\t\s\0]*\[/youtube\]#isu',
		'#\[email=[\r\n\t\s\0\'\"]*?([^\r\n\t\s\0\'\"\[\]]+?)[\r\n\t\s\0\'\"]*?\]([^\[\]]*)\[/email\]#isu',
		'#\[email[^\[\]]*?\]([^\[\]]*)\[/email\]#isu',
	];
	$replacements = [
		'$1="$2"',
		'<$1blockquote>',
		'<img class="go2wpf-bbcode" src="$1" alt="">',
		'<a class="go2wpf-bbcode" rel="nofollow" target="_blank" href="$1">$2</a>',
		'<a class="go2wpf-bbcode" rel="nofollow" target="_blank" href="$1">$1</a>',
		'<$1$2>',
		"<br>",
		'<$1b>',
		'<$1i>',
		'<$1pre>',
		'<$1s>',
		'<$1ul>',
		'<li>$1</li>',
		"\n<hr>",
		'<span style="font-size:$1px;">',
		'<span style="font-family:$1;">',
		'<span style="color:$1;">',
		'</span>',
		'<p style="text-align:$1;">',
		'</p>',
		' $1 ',
		' https://www.youtube.com/watch?v=$1 ',
		'<a href="mailto: $1">$2</a>',
		'<a href="mailto: $1">$1</a>',
	];
	$text         = preg_replace( $patterns, $replacements, (string) $text );
	$text         = preg_replace_callback( '#\[style[\r\n\t\s\0]+([^\[\]]+?)]#iu', 'go2wpf_style_bb2html', (string) $text );

	return preg_replace( '#[\r\n\t]*<br[^<>]*?>[\r\n\t]*#iu', '<br>', (string) $text );
}

/**
 * @param string $fileurl
 * @param string $filename
 * @param string $mime
 *
 * @return string
 */
function go2wpf_attach_make_html( $fileurl, $filename = '', $mime = '' ) {
	if( ! $filename ) $filename = basename( (string) $fileurl );
	if( ! $mime ) {
		$filetype = wp_check_filetype( $fileurl );
		$mime     = $filetype['type'];
	}
	if( strpos( (string) $mime, 'image/' ) !== false ) {
		$format   = '<div class="wpforo-attached-file-img"><img class="go2wpf-inline-attach-img" style="max-width: 320px; max-height: 240px" src="%s" alt=""></div>';
		$filename = sprintf( $format, $fileurl );
	}
	$format = '<div class="wpforo-attached-file"><a class="wpforo-default-attachment go2wpf-inline-attach" href="%s"><i class="fas fa-paperclip"></i> %s</a></div>';

	return sprintf( $format, $fileurl, $filename );
}
