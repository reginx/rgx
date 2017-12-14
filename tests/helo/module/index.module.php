<?php
namespace com\www_default;
use \re\rgx as R;

class index_module extends R\module {

    public function index_action () {
        $this->display('index.tpl');
    }

}