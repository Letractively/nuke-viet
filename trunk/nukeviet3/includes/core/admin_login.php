<?php

/**
 * @Project NUKEVIET 3.0
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @copyright 2009
 * @createdate 12/30/2009 1:31
 */

if ( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );

if ( ! nv_admin_checkip() )
{
    nv_info_die( $global_config['site_description'], $lang_global['site_info'], sprintf( $lang_global['admin_ipincorrect'], $client_info['ip'] ) . "<META HTTP-EQUIV=\"refresh\" content=\"5;URL=" . $global_config['site_url'] . "\" />" );
}

if ( ! nv_admin_checkfirewall() )
{
    header( 'WWW-Authenticate: Basic realm="' . $lang_global['firewallsystem'] . '"' );
    header( NV_HEADERSTATUS . ' Unauthorized' );
    nv_info_die( $global_config['site_description'], $lang_global['site_info'], $lang_global['firewallincorrect'] . "<META HTTP-EQUIV=\"refresh\" content=\"5;URL=" . $global_config['site_url'] . "\" />" );
}

$error = "";
$login = "";

$array_gfx_chk = array( 
    1, 5, 6, 7 
);
if ( in_array( $global_config['gfx_chk'], $array_gfx_chk ) )
{
    $global_config['gfx_chk'] = 1;
}
else
{
    $global_config['gfx_chk'] = 0;
}

if ( $nv_Request->isset_request( 'nv_login,nv_password', 'post' ) )
{
    $nv_username = filter_text_input( 'nv_login', 'post', '', '', 100 );
    $nv_password = filter_text_input( 'nv_password', 'post', '', '', 50 );
    if ( $global_config['gfx_chk'] == 1 )
    {
        $nv_seccode = filter_text_input( 'nv_seccode', 'post', '' );
    }
    if ( empty( $nv_username ) )
    {
        $error = $lang_global['nickname_empty'];
    }
    elseif ( empty( $nv_password ) )
    {
        $error = $lang_global['password_empty'];
    }
    elseif ( $global_config['gfx_chk'] == 1 and ! nv_capcha_txt( $nv_seccode ) )
    {
        $error = $lang_global['securitycodeincorrect'];
    }
    else
    {
        if ( defined( 'NV_IS_USER_FORUM' ) )
        {
            define( 'NV_IS_MOD_USER', true );
            require_once ( NV_ROOTDIR . '/' . DIR_FORUM . '/nukeviet/login.php' );
        }
        $userid = 0;
        $sql = "SELECT userid, username, password FROM `" . NV_USERS_GLOBALTABLE . "` WHERE md5username ='" . md5( $nv_username ) . "'";
        $result = $db->sql_query( $sql );
        if ( $db->sql_numrows( $result ) == 1 )
        {
            $row = $db->sql_fetchrow( $result );
            if ( $row['username'] == $nv_username and $crypt->validate( $nv_password, $row['password'] ) )
            {
                $userid = $row['userid'];
            }
        }
        
        $error = $lang_global['loginincorrect'];
        if ( $userid > 0 )
        {
            $query = "SELECT t1.admin_id as admin_id, t1.lev as admin_lev, t1.last_agent as admin_last_agent, t1.last_ip as admin_last_ip, t1.last_login as admin_last_login, t2.password as admin_pass FROM `" . NV_AUTHORS_GLOBALTABLE . "` AS t1 INNER JOIN  `" . NV_USERS_GLOBALTABLE . "` AS t2 ON t1.admin_id  = t2.userid WHERE t1.admin_id = " . $userid . " AND t1.lev!=0 AND t1.is_suspend=0 AND t2.active=1";
            $result = $db->sql_query( $query );
            $numrows = $db->sql_numrows( $result );
            if ( $numrows == 1 )
            {
                $row = $db->sql_fetchrow( $result );
                $db->sql_freeresult( $result );
                
                $current_login = NV_CURRENTTIME;
                $admin_id = intval( $row['admin_id'] );
                $admin_lev = intval( $row['admin_lev'] );
                $agent = substr( NV_USER_AGENT, 0, 254 );
                $checknum = nv_genpass( 10 );
                $checknum = $crypt->hash( $checknum );
                $array_admin = array( 
                    'admin_id' => $admin_id, 'checknum' => $checknum, 'current_agent' => $agent, 'last_agent' => $row['admin_last_agent'], 'current_ip' => $client_info['ip'], 'last_ip' => $row['admin_last_ip'], 'current_login' => $current_login, 'last_login' => intval( $row['admin_last_login'] ) 
                );
                $admin_serialize = serialize( $array_admin );
                $query = $db->constructQuery( "UPDATE `" . NV_AUTHORS_GLOBALTABLE . "` SET `check_num` = [s], `last_login` = [d], `last_ip` = [s], `last_agent` = [s] WHERE `admin_id`=[d]", $checknum, $current_login, $client_info['ip'], $agent, $admin_id );
                $db->sql_query( $query );
                $nv_Request->set_Session( 'admin', $admin_serialize );
                $nv_Request->set_Session( 'online', '1|' . NV_CURRENTTIME . '|' . NV_CURRENTTIME . '|0' );
                define( 'NV_IS_ADMIN', true );
                
                $redirect = $global_config['site_url'] . '/' . NV_ADMINDIR;
                if ( $nv_Request->isset_request( 'admin_login_redirect', 'session' ) )
                {
                    $r = $nv_Request->get_string( 'admin_login_redirect', 'session', '' );
                    $nv_Request->unset_request( 'admin_login_redirect', 'session' );
                    if ( ! empty( $r ) ) $redirect = $r;
                }
                
                $error = "";
                nv_info_die( $global_config['site_description'], $lang_global['site_info'], $lang_global['admin_loginsuccessfully'] . "<META HTTP-EQUIV=\"refresh\" content=\"3;URL=" . $redirect . "\" />" );
                die();
            }
        }
    }
}
else
{
    $nv_Request->set_Session( 'admin_login_redirect', $client_info['selfurl'] );
    $nv_username = "";
}

if ( file_exists( NV_ROOTDIR . "/language/" . NV_LANG_INTERFACE . "/admin_global.php" ) )
{
    require_once ( NV_ROOTDIR . "/language/" . NV_LANG_INTERFACE . "/admin_global.php" );
}
elseif ( file_exists( NV_ROOTDIR . "/language/en/admin_global.php" ) )
{
    require_once ( NV_ROOTDIR . "/language/en/admin_global.php" );
}

$info = ( ! empty( $error ) ) ? '<div class="error">' . $error . '</div>' : '<div class="normal">' . $lang_global['logininfo'] . '</div>';
$size = @getimagesize( NV_ROOTDIR . '/images/' . $global_config['site_logo'] );

$dir_template = "";
if ( file_exists( NV_ROOTDIR . "/themes/" . $global_config['admin_theme'] . "/system/login.tpl" ) )
{
    $dir_template = NV_ROOTDIR . "/themes/" . $global_config['admin_theme'] . "/system";
}
else
{
    $dir_template = NV_ROOTDIR . "/themes/admin_default/system";
    $global_config['admin_theme'] = "admin_default";
}

$xtpl = new XTemplate( "login.tpl", $dir_template );
$xtpl->assign( 'CHARSET', $global_config['site_charset'] );
$xtpl->assign( 'SITE_NAME', $global_config['site_name'] );
$xtpl->assign( 'PAGE_TITLE', $lang_global['admin_page'] );
$xtpl->assign( 'ADMIN_THEME', $global_config['admin_theme'] );
$xtpl->assign( 'SITELANG', NV_LANG_INTERFACE );
$xtpl->assign( 'NV_BASE_SITEURL', NV_BASE_SITEURL );
$xtpl->assign( 'NV_BASE_ADMINURL', NV_BASE_ADMINURL );
$xtpl->assign( 'CHECK_SC', ( $global_config['gfx_chk'] == 1 ) ? 1 : 0 );
$xtpl->assign( 'LOGIN_TITLE', $lang_global['adminlogin'] );
$xtpl->assign( 'LOGIN_INFO', $info );
$xtpl->assign( 'N_LOGIN', $lang_global['nickname'] );
$xtpl->assign( 'N_PASSWORD', $lang_global['password'] );
$xtpl->assign( 'SITEURL', $global_config['site_url'] );
$xtpl->assign( 'N_SUBMIT', $lang_global['loginsubmit'] );

$xtpl->assign( 'LOGIN_ERROR_SECURITY', addslashes( sprintf( $lang_global['login_error_security'], NV_GFX_NUM ) ) );

$xtpl->assign( 'V_LOGIN', $nv_username );
$xtpl->assign( 'LANGINTERFACE', $lang_global['langinterface'] );
$xtpl->assign( 'LOGO_SRC', NV_BASE_SITEURL . "images/" . $global_config['site_logo'] );
$xtpl->assign( 'LOGO_WIDTH', $size[0] );
$xtpl->assign( 'LOGO_HEIGHT', $size[1] );

$xtpl->assign( 'LANGLOSTPASS', $lang_global['lostpass'] );
$xtpl->assign( 'LINKLOSTPASS', NV_BASE_SITEURL . "index.php?" . NV_LANG_VARIABLE . "=" . $global_config['site_lang'] . "&amp;" . NV_NAME_VARIABLE . "=users&amp;" . NV_OP_VARIABLE . "=lostpass" );

if ( $global_config['gfx_chk'] == 1 )
{
    $xtpl->parse( 'main.jscaptcha' );
    $xtpl->assign( 'CAPTCHA_REFRESH', $lang_global['captcharefresh'] );
    $xtpl->assign( 'CAPTCHA_REFR_SRC', NV_BASE_SITEURL . "images/refresh.png" );
    $xtpl->assign( 'N_CAPTCHA', $lang_global['securitycode'] );
    $xtpl->assign( 'GFX_NUM', NV_GFX_NUM );
    $xtpl->assign( 'GFX_WIDTH', NV_GFX_WIDTH );
    $xtpl->assign( 'GFX_HEIGHT', NV_GFX_HEIGHT );
    $xtpl->parse( 'main.captcha' );
}
if ( $global_config['lang_multi'] == 1 )
{
    foreach ( $global_config['allow_adminlangs'] as $lang_i )
    {
        if ( file_exists( NV_ROOTDIR . "/language/" . $lang_i . "/global.php" ) and file_exists( NV_ROOTDIR . "/language/" . $lang_i . "/admin_global.php" ) )
        {
            $xtpl->assign( 'LANGOP', $lang_i );
            $xtpl->assign( 'LANGTITLE', $lang_global['langinterface'] );
            $xtpl->assign( 'SELECTED', ( $lang_i == NV_LANG_INTERFACE ) ? "selected='selected'" : "" );
            $xtpl->assign( 'LANGVALUE', $language_array[$lang_i]['name'] );
            $xtpl->parse( 'main.lang_multi.option' );
        }
    }
    $xtpl->parse( 'main.lang_multi' );
}
$xtpl->parse( 'main' );

include ( NV_ROOTDIR . "/includes/header.php" );
$xtpl->out( 'main' );
include ( NV_ROOTDIR . "/includes/footer.php" );
die();

?>