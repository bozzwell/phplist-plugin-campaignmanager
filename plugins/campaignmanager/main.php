<?php
/**
 * Campaign Manager main page
 * 
 * This page displays all campaigns and allows managing them
 */

// Don't allow direct access
if (!defined('PHPLISTINIT')) {
    die('Invalid access');
}

// Process actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $messageId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($messageId > 0) {
        switch ($action) {
            case 'pause':
                // Pause a campaign
                Sql_Query(sprintf('UPDATE %s SET status = "suspended" WHERE id = %d', $GLOBALS['tables']['message'], $messageId));
                $actionResult = "Campaign $messageId has been paused";
                break;
                
            case 'resume':
                // Resume a campaign
                Sql_Query(sprintf('UPDATE %s SET status = "inprocess" WHERE id = %d', $GLOBALS['tables']['message'], $messageId));
                $actionResult = "Campaign $messageId has been resumed";
                break;
                
            case 'stop':
                // Stop a campaign
                Sql_Query(sprintf('UPDATE %s SET status = "cancelled" WHERE id = %d', $GLOBALS['tables']['message'], $messageId));
                $actionResult = "Campaign $messageId has been stopped";
                break;
                
            default:
                $actionResult = "Unknown action: $action";
        }
    }
}

// Get all campaigns
$query = sprintf('
    SELECT 
        m.id, 
        m.subject, 
        m.status, 
        m.sent, 
        m.processed, 
        m.sendstart, 
        m.sendend,
        COUNT(um.messageid) AS total,
        SUM(CASE WHEN um.status = "sent" THEN 1 ELSE 0 END) AS sent_count,
        SUM(CASE WHEN um.status = "todo" THEN 1 ELSE 0 END) AS todo_count,
        SUM(CASE WHEN um.status = "failed" THEN 1 ELSE 0 END) AS failed_count
    FROM %s AS m
    LEFT JOIN %s AS um ON m.id = um.messageid
    GROUP BY m.id
    ORDER BY m.sendstart DESC
',
    $GLOBALS['tables']['message'],
    $GLOBALS['tables']['usermessage']
);

$result = Sql_Query($query);
$campaigns = array();

while ($row = Sql_Fetch_Assoc($result)) {
    $campaigns[] = $row;
}

// Display the page
echo '<h1>Campaign Manager</h1>';

if (isset($actionResult)) {
    echo '<div class="actionresult">' . htmlspecialchars($actionResult) . '</div>';
}

echo '<div class="note">This plugin allows you to manage your campaigns, view their status, and control them.</div>';

// Display campaigns table
echo '<table class="campaignlist">';
echo '<tr>
        <th>ID</th>
        <th>Subject</th>
        <th>Status</th>
        <th>Progress</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Actions</th>
      </tr>';

foreach ($campaigns as $campaign) {
    $id = $campaign['id'];
    $subject = htmlspecialchars($campaign['subject']);
    $status = htmlspecialchars($campaign['status']);
    $sendstart = $campaign['sendstart'] ? date('Y-m-d H:i:s', strtotime($campaign['sendstart'])) : 'Not started';
    $sendend = $campaign['sendend'] ? date('Y-m-d H:i:s', strtotime($campaign['sendend'])) : 'Not finished';
    
    // Calculate progress
    $total = intval($campaign['total']);
    $sent = intval($campaign['sent_count']);
    $progress = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
    
    // Determine available actions based on status
    $actions = '';
    if ($status == 'inprocess') {
        $actions .= '<form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="'.$id.'">
                      <input type="hidden" name="action" value="pause">
                      <input type="submit" value="Pause" class="button">
                     </form> ';
        $actions .= '<form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="'.$id.'">
                      <input type="hidden" name="action" value="stop">
                      <input type="submit" value="Stop" class="button">
                     </form>';
    } elseif ($status == 'suspended') {
        $actions .= '<form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="'.$id.'">
                      <input type="hidden" name="action" value="resume">
                      <input type="submit" value="Resume" class="button">
                     </form> ';
        $actions .= '<form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="'.$id.'">
                      <input type="hidden" name="action" value="stop">
                      <input type="submit" value="Stop" class="button">
                     </form>';
    } elseif ($status == 'prepared' || $status == 'draft') {
        $actions = 'N/A - Not started';
    } else {
        $actions = 'N/A - Completed/Cancelled';
    }
    
    echo "<tr>
            <td>$id</td>
            <td>$subject</td>
            <td>$status</td>
            <td>$progress% ($sent/$total)</td>
            <td>$sendstart</td>
            <td>$sendend</td>
            <td>$actions</td>
          </tr>";
}

echo '</table>';

// Add some styling
echo '<style>
    .campaignlist {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .campaignlist th, .campaignlist td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .campaignlist th {
        background-color: #f2f2f2;
    }
    .campaignlist tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .button {
        padding: 5px 10px;
        margin: 2px;
        cursor: pointer;
    }
    .actionresult {
        padding: 10px;
        margin: 10px 0;
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
        color: #3c763d;
        border-radius: 4px;
    }
    .note {
        padding: 10px;
        margin: 10px 0;
        background-color: #d9edf7;
        border: 1px solid #bce8f1;
        color: #31708f;
        border-radius: 4px;
    }
</style>';
