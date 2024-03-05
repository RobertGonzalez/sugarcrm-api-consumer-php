<?php
class DocumentsController extends AbstractController {
    /*
    protected $blacklist = array(
        'detail' => array('id', 'team_count', 'team_name'),
        'edit' => array('id', 'team_count', 'team_name'),
    );
    */

    protected function getListRows($data)
    {
        $rows = array();
        foreach ($data as $row) {
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['document_name'] = '<a href="' . $row['detail'] . '">' . $row['document_name'] . '</a>';
            $rows[] = $row;
        }
        
        return $rows;
    }

    /**
     * The action for handling viewing a record. It's the same as edit, except
     * the rendered template is not a form.
     */
    public function detailAction() {
        $this->template = 'DetailDocument';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->_uploadAttachment();
        }
        parent::detailAction();
    }

    public function removedocAction() {
        $this->template = 'DetailDocument';
        parent::removedocAction();
        $this->setBean();
    }
}
