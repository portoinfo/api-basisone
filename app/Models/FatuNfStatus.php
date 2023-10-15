<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfStatus extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'FATU_NF_STATUS';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ID',
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'FATU_NF_STATUS_ID',
        'OBSERVACAO',
        'DATA',
        'COLABORADOR_ID',
        'USUARIO_ID',
    ];
}
