<h3 class='page-title'>Category: <?php echo get_queried_object()->name; ?></h3>
<hr>
<?php
while (have_posts()) {
    the_post();
    get_template_part('templates/content', 'amc_publications_archive');
}
