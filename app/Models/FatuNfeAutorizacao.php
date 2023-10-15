<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfeAutorizacao extends Model
{
    protected $table = 'FATU_NFE_AUTORIZACAO';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'ENVI_NFE_ID_LOTE',
        'ENVI_NFE_IND_SINC',
        'ENVI_NFE_XML',
        'RET_ENVI_NFE_XML',
        'RET_ENVI_NFE_VER_APLIC',
        'RET_ENVI_NFE_CUF',
        'RET_ENVI_NFE_CSTAT',
        'RET_ENVI_NFE_XMOTIVO',
        'RET_ENVI_NFE_DHRECBTO',
        'RET_ENVI_INF_NFE_XML',
        'RET_ENVI_INF_REC_NREC',
        'RET_ENVI_INF_PROT_VER_APLICACAO',
        'RET_ENVI_INF_PROT_DHRECBTO',
        'RET_ENVI_INF_PROT_NPROT',
        'RET_ENVI_INF_PROT_DIGVAL',
        'RET_ENVI_INF_PROT_CSTAT',
        'RET_ENVI_INF_PROT_XMOTIVO',
    ];
}
