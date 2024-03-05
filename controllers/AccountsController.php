<?php
class AccountsController extends AbstractController {
    protected function getListRows($data)
    {
        $rows = array();
        foreach ($data as $row) {
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['name'] = '<a href="' . $row['detail'] . '">' . $row['name'] . '</a>';
            $rows[] = $row;
        }
        
        return $rows;
    }
}
