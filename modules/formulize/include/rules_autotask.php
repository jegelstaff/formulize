<?php
// ICMS autotask, registered in xoops_version.php: every few minutes, send webhooks and run background rules that
// are waiting or due to be retried (see include/rules.php). ICMS runs autotasks before the Formulize bootstrap loads.
defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");
include_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";
formulize_ruleProcessOutbox(10);
