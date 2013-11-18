<footer>
    <div class="container">
        <div class="row">
            <div class="col-sm-8 col-xs-12 footer-nav">
                <?php
                $defaults = array(
                    'theme_location'  => 'footer_navigation',
                    'menu'            => '',
                    'container'       => 'div',
                    'container_class' => 'row',
                    'container_id'    => '',
                    'menu_class'      => 'list-unstyled',
                    'menu_id'         => '',
                    'echo'            => true,
                    'fallback_cb'     => 'wp_page_menu',
                    'before'          => '',
                    'after'           => '',
                    'link_before'     => '',
                    'link_after'      => '',
                    'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'depth'           => 0,
                    'walker'          => ''
                );

                wp_nav_menu( $defaults );

                $styleGuide = get_page_by_title('Style Guide');
                if (is_user_logged_in() && isset($styleGuide) ) {
                    echo '<a href="' . get_permalink($styleGuide->ID) . '">Style Guide</a>';
                }
                ?>

            </div>

            <?php $opts = get_option('amc_options'); ?>
            <div class="col-xs-12 col-sm-4 footer-icon">
                <?php if($opts['social_show']) { ?>
                <div class="footer-social">
                    <?php
                    echo ($opts['facebook_link'] != '') ? '<a href="' . esc_attr($opts['facebook_link']) . '" class="amc-icon-social-facebook-circular"></a>' : '' ;
                    echo ($opts['twitter_link'] != '') ? '<a href="' . esc_attr($opts['twitter_link']) . '" class="amc-icon-social-twitter-circular"></a>' : '' ;
                    echo ($opts['linkedin_link'] != '') ? '<a href="' . esc_attr($opts['linkedin_link']) . '" class="amc-icon-social-linkedin-circular"></a>' : '' ;
                    ?>
                </div>
                <?php } ?>

                <a href="">
                    <h5>&copy; <?php echo date('Y'); ?> - <?php bloginfo('name'); ?></h5>
                </a>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>