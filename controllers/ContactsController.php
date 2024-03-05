<?php
class ContactsController extends AbstractController {
    protected function getListRows($data)
    {
        $rows = array();
        foreach ($data as $row) {
            $row['has_image'] = !empty($row['picture']) ? 'Y' : '';
            $row['location'] = $row['primary_address_city'] . ', ' . $row['primary_address_state'];
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['full_name'] = '<a href="' . $row['detail'] . '">' . $row['full_name'] . '</a>';
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Gets list view columns from the list metadata. This will parse the labels
     * for each field as part of the process.
     *
     * @return array
     */
    protected function getListHeadings()
    {
        $headings = parent::getListHeadings();
        $headings['location'] = 'Location';
        $headings['has_image'] = 'Picture?';
        return $headings;
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
