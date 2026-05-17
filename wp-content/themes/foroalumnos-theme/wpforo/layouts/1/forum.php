<?php
/**
 * Override wpForo Layout 1 (Extended) — forum.php
 * Diseño: Figma — "Community Forums" + "Categoría Bachillerato"
 *
 * Variables disponibles (pasadas por include desde forum.php de wpForo):
 *   $cat    — array del foro-categoría actual
 *   $forums — array de subforos del $cat
 */
defined( 'ABSPATH' ) || exit;

// Determinar clase de acento según el slug del foro padre
$slug         = isset( $cat['slug'] ) ? $cat['slug'] : '';
$accent_class = '--eso';
if ( $slug === 'fp' || str_contains( $slug, 'fp' ) ) {
    $accent_class = '--fp';
} elseif ( $slug === 'bachillerato' || str_contains( $slug, 'bach' ) ) {
    $accent_class = '--bachillerato';
}
?>

<div class="foro-subcat-group foro-subcat-group<?php echo esc_attr( $accent_class ); ?>">

    <?php /* Cabecera del grupo (nombre + descripción de la categoría) */ ?>
    <div class="foro-subcat-group__header">
        <h2 class="foro-headline-sm">
            <a href="<?php echo esc_url( (string) wpforo_forum( $cat['forumid'], 'url' ) ); ?>">
                <?php echo esc_html( $cat['title'] ); ?>
            </a>
        </h2>
        <?php if ( ! empty( $cat['description'] ) ) : ?>
        <p class="foro-body-sm foro-text-secondary">
            <?php echo wp_kses_post( $cat['description'] ); ?>
        </p>
        <?php endif; ?>
    </div>

    <?php
    $forum_list = false;
    foreach ( $forums as $forum ) :
        if ( ! WPF()->perm->forum_can( 'vf', $forum['forumid'] ) ) continue;

        $forum_list = true;
        $forum_url  = wpforo_forum( $forum['forumid'], 'url' );
        $counts     = wpforo_forum( $forum['forumid'], 'counts' );

        // Último post en este foro
        $last_post   = ! empty( $forum['last_postid'] ) ? wpforo_post( $forum['last_postid'] ) : null;
        $last_member = ! empty( $last_post ) ? wpforo_member( $last_post ) : null;
        $last_date   = ! empty( $forum['last_post_date'] ) && $forum['last_post_date'] !== '0000-00-00 00:00:00'
            ? human_time_diff( strtotime( $forum['last_post_date'] ), current_time( 'timestamp' ) )
            : null;
    ?>
    <a href="<?php echo esc_url( (string) $forum_url ); ?>"
       class="foro-subcat-row foro-subcat-row<?php echo esc_attr( $accent_class ); ?> <?php wpforo_unread( $forum['forumid'], 'forum' ); ?>">

        <div class="foro-subcat-row__info">
            <h3 class="foro-subcat-row__title">
                <?php echo esc_html( $forum['title'] ); ?>
                <?php wpforo_viewing( $forum ); ?>
            </h3>
            <?php if ( ! empty( $forum['description'] ) ) : ?>
            <p class="foro-subcat-row__desc">
                <?php echo wp_kses_post( $forum['description'] ); ?>
            </p>
            <?php endif; ?>
        </div>

        <div class="foro-subcat-row__stats">
            <div class="foro-subcat-row__stat">
                <span class="foro-subcat-row__stat-value">
                    <?php echo esc_html( number_format_i18n( (int) $counts['topics'] ) ); ?>
                </span>
                <span class="foro-subcat-row__stat-label">
                    <?php esc_html_e( 'Temas', 'foroalumnos' ); ?>
                </span>
            </div>
            <div class="foro-subcat-row__stat">
                <span class="foro-subcat-row__stat-value">
                    <?php echo esc_html( number_format_i18n( (int) $counts['posts'] ) ); ?>
                </span>
                <span class="foro-subcat-row__stat-label">
                    <?php esc_html_e( 'Respuestas', 'foroalumnos' ); ?>
                </span>
            </div>
        </div>

        <?php if ( $last_member && $last_date ) : ?>
        <div class="foro-subcat-row__last-post">
            <?php echo wp_kses_post( wpforo_user_avatar( $last_member, 32 ) ); ?>
            <div class="foro-subcat-row__last-meta">
                <span class="foro-subcat-row__last-name">
                    <?php echo esc_html( $last_member['display_name'] ); ?>
                </span>
                <span class="foro-subcat-row__last-date">
                    <?php printf(
                        /* translators: %s: tiempo transcurrido */
                        esc_html__( 'Hace %s', 'foroalumnos' ),
                        esc_html( $last_date )
                    ); ?>
                </span>
            </div>
        </div>
        <?php else : ?>
        <div class="foro-subcat-row__last-post foro-subcat-row__last-post--empty">
            <span class="foro-text-muted foro-body-sm">
                <?php esc_html_e( 'Sin actividad', 'foroalumnos' ); ?>
            </span>
        </div>
        <?php endif; ?>

    </a><!-- .foro-subcat-row -->

    <?php do_action( 'wpforo_loop_hook', 0, $forum ); ?>

    <?php endforeach; // $forums ?>

    <?php if ( ! $forum_list ) : ?>
        <?php do_action( 'wpforo_forum_loop_no_forums', $cat ); ?>
    <?php endif; ?>

</div><!-- .foro-subcat-group -->
