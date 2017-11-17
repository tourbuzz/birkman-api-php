<?php

namespace Repository;

class RecordNotFoundException extends \Exception
{
    protected $message = "record not found";
}
