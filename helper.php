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

     public function renderEditTextarea($_text = null){
          global $ID;
          global $REV;
          global $DATE;
          global $PRE;
          global $SUF;
          global $INFO;
          //global $SUM;
          global $lang;
          global $conf;
          global $TEXT;
          global $RANGE;

          // added
          $TEXT = (is_null($_text)?$TEXT:$_text);

          if (isset($_REQUEST['changecheck'])) {
               $check = $_REQUEST['changecheck'];
          } elseif(!$INFO['exists']){
               // $TEXT has been loaded from page template
               $check = md5('');
          } else {
               $check = md5($TEXT);
          }
          $mod = md5($TEXT) !== $check;

     //     $wr = $INFO['writable'] && !$INFO['locked'];
          $wr=true;
          $include = 'edit';
          if($wr){
               if ($REV) $include = 'editrev';
          }else{
               // check pseudo action 'source'
               if(!actionOK('source')){
                    msg('Command disabled: source',-1);
                    return;
               }
               //$include = 'read';
               $include = 'edit';
          }

          global $license;
                                                       //
          $form = new Doku_Form(array('id' => 'dw__editform'));
          $form->addHidden('id', $ID);
          $form->addHidden('rev', $REV);
          $form->addHidden('date', $DATE);
          $form->addHidden('prefix', $PRE . '.');
          $form->addHidden('suffix', $SUF);
          $form->addHidden('changecheck', $check);
                                                       //
          $data = array('form' => $form,
               'wr'   => $wr,
               'media_manager' => true,
               'target' => (isset($_REQUEST['target']) && $wr && $RANGE !== '') ? $_REQUEST['target'] : 'section',
               'intro_locale' => $include);

          ob_start();

          if ($data['target'] !== 'section') {
               // Only emit event if page is writable, section edit data is valid and
               // edit target is not section.
               trigger_event('HTML_EDIT_FORMSELECTION', $data, 'html_edit_form', true);
          } else {
               html_edit_form($data);
          }
          
          //if (isset($data['intro_locale'])) {
          //     echo p_locale_xhtml($data['intro_locale']);
          //}
                                                       //
          $form->addHidden('target', $data['target']);
          $form->addElement(form_makeOpenTag('div', array('id'=>'wiki__editbar')));
          $form->addElement(form_makeOpenTag('div', array('id'=>'size__ctl')));
          $form->addElement(form_makeCloseTag('div'));

          
          //if ($wr) {
            //   $form->addElement(form_makeOpenTag('div', array('class'=>'editButtons')));
            //   $form->addElement(form_makeButton('submit', 'save', $lang['btn_save'], array('id'=>'edbtn__save', 'accesskey'=>'s', 'tabindex'=>'4')));
            //   $form->addElement(form_makeButton('submit', 'preview', $lang['btn_preview'], array('id'=>'edbtn__preview', 'accesskey'=>'p', 'tabindex'=>'5')));
            //   $form->addElement(form_makeButton('submit', 'draftdel', $lang['btn_cancel'], array('tabindex'=>'6')));
            //   $form->addElement(form_makeCloseTag('div'));
            //   $form->addElement(form_makeOpenTag('div', array('class'=>'summary')));
            //   $form->addElement(form_makeTextField('summary', $SUM, $lang['summary'], 'edit__summary', 'nowrap', array('size'=>'50', 'tabindex'=>'2')));
            //   $elem = html_minoredit();
            //   if ($elem) $form->addElement($elem);
            //        $form->addElement(form_makeCloseTag('div'));
          //}
          
          $form->addElement(form_makeCloseTag('div'));
          //if($wr && $conf['license']){
            //   $form->addElement(form_makeOpenTag('div', array('class'=>'license')));
            //   $out  = $lang['licenseok'];
            //   $out .= ' <a href="'.$license[$conf['license']]['url'].'" rel="license" class="urlextern"';
            //   if($conf['target']['extern']) $out .= ' target="'.$conf['target']['extern'].'"';
            //   $out .= '>'.$license[$conf['license']]['name'].'</a>';
            //   $form->addElement($out);
            //   $form->addElement(form_makeCloseTag('div'));
          //}
                                                       //
          if ($wr) {
               // sets changed to true when previewe
               echo '<script type="text/javascript" charset="utf-8"><!--//--><![CDATA[//><!--'. NL;
               echo 'textChanged = ' . ($mod ? 'true' : 'false');
               echo '//--><!]]></script>' . NL;
          } ?>
          <div style="width:90%;">
               <div class="toolbar">
                    <div id="draft__status"><?php if(!empty($INFO['draft'])) echo $lang['draftdate'].' '.dformat();?></div>
                    <div id="tool__bar"><?php if ($wr && $data['media_manager']){?><a href="<?php echo DOKU_BASE?>lib/exe/mediamanager.php?ns=<?php echo $INFO['namespace']?>" target="_blank"><?php echo $lang['mediaselect'] ?></a><?php }?></div>
               </div>
     <?php
          html_form('edit', $form);
          print '</div>'.NL;

          return str_replace('</form>','',str_replace('<form id="dw__editform" method="post" action="" accept-charset="utf-8">','',ob_get_clean()));
         
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
