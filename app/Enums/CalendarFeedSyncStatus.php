<?php

namespace App\Enums;

enum CalendarFeedSyncStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
