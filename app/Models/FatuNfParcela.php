<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FatuNfParcela extends Model
{
    protected $table = 'FATU_NF_PARCELA';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'EMPRESA_ID',
	    'FILIAL_ID',
        'FATU_NF_ID',
        'FATU_NF_PARCELA_ID',
        'DIAS',
        'VL_PARCELA',
        'FORMA_PGTO_ID',
        'RATEIO',
        'DT_LANCAMENTO',
        'DT_VENCIMENTO',
        'MORA_DIA',
        'MULTA',
        'VL_MORA',
        'IDE_PERCENTUAL_MULTA',
        'TITULO_ID',
        'VL_COMISSAO',
        'VL_GRATIFICACAO',
    ];
}
