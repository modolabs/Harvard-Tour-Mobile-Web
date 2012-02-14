<?php

interface TermsDataRetriever
{
    public function getAvailableTerms();
    public function getTerm($termCode);
}