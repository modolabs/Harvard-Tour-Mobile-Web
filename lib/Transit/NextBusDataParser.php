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
  * NextBusDataParser
  * @package Transit
  */

// TODO: Make this use XMLDataParser?
class NextBusDataParser extends DataParser
{
    public function parseData($data) {
        if ($data) {
            $xml = new DOMDocument();
            $xml->loadXML($data);
            
            $errorCount = 0;
            foreach ($xml->getElementsByTagName('Error') as $error) {
                Kurogo::log(LOG_ERR, 'got error loading NextBus xml: '.$error->nodeValue, 'transit');
                $errorCount++;
            }
            if ($errorCount == 0) {
                return $xml;
            }
        }
        return false;
    }
}
