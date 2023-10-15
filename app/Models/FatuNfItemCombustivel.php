<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfItemCombustivel extends Model
{
    protected $table = 'FATU_NFITEM_COMBUSTIVEL';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
        'FILIAL_ID',
        'FATU_NF_ID',
        'FATU_NFITEM_ID',
        'CODIGO_ANP_ID',
        'CODIGO_CODIF',
        'QTD_COMBUSTIVEL_TEMP_AMBIENTE',
        'CONSUMO_PAIS_ID',
        'CONSUMO_UF_ID',
        'VL_BASE_CALCULO_CIDE',
        'VL_ALIQUOTA_PRODUTO_CIDE',
        'VL_CIDE',
    ];

    public function anp()
    {
        return $this->belongsTo(ProdTabelaAnp::class, 'CODIGO_ANP_ID', 'CODIGO_ANP_ID');
    }
}
