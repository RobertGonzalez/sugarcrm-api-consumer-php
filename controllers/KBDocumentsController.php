<?php
class KBDocumentsController extends AbstractController {
    public function listAction() {
        $this->headings = array(
            'link_name' => 'Document Name',
            'attachments' => 'Attachments',
        );
        
        $res = $this->_getApi()->getList($this->module);
        $rows = array();
        foreach ($res as $row) {
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['link_name'] = '<a href="' . $row['detail'] . '">' . $row['kbdocument_name'] . '</a>';
            
            // Fetch the related revisions to scrape attachments
            $atts = '';
            $revs = $this->_getApi()->getRelatedRecords($this->module, $row['id'], 'revisions');
            foreach ($revs as $rev) {
                if (empty($rev['kbcontent_id'])) {
                    // This is an attachment record, get it's info
                    $info = $this->_getApi()->getAttachmentInfo('KBDocumentRevisions', $rev['id']);
                    if (!empty($info)) {
                        $field = key($info);
                        $atts .= '<a href="?action=download&dlmodule=KBDocumentRevisions&id=' . $rev['id'] . '&field=' . $field . '">' . $info[$field]['name'] . '</a><br />';
                    }
                }
            }
            
            $row['attachments'] = $atts;
            $rows[] = $row;
        }

        $this->rows = $rows;
    }
    
    public function detailAction() {
        $this->template = 'DetailKBDocument';
        $document = $this->_getRecord();
        $revs = $this->_getApi()->getRelatedRecords($this->module, $this->id, 'revisions');
        $atts = array();
        foreach ($revs as $rev) {
            if (empty($rev['kbcontent_id'])) {
                // This is an attachment record, get it's info
                $info = $this->_getApi()->getAttachmentInfo('KBDocumentRevisions', $rev['id']);
                if (!empty($info)) {
                    $field = key($info);
                    $info[$field]['link'] = '<a href="?action=download&dlmodule=KBDocumentRevisions&id=' . $rev['id'] . '&field=' . $field . '">' . $info[$field]['name'] . '</a><br />';
                }
                
                $atts += $info;
            }
        }
        
        $this->document = $document;
        $this->attachments = $atts;
    }
}
