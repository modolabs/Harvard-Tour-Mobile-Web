<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MoodleDownloadCourseContent extends DownloadCourseContent {

    public function getContentFile() {
        $url = $this->getFileURL();
        if ($retriever = $this->getContentRetriever()) {
            $file = $retriever->getFileForUrl($url, $this->getID() . '_' . $this->getFileName());
            return $file;
        }
    }

}
