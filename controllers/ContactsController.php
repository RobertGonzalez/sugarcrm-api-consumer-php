<?php
class ContactsController extends AbstractController {
    public function listAction() {
        $this->headings = array(
            'link_name' => 'Name',
            'location' => 'Location',
            'has_image' => 'Picture?',
        );

        $res = $this->_getApi()->getList($this->module);
        $rows = array();
        foreach ($res as $row) {
            $row['has_image'] = !empty($row['picture']) ? 'Y' : '';
            $row['location'] = $row['primary_address_city'] . ', ' . $row['primary_address_state'];
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['link_name'] = '<a href="' . $row['detail'] . '">' . $row['full_name'] . '</a>';
            $rows[] = $row;
        }

        $this->rows = $rows;
    }

    public function detailAction() {
        $this->template = 'DetailContact';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->_uploadAttachment();
        }
        $this->_setContact();
    }

    public function removedocAction() {
        $this->template = 'DetailContact';
        parent::removedocAction();
        $this->_setContact();
    }

    protected function _setContact() {
        //$contact = $this->_getApi()->getRecord($this->module, $this->id);
        //$this->contact = (object) $contact;
        $this->contact = $this->_getRecord();
    }
}