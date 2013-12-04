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

     /***** Dokuwiki API functions *****/

     public function getID(){
          global $ID;
          return $ID;
     }

     public function getInfo($name = null){
          global $INFO;
          if(is_null($name)) return $INFO;
          return $INFO[$name];
     }

     public function setInfo($name,$value){
          global $INFO;
          $INFO[$name] = $value;
     }

     public function getUserInfo($name){
          global $INFO;
          if(is_null($name)) return $INFO['userinfo'];
          return $INFO['userinfo'][$name];
     }

     public function setUserInfo($name,$value){
          global $INFO;
          $INFO['userinfo'][$name] = $value;
     }

     public function getJsInfo(){
          global $JSINFO;
          return $JSINFO;
     }

     public function setJsInfo($name,$value){
          global $JSINFO;
          $JSINFO[$name] = $value;
     }

     public function getJsUserInfo(){
          global $JSINFO;
          return $JSINFO['user'];
     }

     public function setJsUserInfo($name,$value){
          global $JSINFO;
          $JSINFO['user'][$name] = $value;
     }

     public function getText(){
          global $TEXT;
          return $TEXT;
     }

     public function setText($content){
          global $TEXT;
          $TEXT = $content;
     }

     public function getUserGaid(){
          global $INFO;
          return $INFO['userinfo']['id'];
     }

     public function getUserFullname($user = null){
          global $INFO;
          if(is_null($user)) return (!empty($INFO['userinfo']['fullname'])?$INFO['userinfo']['fullname']:$INFO['userinfo']['username']);
          else return (!empty($user->fullname)?$user->fullname:$user->username);
     }

     public function isAuth(){
          global $INFO;
          if(!empty($INFO['userinfo'])) return true;
          return false;
     }

     public function isAction($action_type){
          global $ACT;
          switch(true){
               case ($ACT==$action_type): return true;
               default: return false;
          }
     }

     /***** Graph API functions *****/

     private $graph_url = 'https://graph.pirati.cz/';

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
                    curl_setopt($groups,CURLOPT_URL,$this->graph_url.'user/'.$username.'/groups');
               } else {
                    curl_setopt($groups,CURLOPT_URL,$this->graph_url.'groups');
               }
               curl_setopt($groups,CURLOPT_HEADER,0);
               curl_setopt($groups,CURLOPT_RETURNTRANSFER,true);
               $groups_raw = curl_exec($groups);
               $groups_data = json_decode($groups_raw);
               if($username){
                    $data->groups = array();
                    foreach($groups_data as $i=>$grp){
                         $grps[$i] = curl_init();
                         curl_setopt($grps[$i],CURLOPT_URL,$this->graph_url.$grp->id);
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
               if($allin){
                    if(!in_array($grp->id,$group_gaids)) return false;
               } else {
                    if(in_array($grp->id,$group_gaids)) return true;
               }
          }
          if($allin) return true;
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
                    curl_setopt($users,CURLOPT_URL,$this->graph_url.'group/'.$groupusername.'/members');
               } else {
                    curl_setopt($users,CURLOPT_URL,$this->graph_url.'users');
               }
               curl_setopt($users,CURLOPT_HEADER,0);
               curl_setopt($users,CURLOPT_RETURNTRANSFER,true);
               $users_raw = curl_exec($users);
               $users_data = json_decode($users_raw);
               if($groupusername){
                    $data->users = array();
                    foreach($users_data as $i=>$usr){
                         $usrs[$i] = curl_init();
                         curl_setopt($usrs[$i],CURLOPT_URL,$this->graph_url.$usr->id);
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
          curl_setopt($user,CURLOPT_URL,$this->graph_url.$id);
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
          curl_setopt($user,CURLOPT_URL,$this->graph_url.'user/'.$username);
          curl_setopt($user,CURLOPT_HEADER,0);
          curl_setopt($user,CURLOPT_RETURNTRANSFER,true);
          return json_decode(curl_exec($user));
     }
}
