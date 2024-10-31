<?php
/***
 * Admin page
 *
 * Package: pdpa-consent
 * (c) Apinan Woratrakun <iamapinan@gmail.com>
 */

if (! defined('ABSPATH')) {
    exit;
}

class PDPA_Admin
{
    private $plugin_info = array();
    private $capability = 'manage_options';
    private $options;
    private $locale;
    private $page_id;
    private $page_name;

    public function __construct()
    {
        $this->plugin_info = get_plugin_data(PDPA_PATH . 'pdpa-consent.php');
        $this->locale = get_locale();
        $this->page_id = get_option('pdpa-consent-page-id') ? get_option('pdpa-consent-page-id') : 0;
        $this->page_name = __('pdpa-term', 'pdpa-consent');
        add_action('admin_menu', array($this, 'pdpa_admin_menu'));
        add_action('admin_init', array($this, 'admin_option_setup'));
        add_action('admin_enqueue_scripts', array( $this, 'pdpa_enqueue_color_picker' ));
        add_action('admin_enqueue_scripts', array( $this, 'pdpa_enqueue_style' ));
    }

    private function serialize_html($html, $settings = [ 'website_name' => '', 'site_description' => '', 'list_data' => '', 'site_address' => '', 'site_contact' => '', 'site_email' => '' ])
    {
        $settings['list_data'] = str_replace("\n", "</li><li>", esc_html($settings['list_data']));
        $html = str_replace('[service]', esc_html($settings['website_name']), $html);
        $html = str_replace('[description]', esc_attr($settings['site_description']), $html);
        $html = str_replace('[list_data]', $settings['list_data'], $html);
        $html = str_replace('[address]', esc_html($settings['site_address']), $html);
        $html = str_replace('[contact]', esc_html($settings['site_contact']), $html);
        $html = str_replace('[email]', esc_html($settings['site_email']), $html);
        return $html;
    }

    public function generate_post_from_template()
    {
        $this->options = get_option('pdpa_option');

        if (!isset($this->options['regenerate'])) {
            return false;
        }

        if (file_exists(PDPA_PATH . 'templates/' . $this->locale . '.html')) {
            $content = $this->serialize_html(file_get_contents(PDPA_PATH . 'templates/'. $this->locale .'.html'), $this->options);
        } else {
            $content = $this->serialize_html(file_get_contents(PDPA_PATH . 'templates/th_TH.html'), $this->options);
        }

        $page_details = array(
            'ID'            => $this->page_id,
            'post_title'    => __('Thailand’s Personal Data Protection Act (PDPA)', 'pdpa-consent'),
            'post_name'     => $this->page_name,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type' => 'page'
        );

        $page_id = wp_insert_post($page_details);
        $this->options['regenerate'] = 0;
        update_option('pdpa_option', $this->options);
        add_option('pdpa-consent-page-id', $page_id);
        add_action('admin_notices', array( $this, 'page_generate_notice' ));
    }

    public function page_generate_notice()
    {
        echo '<div class="notice notice-warning is-dismissible">
            <p>'.__('PDPA Consent term page is regenerated.', 'pdpa-consent').'</p>
        </div>';
    }

    public function pdpa_admin_menu()
    {
        add_menu_page($this->plugin_info['Name'], __('PDPA Consent', 'pdpa-consent'), $this->capability, $this->plugin_info['TextDomain'], array($this, 'pdpa_admin_option'), 'dashicons-shield-alt', 81);
    }

    public function pdpa_enqueue_color_picker($hook_suffix)
    {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('pdpa-script', plugins_url('pdpa-consent/assets/pdpa-admin-script.js'), array( 'wp-color-picker' ), $this->plugin_info['Version'], true );
    }

    public function pdpa_enqueue_style()
    {
        wp_enqueue_style('pdpa-style', plugins_url('pdpa-consent/assets/pdpa-admin-style.css'), array(), $this->plugin_info['Version'], 'all' );
    }

    public function pdpa_admin_option()
    {
        if (isset($_POST)) {
            $this->generate_post_from_template();
        } ?>
        <?php settings_errors(); ?>
        <div class="wrap">
            <h2><?php _e('PDPA Consent', 'pdpa-consent'); ?></h2>
            <form method="post" action="options.php" id="pdpaConsent">
            <?php
                settings_fields('_pdpa_setting_group');
                do_settings_sections('settings'); 
            ?>
            <table class="form-table">
                <tr>
                    <th scope='row'></th>
                    <td><?php submit_button(); ?></td>
                </tr>
            </table>
            </form>
            <p align="center">Version: <?php echo $this->plugin_info['Version']; ?>, Development by <?php echo $this->plugin_info['Author']; ?></p>
        </div>
        <?php
    }

    public function admin_option_setup()
    {
        register_setting(
            '_pdpa_setting_group', // option_group
            'pdpa_option' // option_name
        );

        add_settings_section(
            '_pdpa_setting_section', // id
            __('PDPA Consent setup', 'pdpa-consent'), // title
            array( $this, '_section_fields' ), // callback
            'settings' // page
        );
    }

    public function _section_fields()
    {
        $this->options = get_option('pdpa_option');

        if ($this->page_id !== 0) {
            add_settings_field(
                '_url_', // id
                __('Privacy page', 'pdpa-consent'), // title
                array( $this, 'url_callback' ), // callback
                'settings', // page
                '_pdpa_setting_section' // section
            );
        }

        add_settings_field(
            'regenerate', // id
            __('Regenerate the privacy page.', 'pdpa-consent'), // title
            array( $this, 'regenerate_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );

        add_settings_field(
            'is_enable', // id
            __('Enable consent noti', 'pdpa-consent'), // title
            array( $this, 'is_enable_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );

        

        /***
         * Future functions
         *
        */
        // add_settings_field(
        //     'allow_user_reset', // id
        //     __( 'Allow user to reset consent','pdpa-consent' ), // title
        //     array( $this, 'allow_user_reset_callback' ), // callback
        //     'settings', // page
        //     '_pdpa_setting_section' // section
        // );
        // add_settings_field(
        //     'allow_user_delete', // id
        //     __( 'Allow user to delete account','pdpa-consent' ), // title
        //     array( $this, 'allow_user_delete_callback' ), // callback
        //     'settings', // page
        //     '_pdpa_setting_section' // section
        // );
        // add_settings_field(
        //     'allow_user_download', // id
        //     __( 'Allow user to download profile','pdpa-consent' ), // title
        //     array( $this, 'allow_user_download_callback' ), // callback
        //     'settings', // page
        //     '_pdpa_setting_section' // section
        // );
        add_settings_field(
            'is_darkmode', // id
            __('Use dark theme', 'pdpa-consent'), // title
            array( $this, 'is_darkmode_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'allow_button_color', // id
            __('Allow button color', 'pdpa-consent'), // title
            array( $this, 'allow_button_color_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'not_allow_button_color', // id
            __('Not allow button color', 'pdpa-consent'), // title
            array( $this, 'not_allow_button_color_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'popup_type', // id
            __('Notification popup type', 'pdpa-consent'), // title
            array( $this, 'popup_type_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'popup_message', // id
            __('Popup message', 'pdpa-consent'), // title
            array( $this, 'popup_message_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'website_name', // id
            __('Website name (*)', 'pdpa-consent'), // title
            array( $this, 'website_name_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'site_description', // id
            __('Web description', 'pdpa-consent'), // title
            array( $this, 'description_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'list_data', // id
            __('List of user data (*)<br>(one data set per line)', 'pdpa-consent'), // title
            array( $this, 'list_data_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'site_address', // id
            __('Address (*)', 'pdpa-consent'), // title
            array( $this, 'address_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'site_contact', // id
            __('Contact', 'pdpa-consent'), // title
            array( $this, 'contact_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'site_email', // id
            __('Email (*)', 'pdpa-consent'), // title
            array( $this, 'email_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
        add_settings_field(
            'custom_css', // id
            __('CSS Class', 'pdpa-consent'), // title
            array( $this, 'custom_css_callback' ), // callback
            'settings', // page
            '_pdpa_setting_section' // section
        );
    }
    public function url_callback()
    {
        printf(
            '<a href="/?p=%s"><span class="dashicons dashicons-external"></span></a>&nbsp;<a href="%s"><span class="dashicons dashicons-edit"></span></a>',
            $this->page_id,
            esc_url(get_admin_url().'post.php?post='.get_option('pdpa-consent-page-id').'&action=edit')
        );
    }
    public function is_enable_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[is_enable]" id="is_enable" value="1" %s>
                <span class="slider round"></span>
            </label>',
            isset($this->options['is_enable']) ? 'checked' : ''
        );
    }
    public function regenerate_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[regenerate]" id="regenerate" value="1">
                <span class="slider round"></span>
            </label>'
        );
    }
    public function allow_user_reset_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[allow_user_reset]" id="allow_user_reset" value="1" %s>
                <span class="slider round"></span>
            </label>',
            isset($this->options['allow_user_reset']) ? 'checked' : ''
        );
    }
    public function allow_user_delete_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[allow_user_delete]" id="allow_user_delete" value="1" %s>
                <span class="slider round"></span>
            </label>',
            isset($this->options['allow_user_delete']) ? 'checked' : ''
        );
    }
    public function allow_user_download_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[allow_user_download]" id="allow_user_download" value="1" %s>
                <span class="slider round"></span>
            </label>',
            isset($this->options['allow_user_download']) ? 'checked' : ''
        );
    }
    public function is_darkmode_callback()
    {
        printf(
            '<label class="switch">
                <input type="checkbox" name="pdpa_option[is_darkmode]" id="is_darkmode" value="1" %s>
                <span class="slider round"></span>
            </label>',
            isset($this->options['is_darkmode']) ? 'checked' : ''
        );
    }
    public function allow_button_color_callback()
    {
        printf(
            '<input type="text" name="pdpa_option[allow_button_color]" id="allow_button_color" value="%s"  class="pdpa-color-picker">',
            $this->options['allow_button_color']
        );
    }
    public function not_allow_button_color_callback()
    {
        printf(
            '<input type="text" name="pdpa_option[not_allow_button_color]" id="not_allow_button_color" value="%s"  class="pdpa-color-picker">',
            $this->options['not_allow_button_color']
        );
    }
    public function popup_type_callback()
    {
        ?>
            <select name="pdpa_option[popup_type]">
                <option value="top" <?php echo $this->options['popup_type'] == 'top' ? 'selected' : ''; ?>><?php _e('Top bar', 'pdpa-consent'); ?></option>
                <option value="center" <?php echo $this->options['popup_type'] == 'center' ? 'selected' : ''; ?>><?php _e('Center popup', 'pdpa-consent'); ?></option>
                <option value="bottom" <?php echo $this->options['popup_type'] == 'bottom' ? 'selected' : ''; ?>><?php _e('Bottom bar', 'pdpa-consent'); ?></option>
            </select>
        <?php
    }
    
    public function popup_message_callback()
    {
        printf(
            '<textarea class="large-text" rows=4 name="pdpa_option[popup_message]" id="popup_message" required>%s</textarea>',
            isset($this->options['popup_message']) ? esc_html($this->options['popup_message']) : __('Your privacy is important to us. We need your data just for the important process of services. Please allow if you accept the term of privacy comply with PDPA.', 'pdpa-consent')
        );
    }

    public function website_name_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="pdpa_option[website_name]" id="website_name" value="%s" placeholder="%s" required>',
            isset($this->options['website_name']) ? esc_html($this->options['website_name']) : '',
            __('Your website name or Company name', 'pdpa-consent')
        );
    }

    public function description_callback()
    {
        printf(
            '<input class="large-text" type="text" name="pdpa_option[site_description]" id="site_description" value="%s">',
            isset($this->options['site_description']) ? esc_html($this->options['site_description']) : ''
        );
    }

    public function list_data_callback()
    {
        printf(
            '<textarea class="large-text" rows=5 name="pdpa_option[list_data]" id="list_data" placeholder="%s" required>%s</textarea>',
            __("Fullname\nBirthday\nEtc."),
            isset($this->options['list_data']) ? esc_html($this->options['list_data']) : ''
        );
    }

    public function address_callback()
    {
        printf(
            '<input class="large-text" type="text" name="pdpa_option[site_address]" id="site_address" value="%s" required>',
            isset($this->options['site_address']) ? esc_html($this->options['site_address']) : ''
        );
    }

    public function contact_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="pdpa_option[site_contact]" id="site_contact" value="%s" placeholder="%s">',
            isset($this->options['site_contact']) ? esc_html($this->options['site_contact']) : '',
            __("Such as John Doe (081-111-1111)", 'pdpa-consent')
        );
    }

    public function email_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="pdpa_option[site_email]" id="site_email" value="%s" required>',
            isset($this->options['site_email']) ? sanitize_email($this->options['site_email']) : ''
        );
    }

    public function custom_css_callback()
    {
        ?>
        <table class='pdpa-admin-table'>
            <tr>
                <td>.pdpa-consent-wrap {}</td>
                <td><?php _e('For wrapper div element', 'pdpa-consent'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-place-top {}</td>
                <td><?php _e('Make the box place to top bar', 'pdpa-consent'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-place-center {}</td>
                <td><?php _e('Make the box place center the screen', 'pdpa-consent'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-place-bottom {}</td>
                <td><?php _e('Make the box place to bottom bar'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-consent-text {}</td>
                <td><?php _e('Style for message in the box'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-consent-not-allow-button {}</td>
                <td><?php _e('Style for not allow button', 'pdpa-consent'); ?></td>
            </tr>
            <tr>
                <td>.pdpa-consent-allow-button {}</td>
                <td><?php _e('Style for allow button', 'pdpa-consent'); ?></td>
            </tr>
        </table>
        <?php
    }
}
