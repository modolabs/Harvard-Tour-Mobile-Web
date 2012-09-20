<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class CourseAreasXMLDataParser extends XMLDataParser {

    protected $trimWhiteSpace = true;
    protected function shouldStripTags($element) {
    }

    protected function shouldHandleStartElement($name) {
        return in_array($name, array('COLLEGE','DEPARTMENT'));
    }

    protected function handleStartElement($name, $attribs) {
        switch($name) {
            case 'COLLEGE':
            case 'DEPARTMENT':
                $this->elementStack[] = new CourseXMLArea();
                break;
            default:
                $this->elementStack[] = new XMLElement($name, $attribs);
        }
    }

    protected function shouldHandleEndElement($name) {
        return in_array($name, array('COLLEGE','DEPARTMENT','TITLE','CODE'));
    }

    protected function handleEndElement($name, $element, $parent) {
        switch ($name)
        {
            case 'TITLE':
                $parent->setTitle($element->value());
                break;
            case 'CODE':
                $parent->setCode($element->value());
                break;
            case 'DEPARTMENT':
                $element->setParent($parent->getCode());
                $parent->addArea($element);
                if ($area = $this->getOption('area')) {
                    if ($element->getCode()==$area) {
                        $this->items = $element;
                    }
                } 
                break;
            case 'COLLEGE':
                if ($area = $this->getOption('area')) {
                    if ($element->getCode()==$area) {
                        $this->items = $element;
                    }
                } else {
                    $this->items[] = $element;
                }
                break;
            default:
                break;
        }
    }
}

class CourseXMLArea extends CourseArea
{
    public function setValue($value) {
    }
}
