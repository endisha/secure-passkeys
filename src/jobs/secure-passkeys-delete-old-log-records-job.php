<?php

namespace Secure_Passkeys\Jobs;

use DateTime;
use Secure_Passkeys\Core\Secure_Passkeys_Scheduler;
use Secure_Passkeys\Models\Secure_Passkeys_Log;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Delete_Old_Log_Records_Job extends Secure_Passkeys_Scheduler
{
    protected $hook = 'secure_passkeys_delete_old_log_recrods_job';

    protected $recurrence = 'secure_passkeys_delete_old_log_recrods_job_every_24_hour';

    protected $interval = 86400;

    protected $display = 'Once Every 24 Hours at Midnight';

    public function __construct()
    {
        $this->time = (new DateTime('today midnight', wp_timezone()))->format('U');
    }

    public function process(array $args = []): void
    {
        $log_cleanup_days = intval(Secure_Passkeys_Helper::get_option('log_cleanup_days', 0));

        if ($log_cleanup_days > 0) {
            (new Secure_Passkeys_Log())->delete_old_records($log_cleanup_days);
        }
    }
}
