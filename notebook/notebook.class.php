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

        return $search;
    }

    protected function hook_preCreate()
    {
        $q = $this->_getQuery();
        $this->note_created = $q->dbfnNowWithTZ();

        parent::hook_preCreate();
    }

    protected function hook_preStore()
    {
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

        // TODO: respect permissions

        return $q->loadHashList($this->_tbl_key);
    }
}