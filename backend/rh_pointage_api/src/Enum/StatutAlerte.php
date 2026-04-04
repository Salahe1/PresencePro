<?php

namespace App\Enum;

enum StatutAlerte: string
{
    case NON_LU = 'non_lu';
    case LU = 'lu';
    case ARCHIVEE = 'archivee';
}