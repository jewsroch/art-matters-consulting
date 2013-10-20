<h3 class='page-title'>Search for: <em><?php echo get_search_query(); ?></em></h3>
<hr>
<?php
while (have_posts()) {
    the_post();
    get_template_part('templates/content', 'amc_publications_archive');
}
