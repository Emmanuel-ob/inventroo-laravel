<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class AuditTrail extends Model
{
    protected $fillable = ['user_id','user_ip', 'event', 'before', 'after', 'location', 'model'];

    public function getLocationAttribute($value)
    {
        try {
            return json_decode($value, 1);
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function getFormattedLocationAttribute()
    {
        $str = ($this->location['longitude'] ?? '') . ':' . 
        ($this->location['latitude'] ??  '') . ' - ' . 
        ($this->location['cityName'] ??  '') . ', '. 
        ($this->location['countryName'] ??  '');
        if ($str === ': - , ') {
            return "N/A";
        }
        return $str;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
