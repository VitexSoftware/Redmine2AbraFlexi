<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Redmine2FlexiBee\ui;
/**
 * Description of SearchBox
 *
 * @author vitex
 */
class SearchBox extends \Ease\Html\InputSearchTag
{

        public function afterAdd($parent)
    {
        $parent->addItem(new \Ease\Html\DatalistTag(null,
                ['id' => 'json-datalist']));

        $this->setTagProperties([]);
        
        $this->includeJavaScript('js/remote-list.js');
        $this->addJavaScript('
$(\'#'.$this->getTagId().'\').remoteList({
	minLength: 2,
	maxLength: 10,
	select: function(){
		if(window.console){
			console.log($(this).remoteList(\'selectedOption\'), $(this).remoteList(\'selectedData\'))
		}
	}
});
            
            ');
    }

}
