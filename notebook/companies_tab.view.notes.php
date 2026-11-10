<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $notebookFilter, $AppUI, $m, $company_id;
if (canView('notebook')) {
	if (canAdd('notebook')) {
		echo '<a class="btn btn-primary btn-sm mb-2" href="./index.php?m=notebook&a=addedit&company_id=' . $company_id . '">' . $AppUI->_('Add Note') . '</a>';
	}
	$notebookFilter = array('company' => (int) $company_id);
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}