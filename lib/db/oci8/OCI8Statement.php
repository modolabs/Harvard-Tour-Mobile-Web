<?php

Kurogo::includePackage('db');
class OCI8Statement implements KurogoDatabaseResponse {

    protected $statement;

    public function __construct($statement){
        $this->statement = $statement;
    }

    public function fetch(){
        return oci_fetch_assoc($this->statement);
    }

    public function closeCursor(){
        return oci_free_statement($this->statement);
    }
}
