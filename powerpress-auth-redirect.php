<?php

/*
Plugin Name: PowerPress Auth Redirect
Description: Sends users to a new feed telling them to fix their podcasts if their login does not work. This plugin depends on the <a href="http://wordpress.org/plugins/powerpress/" title="Blubrry PowerPress">Blubrry PowerPress</a> plugin.
Version: 0.2.2
Author: Joe Terranova
Author URI: https://joeterranova.net
*/

define ( 'POWERPRESS_AUTH_REDIRECT_URL' , 'https://www.yourdomain.com/feed/error');

$GLOBALS['POWERPRESS_AUTH_REDIRECT_USERAGENTS'] = Array(
	//user agents to always redirect even if they're submitted without a user/pass
	//Array('Podcasts', 'CFNetwork', 'Darwin'),
	//Array('Podkaster', 'CFNetwork', 'Darwin'),
);

$GLOBALS['POWERPRESS_AUTH_REDIRECT_IPS'] = Array(
	//IPs to always redirect, even if they're submitted without a user/pass
	//'1.2.3.4',
);

function powerpress_auth_redirect_check($authenticated, $type, $feed_slug){
	if(!empty($authenticated)) return $authenticated; //pass back to powerpress

	$FeedSettings = get_option('powerpress_feed_'.$feed_slug);

	/*
	 * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
	 * For this workaround to work, add this line to your .htaccess file:
	 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
	*/

	// Workaround for HTTP Authentication with PHP running as CGI
	if ( !isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
		$ha = base64_decode( substr($_SERVER['HTTP_AUTHORIZATION'],6) ); // Chop off 'basic ' from the beginning of the value
		if( strstr($ha, ':') ) { // Colon found, lets split it for user:password
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $ha);
		}
		unset($ha);
	}

	if( !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ) {
		powerpress_auth_useragent_ip_check(); //check if we want to redirect always for this useragent
		return false; //Let powerpress handle requesting auth
	}
	
	if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])){
		powerpress_auth_redirect();
		exit;
	}

	$user = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	if (!is_null($user) && strlen($user) > 1) {
		if (strpos($user, '@', 1) !== false) {
			$userObjID = get_user_by('email', $user); // Get the user by email
			if (!is_wp_error($userObjID))
				$user = $userObjID->user_login; // Use the user's login (not email) to authenticate
		}
	}

	$userObj = wp_authenticate($user, $password);

	if( !is_wp_error($userObj) )
	{
		// Check capability...
		if( $userObj->has_cap( $FeedSettings['premium'] ) )
			return true; // Nice, let us continue...
		powerpress_auth_redirect();
		exit;
	}

	// If we made it this far, then there was a wp_authenticate error...
	powerpress_auth_redirect();
	exit;

}

function powerpress_auth_redirect(){
	header("Location: ".POWERPRESS_AUTH_REDIRECT_URL);
	exit;
}

function powerpress_auth_useragent_ip_check(){
	//loop through POWERPRESS_AUTH_REDIRECT_USERAGENTS' list of useragent matches
	//if any of the useragents match all the strings, we want to redirect even though no user/pass was given
	if(!empty($_SERVER['HTTP_USER_AGENT']) && !empty($GLOBALS['POWERPRESS_AUTH_REDIRECT_USERAGENTS'])){
		foreach($GLOBALS['POWERPRESS_AUTH_REDIRECT_USERAGENTS'] as $agent_checks){
			$agent_location = false;
			foreach($agent_checks as $agent_check){
				$agent_location = stripos($_SERVER['HTTP_USER_AGENT'], $agent_check, $agent_location);
				if($agent_location === FALSE) break;
			}
			if($agent_location !== FALSE){
				//we have a matching useragent we want to always redirect
				powerpress_auth_redirect();
				exit;
			}
		}
	}
	if(!empty($GLOBALS['POWERPRESS_AUTH_REDIRECT_IPS'])){
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; //Cloudflare or another proxy
		else $ip = $_SERVER['REMOTE_ADDR'];
		if(in_array($ip, $GLOBALS['POWERPRESS_AUTH_REDIRECT_IPS'])){
			//This is one of the problem IPs, just redirect them
			powerpress_auth_redirect();
			exit;
		}
	}
}

add_filter('powerpress_feed_auth', 'powerpress_auth_redirect_check', 10, 3);
//add_action('plugins_loaded', create_function('',' new powerpressAuthRedirect;'));



