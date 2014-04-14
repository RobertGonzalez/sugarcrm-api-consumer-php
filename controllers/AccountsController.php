<?php
class AccountsController extends AbstractController {
    public function listAction() {
        /*
        $this->headings = array(
            'link_name' => 'Company Name',
            //'date_entered' => 'Date Entered',
            'team_name' => 'Team',
        );
        */
        $this->headings = $this->getListHeadings();

        $res = $this->_getApi()->getList($this->module);
        $rows = array();
        foreach ($res as $row) {
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['name'] = '<a href="' . $row['detail'] . '">' . $row['name'] . '</a>';
            $rows[] = $row;
        }

        $this->rows = $rows;
    }
}
