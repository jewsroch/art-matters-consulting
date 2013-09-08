<header class="navbar navbar-static-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 logo col-md-12">
                <a class="logo" href="#"><img src="<?php echo get_template_directory_uri() ?>/assets/img/logo.png" alt="Art Matters Consulting"/></a>
            </div>
            <div class="col-sm-12 col-md-12 col-lg-9">
                <nav class="nav-main" role="navigation">
                    <?php
                    if (has_nav_menu('primary_navigation')) :
                        wp_nav_menu(array('theme_location' => 'primary_navigation', 'menu_class' => 'nav nav-pills'));
                    endif;
                    ?>
                </nav>
            </div>
        </div>
    </div>
</header>