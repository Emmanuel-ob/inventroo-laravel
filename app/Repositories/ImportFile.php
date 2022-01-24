<?php
namespace App\Repositories;
//use App\Invoice;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class ImportFile implements ToArray, ShouldAutoSize
{
    protected $user_logs;

    public function __construct(array $user_logs)
    {
        $this->user_logs = $user_logs;
    }

    public function array()
    {
        return $this->user_logs;
    }
}