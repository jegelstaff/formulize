<?php
/**
 * Formulize Log Viewer
 * Admin page for viewing and filtering system log files
 */

// Only webmasters can access this page
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

// Include necessary functions
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// Get log configuration
$config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
$formulizeLoggingOnOff = $formulizeConfig['formulizeLoggingOnOff'];
$logPath = $formulizeConfig['formulizeLogFileLocation'];

if(!$formulizeLoggingOnOff) {
    $formulizeModId = getFormulizeModId();
    $preferencesUrl = XOOPS_URL . "/modules/system/admin.php?fct=preferences&op=showmod&mod=" . $formulizeModId;
    $adminPage['error'] = "Logging is currently disabled. Enable it in <a href=\"" . htmlspecialchars($preferencesUrl) . "\">Formulize preferences</a> to view logs.";
    $adminPage['template'] = "db:admin/logviewer.html";
    $adminPage['sessions'] = array();
    $adminPage['log_files'] = array();
    return;
}

if(!$logPath || !is_dir($logPath)) {
    $adminPage['error'] = "Log directory not configured or not accessible: " . htmlspecialchars($logPath);
    $adminPage['template'] = "db:admin/logviewer.html";
    $adminPage['sessions'] = array();
    $adminPage['log_files'] = array();
    return;
}

// Get available log files
$availableFiles = getAvailableLogFiles($logPath);
$adminPage['log_files'] = $availableFiles;

// Get selected file (default to active) - from POST or GET
$selectedFile = isset($_POST['file']) ? basename($_POST['file']) : (isset($_GET['file']) ? basename($_GET['file']) : 'active');
if(!isset($availableFiles[$selectedFile])) {
    $selectedFile = 'active';
}
$adminPage['selected_file'] = $selectedFile;

if(!isset($availableFiles[$selectedFile])) {
    $adminPage['error'] = "No log files available.";
    $adminPage['template'] = "db:admin/logviewer.html";
    $adminPage['sessions'] = array();
    return;
}

$filePath = $availableFiles[$selectedFile]['path'];

// Get user's timezone offset for time conversions
$userTimezoneOffset = formulize_getUserUTCOffsetSecs($xoopsUser);
$adminPage['user_timezone_offset'] = $userTimezoneOffset;

// Get filters from POST (preferred) or GET (for backward compatibility with filter links)
$filters = array(
    'session_id' => isset($_POST['session_id']) ? trim($_POST['session_id']) : (isset($_GET['session_id']) ? trim($_GET['session_id']) : ''),
    'request_id' => isset($_POST['request_id']) ? trim($_POST['request_id']) : (isset($_GET['request_id']) ? trim($_GET['request_id']) : ''),
    'user_id' => isset($_POST['user_id']) ? trim($_POST['user_id']) : (isset($_GET['user_id']) ? trim($_GET['user_id']) : ''),
    'event_type' => isset($_POST['event_type']) ? trim($_POST['event_type']) : (isset($_GET['event_type']) ? trim($_GET['event_type']) : ''),
    'form_id' => isset($_POST['form_id']) ? trim($_POST['form_id']) : (isset($_GET['form_id']) ? trim($_GET['form_id']) : ''),
    'screen_id' => isset($_POST['screen_id']) ? trim($_POST['screen_id']) : (isset($_GET['screen_id']) ? trim($_GET['screen_id']) : ''),
    'entry_id' => isset($_POST['entry_id']) ? trim($_POST['entry_id']) : (isset($_GET['entry_id']) ? trim($_GET['entry_id']) : ''),
    'time_from' => isset($_POST['time_from']) ? trim($_POST['time_from']) : (isset($_GET['time_from']) ? trim($_GET['time_from']) : ''),
    'time_to' => isset($_POST['time_to']) ? trim($_POST['time_to']) : (isset($_GET['time_to']) ? trim($_GET['time_to']) : ''),
    'ignore_anonymous' => isset($_POST['ignore_anonymous']) ? true : (isset($_GET['ignore_anonymous']) ? true : false),
    'user_timezone_offset' => $userTimezoneOffset, // Pass offset to filter function
    'log_file_date' => isset($availableFiles[$selectedFile]['date']) ? $availableFiles[$selectedFile]['date'] : null, // Date of the log file being viewed
);
$adminPage['filters'] = $filters;

// Pagination - from POST or GET
$perPage = isset($_POST['per_page']) ? intval($_POST['per_page']) : (isset($_GET['per_page']) ? intval($_GET['per_page']) : 250);
if(!in_array($perPage, array(250, 500, 1000))) $perPage = 250;

// Get the starting offset from POST or GET
$offset = isset($_POST['offset']) ? max(0, intval($_POST['offset'])) : (isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0);

$adminPage['per_page'] = $perPage;

// Read log entries (may return more than $perPage due to completing the last request)
$entries = readLogEntriesReverse($filePath, $perPage, $offset, $filters);

// Calculate line ranges for pagination display (counting from 1 = most recent)
// Line 1 is the most recent entry (end of file), counting up for older entries
$adminPage['line_start'] = $offset + 1;
$adminPage['line_end'] = $offset + count($entries);

// Pass the next offset to the template for the "Next" button
$adminPage['next_offset'] = $offset + count($entries);

// Get event types for filter dropdown
$adminPage['event_types'] = array(
    '' => 'All Events',
    'session-loaded-for-user' => 'Session Loaded',
    'session-being-destroyed' => 'Session Destroyed',
    'session-garbage-collection' => 'Session GC',
    'attempting-screen-rendering' => 'Screen Rendering',
    'attempting-raw-rendering' => 'Raw Rendering',
    'completed-page-rendering' => 'Page Rendered',
    'rendering-form-screen-page' => 'Form Screen Page',
    'rendering-form' => 'Form Rendering',
    'gathering-data-for-list-of-entries' => 'Data Gathering',
    'saving-data' => 'Data Saved',
    'new-entry' => 'New Entry Created',
    'update-entry' => 'Entry Updated',
    'delete-entry' => 'Entry Deleted',
    'processing-notification-for-new-entry' => 'Notification: New Entry',
    'processing-notification-for-update-entry' => 'Notification: Update Entry',
    'processing-notification-for-delete-entry' => 'Notification: Delete Entry',
    'mcp-request-being-handled' => 'MCP Request',
    'PHP-error-recorded' => 'PHP Error',
    'queue-processing-beginning' => 'Queue Processing Start',
    'queue-processing-complete' => 'Queue Processing Complete',
    'item-written-to-queue' => 'Item Queued',
    'processing-queue-item' => 'Processing Queue Item',
    'error-processing-queue-item' => 'Queue Item Error',
    'error-writing-item-to-queue' => 'Queue Write Error',
    'syntax-error-in-queue-item' => 'Queue Syntax Error',
);

if($entries && count($entries) > 0) {
    // Group by session (with timezone conversion)
    $sessions = groupBySession($entries, $userTimezoneOffset);
    $adminPage['sessions'] = $sessions;
    $adminPage['total_entries'] = count($entries);
    $adminPage['session_count'] = count($sessions);
} else {
    $adminPage['sessions'] = array();
    $adminPage['total_entries'] = 0;
    $adminPage['session_count'] = 0;
    if(array_sum(array_map('strlen', $filters)) > 0) {
        $adminPage['info'] = "No log entries found matching your filters.";
    } else {
        $adminPage['info'] = "No log entries found.";
    }
}

$adminPage['template'] = "db:admin/logviewer.html";

// Breadcrumb
$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "System Log Viewer";

// ============================================================================
// Helper Functions
// ============================================================================

function getAvailableLogFiles($logPath) {
    $files = glob($logPath . '/formulize_log_*.log');
    if(!$files) return array();

    $fileList = array();

    foreach($files as $file) {
        $basename = basename($file);
        $size = filesize($file);
        $modified = filemtime($file);

        if($basename === 'formulize_log_active.log') {
            $fileList['active'] = array(
                'path' => $file,
                'display' => 'Active Log (Current)',
                'size' => $size,
                'modified' => $modified
            );
        } else {
            // Extract date from filename: formulize_log_2025-12-16.log
            if(preg_match('/formulize_log_([\d-]+)\.log/', $basename, $matches)) {
                $date = $matches[1];
                $fileList[$date] = array(
                    'path' => $file,
                    'display' => "Archive: $date",
                    'size' => $size,
                    'modified' => $modified
                );
            }
        }
    }

    // Sort: active first, then reverse chronological
    krsort($fileList);
    if(isset($fileList['active'])) {
        $active = $fileList['active'];
        unset($fileList['active']);
        $fileList = array('active' => $active) + $fileList;
    }

    return $fileList;
}

function countMatchingEntries($filePath, $filters) {
    $handle = @fopen($filePath, 'r');
    if(!$handle) return 0;

    $count = 0;
    while(($line = fgets($handle)) !== false) {
        if(trim($line) === '') continue;
        $entry = json_decode($line, true);
        if($entry && is_array($entry) && passesFilters($entry, $filters)) {
            $count++;
        }
    }

    fclose($handle);
    return $count;
}

function readLogEntriesReverse($filePath, $limit, $offset, $filters) {
    $handle = @fopen($filePath, 'r');
    if(!$handle) return array();

    // Get file size
    fseek($handle, 0, SEEK_END);
    $fileSize = ftell($handle);

    if($fileSize == 0) {
        fclose($handle);
        return array();
    }

    // Read in chunks from the end
    $chunkSize = 8192; // 8KB chunks
    $buffer = '';
    $lines = array();
    $position = $fileSize;
    $linesRead = 0;
    // Read extra entries (up to 100) beyond the target to allow request completion logic to work
    $targetCount = $limit + $offset + 100;

    while($position > 0 && $linesRead < $targetCount) {
        $chunkSize = min($chunkSize, $position);
        $position -= $chunkSize;
        fseek($handle, $position);
        $chunk = fread($handle, $chunkSize);
        $buffer = $chunk . $buffer;

        // Extract complete lines
        while(($newlinePos = strrpos($buffer, "\n")) !== false) {
            $line = substr($buffer, $newlinePos + 1);
            $buffer = substr($buffer, 0, $newlinePos);

            if(trim($line) === '') continue;

            $entry = json_decode($line, true);
            if($entry && is_array($entry) && passesFilters($entry, $filters)) {
                $lines[] = $entry;
                $linesRead++;
                if($linesRead >= $targetCount) break 2;
            }
        }
    }

    // Handle remaining buffer
    if($position == 0 && trim($buffer) !== '' && $linesRead < $targetCount) {
        $entry = json_decode($buffer, true);
        if($entry && is_array($entry) && passesFilters($entry, $filters)) {
            $lines[] = $entry;
        }
    }

    fclose($handle);

    // Apply offset and limit, but complete the current request
    $result = array_slice($lines, $offset, $limit);

    // If we have results and hit exactly the limit, check if we need to complete the request
    if(count($result) == $limit && isset($lines[$offset + $limit])) {
        $lastEntry = end($result);
        $lastRequestId = isset($lastEntry['request_id']) ? $lastEntry['request_id'] : null;

        if($lastRequestId !== null) {
            // Keep adding entries from the same request
            $additionalIndex = $offset + $limit;
            while(isset($lines[$additionalIndex])) {
                $nextEntry = $lines[$additionalIndex];
                if(isset($nextEntry['request_id']) && $nextEntry['request_id'] === $lastRequestId) {
                    $result[] = $nextEntry;
                    $additionalIndex++;
                } else {
                    break; // Different request, stop here
                }
            }
        }
    }

    return $result;
}

function passesFilters($entry, $filters) {
    // Session ID filter
    if(!empty($filters['session_id']) && $entry['session_id'] !== $filters['session_id']) {
        return false;
    }

    // Request ID filter
    if(!empty($filters['request_id']) && $entry['request_id'] !== $filters['request_id']) {
        return false;
    }

    // User ID filter
    if($filters['user_id'] !== '' && $entry['user_id'] != $filters['user_id']) {
        return false;
    }

    // Event type filter
    if(!empty($filters['event_type']) && $entry['formulize_event'] !== $filters['event_type']) {
        return false;
    }

    // Form ID filter
    if($filters['form_id'] !== '' && $entry['form_id'] != $filters['form_id']) {
        return false;
    }

    // Screen ID filter
    if($filters['screen_id'] !== '' && $entry['screen_id'] != $filters['screen_id']) {
        return false;
    }

    // Entry ID filter
    if($filters['entry_id'] !== '' && $entry['entry_id'] != $filters['entry_id']) {
        return false;
    }

    // Time range filter (time-of-day in user's local timezone)
    // Convert entry's UTC timestamp to user's local time for comparison
    if(!empty($filters['time_from']) || !empty($filters['time_to'])) {
        // Convert entry's UTC microtime to user's local time
        $entryLocalTime = $entry['microtime'] + $filters['user_timezone_offset'];
        $entryTimeOfDay = date('H:i', (int)$entryLocalTime);

        if(!empty($filters['time_from'])) {
            if($entryTimeOfDay < $filters['time_from']) return false;
        }
        if(!empty($filters['time_to'])) {
            if($entryTimeOfDay > $filters['time_to']) return false;
        }
    }

    // Ignore anonymous users filter (user_id == 0)
    if(!empty($filters['ignore_anonymous']) && $entry['user_id'] == 0) {
        return false;
    }

    return true;
}

function groupBySession($entries, $timezoneOffset = 0) {
    $sessions = array();

    // Get handlers for data enrichment
    $member_handler = xoops_gethandler('member');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');

    // Cache for lookups to avoid repeated queries
    $userCache = array();
    $formCache = array();
    $screenCache = array();
    $entryCache = array();

    foreach($entries as $entry) {
        // Convert UTC timestamp to user's local time
        $entry['microtime'] = $entry['microtime'] + $timezoneOffset;

        $sessionId = $entry['session_id'];
        if(!isset($sessions[$sessionId])) {
            // Lookup username
            $userId = intval($entry['user_id']);
            if(!isset($userCache[$userId])) {
                if($userId > 0) {
                    $user = $member_handler->getUser($userId);
                    if($user) {
                        $uname = $user->getVar('uname');
                        $login_name = $user->getVar('login_name');
                        $userCache[$userId] = $uname ? $uname : $login_name;
                    } else {
                        $userCache[$userId] = 'Unknown User';
                    }
                } else {
                    $userCache[$userId] = 'Anonymous';
                }
            }

            $sessions[$sessionId] = array(
                'session_id' => $sessionId,
                'first_seen' => $entry['microtime'],
                'last_seen' => $entry['microtime'],
                'user_id' => $userId,
                'username' => $userCache[$userId],
                'event_count' => 0,
                'events' => array(),
                'request_count' => 0,
                'requests' => array(),
                'has_errors' => false
            );
        }

        $sessions[$sessionId]['events'][] = $entry;
        $sessions[$sessionId]['event_count']++;
        $sessions[$sessionId]['last_seen'] = max($sessions[$sessionId]['last_seen'], $entry['microtime']);
        $sessions[$sessionId]['first_seen'] = min($sessions[$sessionId]['first_seen'], $entry['microtime']);

        // Check for errors
        if(!empty($entry['PHP_error_number']) || strpos($entry['formulize_event'], 'error') !== false) {
            $sessions[$sessionId]['has_errors'] = true;
        }
    }

    // Sort events within each session chronologically and group by request
    foreach($sessions as &$session) {
        usort($session['events'], function($a, $b) {
            return $a['microtime'] <=> $b['microtime'];
        });

        // Group events by request ID
        $requestGroups = array();
        foreach($session['events'] as $event) {
            $requestId = $event['request_id'];
            if(!isset($requestGroups[$requestId])) {
                // Determine request type
                $url = isset($event['url']) ? $event['url'] : '';
                $isAdmin = (strpos($url, 'modules/formulize/admin/') !== false);
                $isMCP = (strpos($url, '/mcp/') !== false);

                $requestGroups[$requestId] = array(
                    'request_id' => $requestId,
                    'url' => $url,
                    'is_admin' => $isAdmin,
                    'is_mcp' => $isMCP,
                    'first_event_time' => $event['microtime'],
                    'events' => array()
                );
            }

            // Enrich event data with titles
            $enrichedEvent = $event;

            // Form title lookup
            if(isset($event['form_id']) && $event['form_id'] > 0) {
                $formId = intval($event['form_id']);
                if(!isset($formCache[$formId])) {
                    $form = $form_handler->get($formId);
                    $formCache[$formId] = $form ? $form->getVar('title') : '';
                }
                $enrichedEvent['form_title'] = $formCache[$formId];
            }

            // Screen title lookup
            if(isset($event['screen_id']) && $event['screen_id'] > 0) {
                $screenId = intval($event['screen_id']);
                if(!isset($screenCache[$screenId])) {
                    $screen = $screen_handler->get($screenId);
                    $screenCache[$screenId] = $screen ? $screen->getVar('title') : '';
                }
                $enrichedEvent['screen_title'] = $screenCache[$screenId];
            }

            // Entry principal identifier lookup
            if(isset($event['entry_id']) && $event['entry_id'] > 0 && isset($event['form_id']) && $event['form_id'] > 0) {
                $entryId = intval($event['entry_id']);
                $formId = intval($event['form_id']);
                $cacheKey = $formId . '_' . $entryId;

                if(!isset($entryCache[$cacheKey])) {
                    $form = $form_handler->get($formId);
                    if($form && ($principalIdentifierElementId = $form->getVar('pi'))) {
                        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
                        $data_handler = new formulizeDataHandler($formId);
                        $element_handler = xoops_getmodulehandler('elements', 'formulize');

                        if($principalIdentifierValue = $data_handler->getElementValueInEntry($entryId, $principalIdentifierElementId)) {
                            $principalIdentifierElementObject = $element_handler->get($principalIdentifierElementId);
                            $principalIdentifierHandle = $principalIdentifierElementObject->getVar('ele_handle');
                            include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
                            $principalIdentifierValueArray = prepvalues($principalIdentifierValue, $principalIdentifierHandle, $entryId);
                            $entryCache[$cacheKey] = (isset($principalIdentifierValueArray[0]) ? $principalIdentifierValueArray[0] : '');
                        } else {
                            $entryCache[$cacheKey] = '';
                        }
                    } else {
                        $entryCache[$cacheKey] = '';
                    }
                }
                $enrichedEvent['entry_descriptor'] = $entryCache[$cacheKey];
            }

            $requestGroups[$requestId]['events'][] = $enrichedEvent;
        }

        $session['requests'] = array_values($requestGroups);
        $session['request_count'] = count($requestGroups);
    }

    return $sessions;
}
