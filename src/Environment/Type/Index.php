<?php

namespace Logs2ELK\Environment\Type;

enum Index: string
{
    use EnumTrait;

    case WPINDEX = 'wpindex';
    case WPERROR = 'wperrors';
    case INDEX = 'index';
    case ERROR = 'errors';
    case APPSYS = 'appsys';

}
