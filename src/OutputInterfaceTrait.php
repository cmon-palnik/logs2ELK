<?php

namespace Logs2ELK;

use Logs2ELK\GeneralExceptionCode as Code;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputInterfaceTrait
{
    protected OutputInterface $output;

    protected function writeln(mixed $output)
    {
        $this->getOutputInterface()->writeln($output);
    }

    protected function setOutputInterface(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected function getOutputInterface(): OutputInterface
    {
        if (empty($this->output)) {
            throw GeneralException::withCode(Code::TOO_EARLY_INVOCATION);
        }
        return $this->output;
    }

    public function setOutputInterfaceToServices()
    {
        foreach (get_object_vars($this) as $obj) {
            if (!is_object($obj)) {
                continue;
            }
            $reflClass = new \ReflectionClass(get_class($obj));
            foreach ($reflClass->getTraits() as $name => $trait) {
                if ($name == self::class) {
                    $obj->setOutputInterface($this->getOutputInterface());
                }
            }
        }
    }
}
