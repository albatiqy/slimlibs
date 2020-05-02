<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command;

abstract class AbstractSchedule {

    public const SCH_20S = 20;
    public const SCH_40S = 40;
    public const SCH_M = 60;
    public const SCH_H = 3600;
    public const SCH_D = (3600*24);

    protected const MAP = 'undefined';
    protected const SCHEDULE = self::SCH_20S;
    protected const JOB_CLASS = '';
    protected const JOB_DATA = [];
}