<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stevebauman\Location\Facades\Location;

class AuditTrailedModel extends Model
{
    protected static function makeAuditTrail($event, $before, $after)
	{
		try {
			$location  = Location::get(request()->ip())->toArray();
		} catch (\Throwable $th) {
			$location = "";
		}

		AuditTrail::create([
			'user_id' => auth()->user()->id ?? null,
			'user_ip' => request()->ip(),
			"before" => json_encode($before),
			"after" => json_encode($after),
			"location" => json_encode($location),
			"event" => $event,
			"model" => static::class,
		]);
	}


	protected static function booted()
    {
		parent::booted();
		
		static::created(function ($item) {
			if (static::class === AuditTrail::class) return;
			static::makeAuditTrail('Role Created', $item->getOriginal(), $item->toArray());
		});
		
		static::updated(function ($item) {
			if (static::class === AuditTrail::class) return;
			static::makeAuditTrail('Role Updated', $item->getOriginal(), $item->toArray());
		});
    } 
}
