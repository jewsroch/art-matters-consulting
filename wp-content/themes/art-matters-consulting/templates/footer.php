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

                ?>
                <!--<div class="row">
                    <ul class="list-unstyled">
                        <div class="col-sm-4">
                            <li class="active"><a href="#">Home</a></li>
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">For Collectors</a></li>
                            <li><a href="#">For Institutions</a></li>
                        </div>
                        <div class="col-sm-4">
                            <li><a href="#">Publications</a></li>
                            <li><a href="#">Representative Projects</a></li>
                            <li><a href="#">Contact Us</a></li>
                        </div>
                    </ul>
                </div>-->
            </div>
            <div class="col-xs-12 col-sm-4 footer-icon">
                <a href="">
                    <h3><?php bloginfo('name'); ?></h3>
                    <h5>&copy; <?php echo date('Y'); ?></h5>
                </a>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>