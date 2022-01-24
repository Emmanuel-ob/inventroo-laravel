<?php
namespace App\Repositories;
//use App\Invoice;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


//class ExportFile implements FromArray, ShouldAutoSize
class ExportFile implements FromArray
{
    protected $user_logs;

    public function __construct(array $user_logs)
    {
        $this->user_logs = $user_logs;
    }

    public function array(): array
    {
        return $this->user_logs;
    }
}