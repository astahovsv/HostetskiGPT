<?php

namespace Modules\HostetskiGPT\Entities;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model {

    protected $table = 'gptassistant_settings';
    protected $fillable = ['mailbox_id', 'api_key', 'assistant_id'];
    protected $primaryKey = 'mailbox_id';

    public $timestamps = false;
    public $incrementing = false;
}