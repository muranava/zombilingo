<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['scheduled_at', 'user_id', 'type'];
  
}
