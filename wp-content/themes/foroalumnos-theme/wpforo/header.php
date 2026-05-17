<?php
/**
 * Override wpForo header.php
 * Sustituye la barra de navegación de wpForo por las migas de pan del design system.
 */
defined( 'ABSPATH' ) || exit;

do_action( 'wpforo_top_hook' );
?>

<div class="foro-wpforo-header">

    <?php /* Migas de pan de wpForo (data ya calculada por el plugin) */ ?>
    <?php if ( wpforo_setting( 'components', 'breadcrumb' ) ) : ?>
    <div class="foro-breadcrumbs-wrap">
        <?php WPF()->tpl->breadcrumb( WPF()->current_object ); ?>
    </div>
    <?php endif; ?>

    <?php /* Notificaciones (mantener funcionalidad de wpForo) */ ?>
    <?php if ( wpforo_setting( 'notifications', 'notifications' ) ) : ?>
        <?php wpforo_notifications(); ?>
    <?php endif; ?>

    <?php do_action( 'wpforo_header_hook' ); ?>

</div><!-- .foro-wpforo-header -->
