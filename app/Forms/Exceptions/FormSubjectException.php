<?php

namespace App\Forms\Exceptions;

use Livewire\Exceptions\BypassViewHandler;

class FormSubjectException extends \LogicException
{
    use BypassViewHandler;
}
