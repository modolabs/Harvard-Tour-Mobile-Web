<?php

interface KurogoDatabaseResponse {
    public function fetch();
    public function closeCursor();
}
