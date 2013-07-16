<?php
/**
 *
 * Pirati: Helper
 *
 * @author    Vaclav Malek <vaclav.malek@pirati.cz> 
 *
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_TAB')) define('DOKU_TAB', "\t");

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DOKU_TEMP')) define('DOKU_TEMP',DOKU_INC.'data/tmp/');

class helper_plugin_piratihelper extends DokuWiki_Plugin {

     /**
      *
      *   create cache dir for json output from graph api
      *
      */
     function _initCache(){
          if(!file_exists(DOKU_TEMP.'piratihelper')) mkdir(DOKU_TEMP.'piratihelper');
     }

     /**
      *
      *   @param[in] username optional parameter for username (return groups only from this user)
      *
      *   @return array of group objects
      *
      */
     function getGraphGroups($username=false){
          // tmp dir
          $this->_initCache();
          // cache file
          if($username){
               $cacheFilePath = DOKU_TEMP.'piratihelper/graphapi_'.urlencode($username).'_groups.tmpo'; 
          } else {
               $cacheFilePath = DOKU_TEMP.'piratihelper/graphapi_groups.tmpo';
          }

          $updateInterval = 43200;
          $data = new stdClass();
          $data->timestamp = 0;
          $data->groups = array();
          if(file_exists($cacheFilePath)){
               $handle = fopen($cacheFilePath,'r');
               $data = unserialize(fread($handle, filesize($cacheFilePath)));
               fclose($handle);
          }
          // re-cache?
          if((time()-$data->timestamp)>$updateInterval){
               // groups
               $groups = curl_init();
               if($username){
                    curl_setopt($groups,CURLOPT_URL,'https://graph.pirati.cz/user/'.$username.'/groups');
               } else {
                    curl_setopt($groups,CURLOPT_URL,'https://graph.pirati.cz/groups');
               }
               curl_setopt($groups,CURLOPT_HEADER,0);
               curl_setopt($groups,CURLOPT_RETURNTRANSFER,true);
               $groups_raw = curl_exec($groups);
               $groups_data = json_decode($groups_raw);
               if($username){
                    $data->groups = array();
                    foreach($groups_data as $i=>$grp){
                         $grps[$i] = curl_init();
                         curl_setopt($grps[$i],CURLOPT_URL,'https://graph.pirati.cz/'.$grp->id);
                         curl_setopt($grps[$i],CURLOPT_HEADER,0);
                         curl_setopt($grps[$i],CURLOPT_RETURNTRANSFER,true);
                         $data->groups[] = json_decode(curl_exec($grps[$i]));
                    }
               } else $data->groups = $groups_data;
               $data->timestamp = time();
               $handle = fopen($cacheFilePath,'w');
               fwrite($handle,serialize($data));
               fclose($handle);
               curl_close($groups);
          }

          return $data->groups;
     }

     /**
      *
      *   @param[in] gaid user unique id from graph api
      *   @param[in] group_gaids array of unique id of groups
      *   @param[in] allin user must be in all groups
      *
      *   @return boolean if gaid is in one of group_gaids
      */
     function isInGroup($gaid,$group_gaids = array(),$allin=false){
          if(empty($gaid)) return false;

          $user = $this->getGraphUser($gaid);
          $groups = $this->getGraphGroups($user->username);

          foreach($groups as $grp){
               if(in_array($grp->id,$group_gaids)) return true;
          }
          return false;
     }

     /**
      *   
      *   @param[in] groupusername optional parameter for groupname (return users only from this group)
      *
      *   @return array of user objects
      *
      */
     function getGraphUsers($groupusername=false){
          // tmp dir
          $this->_initCache();
          // cache file
          if($groupusername){
               $cacheFilePath = DOKU_TEMP.'piratihelper/graphapi_'.urlencode($groupusername).'_users.tmpo'; 
          } else {
               $cacheFilePath = DOKU_TEMP.'piratihelper/graphapi_users.tmpo';
          }

          $updateInterval = 43200;
          $data = new stdClass();
          $data->timestamp = 0;
          $data->users = array();
          if(file_exists($cacheFilePath)){
               $handle = fopen($cacheFilePath,'r');
               $data = unserialize(fread($handle, filesize($cacheFilePath)));
               fclose($handle);
          }
          // re-cache?
          if((time()-$data->timestamp)>$updateInterval){
               // users
               $users = curl_init();
               if($groupusername){
                    curl_setopt($users,CURLOPT_URL,'https://graph.pirati.cz/group/'.$groupusername.'/members');
               } else {
                    curl_setopt($users,CURLOPT_URL,'https://graph.pirati.cz/users');
               }
               curl_setopt($users,CURLOPT_HEADER,0);
               curl_setopt($users,CURLOPT_RETURNTRANSFER,true);
               $users_raw = curl_exec($users);
               $users_data = json_decode($users_raw);
               if($groupusername){
                    $data->users = array();
                    foreach($users_data as $i=>$usr){
                         $usrs[$i] = curl_init();
                         curl_setopt($usrs[$i],CURLOPT_URL,'https://graph.pirati.cz/'.$usr->id);
                         curl_setopt($usrs[$i],CURLOPT_HEADER,0);
                         curl_setopt($usrs[$i],CURLOPT_RETURNTRANSFER,true);
                         $data->users[] = json_decode(curl_exec($usrs[$i]));
                    }
               } else $data->users = $users_data;
               $data->timestamp = time();
               $handle = fopen($cacheFilePath,'w');
               fwrite($handle,serialize($data));
               fclose($handle);
               curl_close($users);
          }

          return $data->users;
     }

     /**
      *
      *   @param[in] id user unique id from graph api
      *
      *   @return object of user
      *
      */
     function getGraphUser($id){
          $user = curl_init();
          curl_setopt($user,CURLOPT_URL,'https://graph.pirati.cz/'.$id);
          curl_setopt($user,CURLOPT_HEADER,0);
          curl_setopt($user,CURLOPT_RETURNTRANSFER,true);
          return json_decode(curl_exec($user));
     }

     /**
      *
      *   @param[in] username full username of user
      *
      *   @return object of user
      *
      * */
     function getGraphUserByUsername($username){
          $user = curl_init();
          curl_setopt($user,CURLOPT_URL,'https://graph.pirati.cz/user/'.$username);
          curl_setopt($user,CURLOPT_HEADER,0);
          curl_setopt($user,CURLOPT_RETURNTRANSFER,true);
          return json_decode(curl_exec($user));
     }
}

