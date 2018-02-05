<?php
namespace Komparu\Translation;

use Illuminate\Translation\Translator as LaravelTranslator;
use App;

class Translator extends LaravelTranslator{

    public function setConditions($conditions){

        /** @var \Komparu\Translation\ApiLoader $loader */
        $loader = app('translation.loader');
        $loader->setConditions($conditions);
        $this->loaded = [];
    }

}
