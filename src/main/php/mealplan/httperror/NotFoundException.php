<?php
namespace mealplan\httperror;

use RuntimeException;

class NotFoundException extends HttpException
{
    public function __construct(string $message = "")
    {
        parent::__construct($message, 404);
    }
}