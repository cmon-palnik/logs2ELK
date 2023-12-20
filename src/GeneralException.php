<?php
namespace Logs2ELK;

class GeneralException extends \Exception
{

    /**
     * @var string
     */
    protected $code;

    /**
     * @var array
     */
    private $context = [];

    public function __construct(string $message, string $code, array $context = [])
    {
        parent::__construct($message);

        $this->code = $code;
        $this->context = $context;
    }

    public static function withCode(string $code, array $context = [])
    {
        return new static($code, $code, $context);
    }

    public function is(string $code): bool
    {
        return ($this->code === $code);
    }

    public function getContext() : array
    {
        return $this->context;
    }

}
