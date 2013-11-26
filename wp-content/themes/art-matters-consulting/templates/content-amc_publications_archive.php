<?php $cf = get_post_custom(); //var_dump($cf);?>

<div class="row publication">
    <article class="col-sm-12 well ">
        <header>
            <h3 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php // get_template_part('templates/entry-meta'); ?>
        </header>
        <div class="row">
            <div class="col-xs-12 col-sm-8">
                <row>
                    <div class="col-xs-4 text-center <?php //echo (get_the_ID() % 2 == 0) ? 'pull-right': ''; ?>">
                        <a target="_blank" href="<?php echo $cf['pub_dl_link']['0'] == '' ? esc_url($cf['pub_url']['0']) : esc_url($cf['pub_dl_link']['0']); ?>">
                            <?php the_post_thumbnail('publication', array(
                                'class' => 'img-responsive img-rounded publication-image',
                                'alt' => get_the_title(),
                            )); ?>
                        </a>
                    </div>
                    <div class="col-xs-8">
                        <dl class="dl-horizontal">
                            <?php if ($cf['pub_date']['0'] != ''){ ?>
                                <dt><span class="glyphicon glyphicon-calendar"></span></dt>
                                <dd><?php echo esc_attr($cf['pub_date']['0']); ?></dd>
                            <?php } ?>

                            <?php if ($cf['pub_source']['0'] != ''){ ?>
                                <dt><span class="glyphicon glyphicon-book"></span></dt>
                                <dd>
                                    <a target="_blank" href="<?php echo esc_url($cf['pub_url'][0]); ?>">
                                        <?php echo esc_attr($cf['pub_source']['0']); ?>
                                    </a>
                                </dd>
                            <?php } ?>

                            <?php if ($cf['pub_author']['0'] != ''){ ?>
                                <dt><span class="glyphicon glyphicon-user"></span></dt>
                                <dd><?php echo esc_attr($cf['pub_author']['0']); ?></dd>
                            <?php } ?>

                            <?php
                            $terms = get_the_terms(get_the_ID(), 'publications_categories');
                            if ($terms != false && !is_wp_error($terms)){ ?>
                                <dt><span class="glyphicon glyphicon-tags"></span></dt>
                                <dd><?php the_terms(get_the_ID(), 'publications_categories', '', ', '); ?></dd>
                            <?php } ?>

                            <?php if ($cf['pub_url_note']['0'] != ''){ ?>
                                <dt><span class="glyphicon glyphicon-asterisk"></span></dt>
                                <dd><em><?php echo esc_attr($cf['pub_url_note']['0']); ?></em></dd>
                            <?php } ?>
                        </dl>
                    </div>
                </row>
            </div>
            <div class="col-xs-12 col-sm-4 pub-actions">
<!--                <a href="--><?php //echo esc_url($cf['pub_url']['0']); ?><!--" class=" btn btn-default"><span class="glyphicon glyphicon-search"></span>Visit Journal</a>-->
                <?php if ($cf['pub_dl_link']['0'] != '') { ?>
                    <a href="<?php echo esc_url($cf['pub_dl_link']['0']); ?>" class=" btn btn-default"><span class="glyphicon glyphicon-print"></span>Download</a>
                <?php } ?>
            </div>
        </div>
        <?php //comments_template('/templates/comments.php'); ?>
    </article>
</div>
