<?php
namespace Logs2ELK\Environment\Type;

enum Env: string
{
    use EnumTrait;

    case DEV = 'dev';
    case PRD = 'prod';
    case PREPROD = 'preprod';
    case STAGE = 'stage';
    case LOC = 'local';
    case TEST = 'test';

}
