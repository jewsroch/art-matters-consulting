<?php

function amcGetOptions()
{
    $opts = get_option('amc_options');

    $ret = array(
        'facebook_link'   => '',
        'twitter_link'    => '',
        'linkedin_link'   => '',
        'social_show'     => '0',
    );

    if ($opts && is_array($opts)) {
        foreach ($opts as $k => $v) {
            if (isset($ret[$k])) {
                $ret[$k] = $v;
            }
        }
    }

    return $ret;
}

$opts = amcGetOptions();

if (isset($_POST['amc_options'])) {

    // Sanitize
    $opts = $_POST['amc_options'];

    update_option('amc_options', $opts);

    echo '<div class="updated"><p><strong>' . __('Settings saved.', 'amc') . '</strong></p></div>';
}

?>

<h1><?php _e('AMC Site Options', 'amc') ?></h1>

<hr />

<form action="" method="post" enctype="multipart/form-data">

    <h2 style='margin-top: 50px;'><?php _e('Footer social Settings', 'amc') ?></h2>

    <table border='0'>
        <tr>
            <td style='width: 100px; text-align: right; padding-right: 5px;'>&nbsp;</td>
            <td style='width: 505px;'>
                <input type="checkbox" name='amc_options[social_show]' id="amc_options_social_show" value='1' <?php echo $opts['social_show'] == '1' ? 'checked' : '' ?> /> <label for="amc_options_social_show">Show social buttons in footer</label>
            </td>
        </tr>
    </table>

    <input type="submit" value="Update" style="margin: 10px 0 0 110px;" />



    <h2 style='margin-top: 50px;'><?php _e('AMC Social Pages', 'amc') ?></h2>

    <table border='0'>
        <tr>
            <td style='width: 100px; text-align: right; padding-right: 5px;'>
                Facebook:
            </td>
            <td style='width: 400px;'>
                <input name='amc_options[facebook_link]' value='<?php echo esc_attr($opts['facebook_link']) ?>' style='width: 100%;' />
            </td>
        </tr>
        <tr>
            <td style='width: 100px; text-align: right; padding-right: 5px;'>
                Twitter:
            </td>
            <td style='width: 400px;'>
                <input name='amc_options[twitter_link]' value='<?php echo esc_attr($opts['twitter_link']) ?>' style='width: 100%;' />
            </td>
        </tr>
        <tr>
            <td style='width: 100px; text-align: right; padding-right: 5px;'>
                LinkedIn:
            </td>
            <td style='width: 400px;'>
                <input name='amc_options[linkedin_link]' value='<?php echo esc_attr($opts['linkedin_link']) ?>' style='width: 100%;' />
            </td>
        </tr>
    </table>

    <input type="submit" value="Update" style="margin: 10px 0 0 110px;" />

</form>