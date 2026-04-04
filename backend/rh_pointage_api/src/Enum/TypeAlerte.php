<?php

namespace App\Enum;

enum TypeAlerte: string 
{
    case RETARD = 'retard';
    case ABSENCE = 'absence';
    case ANOMALIE = 'anomalie';
}