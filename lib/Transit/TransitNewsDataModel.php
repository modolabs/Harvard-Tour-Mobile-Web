<?php

/**
  * TransitNewsDataModel
  * @package Transit
  */

includePackage('DataModel');
class TransitNewsDataModel extends ItemListDataModel
{
    protected $DEFAULT_PARSER_CLASS='TransitNewsDataParser';
}
