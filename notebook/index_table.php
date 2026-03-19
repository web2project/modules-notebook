<?php /* $Id: index_table.php 374 2012-06-26 07:35:45Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/index_table.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $AppUI, $deny1, $canRead, $canEdit;
global $notebook, $company_id, $project_id, $task_id, $user_id, $note_status, $showCompany, $m, $tab, $search_string;
global $company;

$company_id = (int) w2PgetParam($_REQUEST, 'company_id', 0);
$project_id = (int) w2PgetParam($_REQUEST, 'project_id', 0);
$task_id = (int) w2PgetParam($_REQUEST, 'task_id', 0);
$note_status = (int) w2PgetParam($_REQUEST, 'note_status', 0);

$page = (int) w2PgetParam($_REQUEST, 'page', 1);
$search = w2PgetParam($_REQUEST, 'search_string', '');

$project = new CProject();
$task = new CTask();

$notebook = new CNotebook();
$where = [];

if ($company_id) {
	$where[] = "note_company = $company_id";
}
if ($project_id) {
	$where[] = "note_project = $project_id";
}
if ($task_id) {
	$where[] = "note_task = $task_id";
}
if ($note_status > 0) {
	$where[] = "note_status = $note_status";
}
if ('' !== $search) {
	$where[] = "(note_name LIKE '%$search%' OR note_body LIKE '%$search%')";
}

$filter = implode(" AND ", $where);
$items = $notebook->loadAll(null, $filter);

$module = new w2p_System_Module();
$fields = $module->loadSettings('notebook', 'index_list');

if (0 == count($fields)) {
    $fieldList = array('note_name', 'note_category', 'note_status', 'note_project', 'note_task', 'note_creator', 'note_created');
    $fieldNames = array('Note Title', 'Category', 'Status', 'Project', 'Task', 'Creator', 'Date');

	$module->storeSettings('notebook', 'index_list', $fieldList, $fieldNames);
    $fields = array_combine($fieldList, $fieldNames);
}

$xpg_pagesize = w2PgetConfig('page_size', 50);
$xpg_min = $xpg_pagesize * ($page - 1); // This is where we start our record set from
// counts total recs from selection
$xpg_totalrecs = count($items);
$items = array_slice($items, $xpg_min, $xpg_pagesize);

$pageNav = buildPaginationNav($AppUI, $m, $tab, $xpg_totalrecs, $xpg_pagesize, $page);
echo $pageNav;

$note_category = w2PgetSysVal('NoteCategory');
$note_status = w2PgetSysVal('NoteStatus');
$customLookups = array('note_category' => $note_category, 'note_status' => $note_status);

$listTable = new w2p_Output_ListTable($AppUI);
echo $listTable->startTable('notebook');
echo $listTable->buildHeader($fields);
/*
 * Begin: I hate that we have to capture the result and do a string replace but this doesn't follow the naming convention
*/
$_output = $listTable->buildRows($items, $customLookups);
echo str_replace('m=notes', 'm=notebook', $_output);
/*
 * End
 */
echo $listTable->endTable();
echo $pageNav;