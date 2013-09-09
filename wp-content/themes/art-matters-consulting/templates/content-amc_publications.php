    <?php $cf = get_post_custom(); //var_dump($cf);?>

    <div class="row publication">
        <article class="col-md-10 col-md-offset-1 col-sm-12 well ">
            <header>
                <h3 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php // get_template_part('templates/entry-meta'); ?>
            </header>
            <div class="row">
                <div class="col-xs-6 text-center col-sm-3 <?php echo (get_the_ID() % 2 == 0) ? 'pull-right': ''; ?>">
                    <a target="_blank" href="<?php echo esc_attr($cf['pub_url'][0]); ?>">
                    <?php the_post_thumbnail('publication', array(
                        'class' => 'img-responsive img-rounded publication-image',
                        'alt' => get_the_title(),
                    )); ?>
                    </a>
                </div>
                <div class="col-xs-12 col-sm-9">
                    <?php echo $cf['pub_description']['0']; ?>
                    <dl class="dl-horizontal">
                        <dt>Date Published:</dt>
                        <dd><?php echo esc_attr($cf['pub_date']['0']); ?></dd>
                        <dt>Journal / Publication:</dt>
                        <dd>
                            <a target="_blank" href="<?php echo esc_attr($cf['pub_url'][0]); ?>">
                            <?php echo esc_attr($cf['pub_source']['0']); ?>
                            </a>
                        </dd>
                        <dt>Author:</dt>
                        <dd><?php echo esc_attr($cf['pub_author']['0']); ?></dd>
                        <?php if ($cf['pub_url_note']['0'] != ''){ ?>
                        <dt><em>Notes:</em></dt>
                        <dd><em><?php echo esc_attr($cf['pub_url_note']['0']); ?></em></dd>
                        <?php } ?>
                    </dl>
                </div>
            </div>
            <?php //comments_template('/templates/comments.php'); ?>
        </article>
    </div>
