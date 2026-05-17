<?php
/**
 * Plantilla: Foro Comunidad
 * Template Name: Foro Comunidad
 * Diseño: Figma — "Community Forums" (Stitch, 15/05/2026)
 */

defined( 'ABSPATH' ) || exit;

// Forzar layout sin sidebar de Astra para esta plantilla
add_filter( 'astra_page_layout', function() { return 'no-sidebar'; } );

get_header();

global $wpdb;

$total_topics  = (int) $wpdb->get_var( "SELECT SUM(topics) FROM {$wpdb->prefix}wpforo_forums WHERE parentid > 0" );
$total_posts   = (int) $wpdb->get_var( "SELECT SUM(posts)  FROM {$wpdb->prefix}wpforo_forums WHERE parentid > 0" );
$total_members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

$forum_page_id  = (int) get_option( 'wpforo_pageid', 0 );
$forum_base_url = $forum_page_id ? get_permalink( $forum_page_id ) : home_url( '/community/' );
?>

<div class="foro-page-forum">

    <?php /* ── CABECERA DE ESTADÍSTICAS ──────────────────────────── */ ?>
    <section class="foro-container">
        <div class="foro-stats-bar">
            <div class="foro-stats-bar__counters">
                <div class="foro-stats-bar__item">
                    <span class="foro-stats-bar__value"><?php echo esc_html( number_format_i18n( $total_topics ) ); ?></span>
                    <span class="foro-stats-bar__label"><?php esc_html_e( 'Temas', 'foroalumnos' ); ?></span>
                </div>
                <div class="foro-stats-bar__item">
                    <span class="foro-stats-bar__value"><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></span>
                    <span class="foro-stats-bar__label"><?php esc_html_e( 'Respuestas', 'foroalumnos' ); ?></span>
                </div>
                <div class="foro-stats-bar__item">
                    <span class="foro-stats-bar__value"><?php echo esc_html( number_format_i18n( $total_members ) ); ?></span>
                    <span class="foro-stats-bar__label"><?php esc_html_e( 'Miembros', 'foroalumnos' ); ?></span>
                </div>
            </div>
            <?php if ( is_user_logged_in() ) : ?>
            <a href="<?php echo esc_url( trailingslashit( $forum_base_url ) . '?action=new-topic' ); ?>"
               class="foro-btn foro-btn--primary">
                <?php esc_html_e( '+ Nuevo tema', 'foroalumnos' ); ?>
            </a>
            <?php else : ?>
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
               class="foro-btn foro-btn--outline">
                <?php esc_html_e( 'Acceder para participar', 'foroalumnos' ); ?>
            </a>
            <?php endif; ?>
        </div>
    </section>

    <?php /* ── CONTENIDO WPFORO + SIDEBAR ──────────────────────── */ ?>
    <section class="foro-container">
        <div class="foro-main-layout">

            <div class="foro-forum-content">
                <?php while ( have_posts() ) :
                    the_post();
                    the_content();
                endwhile; ?>
            </div>

            <aside class="foro-sidebar">

                <?php /* Miembros activos */ ?>
                <div class="foro-sidebar-card">
                    <h3 class="foro-sidebar-card__title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <?php esc_html_e( 'Miembros activos', 'foroalumnos' ); ?>
                    </h3>
                    <?php
                    $recent_users = get_users( [
                        'number'  => 4,
                        'orderby' => 'registered',
                        'order'   => 'DESC',
                    ] );
                    foreach ( $recent_users as $member ) :
                        $member_avatar = get_avatar_url( $member->ID, [ 'size' => 32 ] );
                        $display_role  = foroalumnos_get_display_role( $member->roles );
                    ?>
                    <div class="foro-member-item">
                        <div class="foro-member-item__avatar-wrap">
                            <img class="foro-member-item__avatar"
                                 src="<?php echo esc_url( $member_avatar ); ?>"
                                 alt="<?php echo esc_attr( $member->display_name ); ?>"
                                 width="32" height="32">
                            <span class="foro-member-item__online-dot" aria-hidden="true"></span>
                        </div>
                        <div>
                            <div class="foro-member-item__name"><?php echo esc_html( $member->display_name ); ?></div>
                            <div class="foro-member-item__role <?php echo esc_attr( $display_role['class'] ); ?>">
                                <?php echo esc_html( $display_role['label'] ); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ( $total_members > 4 ) : ?>
                    <p class="foro-text-muted foro-body-sm" style="margin-top:var(--space-4)">
                        <?php printf(
                            esc_html__( '+ %s miembros registrados', 'foroalumnos' ),
                            esc_html( number_format_i18n( $total_members - 4 ) )
                        ); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <?php /* Estadísticas */ ?>
                <div class="foro-sidebar-card">
                    <h3 class="foro-sidebar-card__title">
                        <?php esc_html_e( 'Estadísticas', 'foroalumnos' ); ?>
                    </h3>
                    <div class="foro-stat-row">
                        <span class="foro-stat-row__label"><?php esc_html_e( 'Temas', 'foroalumnos' ); ?></span>
                        <span class="foro-stat-row__value"><?php echo esc_html( number_format_i18n( $total_topics ) ); ?></span>
                    </div>
                    <div class="foro-stat-row">
                        <span class="foro-stat-row__label"><?php esc_html_e( 'Respuestas', 'foroalumnos' ); ?></span>
                        <span class="foro-stat-row__value"><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></span>
                    </div>
                    <div class="foro-stat-row">
                        <span class="foro-stat-row__label"><?php esc_html_e( 'Miembros', 'foroalumnos' ); ?></span>
                        <span class="foro-stat-row__value"><?php echo esc_html( number_format_i18n( $total_members ) ); ?></span>
                    </div>
                </div>

                <?php if ( is_active_sidebar( 'foroalumnos-forum-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'foroalumnos-forum-sidebar' ); ?>
                <?php endif; ?>

            </aside>
        </div>
    </section>

</div><!-- .foro-page-forum -->

<?php get_footer(); ?>
