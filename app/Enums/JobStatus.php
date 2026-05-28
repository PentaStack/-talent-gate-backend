<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Closed = 'closed';
    case Rejected = 'rejected';
}
