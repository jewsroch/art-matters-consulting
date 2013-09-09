<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>

  <!--[if lt IE 7]><div class="alert"><?php _e('You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.', 'roots'); ?></div><![endif]-->

  <?php
    do_action('get_header');
    // Use Bootstrap's navbar if enabled in config.php
    if (current_theme_supports('bootstrap-top-navbar')) {
      get_template_part('templates/header-top-navbar');
    } else {
      get_template_part('templates/header');
    }
  ?>

    <?php // Optional Top Quote
    global $post;

    $topQuote = get_post_meta($post->ID, 'top_quote', true);
    if($topQuote != ''){?>
        <div class="full-width banner-text">
            <div class="container">
                <p class="lead">
                    <?php echo get_post_meta($post->ID, 'top_quote', true); ?>
                <p>
            </div>
        </div>
    <?php } else { ?>
        <div class="full-width single-stripe"></div>
    <?php } ?>

    <?php // Optional Page Thumbnail
    if (has_post_thumbnail($post->ID) && is_page()) {?>
        <div class="full-width-image">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <?php echo get_the_post_thumbnail($post->ID,'responsive-wide', array('class' => 'top-image img-responsive img-rounded')) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="full-width-stripe hidden-xs"></div>
    <?php } ?>

      <div class="main <?php echo roots_main_class(); ?>" role="main">
        <?php include roots_template_path(); ?>
      </div><!-- /.main -->
      <?php if (roots_display_sidebar()) : ?>
      <!--<aside class="sidebar <?php /*echo roots_sidebar_class(); */?>" role="complementary">
        <?php /*include roots_sidebar_path(); */?>
      </aside>--><!-- /.sidebar -->
      <?php endif; ?>

  <?php get_template_part('templates/footer'); ?>

</body>
</html>
