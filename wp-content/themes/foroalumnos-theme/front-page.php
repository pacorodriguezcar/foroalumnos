<?php
/**
 * Plantilla: Portal de Inicio
 * Diseño: Figma — "Portal de Inicio" (Stitch, 15/05/2026)
 */

defined( 'ABSPATH' ) || exit;

get_header();

// ── Datos del foro ────────────────────────────────────────────────
global $wpdb;

// URL base de wpForo
$forum_page_id  = (int) get_option( 'wpforo_pageid', 0 );
$forum_base_url = $forum_page_id ? get_permalink( $forum_page_id ) : home_url( '/forum/' );

// Estadísticas globales
$total_topics  = (int) $wpdb->get_var( "SELECT SUM(topics) FROM {$wpdb->prefix}wpforo_forums WHERE parentid > 0" );
$total_posts   = (int) $wpdb->get_var( "SELECT SUM(posts)  FROM {$wpdb->prefix}wpforo_forums WHERE parentid > 0" );
$total_members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

// Últimos temas (máx. 5)
$recent_topics = $wpdb->get_results( "
    SELECT t.topicid, t.title, t.views, t.posts, t.created, t.forumid,
           u.display_name, u.ID as user_id,
           f.title AS forum_title, f.slug AS forum_slug
    FROM   {$wpdb->prefix}wpforo_topics t
    LEFT JOIN {$wpdb->users}               u ON u.ID = t.userid
    LEFT JOIN {$wpdb->prefix}wpforo_forums f ON f.forumid = t.forumid
    WHERE  t.status = 0
    ORDER  BY t.created DESC
    LIMIT  5
", ARRAY_A );

// Miembros en línea (activos en los últimos 15 min)
$online_members = $wpdb->get_results( "
    SELECT u.ID, u.display_name, um.meta_value AS last_activity
    FROM   {$wpdb->users}     u
    JOIN   {$wpdb->usermeta}  um ON um.user_id = u.ID AND um.meta_key = 'session_tokens'
    LIMIT  5
", ARRAY_A );

// Foros de categoría para las tarjetas (se crean en el Paso A)
$category_cards = [
    [
        'slug'        => 'eso',
        'title'       => 'ESO',
        'desc'        => 'Recursos y debates para Educación Secundaria Obligatoria. Matemáticas, Lengua, Ciencias y más.',
        'icon_class'  => 'foro-cat-card__icon--eso',
        'badge_class' => 'foro-badge--eso',
    ],
    [
        'slug'        => 'fp',
        'title'       => 'FP',
        'desc'        => 'Formación Profesional Dual, Ciclos de Grado Medio y Superior. Comparte experiencias.',
        'icon_class'  => 'foro-cat-card__icon--fp',
        'badge_class' => 'foro-badge--fp',
    ],
    [
        'slug'        => 'bachillerato',
        'title'       => 'Bachillerato',
        'desc'        => 'Preparación para la EVAU y recursos para Bachillerato Científico, Humanidades y Artes.',
        'icon_class'  => 'foro-cat-card__icon--bachillerato',
        'badge_class' => 'foro-badge--bachillerato',
    ],
];
?>

<div class="foro-page-home">

    <?php /* ── HERO ─────────────────────────────────────────────── */ ?>
    <section class="foro-hero foro-container">
        <h1 class="foro-hero__title">
            <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
        </h1>
        <p class="foro-hero__subtitle">
            <?php esc_html_e( 'La comunidad académica para resolver dudas, compartir recursos y crecer juntos en ESO, FP y Bachillerato.', 'foroalumnos' ); ?>
        </p>
        <div class="foro-hero__actions">
            <a href="<?php echo esc_url( $forum_base_url ); ?>" class="foro-btn foro-btn--primary foro-btn--lg">
                <?php esc_html_e( 'Explorar el foro', 'foroalumnos' ); ?>
            </a>
        </div>
    </section>

    <?php /* ── CATEGORÍAS ───────────────────────────────────────── */ ?>
    <section class="foro-container">
        <div class="foro-category-grid">
            <?php foreach ( $category_cards as $card ) :
                $card_url = trailingslashit( $forum_base_url ) . $card['slug'] . '/';
            ?>
            <a href="<?php echo esc_url( $card_url ); ?>" class="foro-cat-card">
                <div class="foro-cat-card__icon <?php echo esc_attr( $card['icon_class'] ); ?>">
                    <?php foroalumnos_category_icon( $card['slug'] ); ?>
                </div>
                <h2 class="foro-cat-card__title"><?php echo esc_html( $card['title'] ); ?></h2>
                <p class="foro-cat-card__desc"><?php echo esc_html( $card['desc'] ); ?></p>
                <span class="foro-cat-card__link">
                    <?php esc_html_e( 'Explorar foro', 'foroalumnos' ); ?> →
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php /* ── CONTENIDO PRINCIPAL + SIDEBAR ──────────────────── */ ?>
    <section class="foro-container">
        <div class="foro-main-layout">

            <?php /* Hilos recientes */ ?>
            <div class="foro-topic-area">
                <div class="foro-topic-list__header">
                    <h2 class="foro-headline-sm">
                        <?php esc_html_e( 'Temas recientes', 'foroalumnos' ); ?>
                    </h2>
                    <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( trailingslashit( $forum_base_url ) . '?action=new-topic' ); ?>"
                       class="foro-btn foro-btn--primary">
                        <?php esc_html_e( '+ Nuevo tema', 'foroalumnos' ); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $recent_topics ) ) : ?>
                    <?php foreach ( $recent_topics as $topic ) :
                        $topic_url   = trailingslashit( $forum_base_url ) . $topic['forum_slug'] . '/' . $topic['topicid'] . '/';
                        $avatar_url  = get_avatar_url( $topic['user_id'], [ 'size' => 40 ] );
                        $time_ago    = human_time_diff( strtotime( $topic['created'] ), current_time( 'timestamp' ) );
                        $forum_badge = foroalumnos_get_forum_badge( $topic['forum_title'] );
                    ?>
                    <article class="foro-topic-card">
                        <div class="foro-topic-card__content">
                            <div class="foro-topic-card__meta">
                                <span class="foro-badge <?php echo esc_attr( $forum_badge['class'] ); ?>">
                                    <?php echo esc_html( $topic['forum_title'] ); ?>
                                </span>
                                <span class="foro-topic-card__time">
                                    <?php printf(
                                        /* translators: %s: tiempo transcurrido */
                                        esc_html__( 'Hace %s', 'foroalumnos' ),
                                        esc_html( $time_ago )
                                    ); ?>
                                </span>
                            </div>
                            <h3 class="foro-topic-card__title">
                                <a href="<?php echo esc_url( $topic_url ); ?>">
                                    <?php echo esc_html( $topic['title'] ); ?>
                                </a>
                            </h3>
                        </div>
                        <div class="foro-topic-card__stats">
                            <div class="foro-topic-card__stat">
                                <div class="foro-topic-card__stat-value"><?php echo esc_html( number_format_i18n( $topic['posts'] ) ); ?></div>
                                <div class="foro-topic-card__stat-label"><?php esc_html_e( 'Respuestas', 'foroalumnos' ); ?></div>
                            </div>
                            <div class="foro-topic-card__stat">
                                <div class="foro-topic-card__stat-value"><?php echo esc_html( number_format_i18n( $topic['views'] ) ); ?></div>
                                <div class="foro-topic-card__stat-label"><?php esc_html_e( 'Vistas', 'foroalumnos' ); ?></div>
                            </div>
                            <img class="foro-topic-card__avatar"
                                 src="<?php echo esc_url( $avatar_url ); ?>"
                                 alt="<?php echo esc_attr( $topic['display_name'] ); ?>"
                                 width="40" height="40">
                        </div>
                    </article>
                    <?php endforeach; ?>

                <?php else : ?>
                    <div class="foro-empty-state">
                        <p><?php esc_html_e( 'Todavía no hay temas. ¡Sé el primero en publicar!', 'foroalumnos' ); ?></p>
                        <a href="<?php echo esc_url( $forum_base_url ); ?>" class="foro-btn foro-btn--primary">
                            <?php esc_html_e( 'Ir al foro', 'foroalumnos' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php /* ── SIDEBAR ───────────────────────────────────── */ ?>
            <aside class="foro-sidebar">

                <?php /* Miembros online */ ?>
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
                        $member_roles  = $member->roles;
                        $display_role  = foroalumnos_get_display_role( $member_roles );
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
                            <div class="foro-member-item__name">
                                <?php echo esc_html( $member->display_name ); ?>
                            </div>
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

                <?php /* Estadísticas del foro */ ?>
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

            </aside>
        </div>
    </section>

</div><!-- .foro-page-home -->

<?php get_footer(); ?>
