<?php
// ICMS autotask, registered in xoops_version.php: every few minutes, send webhooks, run background rules and refresh
// summaries that are waiting or due to be retried (see include/rules.php and include/summaries.php), after queueing
// refreshes of scheduled summaries that are due. ICMS runs autotasks before the Formulize bootstrap loads.
defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");
include_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";
formulize_summariesQueueScheduled();
formulize_ruleProcessOutbox(10);
