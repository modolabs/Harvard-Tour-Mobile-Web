<?php

/**
  * TransitNewsDataParser
  * @package Transit
  */

class TransitNewsDataParser extends RSSDataParser
{
    protected function shouldStripTags($element) {
        $strip_tags = true;
        switch ($element->name()) {
            case 'DESCRIPTION':
                $strip_tags = false;
                break;
                
            default:
                $strip_tags = parent::shouldStripTags($element);
                break;
        }
        
        return $strip_tags;
    }
}
