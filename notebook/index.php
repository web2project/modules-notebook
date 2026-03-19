<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$company_id = $AppUI->processIntState('NoteIdxCompany', $_POST, 'company_id', 0);
$project_id = $AppUI->processIntState('NoteIdxProject', $_POST, 'project_id', 0);
$note_status = $AppUI->processIntState('NoteIdxStatus', $_POST, 'note_status', -1);


if (w2PgetParam($_GET, 'tab', -1) != -1) {
	$AppUI->setState('NoteIdxTab', w2PgetParam($_GET, 'tab'));
}
$tab = $AppUI->getState('NoteIdxTab') !== null ? $AppUI->getState('NoteIdxTab') : 0;
$active = intval(!$AppUI->getState('NoteIdxTab'));

// get the list of visible companies
$extra = array('from' => 'notes', 'where' => 'companies.company_id = note_company');

$company = new CCompany();
$companies = $company->getAllowedRecords($AppUI->user_id, 'companies.company_id,company_name', 'company_name', null, $extra, 'companies');
$companies = arrayMerge(array('0' => $AppUI->_('All', UI_OUTPUT_JS)), $companies);

// get the list of visible companies
$extra = array('from' => 'notes', 'where' => 'projects.project_id = note_project');

$project = new CProject();
$projects = $project->getAllowedRecords($AppUI->user_id, 'projects.project_id,project_name', 'project_name', null, $extra, 'projects');
$projects = arrayMerge(array('0' => $AppUI->_('All', UI_OUTPUT_JS)), $projects);

$status = w2PgetSysVal('NoteStatus');
$status = arrayMerge(array('-1' => $AppUI->_('All', UI_OUTPUT_JS)), $status);

$search_string = w2PgetParam($_POST, 'search_string', '');
$search_string = w2PformSafe($search_string, true);

$notebook = new CNotebook();
$canCreate = $notebook->canCreate();

// setup the title block
$titleBlock = new w2p_Theme_TitleBlock('Notebook', 'notebook.png', $m, $m . '.' . $a);
$titleBlock->addSearchCell($search_string);
$titleBlock->addFilterCell('Company', 'company_id', $companies, $company_id);
$titleBlock->addFilterCell('Project', 'project_id', $projects, $project_id);
$titleBlock->addFilterCell('Status', 'note_status', $status, $note_status);

if ($canCreate) {
	$titleBlock->addCell('<input type="submit" class="button" value="' . $AppUI->_('new note') . '">', '', '<form action="?m=notebook&a=addedit" method="post">', '</form>');
}
$titleBlock->show();

$note_types = w2PgetSysVal('NoteCategory');
if ($tab != -1) {
	array_unshift($note_types, 'All Notes');
}
array_map(array($AppUI, '_'), $note_types);

$tabBox = new CTabBox('?m=notebook', W2P_BASE_DIR . '/modules/notebook/', $tab);

$i = 0;

foreach ($note_types as $note_type) {
	$tabBox->add('index_table', $note_type);
	++$i;
}

$tabBox->show();