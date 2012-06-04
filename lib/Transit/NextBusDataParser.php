<?php

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
