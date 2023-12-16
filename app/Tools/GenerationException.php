<?php
namespace App\Tools;

class GenerationException extends \Exception
{
    public $desc;
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, $desc='', \Throwable $previous = null) {
        // some code
        $this->desc = $desc;
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}