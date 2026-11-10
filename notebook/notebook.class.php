<?php /* $Id: notebook.class.php 374 2012-06-26 07:35:45Z caseydk $ $URL: svn+ssh://caseydk@svn.code.sf.net/p/web2project-mod/code/notebook/trunk/notebook.class.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

class CNotebook extends w2p_Core_BaseObject
{

	public $note_id = null;
	public $note_parent = null;
	public $note_company = null;
	public $note_department = null;
	public $note_project = null;
	public $note_task = null;
	public $note_file = null;
	public $note_module = null;
	public $note_module_name = null;
	public $note_record_id = null;
	public $note_category = null;
	public $note_status = null;
	public $note_name = null;
	public $note_body = null;
	public $note_creator = null;
	public $note_created = null;
	public $note_modified = null;
	public $note_modified_by = null;
	public $note_private = null;
	public $note_doc_url = null;

	public function __construct()
	{
		parent::__construct('notes', 'note_id', 'notebook');
	}

    public function isValid()
    {
        $baseErrorMsg = get_class($this) . '::store-check failed - ';

        if ('' == trim($this->note_name)) {
            $this->_error['note_name'] = $baseErrorMsg . 'note name is not set';
        }
        if ('' == trim($this->note_body)) {
            $this->_error['note_body'] = $baseErrorMsg . 'note body is not set';
        }

        return (count($this->_error)) ? false : true;
    }

    public function hook_search()
    {
        $search['table'] = 'notes';
        $search['table_alias'] = 'n';
        $search['table_module'] = 'notebook';
        $search['table_key'] = 'note_id'; // primary key in searched table
        $search['table_link'] = 'index.php?m=notebook&a=view&note_id='; // first part of link
        $search['table_title'] = 'Notebook';
        $search['table_orderby'] = 'note_name';
        $search['search_fields'] = array('n.note_name', 'n.note_body', 'n.note_doc_url');
        $search['display_fields'] = array('n.note_name', 'n.note_body', 'n.note_doc_url');
        $search['table_extra'] = implode(' AND ', $this->getVisibleSQL($this->_AppUI->user_id));

        return $search;
    }

    protected function hook_preCreate()
    {
        $q = $this->_getQuery();
        $this->note_created = $q->dbfnNowWithTZ();
        $this->note_creator = (int) $this->_AppUI->user_id;

        parent::hook_preCreate();
    }

    protected function hook_preStore()
    {
        // A note on a task belongs to the task's project, and a note on a
        //   project to the project's company.
        if ((int) $this->note_task) {
            $task = new CTask();
            if ($task->load((int) $this->note_task)) {
                $this->note_project = (int) $task->task_project;
            }
        }
        if ((int) $this->note_project) {
            $project = new CProject();
            if ($project->load((int) $this->note_project)) {
                $this->note_company = (int) $project->project_company;
            }
        }

        $q = $this->_getQuery();
        $this->note_modified = $q->dbfnNowWithTZ();
        $this->note_modified_by = $this->_AppUI->user_id;

        parent::hook_preStore();
    }

    /**
     * Turns a note body written with the old TinyMCE editor into plain text.
     *   Bodies with no HTML tags are returned unchanged, so a plain note that
     *   happens to contain "<" is left alone.
     *
     * @param string $body
     *
     * @return string
     */
    public static function htmlToText($body)
    {
        $body = (string) $body;
        $tags = 'p|br|div|span|ul|ol|li|strong|b|em|i|u|s|strike|sub|sup|h[1-6]|table|thead|tbody|tr|td|th|a|img|hr|blockquote|pre|font|address|del|ins|abbr|acronym|cite';
        if (!preg_match('#</?(' . $tags . ')\b[^>]*>#i', $body)) {
            return $body;
        }

        // links keep their address: "text (https://...)"
        $body = preg_replace_callback('#<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)</a>#is', function ($m) {
            $text = trim(strip_tags($m[2]));
            $href = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return ('' == $text || $text == $href) ? $href : $text . ' (' . $href . ')';
        }, $body);
        $body = preg_replace('#[\r\n]+#', ' ', $body);
        $body = preg_replace('#<br\s*/?>#i', "\n", $body);
        $body = preg_replace('#<li\b[^>]*>#i', "\n- ", $body);
        $body = preg_replace('#</(td|th)>#i', "\t", $body);
        $body = preg_replace('#<hr\b[^>]*>#i', "\n----\n", $body);
        $body = preg_replace('#</(p|div|h[1-6]|ul|ol|table|tr|blockquote|pre|address)>#i', "\n\n", $body);
        $body = strip_tags($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = str_replace("\xc2\xa0", ' ', $body);
        $body = preg_replace('#[ \t]+\n#', "\n", $body);
        $body = preg_replace('#\n[ \t]+(?!- )#', "\n", $body);
        $body = preg_replace("#\n{3,}#", "\n\n", $body);

        return trim($body);
    }

    public function loadAll($order = null, $where = null)
    {
        $q = $this->_getQuery();
        $q->addQuery('notes.*');
        $q->addTable($this->_tbl);
        $q->addOrder('note_modified');

        if ($where) {
            $q->addWhere($where);
        }

        $q->leftJoin('users', 'u', 'user_id = note_creator');
        $q->leftJoin('contacts', 'c', 'user_contact = contact_id');
        $q->addQuery('contact_first_name, contact_last_name');

        foreach ($this->getVisibleSQL($this->_AppUI->user_id) as $criteria) {
            $q->addWhere($criteria);
        }

        return $q->loadHashList($this->_tbl_key);
    }

    /**
     * The conditions a note must meet for a user to see it: a note on a
     *   project or company is only visible to users who can see that project
     *   or company, and a private note only to its creator.
     *
     * @param int $uid
     *
     * @return array<int, string> SQL conditions on the notes table
     */
    public function getVisibleSQL($uid)
    {
        $uid = (int) $uid;
        static $cache = array();
        if (!isset($cache[$uid])) {
            $project = new CProject();
            $projects = $project->getAllowedRecords($uid, 'projects.project_id,project_name', '', null, null, 'projects');
            $company = new CCompany();
            $companies = $company->getAllowedRecords($uid, 'companies.company_id,company_name', '', null, null, 'companies');

            $projectIds = count($projects) ? implode(',', array_map('intval', array_keys($projects))) : '0';
            $companyIds = count($companies) ? implode(',', array_map('intval', array_keys($companies))) : '0';
            // Private notes are for their creator only. A private note whose
            //   creator isn't known (from before 5.0) stays visible to the
            //   system administrators so it can't be lost.
            $private = '(note_private = 0 OR note_creator = ' . $uid;
            if ($uid == (int) $this->_AppUI->user_id && canEdit('system')) {
                $private .= ' OR note_creator = 0';
            }
            $private .= ')';

            $cache[$uid] = array(
                "(note_project = 0 OR note_project IN ($projectIds))",
                "(note_company = 0 OR note_company IN ($companyIds))",
                $private,
            );
        }

        return $cache[$uid];
    }

    /**
     * Whether the current user may see this (saved) note, by getVisibleSQL().
     *   A note that isn't saved yet is always visible.
     *
     * @return bool
     */
    protected function isVisible()
    {
        $id = (int) $this->{$this->_tbl_key};
        if (!$id) {
            return true;
        }

        $q = new w2p_Database_Query();
        $q->addTable($this->_tbl);
        $q->addQuery('COUNT(*)');
        $q->addWhere($this->_tbl_key . ' = ' . $id);
        foreach ($this->getVisibleSQL($this->_AppUI->user_id) as $criteria) {
            $q->addWhere($criteria);
        }

        return (bool) $q->loadResult();
    }

    public function canView()
    {
        return parent::canView() && $this->isVisible();
    }

    public function canEdit()
    {
        return parent::canEdit() && $this->isVisible();
    }

    public function canDelete($notUsed = '', $oid = null, $joins = null)
    {
        if ($oid) {
            $this->{$this->_tbl_key} = (int) $oid;
        }
        if (!$this->isVisible()) {
            $this->_error['noDeletePermission'] = $this->_AppUI->_('noDeletePermission');
            return false;
        }

        return parent::canDelete($notUsed, $oid, $joins);
    }
}