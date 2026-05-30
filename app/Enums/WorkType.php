<?php

namespace App\Enums;

enum WorkType: string
{
    case Remote = 'remote';
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
}
