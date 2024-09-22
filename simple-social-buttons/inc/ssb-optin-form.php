<?php
/**
 * Structure for Optin Form.
 * @since 5.3.2
 */
?>
<style media="screen">
    #wpwrap {
        background-color: #fdfdfd;
    }
    .admin_page_ssb-optin #wpwrap {
        background-color: #F6F9FF;
    }
    .admin_page_ssb-optin #wpbody-content{
        display: flex;
        flex-direction: column;
    }
    .ssb-alert-notice{
        order: -1;
    }
    .admin_page_ssb-optin #wpbody-content .ssb-header-wrapper{
        order: -2;
        margin: 0 0px 20px 0 !important;
        width: 100% !important;
    }
    #wpcontent {
        padding: 0!important
    }
    #ssb-logo-wrapper {
        padding: 10px 0;
        width: 80%;
        margin: 0 auto;
        border-bottom: solid 1px #d5d5d5
    }
    #ssb-logo-wrapper-inner {
        max-width: 600px;
        width: 100%;
        margin: auto
    }
    #ssb-splash {
        width: calc(46% - 40px);
        margin: auto;
        /* background-color: #fdfdfd; */
        text-align: center
    }
    .admin_page_ssb-optin #wpbody-content form #ssb-splash{
        max-width: 680px;
    }
    #ssb-splash {
        margin-top: 40px;
    }
    #ssb-splash h1 {

        margin-bottom: 15px;
        font-size: 26px;
        line-height: 32px;
        color: black;
        font-family: "Poppins", sans-serif;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #ssb-splash-main {
        padding-bottom: 0
    }
    #ssb-splash-permissions-toggle {
        font-size: 16px;
        font-weight: 600;
        position: relative;
        color: #3C50E0;
        text-decoration: none;
        padding-right: 13px;
        outline: none !important;
        box-shadow: none;
        text-align: left;
        display: inline-block;
    }
    #ssb-splash-permissions-toggle:after {
        content: "";
        width: 8px;
        height: 8px;
        border-width: 0 2px 2px 0;
        border-style: solid;
        border-color: inherit;
        right: 27px;
        top: 50%;
        transform: rotate(45deg) translateY(-4px);
        display: inline-block;
        margin-left: 6px;
    }
    #ssb-splash-permissions #ssb-splash-permissions-dropdown{
        margin-top: 25px;
    }
    #ssb-splash-permissions-dropdown h3 {
        font-size: 16px;
        margin-bottom: 5px;
        color: #516885;
        font-weight: 700;
        line-height: 24px;
        margin: 0 0 5px;
    }
    #ssb-splash-permissions-dropdown p {
        margin-top: 0;
        font-size: 14px;
        margin-bottom: 25px;
        color: #516885;
    }
    #ssb-splash-permissions-dropdown h3:last-child,
    #ssb-splash-permissions-dropdown p:last-child{
        margin-bottom: 0;
    }
    #ssb-splash-main-text {
        font-size: 16px;
        padding: 0;
        margin: 0;
        color: black;
    }
    #ssb-splash-footer {
        width: 80%;
        padding: 15px 0;
        border: 1px solid #d5d5d5;
        font-size: 10px;
        text-align: center;
        margin-top: 238px;
        margin-left: auto;
        margin-right: auto;
    }
    #ssb-ga-optout-btn {
        background: none!important;
        border: none;
        padding: 0!important;
        font: inherit;
        cursor: pointer;
        margin-bottom: 20px;
        font-size: 14px;
        text-decoration: underline;
        text-decoration-style: Dashed;
        text-underline-position: under;
        color: rgb(92 118 151 / 80%);
    }
    #ssb-ga-optout-btn:hover{
        text-decoration: none;
    }
    #ssb-splash-permissions-toggle:hover{
        text-decoration: none;
    }
    .about-wrap .nav-tab + .nav-tab{
        border-left: 0;
    }
    .about-wrap .nav-tab:focus{
        box-shadow: none;
    }
    #ssb-ga-submit-btn {
        border: 0;
        padding: 15px 20px 15px 20px;
        background-color: #1441d8;
        text-decoration: none;
        color: #fff;
        font-size: 17px;
        line-height: 24px;
        font-weight: 500;
        font-family: "Poppins", sans-serif;
        border-radius: 5px;
        transition: all 0.3s;
        display: inline-block;
        max-width: 100%;
        cursor: pointer;
        /* z-index: 6; */
        display: inline-block;
        -webkit-appearance: none;
        margin-bottom: 20px;
        min-height: auto;
        white-space: normal;
    }
    #ssb-ga-submit-btn:before {
        content: "";
        width: 216px;
        display: block;
        max-width: 100%;
    }
    #ssb-ga-submit-btn:hover{
        background-color: #5272e1;
    }
    #ssb-ga-submit-btn:after{
        content: '\279C';
    }
    .ssb-splash-box {
        width: 100%;
        max-width: 600px;
        background-color: #fff;
        border: solid 1px #d5d5d5;
        margin: auto;
        margin-bottom: 20px;
        text-align: center;
        padding: 15px
    }
    .about-wrap .nav-tab{
        height: auto;
        float: none;
        display: inline-block;
        margin-right: 0;
        margin-left: 0;
        font-size: 18px;
        width: 33.333%;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        padding: 8px 15px;
    }
    .step-wrapper .ssb-splash-box{
        padding: 0;
        border: 0;
        margin-top: 20px;
        margin-bottom: 0;
        text-align: left;
    }
    .nav-tab-wrapper{
        margin:0;
        font-size: 0;
    }
    .nav-tab-wrapper, .wrap h2.nav-tab-wrapper{
        margin:0;
        font-size: 0;
    }
    .ssb-tab-content{
        display: none;
        border:1px solid #d5d5d5;
        padding:1px 20px 20px;
        border-top: 0;
    }
    .ssb-tab-content.active{
        display: block;
    }
    .ssb-seprator{
        border:0;
        border-top: 1px solid #ccc;
        margin: 50px 0;
    }
    #wpbody{
        padding-right: 0;
    }
    #ssb-splash{
        max-width: calc(100% - 64px);
        /* background: #f1f1f1; */
    }
    .ssb-splash-box{
        max-width: 100%;
        box-sizing: border-box;
        overflow: hidden;
    }
    .about-wrap {
        position: relative;
        margin: 25px 35px 0 35px;
        max-width: 80%;
        font-size: 15px;
        width: calc(100% - 64px);
        margin: 0 auto;
    }
    .ssb-left-screenshot{
        float: left;
    }
    .about-wrap p{
        font-size: 14px;
    }
    .ssb-text-settings h5{
        margin: 25px 0 5px;
    }
    .about-wrap .about-description, .about-wrap .about-text{
        font-size: 16px;
    }
    .about-wrap .feature-section h4,.about-wrap .changelog h3{
        font-size: 1em;
    }
    h5{
        font-size: 1em;
    }
    .about-wrap .feature-section img.ssb-left-screenshot{
        margin-left: 0 !important;
        margin-right: 30px !important;
    }
    .about-wrap img{
        width: 50%;
    }
    .ssb-text-settings{
        overflow: hidden;
    }
    #ssb-splash-footer{
        margin-top: 50px;
    }
    .step-wrapper{
        width: 100%;
        transition: all 0.3s ease-in-out;
        -webkit-transition: all 0.3s ease-in-out;
    }
    /*.step-wrapper.slide{
        -webkit-transform: translateX(-50%);
        transform: translateX(-50%);
    }*/
    .step-wrapper:after{
        content: '';
        display: table;
        clear: both;
    }
    .step{
        width: 100%;
        float: left;
        padding: 0 20px;
        box-sizing: border-box;
    }

    .admin_page_ssb-optin #wpbody-content form .step{
        padding-left: 0;
        padding-right: 0;
    }
    .ssb-welcome-screenshots{
        margin-left: 30px !important;
    }
    #ssb-splash-footer{
        font-size: 12px;
    }
    .about-wrap .changelog.ssb-backend-settings{
        margin-bottom: 20px;
    }
    .ssb-backend-settings .feature-section{
        padding-bottom: 20px;
    }
    a.ssb-ga-button.button.button-primary{
        height: auto !important;
    }
    .changelog:last-child{
        margin-bottom: 0;
    }
    .changelog:last-child .feature-section{
        padding-bottom: 0;
    }
    #ssb-logo-text{
        position: relative;
        bottom: 0px;
        max-width: 90px;
        vertical-align: middle;
    }

    .about-wrap .ssb-badge {
        position: absolute;
        top: 0;
        right: 0;
    }
    .ssb-welcome-screenshots {
        float: right;
        margin-left: 10px !important;
        border:1px solid #ccc;
        padding:0;
        box-shadow:4px 4px 0px rgba(0,0,0,.05)
    }
    .about-wrap .feature-section {
        margin-top: 20px;
    }
    .about-wrap .feature-section p{
        max-width: none !important;
    }
    .ssb-welcome-settings{
        clear: both;
        padding-top: 20px;
    }
    .ssb-left-screenshot {
        float: left !important;
    }

    #ssb-splash-main{
        background-color: #fff;
        border: 2px solid #999797;
        -webkit-border-radius: 8px;
        -moz-border-radius: 8px;
        -ms-border-radius: 8px;
        -o-border-radius: 8px;
        border-radius: 8px;
        min-height: 320px;
        margin: 0 auto;
        padding: 30px 30px 30px 30px;
        display: flex;
        align-items: center;
        text-align: left;
    }

    @media only screen and (max-width: 767px) {
        #ssb-splash-main{
            padding: 20px;
        }
        #ssb-ga-submit-btn {
            padding: 15px 20px 15px 20px;
            font-size: 15px;
            line-height: 21px;
        }
        #ssb-ga-submit-btn:before {
            width: 180px;
        }
        #ssb-splash h1 {
            flex-direction: column;
        }
        #ssb-logo-text {
            margin-right: 0;
            bottom: 15px;
        }
    }

    @media only screen and (max-width: 580px) {
        #ssb-splash h1 {
            font-size: 18px;
        }
        #ssb-logo-text {
            max-width: 70px;
        }
    }

    }
</style>
<?php

$user 		                    = wp_get_current_user();
$name 		                    = empty( $user->user_firstname ) ? $user->display_name : $user->user_firstname;
$email 		                    = $user->user_email;
$site_link 	                  = '<a href="' . get_site_url() . '">'. get_site_url() . '</a>';
$website 	                    = get_site_url();
$nonce                        = wp_create_nonce( 'ssb_submit_optin_nonce' );
$default_ssb_redirect = 'simple-social-buttons';
$plugin_title = 'Simple Social Buttons';
$logo_path = 'Simple Social Buttons';

/**
 * XSS Attack fix in the opt-in form.
 *
 * @since 1.5.12
 * @version 3.0.0
 */

echo '<form method="post" action="' . admin_url( 'admin.php?page=' . $default_ssb_redirect ) . '">';
echo "<input type='hidden' name='email' value='$email'>";
echo "<input type='hidden' name='ssb_submit_optin_nonce' value='" . sanitize_text_field( $nonce ) . "'>";
echo '<div id="ssb-splash">';
echo '<h1><img id="ssb-logo-text" src="' . plugins_url( 'assets/images/ssb_icon.png', dirname( __FILE__ ) )  . '"></h1>';
echo '<h1>' . esc_html__( $plugin_title, 'ssb' ) . '</h1>';
echo '<div id="ssb-splash-main" class="ssb-splash-box">';
echo '<div class="step-wrapper">';

echo "<div class='first-step step'>";
echo sprintf ( __( '%1$s Hey %2$s,  %4$s If you opt-in some data about your installation of ssb will be sent to WPBrigade.com (This doesn\'t include stats)%4$s and You will receive new feature updates, security notifications etc %5$sNo Spam, I promise.%6$s %4$s%4$s Help us %7$s Improve Simple Social Buttons%8$s %4$s %4$s ', 'ssb' ), '<p id="ssb-splash-main-text">', '<strong>' . $name . '</strong>', '<strong>' . $website . '</strong>', '<br>', '<i>', '</i>', '<strong>', '</strong>' ) . '</p>';
echo "<button type='submit' id='ssb-ga-submit-btn' class='ssb-ga-button button button-primary' name='ssb-submit-optin' >" . __( 'Allow and Continue  ', 'ssb') . "</button><br>";
echo "<button type='submit' id='ssb-ga-optout-btn' name='ssb-submit-optout' >" . __( 'Skip This Step', 'ssb') . "</button>";
echo '<div id="ssb-splash-permissions" class="ssb-splash-box">';
echo '<div id="ssb-splash-permissions-dropdown" style="display: none;">';
echo '<h3>' . __( 'Your Website Overview', 'ssb' ) . '</h3>';
echo '<p>' . __( 'Your Site URL, WordPress & PHP version, plugins & themes. This data lets us make sure this plugin always stays compatible with the most popular plugins and themes.', 'ssb' ) . '</p>';

echo '<h3>' . __( 'Your Profile Overview', 'ssb' ) . '</h3>';
echo '<p>' . __( 'Your name and email address.', 'ssb' ) . '</p>';

echo '<h3>' . __( 'Admin Notices', 'ssb' ) . '</h3>';
echo '<p>' . __( "Updates, Announcement, Marketing. No Spam, I promise.", 'ssb' ) . '</p>';

echo '<h3>' . __( 'Plugin Actions', 'ssb' ) . '</h3>';
echo '<p>' . __( "Active, Deactive, Uninstallation and How you use this plugin's features and settings. This is limited to usage data. It does not include any of your sensitive ssb data, such as traffic. This data helps us learn which features are most popular, so we can improve the plugin further.", 'ssb' ) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';


echo '</div>';
echo '</div>';
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
    jQuery(document).ready(function(s) {
        var o = parseInt(s("#ssb-splash-footer").css("margin-top"));
        s("#ssb-splash-permissions-toggle").click(function(a) {
            a.preventDefault(), s("#ssb-splash-permissions-dropdown").toggle(), 1 == s("#ssb-splash-permissions-dropdown:visible").length ? s("#ssb-splash-footer").css("margin-top", o - 208 + "px") : s("#ssb-splash-footer").css("margin-top", o + "px")
        })
    });
</script>
