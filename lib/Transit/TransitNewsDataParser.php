<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
