<?php
class NotesController extends AbstractController {
    public function listAction() {
        $this->headings = array(
            'link_name' => 'Subject',
            //'date_entered' => 'Date Entered',
            'download' => 'Attachment',
        );

        $res = $this->_getApi()->getList($this->module);
        $rows = array();
        foreach ($res as $row) {
            $row['has_doc'] = !empty($row['filename']) ? 'Y' : '';
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['link_name'] = '<a href="' . $row['detail'] . '">' . $row['name'] . ' - ' . $row['description'] . '</a>';
            $row['download'] = '';
            if ($row['has_doc']) {
                $row['download'] = '<a href="?action=download&id=' . $row['id'] . '&field=filename">' . $row['filename'] . '</a>';
            }

            $rows[] = $row;
        }

        $this->rows = $rows;
    }

    public function detailAction() {
        $this->template = 'DetailNote';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->_uploadAttachment();
        }
        $this->_setNote();
    }

    public function removedocAction() {
        $this->template = 'DetailNote';
        parent::removedocAction();
        $this->_setNote();
    }

    protected function _setNote() {
        $note = $this->_getApi()->getRecord($this->module, $this->id);
        $this->note = (object) $note;
    }
}