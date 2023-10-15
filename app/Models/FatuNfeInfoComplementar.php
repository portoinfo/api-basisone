<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeInfoComplementar extends Model
{
    protected $table = 'FATU_NFE_INFO_COMPLEMENTAR';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'INFO_ADICIONAIS_INTERESSE_FISCO',
        'INFO_COMPLEMENTAR_CONTRIBUINTE',
        'INFO_COMPLEMENTAR_CONTRI_MANUAL',
        'INFO_COMPLEMENTAR_CONTRI_AUTO',
    ];
}
