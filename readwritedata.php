<?
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################



	$myts =& MyTextSanitizer::getInstance();
	$msg = '';
	$i=0;
	unset($_POST['submit']);
	foreach( $_POST as $k => $v ){
		if( preg_match('/ele_/', $k)){
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
		if($k == 'xoops_upload_file'){
			$tmp = $k;
			$k = $v[0];			
			$v = $tmp;
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
	}
	
	$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("form_form")." order by id_req DESC");
	list($id_req) = $xoopsDB->fetchRow($sql);
	if ($id_req == 0) { $num_id = 1; }
	else if ($num_id <= $id_req) $num_id = $id_req + 1;


	$up = array();
	$desc_form = array();
	$value = null;

// START LOOPING THROUGH ALL THE ELEMENTS THAT WERE RETURNED FROM THE FORM
foreach( $id as $i ){

$element =& $formulize_mgr->get($i);
		if( !empty($ele[$i]) ){
			//$pds = $element->getVar('pds');
			$id_form = $element->getVar('id_form');
			$ele_id = $element->getVar('ele_id');
			$ele_type = $element->getVar('ele_type');
			$ele_value = $element->getVar('ele_value');
			$ele_caption = $element->getVar('ele_caption');
			$ele_caption = stripslashes($ele_caption);
			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$sql = $xoopsDB->query("SELECT desc_form from ".$xoopsDB->prefix("form_id")." WHERE id_form= ".$id_form.'');
			while ($row = mysql_fetch_array ($sql)) 
			{	$desc_form[] = $row['desc_form']; }
			
			switch($ele_type){
				case 'text':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04
				break;
				case 'textarea':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04

				break;
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'radio':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( $opt_count == $ele[$i] ){
							$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
							$value = $v['key'];
						}
						$opt_count++;
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'yn':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$v = ($ele[$i]==2) ? _NO : _YES;
					$msg.= $myts->stripSlashesGPC($v)."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'checkbox':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( is_array($ele[$i]) ){
							if( in_array($opt_count, $ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.'*=+*:'.$v['key'];
							}
							$opt_count++;
						}else{
							if( !empty($ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.'*=+*:'.$v['key'];
							}
						}						
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'select':
					// section to handle linked select boxes differently from others...
					$formlinktrue = 0;
					if(is_array($ele[$i]))  // look for the formlink delimiter
					{
						foreach($ele[$i] as $justacheck)
						{
							if(strstr($justacheck, "#*=:*"))
							{
								$formlinktrue = 1;
								break;
							}
						}
					}
					else
					{
						if(strstr($ele[$i], "#*=:*"))
						{
							$formlinktrue = 1;
						}
					}
					if($formlinktrue) // if we've got a formlink, then handle it here...
					{
						if(is_array($ele[$i]))
						{
							//print_r($ele[$i]);
							array($compparts);
							$compinit = 0;
							$selinit = 0;
							foreach($ele[$i] as $whatwasselected)
							{
							//	print "<br>$whatwasselected<br>";
								$compparts = explode("#*=:*", $whatwasselected);
							//	print_r($compparts);
								if($compinit == 0)
								{
									$value = $compparts[0] . "#*=:*" . $compparts[1] . "#*=:*";
									$compinit = 1;
								}
								if($selinit == 1)
								{
									$value = $value . "[=*9*:";
								}
								$value = $value . $compparts[2];
								$selinit = 1;
							}
						}
						else
						{
							$value = $ele[$i];
						}	
//						print "<br>VALUE: $value";	
						break;			
					}
					else
					{


			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';


							// The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
							// print_r($ele_value[2]);
							$entriesPassedBack = array_keys($ele_value[2]);
							$keysPassedBack = array_keys($entriesPassedBack);
							$entrycounterjwe = 0;
							foreach($keysPassedBack as $masterentlistjwe)
							{
	      						if(is_array($ele[$i]))

								{
									foreach($ele[$i] as $whattheuserselected)
									{
										// if the user selected an entry found in the master list of all possible entries...
										//print "internal loop $entrycounterjwe<br>userselected: $whattheuserselected<br>selectbox contained: $masterentlistjwe<br><br>";	
										if($whattheuserselected == $masterentlistjwe)
										{
											//print "WE HAVE A MATCH!<BR>";
											$value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
											$msg.= $myts->stripSlashesGPC($value).'<br>';
											//print "$value<br><br>";
										}
									}
									$entrycounterjwe++;
								}
								else
								{
									//print "internal loop $entrycounterjwe<br>userselected: $ele[$i]<br>selectbox contained: $masterentlistjwe<br><br>";	
									if($ele[$i] == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
									{
										//print "WE HAVE A MATCH!<BR>";
										$value = $entriesPassedBack[$entrycounterjwe];
										$msg.= $myts->stripSlashesGPC($value).'<br>';
										//print "$value<br><br>";
										break;
									}
									$entrycounterjwe++;
								}
							}
					// print "selects: $value<br>";
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				//Marie le 20/04/04
					} // end of if that checks for a linked select box.
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'date':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					// code below commented/added by jwe 10/23/04 to convert dates into the proper standard format
					if($ele[$i] != "YYYY-mm-dd" AND $ele[$i] != "") { 
						$ele[$i] = date("Y-m-d", strtotime($ele[$i])); 
					} else {
						continue 2; // forget about this date element and go on to the next element in the form
					}
					$value = ''.$ele[$i];
				break;
				case 'sep':
			/*if ($ele_caption != '{SEPAR}') {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>"; }
			else {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."</b></td></table><br>"; }*/
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'upload':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
							/************* UPLOAD *************/
				$img_dir = XOOPS_ROOT_PATH . "/modules/formulize/upload" ;
				$allowed_mimetypes = array();
				foreach ($ele_value[2] as $v){ $allowed_mimetypes[] = 'image/'.$v[1];
				}
				// types proposés : pdf, doc, txt, gif, mpeg, jpeg
				$max_imgsize = $ele_value[1];
				$max_imgwidth = 12000;
				$max_imgheight = 12000;
				
				$fichier = $_POST["xoops_upload_file"][0] ; 
// teste si le champ a été rempli :
			if( !empty( $fichier ) || $fichier != "") {
// test si aucun fichier n'a été joint
				if($_FILES[$fichier]['error'] == '2' || $_FILES[$fichier]['error'] == '1') {	
					$error = sprintf(_formulize_MSG_BIG, $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				}
				if(filesize($_FILES[$fichier]['tmp_name']) ==null) {	
					$value = $path = '';
					$filename = '';
					$msg.= $filename.'</TD></table><br>';
					break;
				}
				if($_FILES[$fichier]['size'] > $max_imgsize) {	
					$error = sprintf(_formulize_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				}
// teste si le fichier a été uploadé dans le répertoire temporaire:
				if( ! is_readable( $_FILES[$fichier]['tmp_name'])  || $_FILES[$fichier]['tmp_name'] == "" ) 
				{
				//redirect_header( XOOPS_URL.'/modules/formulize/index.php?title='.$title , 2, _MD_FILEERROR ) ; 
					$path = '';
					$filename = '';
					$error = sprintf(_formulize_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				//	exit ;				
				}
// création de l'objet uploader
				$uploader = new XoopsMediaUploader_FA($img_dir, $allowed_mimetypes, $max_imgsize, $max_imgwidth, $max_imgheight);
// fichier uploadé conforme en dimension et taille, bien copié du répertoire temporaire au répertoire indiqué ??
				if( $uploader->fetchMedia( $fichier ) && $uploader->upload() ) { 
					$pos = strrpos($uploader->getSavedFileName(), '.');
					$type = 'image/'.substr($uploader->getSavedFileName(), $pos+1);
					if (!in_array ($type, $allowed_mimetypes)) {	//si ce type est autorisé
						$path = '';
						$filename = '';
						$error = sprintf(_formulize_MSG_UNTYPE.implode(', ',$allowed_mimetypes))._formulize_MSG_THANK;
						redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
					}
// L'upload a réussi 
					$path = $uploader->getSavedDestination();
					$filename = $uploader->getSavedFileName();
					$up[$path] = $filename;
					$value = $path;
					$msg.= $filename.'</TD></table><br>';
// sinon l''upload a échoué : message d'erreur 
				} 
			}
			else {
				$value = $path = '';
				$filename = '';
				$msg.= $filename.'</TD></table><br>';
			}
				break;
				default:
				break;
			}

//*************************************
// START WRITING DATA TO THE DB
//*************************************


$date = date ("Y-m-d");
$value = addslashes ($value);

//print "<br>Value about to write:  $value";

// added code to handle proxy entries -- jwe 8/28/04 and then updating entries 9/06/04
// 1. set the uid to be the value sent from the proxyuser form element
// 2. set the proxyid to the be the uid
// 3. add proxyid call to all SQL queries that handle form_form records




if(isset($_POST['proxyuser']) AND $_POST['proxyuser'] != "noproxy")
{
	$proxyid = $realuid; // proxy flag set to user who made entry
	$uid = $_POST['proxyuser']; // uid set to the proxy uid
	$viewentry = 0; // necessary to make everything work as if a new entry is being made, which it is.
}
elseif($viewentry AND $uid != $veuid) // they are an admin who has updated someone's entry
{
	$proxyid = $realuid; // proxy flag set to user who updated entry
	$uid = $veuid; // uid set to uid of the original entry
}

// modified to update existing entries -- jwe 7/24/04

// Process to write over an entry...  (once again, assume captions are unique)
// 1. check to see if the current caption has a record that matches the viewentry (a record that is part of the current submission)
// 2. if the current caption does have a record, extract the ele_id
// 3. if there is a record and we've extracted an ele_id, then update the record with that ele_id, viewentry for id_req, and the info from the form
// 4. if the current caption does not have a record, then we create a new record (same as if viewentry were false, *except* we use the current viewentry for the id_req)

array ($submittedcaptions);

if($viewentry)
{

	// make an array out of the ele_captions so we can do a check at the end to see if any existing ones were blanked.  
	$submittedcaptions[$subcapindex] = $ele_caption;
	$subcapindex++;

	// check to see if the caption exists...
	$captionExistsJwe = 0;
	foreach($reqCaptionsJwe as $existingCaption)
	{
		if($existingCaption == $ele_caption)
		{
			$captionExistsJwe = 1;
			break;
		}
	}

	if($captionExistsJwe)
	{
		//get the ele_id
		$extractEleid = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"$ele_caption\" AND id_req=$viewentry";
		//print "*extractEleid*". $extractEleid . "*";
		$resultExtractEleid = mysql_query($extractEleid);
		$finalresulteleidex = mysql_fetch_row($resultExtractEleid);
		$ele_id = $finalresulteleidex[0];

		$sql="UPDATE " .$xoopsDB->prefix("form_form") . " SET id_form=\"$id_form\", id_req=\"$viewentry\", ele_id=\"$ele_id\", ele_type=\"$ele_type\", ele_caption=\"$ele_caption\", ele_value=\"$value\", uid=\"$uid\", proxyid=\"$proxyid\", date=\"$date\" WHERE ele_id = $ele_id";
		
	}
	else // or if the caption does not exist (it was blank last time the form was filled in...make a new entry but use the current viewentry for the id_req (to tie this new entry to the other elements that are part of the same record)
	{
	$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$viewentry\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\")";
	}
}
else
{
$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$num_id\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\")";
}

$result = $xoopsDB->query($sql);
    if ($result == false) {
        die('Erreur insertion : <br>' . $sql . '<br>');
    } 
    		} // end of the If that accompanies the foreach
	} // end of the foreach that goes through all the elements
	$msg = nl2br($msg);



?>	

