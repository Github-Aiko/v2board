<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SNI
{
    protected $user;
    protected $sniValue;

    public function __construct($user, $sniValue)
    {
        $this->user = $user;
        $this->sniValue = $sniValue;
    }

    public function handle()
    {
        DB::table('v2_user')
            ->where('uuid', $this->user['uuid'])
            ->update(['user_sni' => $this->sniValue]);
        return "SNI updated";
    }
}