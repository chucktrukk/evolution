<?php 
if( !DEFINED('MODX_BASE_PATH')) {
  echo 'No direct access.';
}


if( !isset($_SESSION['mgrRole']) OR $_SESSION['mgrRole'] != 4) {
  // Logged in as SuperAdmin. Need to assume role of user to see what they see.
  $baseurl = '_/sites/Wrong Manager Role/public';
} else {
  // We are logged in as a site owner
  $mgr_group = isset($_SESSION['mgrDocgroups']) ? array_pop($_SESSION['mgrDocgroups']) : false;
  if( $mgr_group === false) {
    $baseurl = '_/sites/Manager Group Empty/public';
  } else {

    include_once '../../../../../includes/document.parser.class.inc.php';
    $modx = new DocumentParser;

    include_once '../../../../../includes/extenders/dbapi.mysql.class.inc.php';
    $db = new DBAPI();
    
    //Get membergroups
    $users_groups = $modx->db->makeArray($modx->db->select('id, user_group, member', 'modx_member_groups', "member='{$_SESSION['mgrInternalKey']}'"));
    $group_id = isset($users_groups[0]['user_group']) ? $users_groups[0]['user_group'] : FALSE;
    if( $group_id == FALSE) {
      $baseurl = '_/sites/No User Groups/public';
    } else {
      $group_name = $modx->db->getValue($modx->db->select('name', 'modx_membergroup_names', "id='{$group_id}'"));

      if($group_name == '' OR $group_name === FALSE OR $group_name === NULL) {
      } else {
        $baseurl = "_/sites/{$group_name}/public";
      }
    }
  }
}