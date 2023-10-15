<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeVolumes extends Model
{
    protected $table = 'FATU_NFE_VOLUMES';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'ID',
        'QUANTIDADE',
        'ESPECIE',
        'MARCA',
        'NUMERACAO',
        'PESO_LIQUIDO',
        'PESO_BRUTO',
    ];
}
