<?php

class TourWebModule extends WebModule {
  protected $id = 'tour';
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index':
        break;
    }
  }
}
