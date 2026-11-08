<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="kayan-cc-wrap">
    <div class="kayan-cc-header">
        <div class="kayan-cc-logo">
            <span class="dashicons dashicons-phone"></span>
            <h1>KAYAN Control Center</h1>
            <span class="kayan-cc-version">v<?php echo esc_html( KAYAN_CC_VERSION ); ?></span>
        </div>
        <div class="kayan-cc-status-bar">
            <?php if ( ! KayanCC_SEO_Manager::get_instance()->is_rank_math_active() ) : ?>
                <span class="kayan-cc-badge kayan-cc-badge--warning">⚠ Rank Math غير مفعل</span>
            <?php else : ?>
                <span class="kayan-cc-badge kayan-cc-badge--success">✓ Rank Math مفعل</span>
            <?php endif; ?>
        </div>
    </div>
    <nav class="kayan-cc-nav">
        <?php
        $nav_items = [
            [ 'kayan-control-center', '📞 إدارة الأرقام' ],
            [ 'kayan-cc-seo-manager', '🔎 عناوين SEO'    ],
        ];
        $current = $_GET['page'] ?? 'kayan-control-center';
        foreach ( $nav_items as [ $slug, $label ] ) :
        ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
               class="kayan-cc-nav__item<?php echo $current === $slug ? ' kayan-cc-nav__item--active' : ''; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="kayan-cc-content">
