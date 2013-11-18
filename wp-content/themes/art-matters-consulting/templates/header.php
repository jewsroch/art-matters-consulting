<header class="navbar navbar-static-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-12">
                <a class="logo" href="<?php echo get_site_url() ?>"><span class="logo-svg"></span></a>
<!--                <img src="--><?php //echo get_template_directory_uri() ?><!--/assets/img/logo.png" alt="Art Matters Consulting"/>-->
                <button class="navbar-toggle">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div class="nav-container col-sm-12 col-md-12 col-lg-9">
                <?php
                $menu_to_count = wp_nav_menu(array(
                    'echo' => false,
                    'theme_location' => 'primary_navigation',
                    'depth' => 1,
                ));
                $menu_items = substr_count($menu_to_count,'<li class="');
                ?>
                <nav class="nav-main <?php
                    switch ($menu_items) {
                        case 2:
                            echo "two-items";
                            break;
                        case 3:
                            echo "three-items";
                            break;
                        case 4:
                            echo "four-items";
                            break;
                        case 5:
                            echo "five-items";
                            break;
                        case 6:
                            echo "six-items";
                            break;
                        case 7:
                            echo "seven-items";
                            break;
                        case 8:
                            echo "nine-items";
                            break;
                        case 9:
                            echo "ten-items";
                            break;
                    }
                ?>" role="navigation">
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