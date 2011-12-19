<?php
function b_waiting_yomi(){
	////////////////////////////////////
	//仮登録ログファイルのディレクトリ名
	$log_path = XOOPS_ROOT_PATH."/modules/yomi/log/";
	////////////////////////////////////
	//仮登録ログファイルのファイル名
	$log_file = "ys4_temp.cgi";
	////////////////////////////////////
	//設定ここまで
	
	$lang_linkname = "承認待ちリンク(Yomi)";
	$block = array();
	$Ctemp=0;
	$fp=fopen($log_path.$log_file, "r");
	while($tmp=fgets($fp, 4096)){
		$Ctemp++;
	}
	fclose($fp);

	$block['adminlink'] = XOOPS_URL."/modules/yomi/admin.php";
	$block['pendingnum'] = $Ctemp;
	$block['lang_linkname'] = _PI_WAITING_WAITINGS;

	return $block;
}
?>