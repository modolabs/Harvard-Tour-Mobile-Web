<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class FileCourseContent extends CourseContent
{
    protected $contentType = 'file';

    public function getContentClass() {
        $attachments = $this->getAttachments();
        if(count($attachments) > 1){
            return 'multi';
        }elseif(count($attachments) == 1){
            $attachment = current($attachments);
            return $attachment->getContentClass();
        }

        return parent::getContentClass();
    }
}
