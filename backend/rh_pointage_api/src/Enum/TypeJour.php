<?php

namespace App\Enum;

enum TypeJour: string
{
    case FERIE = 'ferie';
    case CONGE = 'conge';
    case WEEKEND = 'weekend';
    case OUVRABLE = 'ouvrable';
}