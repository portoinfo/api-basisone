<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeNumeracao extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'FATU_NFE_NUMERACAO';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'SERIE',
        'NUMERO',
        'SITUACAO_NUMERO_ID',
        'FATU_NF_ID',
    ];
}
