<?php
while (have_posts()) {
    the_post();
    get_template_part('templates/content', 'amc_publications');
}
