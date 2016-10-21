<?php

namespace Hawk\Plugins\HGitter;

class GitException extends \Exception {
    public function __construct($message = '') {
        parent::__construct('[GitException] : ' . $message);
    }
}