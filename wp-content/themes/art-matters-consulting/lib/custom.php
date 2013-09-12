<?php
/**
 * Custom functions
 */
//remove_filter( 'the_content', 'wpautop' );
//add_filter( 'the_content', 'wpautop' , 99);
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
    $code = '';
    $code .= '<div class="row ' . esc_attr($class) . '">';
    $code .= do_shortcode($content);
    $code .= '</div>';
    return $code;
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

    if ($pad_left > (12 - $size)) {
        $pad_left = (12 - $size);
    }

    $code  = "<div class='" . esc_attr("col-sm-{$size} ") . ($offset > 0 ? esc_attr("col-md-offset-{$offset} ") : "") . esc_attr("text-{$align}") . "'>\n";
    $code .= do_shortcode($content);
    $code .= "</div>\n";

    return $code;
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

function heading_shortcode( $atts, $content = null ) {
    extract( shortcode_atts(
            array(
                'size' => 'large',
            ), $atts )
    );

    $htype = '';
    switch ($size) {
        case 'large':
            $htype = 'h2';
            break;
        case 'medium':
            $htype = 'h3';
            break;
        case 'small':
            $htype = 'h4';
            break;
        case 'extra-small':
            $htype = 'h5';
            break;
    }
    return '<' . esc_attr($htype) . '>' . do_shortcode($content) . '</' . esc_attr($htype) . '>';
}
add_shortcode('heading', 'heading_shortcode');

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