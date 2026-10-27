<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the tables behind the Automations tab on forms (see automations-plan.md):
// - formulize_automations: each automation is a trigger (new/updated/saved/deleted entry, or a custom button click),
//   conditions for which entries it applies to, and a list of steps (notify, update entries, create an entry,
//   send a webhook);
// - formulize_automation_outbox: webhooks, and automations queued to run in the background, so they can be retried and their progress seen;
// - formulize_automation_log: a record of each time an automation ran, shown on the automation's Activity tab.
//
// A new site gets these from sql/mysql.sql.
//
// Gated to run once, when the stored dbversion is below 25.
function formulize_patch_015_automations($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 25) {
        return true; // already applied
    }

    $tables = array(
        'formulize_automations' => "(
            automation_id int(11) unsigned NOT NULL auto_increment,
            fid int(11) NOT NULL default 0,
            name varchar(255) NOT NULL default '',
            trigger_type varchar(50) NOT NULL default '',
            trigger_settings text NULL,
            conditions text NULL,
            steps mediumtext NULL,
            active tinyint(1) NOT NULL default 1,
            weight int(11) NOT NULL default 0,
            PRIMARY KEY (`automation_id`),
            INDEX i_fid_trigger (`fid`, `trigger_type`)
        ) ENGINE=InnoDB",
        'formulize_automation_outbox' => "(
            outbox_id int(11) unsigned NOT NULL auto_increment,
            automation_id int(11) NOT NULL default 0,
            fid int(11) NOT NULL default 0,
            entry_id int(11) NOT NULL default 0,
            event varchar(50) NOT NULL default '',
            channel varchar(20) NOT NULL default 'webhook',
            target varchar(2000) NOT NULL default '',
            payload mediumtext NULL,
            status varchar(20) NOT NULL default 'pending',
            attempts int(11) NOT NULL default 0,
            response_code int(11) NOT NULL default 0,
            last_error text NULL,
            created_at datetime NULL default NULL,
            next_attempt_at datetime NULL default NULL,
            delivered_at datetime NULL default NULL,
            PRIMARY KEY (`outbox_id`),
            INDEX i_status_next (`status`, `next_attempt_at`),
            INDEX i_automation_id (`automation_id`),
            INDEX i_fid (`fid`)
        ) ENGINE=InnoDB",
        'formulize_automation_log' => "(
            log_id int(11) unsigned NOT NULL auto_increment,
            automation_id int(11) NOT NULL default 0,
            fid int(11) NOT NULL default 0,
            entry_id int(11) NOT NULL default 0,
            event varchar(50) NOT NULL default '',
            uid int(11) NOT NULL default 0,
            outcome text NULL,
            created_at datetime NULL default NULL,
            PRIMARY KEY (`log_id`),
            INDEX i_automation_id (`automation_id`),
            INDEX i_created_at (`created_at`)
        ) ENGINE=InnoDB",
    );

    foreach ($tables as $table => $definition) {
        if (!$xoopsDB->queryF("CREATE TABLE IF NOT EXISTS " . $xoopsDB->prefix($table) . " $definition")) {
            echo '<p>015_automations: could not create the ' . $table . ' table: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
    }

    echo '<p>Added the Automations tab to forms, where you can set up what happens when entries are created,'
        . ' updated or deleted, or when a custom button is clicked: send notifications, update or create entries,'
        . ' and send webhooks.</p>';
    return true;
}
