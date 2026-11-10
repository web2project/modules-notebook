<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $notebookFilter, $notebookPageUrl, $AppUI, $m, $obj, $user_id, $company_id, $showCompany;

if (canView('notebook')) {
	// a user's Notes tab lists the notes that user wrote
	if (canEdit('notebook') && (int) $user_id == (int) $AppUI->user_id) {
		echo '<a href="./index.php?m=notebook&a=addedit">' . $AppUI->_('Add Note') . '</a>';
		echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
	}
	$company_id = 0;
	$notebookFilter = array('creator' => (int) $user_id);
	$notebookPageUrl = 'users&a=view&user_id=' . (int) $user_id;   // page links come back to this tab
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}
