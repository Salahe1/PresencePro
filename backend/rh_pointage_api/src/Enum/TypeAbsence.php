<?php

namespace App\Enum;

enum TypeAbsence: string
{
    cASE MALADIE = 'maladie';
    CASE CONGE_PAYE = 'conge_paye';
    CASE CONGE_SANS_SOLDE = 'conge_sans_solde';
    CASE CONGE_MATERNITE = 'conge_maternite';
    CASE CONGE_PATERNITE = 'conge_paternite';
    CASE CONGE_PARENTAL = 'conge_parental';
    CASE CONGE_SAINS = 'conge_sains';
    CASE CONGE_FAMILIAL = 'conge_familial';
    CASE TELETRAVAIL = 'teletravail';
}