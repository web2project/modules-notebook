<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$company_id = $AppUI->processIntState('NoteIdxCompany', $_POST, 'company_id', 0);
$project_id = $AppUI->processIntState('NoteIdxProject', $_POST, 'project_id', 0);
$note_status = $AppUI->processIntState('NoteIdxStatus', $_POST, 'note_status', -1);
if (isset($_POST['search_string'])) {
	$AppUI->setState('NoteIdxSearch', trim((string) w2PgetParam($_POST, 'search_string', '')));
}
$search_string = (string) $AppUI->getState('NoteIdxSearch');

// the list (index_table.php) is filtered by these
$notebookFilter = array('company' => $company_id, 'project' => $project_id,
	'status' => $note_status, 'search' => $search_string);


if (w2PgetParam($_GET, 'tab', -1) != -1) {
	$AppUI->setState('NoteIdxTab', w2PgetParam($_GET, 'tab'));
}
$tab = $AppUI->getState('NoteIdxTab') !== null ? $AppUI->getState('NoteIdxTab') : 0;
$active = intval(!$AppUI->getState('NoteIdxTab'));

// the companies with notes, filed on the company or on one of its projects
$extra = array('from' => 'notes', 'where' => '(companies.company_id = note_company OR companies.company_id IN (SELECT project_company FROM projects WHERE project_id = note_project))');

$company = new CCompany();
$companies = $company->getAllowedRecords($AppUI->user_id, 'companies.company_id,company_name', 'company_name', null, $extra, 'companies');
$companies = arrayMerge(array('0' => $AppUI->_('All', UI_OUTPUT_JS)), $companies);

// the projects with notes
$extra = array('from' => 'notes', 'where' => 'projects.project_id = note_project');

$project = new CProject();
$projects = $project->getAllowedRecords($AppUI->user_id, 'projects.project_id,project_name', 'project_name', null, $extra, 'projects');
$projects = arrayMerge(array('0' => $AppUI->_('All', UI_OUTPUT_JS)), $projects);

$status = w2PgetSysVal('NoteStatus');
$status = arrayMerge(array('-1' => $AppUI->_('All', UI_OUTPUT_JS)), $status);


$notebook = new CNotebook();
if (!$notebook->canAccess()) {
	$AppUI->redirect(ACCESS_DENIED);
}
$canCreate = $notebook->canCreate();

// setup the title block
$titleBlock = new w2p_Theme_TitleBlock('Notebook', 'notebook.png', $m, $m . '.' . $a);
$titleBlock->addSearchCell(htmlspecialchars($search_string, ENT_QUOTES));
$titleBlock->addFilterCell('Company', 'company_id', $companies, $company_id);
$titleBlock->addFilterCell('Project', 'project_id', $projects, $project_id);
$titleBlock->addFilterCell('Status', 'note_status', $status, $note_status);

if ($canCreate) {
	$titleBlock->addCell('<input type="submit" class="button" value="' . $AppUI->_('new note') . '">', '', '<form action="?m=notebook&a=addedit" method="post">', '</form>');
}
$titleBlock->show();

// One tab for all notes, then one per category. index_table.php reads
//   $noteCategoryTabs to know which category each tab shows.
$noteCategoryTabs = array(-1 => 'All Notes') + w2PgetSysVal('NoteCategory');

$tabBox = new CTabBox('?m=notebook', W2P_BASE_DIR . '/modules/notebook/', $tab);
foreach ($noteCategoryTabs as $note_type) {
	$tabBox->add('index_table', $note_type);
}
$tabBox->show();
