<?php

namespace Logs2ELK\Environment;

trait EnvironmentTrait
{

    const WPINDEX = "wpindex";
    const WPERROR = "wperrors";
    const INDEX = "index";
    const ERROR = "errors";
    const APPSYS = "appsys";

    const E_PRD = 'prod';
    const E_DEV = 'dev';
    const E_PREPROD = 'preprod';
    const E_STAGE = 'stage';
    const E_LOC = 'local';
    const E_TEST = 'test';

    private $envs = [
        self::E_DEV,
        self::E_PRD,
        self::E_PREPROD,
        self::E_LOC,
        self::E_TEST,
        self::E_STAGE,
    ];

    public $indexes = [
        self::INDEX,
        self::ERROR,
        self::WPINDEX,
        self::WPERROR,
        self::APPSYS,
    ];

}
