<?php /* $Id: companies_tab.view.notes.php 181 2010-12-29 16:20:58Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/companies_tab.view.notes.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $notebookPageUrl, $AppUI, $m, $company_id, $deny, $canRead, $canEdit, $w2Pconfig, $showCompany;
$showCompany = false;
if (canView('notebook')) {
	if (canEdit('notebook')) {
		echo '<a href="./index.php?m=notebook&a=addedit&company_id=' . $company_id . '">' . $AppUI->_('Add Note') . '</a>';
		echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
	}
	$notebookPageUrl = 'companies&a=view&company_id=' . (int) $company_id;   // page links come back to this tab
	include (W2P_BASE_DIR . '/modules/notebook/index_table.php');
}