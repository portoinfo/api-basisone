<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfItemStatus extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'FATU_NFITEM_STATUS';
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
        'FATU_NFITEM_ID',
        'FATU_NFITEM_STATUS_ID',
        'DATA',
        'OBSERVACAO',
        'COLABORADOR_ID',
        'USUARIO_ID',
    ];
}
