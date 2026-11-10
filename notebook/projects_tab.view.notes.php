<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $notebookPageUrl, $AppUI, $m, $project_id, $deny, $canRead, $canEdit, $w2Pconfig, $showCompany;
$showCompany = false;
if (canView('notebook')) {
	if (canEdit('notebook')) {
		echo '<a href="./index.php?m=notebook&a=addedit&project_id=' . $project_id . '">' . $AppUI->_('Add Note') . '</a>';
		echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
	}
	$notebookPageUrl = 'projects&a=view&project_id=' . (int) $project_id;   // page links come back to this tab
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}