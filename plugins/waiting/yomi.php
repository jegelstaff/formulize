<?php
function b_waiting_yomi() {
	////////////////////////////////////
	//����Ͽ�?�ե�����Υǥ��쥯�ȥ�̾
	$log_path = ICMS_ROOT_PATH."/modules/yomi/log/";
	////////////////////////////////////
	//����Ͽ�?�ե�����Υե�����̾
	$log_file = "ys4_temp.cgi";
	////////////////////////////////////
	//���ꤳ���ޤ�
	
	$lang_linkname = "��ǧ�Ԥ����(Yomi)";
	$block = array();
	$Ctemp=0;
	$fp=fopen($log_path.$log_file, "r");
	while ($tmp=fgets($fp, 4096)) {
		$Ctemp++;
	}
	fclose($fp);

	$block['adminlink'] = ICMS_ROOT_PATH."/modules/yomi/admin.php";
	$block['pendingnum'] = $Ctemp;
	$block['lang_linkname'] = _PI_WAITING_WAITINGS;

	return $block;
}
?>