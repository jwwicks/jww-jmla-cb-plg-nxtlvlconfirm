<?php
/**
* Joomla Community Builder User Plugin: plug_nextlvlconfirm
* @version 1.0.0
* @package plug_nxtlvlconfirm
* @subpackage nxtlvlconfirm.xml
* @author John Wicks (Gh0st)
* @copyright (C) (Gh0st), GreatLittleBook Publishing Co., Inc
* @license Limited http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @final 1.0.0
*/

/** ensure this file is being included by a parent file **/
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->registerFunction('onUserActive', 'nxtlvlOnUserActive', 'plug_nxtlvlconfirm');
$_PLUGINS->registerFunction('onBeforeUserActive', 'nxtlvlOnBeforeUserActive', 'plug_nxtlvlconfirm');

/**
 * NextLevel Confirmation Class.
 * This plugin is used to confirm a user via a user-created CB field.
 */
class plug_nxtlvlconfirm extends cbTabHandler {
	/**
	 * Constructor
	 */
	function plug_nxtlvlconfirm() {
		$this->cbTabHandler();
	}
	
	/**
	* Get plugin, tab, and Community Builder fields related to this application
	* @returns associative array of parameters/values pairs for the plugin
	*/
	function _nxtlvlGetPlugParameters(){
		$params = $this->params;
			
		$PlugParams["nxtlvlplugenabled"] = intval($params->get('nxtlvlPlugEnabled', 1));
		$PlugParams["nxtlvlwatchfieldid"] = $params->get('nxtlvlWatchFieldId',"email");
		$PlugParams["nxtlvlonconfirmedgroup"] = intval($params->get('nxtlvlOnConfirmedGroup',19));
		
		$PlugParams["nxtlvlemailfromuserid"] = intval($params->get('nxtlvlEmailFromUserId',0));
		$PlugParams["nxtlvlemailtouserid"] = intval($params->get('nxtlvlEmailToUserId',62));
		$PlugParams["nxtlvlonchecksubject"] = $params->get('nxtlvlOnCheckSubject',"[SITENAME] - Next Level Verification");
		$PlugParams["nxtlvloncheckbody"] = $params->get('nxtlvlOnCheckBody',"Username: [NAME][BR]Email: [EMAIL][BR]Field Value:  [NXTLVLFIELD][BR]Confirm: [NXTLVLCONFIRM][BR]Deny: [NXTLVLDENY]");
		
		$PlugParams['nxtlvlonconfirmedsubject'] = $params->get('nxtlvlOnConfirmedSubject',"[SITENAME] - Next Level Confirmed");
		$PlugParams['nxtlvlonconfirmedbody'] = $params->get('nxtlvlOnConfirmedBody',"Username: [NAME][BR]Email: [EMAIL][BR]Field Value:  [NXTLVLFIELD][BR] is confirmed for Next Level.");
		
		$PlugParams['nxtlvlonfailedsubject'] = $params->get('nxtlvlOnFailedSubject',"[SITENAME] - Next Level Failure");
		$PlugParams['nxtlvlonfailedbody'] = $params->get('nxtlvlOnFailedBody',"Username: [NAME][BR]Email: [EMAIL][BR]Field Value:  [NXTLVLFIELD][BR] has failed the Next Level verification process.");
		
		return $PlugParams;
	}// end function
	
	/**
	* Saves value in cbactivation field into nxtlvlactivation field.
	*
	* The nxtlvlactivation field is used to identify the user for NxtLevel upgrade process
	*
	*/
	function nxtlvlOnBeforeUserActive( &$user, $ui, $cause, $mailToAdmins, $mailToUser ){
		global $_CB_framework, $_CB_database;
		$ret_val = true;
		
		$query = "SELECT cbactivation FROM #__comprofiler WHERE id = " . (int)$user->id;
		$_CB_database->setQuery($query);
		$_CB_database->query();
		$code = $_CB_database->loadResult();
		if(!$code){
			$this->_setErrorMSG("NxtLvl Plugin unable to find cbactivation code for User.");
			return;
		}
		
		$_CB_database->setQuery("UPDATE #__comprofiler SET cb_nxtlvlactivation =" .$_CB_database->Quote($code). " WHERE id=" . (int)$user->id);
		$_CB_database->query();
		if(!$_CB_database->getAffectedRows()){
			$this->_setErrorMSG("NxtLvl Plugin unable to update cb_nxtlvlactivation field for User.");
			return;
		}
		
		return $ret_val;
	}
	
	/**
	* Sends Next Level User confirmation email.
	* Used to verify secondary field value in User registration process after successful activation.
	* @param $user - CB user information record
	* @param $success - boolean indicating CB User Activation Process status
	* @returns true if email successful, false otherwise
	*/
	function nxtlvlOnUserActive($user, $success) {
		global $_CB_framework, $_CB_database;
		$ret_val = true;
		
		if (!$success || !$user) return;
		
		$plugparams=$this->_nxtlvlGetPlugParameters();
		$nxtlvlNotifications = new cbNotification();
		
		if($plugparams["nxtlvlplugenabled"]){
			
			$myUser =&	CBuser::getInstance( $user->id );
			if(!$myUser){
				$this->_setErrorMSG("NxtLvl Plugin failed to load User record for Verification.");
				return;
			}
			
			$watchField = $myUser->getField($plugparams['nxtlvlwatchfieldid']);
			if(!$watchField){
				$this->_setErrorMSG("NxtLvl Plugin failed to find WatchField in User record.");
				return;
			}
			
			$extraFields = array();
			$extraFields['nxtlvlfield'] = $watchField; 
			
			$ret_val = $nxtlvlNotifications->sendUserEmail(
														 $plugparams["nxtlvlemailtouserid"],
														 $plugparams["nxtlvlemailfromuserid"],
														 $this->nxtlvl_replaceVariables(getLangDefinition($plugparams["nxtlvlonchecksubject"]),$user),
														 $this->nxtlvl_replaceVariables(getLangDefinition($plugparams["nxtlvloncheckbody"]),$user, $extraFields),
														 false);
			if(!$ret_val){
				$this->_setErrorMSG("NxtLvl Plugin failed to send User Verification email.");
			}
		}
		return $ret_val;
	} // end function
	
	/**
	 * This function is a copy of the CB replaceVariables function
	 * that substitutes [FieldName] type strings with their values.
	 * Extended to take care or NextLevel tags.
	 *
	 * @param $msg - Text of message with replaceable elements
	 * @param moscomprofilerUser $row - CB user information record
	 * @param array $rowExtras - Associative array containing extra replaceable elements. Key must match [FieldName] (case insensitive).
	 * @return string of $msg with [FieldName] elements filled out
	 */
	function nxtlvl_replaceVariables($msg, $row, $rowExtras = array(), $htmlspecialchars = true){
		global $_CB_framework, $ueConfig;
		
		if ( strpos( $msg, '[' ) === false ) {
			return $msg;
		}
		
		$msg = str_replace( array( '\n' ), array( "\n" ), $msg ); 
		$msg = cbstr_ireplace("[USERNAME]", $row->username, $msg);
		$msg = cbstr_ireplace("[NAME]", $row->name, $msg);
		$msg = cbstr_ireplace("[EMAILADDRESS]", $row->email, $msg);
		$msg = cbstr_ireplace("[DETAILS]", $this->nxtlvl_getUserDetails($row,$_CB_framework->getCfg( 'emailpass' )), $msg);
		$msg = cbstr_ireplace("[BR]", "\n", $msg);
		$msg = cbstr_ireplace("[SITENAME]", $_CB_framework->getCfg('sitename'), $msg);
		$msg = cbstr_ireplace("[SITEURL]", $_CB_framework->getCfg( 'live_site' ), $msg);
		
		if( $ueConfig['reg_confirmation'] == 1 ) {
			if ( $row->confirmed ) {
				if ( $row->cbactivation ) {
					$confirmCode = $row->cbactivation;
				} else {
					$confirmCode = '';
				}
				$confirmLink = " \n".$_CB_framework->getCfg( 'live_site' )."/index.php?option=com_comprofiler&task=nxtlvlconfirm&confirmcode=".$confirmCode." \n";
				$denyLink = " \n".$_CB_framework->getCfg( 'live_site' )."/index.php?option=com_comprofiler&task=nxtlvldeny&confirmcode=".$confirmCode." \n";
			}
		} else {
			$confirmLink = ' ';
		}
				
		$msg = cbstr_ireplace("[NXTLVLCONFIRM]", $confirmLink, $msg);
		$msg = cbstr_ireplace("[NXTLVLDENY]", $denyLink, $msg);
		
		$array = array();
		$array = get_object_vars($row);
		foreach( $array AS $k=>$v ) {
			if( !is_object($v) && !is_array($v) ) {
				if ( !(strtolower($k) == "password" && strlen($v) >= 32) ) {
					$msg = cbstr_ireplace("[".$k."]", getLangDefinition($v), $msg );
				}
			}
		}
		foreach( $rowExtras AS $k => $v) {
			if( ( ! is_object( $v ) ) && ( ! is_array( $v ) ) ) {
				if ( is_array( $htmlspecialchars ) ) {
					$v = call_user_func_array( $htmlspecialchars, array( $v ) );
				}
				$msg = cbstr_ireplace("[".$k."]", $htmlspecialchars === true ? htmlspecialchars( $v ) : $v, $msg );
			}
		}
		
		return $msg;
	}// end function

	/**
	 * Copy of CB function _getUserDetails
	 *
	 * @param moscomprofilerUser $row - CB user information record
	 * @param boolean $includePWD - Show the users Password in details
	 * @return string containing User Details
	 */
	function nxtlvl_getUserDetails($row,$includePWD) {
		$uDetails = _UE_EMAIL." : ".$row->email;
		$uDetails .= "\n"._UE_UNAME." : ".$row->username."\n";
		if($includePWD==1) $uDetails .= _UE_PASS." : ".$row->password."\n";
	 	return $uDetails;
	}// end function
	
} // end of getNxtLvlTab class
?>