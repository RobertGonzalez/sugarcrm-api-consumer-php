<?php
class HelpController extends AbstractController
{
    public function listAction()
    {
        $this->template = 'HelpList';
        $this->headings = array(
            'endpoint' => 'Endpoint',
            'desc' => 'Description',
        );

        $this->from = empty($_REQUEST['from']) ? $this->getDefaultModule() : $_REQUEST['from'];

        $rows = array();
        $reply = $this->_getApi()->call("help");
        $content = $reply['replyRaw'];
        //$pattern = '@<div class="span4">(\s*)(GET|POST|PUT|DELETE)(\s*)<span class="btn-link" type="button" data-toggle="collapse" data-target="#endpoint_([0-9]*)_full">(\s*)(.*)(\s*)</span>@';
        $pattern = '@<div class="span4">(\s*)(GET|POST|PUT|DELETE)(\s*)<span class="btn-link" type="button" data-toggle="collapse" data-target="#endpoint_([0-9]*)_full">(\s*)(.*)(\s*)</span>(\s*)</div>(\s*)<div class="span2">(\s*)(.*)(\s*)</div>(\s+)<div class="span3">(\s+)(.*)(\s+)</div>@';
        $matches = array();
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        // 2 = method, 4 = index, 6 = endpoint, 15 = desc
        foreach ($matches as $match) {
            $rows[] = array(
                'id' => trim($match[4]),
                'endpoint' => trim($match[2]) . ' ' . trim($match[6]),
                'desc' => trim($match[15]),
            );
        }

        $this->rows = $rows;
    }

    public function homeAction()
    {
        $_SESSION['module'] = empty($_REQUEST['goto']) ? $this->getDefaultModule() : $_REQUEST['goto'];
        $this->_redirect();
    }
}
