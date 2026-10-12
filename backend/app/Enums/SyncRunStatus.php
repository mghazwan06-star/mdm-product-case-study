<?php

namespace App\Enums;

enum SyncRunStatus: string
{
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
    case Refused = 'refused';
}
