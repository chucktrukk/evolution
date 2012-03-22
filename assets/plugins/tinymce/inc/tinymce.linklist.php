<?php

function pPrint($arr, $return = false){
    $output = '<pre>'.print_r($arr, TRUE).'</pre>';
    if ($return)
        return $output;
    else
        echo $output;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

//define('MODX_BASE_PATH', dirname(__FILE__).'/' );
define('MODX_BASE_PATH', '../../../../');

require_once MODX_BASE_PATH.'manager/includes/config.inc.php';
require_once MODX_BASE_PATH.'manager/includes/document.parser.class.inc.php';
require_once MODX_BASE_PATH.'MODxAPI.class.php';
$modx = new MODxAPI();
$modx->connect();

		session_name($site_sessionname);
		session_start();
		$cookieExpiration= 0;
        if (isset ($_SESSION['mgrValidated']) || isset ($_SESSION['webValidated'])) {
            $contextKey= isset ($_SESSION['mgrValidated']) ? 'mgr' : 'web';
            if (isset ($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime']) && is_numeric($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime'])) {
                $cookieLifetime= intval($_SESSION['modx.' . $contextKey . '.session.cookie.lifetime']);
            }
            if ($cookieLifetime) {
                $cookieExpiration= time() + $cookieLifetime;
            }
			if (!isset($_SESSION['modx.session.created.time'])) {
			  $_SESSION['modx.session.created.time'] = time();
			}
        }
		setcookie(session_name(), session_id(), $cookieExpiration, MODX_BASE_URL);

if(!defined('APP_PATH')) {
    DEFINE('APP_PATH', MODX_BASE_PATH.'/_/');
}

if(!defined('VENDOR_PATH')) {
    DEFINE('VENDOR_PATH', APP_PATH.'/vendors/');
}

require_once APP_PATH.'global/App.php';
App::getInstance();

$modx->getSettings();

// Override system settings with user settings
define('IN_MANAGER_MODE', 'true'); // set this so that user_settings will trust us.
include MODX_BASE_PATH . 'manager/includes/settings.inc.php';
include MODX_BASE_PATH . 'manager/includes/user_settings.inc.php';

if($settings['use_browser'] != 1){
	die("<b>PERMISSION DENIED</b><br /><br />You do not have permission to access this file!");
}

if(!isset($_SESSION['mgrValidated'])){
	if($_SESSION['webValidated'] && $settings['rb_webuser'] != 1 ){
		die("<b>PERMISSION DENIED</b><br /><br />You do not have permission to access this file!");
	}
}

include_once dirname(__FILE__).'/class.modxtree.php';

function getUserGroups() {
  global $modx;
  
  $groups = $modx->db->makeArray($modx->db->select('id, user_group, member', 'modx_member_groups', "member='{$_SESSION['mgrInternalKey']}'"));
  
  $group_ids = array();
  foreach($groups as $group) {
    $group_ids[] = $group['user_group'];
  }

  return $group_ids;

}

function getSiteParents() {	
    
    global $modx;
    
    $tblsc  = $modx->getFullTableName('site_content');
    $tbldg  = $modx->getFullTableName('document_groups');
    $tbldgn = $modx->getFullTableName('documentgroup_names');

    $doc_groups = getUserGroups();
    if ( !is_array($doc_groups) OR empty($doc_groups) ) {
      $docgrp = 0;
    } else {
      $docgrp = implode(",", $doc_groups);
    }

    $sql = "SELECT DISTINCT 
                sc.id 
            FROM 
                $tblsc sc
            LEFT JOIN 
                $tbldg dg on dg.document = sc.id 
            WHERE 
                sc.parent = 18 AND dg.document_group IN ({$docgrp}) 
            ORDER BY 
                sc.menuindex ASC";
    
    $results = $modx->db->makeArray($modx->db->query($sql));
    
    $ids = array();
    foreach($results as $r) {
      $ids[] = $r['id'];
    }
    
    return $ids;
}


$group_ids = getUserGroups();
$parents = getSiteParents();

if( count($parents) == 0) {
  $parents = 0;
} else {
  $parents = implode(',', $parents);
}

$modx->event->params['parents'] = $parents;
$modx->event->params['include_parents'] = 1;
$modx->event->params['depth']   = 100;
$modx->event->params['include_tvs'] = 0;
$menu = new TwigMenu();

$docs = $menu->documents;

$output = array();

foreach($docs as $doc) {
  print_row($doc);
}

function print_row($item, $level = 1) {
  global $output;
  
  $output[] = sprintf("['%s (%s)', '[~%s~]']", $item['alias'], $item['id'], $item['id']);
  
  if( isset($item['_children']) AND is_array($item['_children']) AND !empty($item['_children']) ) {
    $level = $level + 1;
    foreach($item['_children'] as $child) {
      print_row($child, $level);    
    }
  }
}

foreach($output as &$url) {
  $url = str_replace('websites/', '', $url);
}

$output_string = implode(",\n", $output);

echo "var tinyMCELinkList = new Array( \n\n";
echo $output_string;
echo "\n\n );";

