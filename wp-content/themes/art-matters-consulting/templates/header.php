<header class="navbar navbar-static-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 logo col-md-12">
                <a class="logo" href="#"><img src="/assets/img/logo.png" alt="Art Matters Consulting"/></a>
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
<div class="full-width banner-text">
    <div class="container">
        <p class="lead">Your trusted art advisory firm.
        <nobr>We’re here to help.</nobr>
        </p>
    </div>
</div>

<div class="full-width-image">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <img src="/assets/img/art-museum.jpg" class="top-image img-responsive img-rounded" alt="Responsive image">
            </div>
        </div>
    </div>
</div>
<div class="full-width-stripe hidden-xs"></div>