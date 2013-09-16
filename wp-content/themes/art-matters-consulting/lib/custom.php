<?php
/**
 * Custom functions
 */
remove_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'wpautop' , 99);
add_filter( 'the_content', 'shortcode_unautop', 98);

/**
 * Custom shortcodes
 */
function row_shortcode( $atts , $content = null ) {

    // Attributes
    extract( shortcode_atts(
            array(
                'class' => '',
            ), $atts )
    );

    // Code
    ob_start();?>
    <div class="row<?php echo esc_attr($class); ?>">
    <?php echo do_shortcode($content); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'row', 'row_shortcode' );


function shortcodeColumn($atts, $content = null)
{
    extract( shortcode_atts( array(
        'align'     => 'left',
        'size'      => '12',
        'offset'  => '0',
    ), $atts ) );

    if ($size <= 0) {
        $size = 1;
    }
    if ($size > 12) {
        $size = 12;
    }

    ob_start(); ?>
    <div class="col-sm-<?php echo $size . " " . ($offset > 0 ? esc_attr("col-md-offset-{$offset} ") : " ") . esc_attr("text-{$align}");?>">
    <?php echo do_shortcode($content); ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('column', 'shortcodeColumn');

function line_shortcode( $atts ) {
    extract( shortcode_atts(
        array(
            'color' => 'orange',
        ), $atts )
    );

    return '<div class="full-width rounded ' . esc_attr($color) . '"></div>';
}
add_shortcode('line', 'line_shortcode');

function full_width_buttons_shortcode( $atts, $content = null ) {
    extract( shortcode_atts(
            array(
                'button1text' => 'Button 1',
                'button1url' => '#',
                'button2text' => 'Button 2',
                'button2url' => '#',
            ), $atts )
    );

    ob_start(); ?>
        </div>
        <div class="full-width-buttons">
            <div class="container">
                <div class="row">
                    <div class="col-sm-5 col-md-4 col-md-offset-1 col-sm-offset-1 col-xs-12">
                        <a href="<?php echo $button1url; ?>" class="btn btn-block btn-primary btn-lg btn-amc"><?php echo $button1text; ?></a>
                    </div>
                    <div class="col-sm-5 col-md-4 col-md-offset-2 col-xs-12">
                        <a href="<?php echo $button2url; ?>" class="btn btn-block btn-primary btn-lg btn-amc"><?php echo $button2text; ?></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="full-width-stripes-blue hidden-xs">
            <div class="full-with blue-light"></div>
            <div class="full-with blue-light"></div>
            <div class="full-with blue-light"></div>
            <div class="full-with blue-light"></div>
        </div>
        <div class="container">
    <?php
    return ob_get_clean();
}
add_shortcode('full_width_buttons', 'full_width_buttons_shortcode');

function button_list_left_shortcode( $atts, $content = null ) {

    ob_start();?>
        <div class="col-sm-5 col-md-4 col-md-offset-1 col-sm-offset-1 col-xs-12 button-list">
            <?php echo do_shortcode($content); ?>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode('button_list_left', 'button_list_left_shortcode');

function button_list_right_shortcode( $atts, $content = null ) {

    ob_start();?>
        <div class="col-sm-5 col-md-4 col-md-offset-2 col-xs-12 button-list">
            <?php echo do_shortcode($content); ?>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode('button_list_right', 'button_list_right_shortcode');

function well_shortcode( $atts, $content = null ) {

    extract( shortcode_atts(
            array(
                'size' => '',
            ), $atts )
    );

    ob_start();?>
    <div class="well <?php echo $size == 'large' ? 'well-lg' : ''; echo $size == 'small' ? 'well-sm' : '';?>">
        <?php echo do_shortcode($content); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('well', 'well_shortcode');

function button_shortcode( $atts, $conten = null) {

    extract( shortcode_atts(
       array(
           'size' => '',
           'color' => 'btn-default',
           'block' => false,
           'url' => '#',
           'text' => 'Button Text',
       ), $atts)
    );
    $btn_style = '';
    switch ($color) {
        case 'blue-light':
            $btn_style = 'btn-primary';
            break;
        case 'blue-dark':
            $btn_style = 'btn-info';
            break;
        case 'orange':
            $btn_style = 'btn-warning';
            break;
        case 'red':
            $btn_style = 'btn-danger';
            break;
        case 'green':
            $btn_style = 'btn-success';
            break;
        default:
            $btn_style = 'btn-default';
    }

    $btn_size = '';
    switch ($size) {
        case 'small':
            $btn_size = 'btn-sm';
            break;
        case 'large':
            $btn_size = 'btn-lg';
            break;
        case 'extra-small':
            $btn_size = 'btn-xs';
            break;
        default:
            $btn_size = '';
    }

    $code = "";
    $code .= '<a href="' . $url . '" class="btn ' . $btn_style . ($block ? " btn-block" : "") . ' ' . $btn_size . '">' . $text . '</a>';
    return $code;
}
add_shortcode('button', 'button_shortcode');

function list_shortcode( $atts, $content = null ) {

    ob_start();?>
    <div class="check-list">
        <?php echo do_shortcode($content); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('checked_list', 'list_shortcode');