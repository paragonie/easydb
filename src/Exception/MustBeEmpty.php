<?php
declare(strict_types=1);
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerInterface;
use ParagonIE\Corner\CornerTrait;

class MustBeEmpty extends EasyDBException
{
    use CornerTrait;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->supportLink = 'https://github.com/paragonie/easydb';
        $this->helpfulMessage = "When calling the andWith() and orWith() methods on the EasyStatement class,
if the first argument is an EasyStatement object, it MUST be the only parameter.";
    }
}
