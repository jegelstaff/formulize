<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the tables behind summaries: AI-readable digests of the data in a form, kept up to date as the data changes
// (see class/summaries.php and include/summaries.php):
// - formulize_summary_definitions: what each summary covers, how it is kept current, and who may read it;
// - formulize_summaries: the text of each summary, with when it was written and what it covers. Earlier versions are
//   kept. This table is also the hidden, system-managed "System Summaries" form.
//
// A new site gets these from sql/mysql.sql.
//
// Gated to run once, when the stored dbversion is below 27.
function formulize_patch_016_summaries($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 27) {
        return true; // already applied
    }

    $tables = array(
        'formulize_summary_definitions' => "(
            summary_id int(11) unsigned NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            form_id int(11) NOT NULL default 0,
            include_connected_forms tinyint(1) NOT NULL default 0,
            only_entries_where text NULL,
            level varchar(20) NOT NULL default 'whole_scope',
            group_by varchar(255) NOT NULL default '',
            built_from_summary_id int(11) NOT NULL default 0,
            instructions text NULL,
            written_by varchar(20) NOT NULL default 'server_ai',
            keep_current varchar(20) NOT NULL default 'on_change',
            wait_minutes int(11) NOT NULL default 10,
            refresh_every_minutes int(11) NOT NULL default 1440,
            readable_by_groups text NULL,
            all_scope_viewers tinyint(1) NOT NULL default 1,
            max_length int(11) NOT NULL default 2000,
            active tinyint(1) NOT NULL default 1,
            created_uid int(11) NOT NULL default 0,
            created_at datetime NULL default NULL,
            updated_at datetime NULL default NULL,
            PRIMARY KEY (`summary_id`),
            INDEX i_form_id (`form_id`)
        ) ENGINE=InnoDB",
        'formulize_summaries' => "(
            text_id int(11) unsigned NOT NULL auto_increment,
            summary_id int(11) NOT NULL default 0,
            summary_name varchar(255) NOT NULL default '',
            form_id int(11) NOT NULL default 0,
            entry_id int(11) NOT NULL default 0,
            group_value varchar(255) NOT NULL default '',
            summary_text mediumtext NULL,
            as_of datetime NULL default NULL,
            covers mediumtext NULL,
            written_by varchar(255) NOT NULL default '',
            written_uid int(11) NOT NULL default 0,
            is_current tinyint(1) NOT NULL default 1,
            created_at datetime NULL default NULL,
            PRIMARY KEY (`text_id`),
            INDEX i_summary_piece (`summary_id`, `entry_id`, `group_value`(100), `is_current`),
            INDEX i_form_id (`form_id`)
        ) ENGINE=InnoDB",
    );

    foreach ($tables as $table => $definition) {
        if (!$xoopsDB->queryF("CREATE TABLE IF NOT EXISTS " . $xoopsDB->prefix($table) . " $definition")) {
            echo '<p>016_summaries: could not create the ' . $table . ' table: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
    }

    echo '<p>Added summaries: digests of the data in a form, written by AI and kept up to date as the data changes,'
        . ' which AI assistants connected to the site can read to orient themselves.</p>';
    return true;
}
