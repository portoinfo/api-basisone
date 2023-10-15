<?php

namespace App\Utilities;

use Carbon\Carbon;

class DatesAndHollydays
{

    /**
     * @var Carbon
     */
    protected $dt;
    /**
     * @var array
     */
    protected $days;
    /**
     * @var int
     */
    protected $limit_day = 25;

    /**
     * Construtor
     * Recebe a data a ser avaliada
     * @param string $date
     */
    public function __construct(string $date)
    {
        $this->dt = Carbon::parse($date);
        $year = (int)$this->dt->format('Y');
        $this->days = $this->hollydays($year);
    }

    /**
     * Ajusta o dia limite a ser utilizado
     * @param int $day
     * @return $this
     */
    public function setLimitDay(int $day)
    {
        $this->limit_day = $day;
        return $this;
    }

    public static function adjust(string $date)
    {
        return new static($date);
    }

    public function getHollyDays()
    {
        return $this->days;
    }

    /**
     * Ajusta uma data para que não caia num fim de semana ou feriado nacional
     * exceto qualquer data maior que a data limite estabelecida
     * @return string
     */
    public function expirationDate(): string
    {
        $plus = 0; //poderá ser usado futuramente para ajustes retroativos em caso de ter que antecipar a data
        $dt = $this->dt;
        if ((int)$dt->format('d') >= $this->limit_day) {
            $expire = Carbon::createFromDate((int)$dt->format('Y'), (int)$dt->format('m'), $this->limit_day);
            return $expire->format('Y-m-d');
        }
        foreach ($this->days as $day) {
            //se o feriado cair em um fim de semana pular
            $wdh = Carbon::parse($day)->dayOfWeekIso;
            if ($wdh >= 6) {
                continue;
            }
            if ($dt->format('Y-m-d') === $day) {
                $dt->addDay();
                ++$plus;
            }
        }
        if ($dt->dayOfWeekIso == 6) {
            $dt->addDays(2);
            $plus += 2;
        } elseif ($dt->dayOfWeekIso == 7) {
            $dt->addDay();
            ++$plus;
        }
        $expire = $dt;
        $diftolimit = $this->limit_day - (int)$dt->format('d');
        if ($diftolimit < 0) {
            $expire = $dt->subDays(-1 * $diftolimit);
        }
        return $expire->format('Y-m-d');
    }

    /**
     * Cria o array com os feriados nacionais
     * @param int $year
     * @return array
     */
    private function hollydays(int $year): array
    {
        $march21 = Carbon::createFromDate($year, 3, 21);
        $pascoa = $march21->addDays(easter_days($year));

        // Confraternização Universal - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 1, 1)->startOfDay()->format('Y-m-d');
        // Tiradentes - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 4, 21)->startOfDay()->format('Y-m-d');
        // Dia do Trabalhador - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 5, 1)->startOfDay()->format('Y-m-d');
        // Dia da Independência - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 9, 7)->startOfDay()->format('Y-m-d');
        // N. S. Aparecida - Lei nº 6802, de 30/06/80
        $hollys[] = Carbon::createFromDate($year, 10, 12)->startOfDay()->format('Y-m-d');
        // Todos os santos - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 11, 2)->startOfDay()->format('Y-m-d');
        // Proclamação da republica - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 11, 15)->startOfDay()->format('Y-m-d');
        // Natal - Lei nº 662, de 06/04/49
        $hollys[] = Carbon::createFromDate($year, 12, 25)->startOfDay()->format('Y-m-d');
        //Corpus Cristi
        $hollys[] = $pascoa->addDays(60)->format('Y-m-d');
        sort($hollys);
        return $hollys;
    }

}
