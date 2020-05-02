<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Command\Schedules;

use Albatiqy\Slimlibs\Command\AbstractSchedule;
use Albatiqy\Slimlibs\Command\Jobs\Telegram as Job;

final class TelegramDaemon extends AbstractSchedule {
    protected const MAP = 'telegramsrv';
    protected const SCHEDULE = self::SCH_20S;
    protected const JOB_CLASS = Job::class;
    protected const JOB_DATA = [];
}