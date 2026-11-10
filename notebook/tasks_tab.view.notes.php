<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $notebookFilter, $notebookPageUrl, $AppUI, $m, $obj, $task_id, $showCompany;
$showCompany = false;
if (canView('notebook')) {
	if (canAdd('notebook')) {
		echo '<a class="btn btn-primary btn-sm mb-2" href="./index.php?m=notebook&a=addedit&project_id=' . (int) $obj->task_project . '&task_id=' . (int) $task_id . '">' . $AppUI->_('Add Note') . '</a>';
	}
	$project_id = $obj->task_project;
	$notebookFilter = array('task' => (int) $task_id);
	$notebookPageUrl = 'tasks&a=view&task_id=' . (int) $task_id;   // page links come back to this tab
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}