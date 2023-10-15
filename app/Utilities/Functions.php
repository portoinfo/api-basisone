<?php

namespace App\Utilities;

class Functions
{
    /**
     * @param string $mask Mascara
     * @param string $val Valor
     * @return string
     *
     * Formata um valor da seguine maneira #####-###
     * @author Cleiton Perin
     * #since 25/05/2017
     */
    public static function mascara($val, $mask)
    {
        $maskared = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($val[$k]))
                    $maskared .= $val[$k++];
            } else {
                if (isset($mask[$i]))
                    $maskared .= $mask[$i];
            }
        }
        return $maskared;
    }

    public static function soNumeros($_prString)
    {
        return str_replace(" ", "", preg_replace("/[^0-9\s]/", "", $_prString));
    }

    public static function is_json($str)
    {
        return (bool)is_array(json_decode($str, true));
    }

    public static function nomeMes($mes)
    {
        switch ($mes) {
            case "01" :
                $mesExt = "Jan";
                break;
            case "02" :
                $mesExt = "Fev";
                break;
            case "03" :
                $mesExt = "Mar";
                break;
            case "04" :
                $mesExt = "Abr";
                break;
            case "05" :
                $mesExt = "Mai";
                break;
            case "06" :
                $mesExt = "Jun";
                break;
            case "07" :
                $mesExt = "Jul";
                break;
            case "08" :
                $mesExt = "Ago";
                break;
            case "09" :
                $mesExt = "Set";
                break;
            case "10" :
                $mesExt = "Out";
                break;
            case "11" :
                $mesExt = "Nov";
                break;
            case "12" :
                $mesExt = "Dez";
                break;
        }
        return $mesExt;
    }
}