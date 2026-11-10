<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $AppUI, $m, $obj, $user_id, $company_id, $showCompany, $notebookCreator;

if (canView('notebook')) {
	// a user's Notes tab lists the notes that user wrote
	if (canEdit('notebook') && (int) $user_id == (int) $AppUI->user_id) {
		echo '<a href="./index.php?m=notebook&a=addedit">' . $AppUI->_('Add Note') . '</a>';
		echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
	}
	$company_id = 0;
	$notebookCreator = (int) $user_id;

    $m = 'users&a=view&user_id=' . $user_id;                        // override to correct pagination issue
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}
