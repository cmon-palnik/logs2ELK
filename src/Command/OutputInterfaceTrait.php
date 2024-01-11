<?php

namespace Logs2ELK\Command;

use Logs2ELK\Exception;
use Logs2ELK\ExceptionCode as Code;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputInterfaceTrait
{
    protected OutputInterface $output;

    protected function writeln(iterable|string $output): void
    {
        $this->getOutputInterface()->writeln($output);
    }

    protected function write(iterable|string $output): void
    {
        $this->getOutputInterface()->write($output);
    }

    protected function setOutputInterface(OutputInterface $output): void
    {
        $this->output = $output;
    }

    protected function getOutputInterface(): OutputInterface
    {
        if (empty($this->output)) {
            throw Exception::withCode(Code::TOO_EARLY_INVOCATION);
        }
        return $this->output;
    }

    public function setOutputInterfaceToServices(): void
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
