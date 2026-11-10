<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $notebookFilter, $notebookPageUrl, $AppUI, $m, $project_id;
if (canView('notebook')) {
	if (canAdd('notebook')) {
		echo '<a class="btn btn-primary btn-sm mb-2" href="./index.php?m=notebook&a=addedit&project_id=' . $project_id . '">' . $AppUI->_('Add Note') . '</a>';
	}
	$notebookFilter = array('project' => (int) $project_id);
	$notebookPageUrl = 'projects&a=view&project_id=' . (int) $project_id;   // page links come back to this tab
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}