<?php

namespace MBLSolutions\SendgridNotification\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Lerouse\LaravelRepository\EloquentRepository;
use MBLSolutions\SendgridNotification\Models\NotificationLog;

class NotificationLogRepository extends EloquentRepository
{    
    public function builder(): Builder
    {
        return NotificationLog::query()->selectRaw(config('notification.database.table').'.*');
    }

}
