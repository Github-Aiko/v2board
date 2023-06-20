<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Protocols\General;
use App\Http\Controllers\Client\SNI;
use App\Http\Controllers\Controller;
use App\Services\ServerService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use App\Services\UserService;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $flag = $request->input('flag')
            ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);
        $user = $request->user;
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            $this->setSubscribeInfoToServers($servers, $user);
            if ($flag) {
                    if ($flag === 'sni') {
                        $sniValue = $request->input('sni');
                        $class = new SNI($user, $sniValue);
                        die($class->handle());
                    } else {
                    foreach (array_reverse(glob(app_path('Http//Controllers//Client//Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Http\\Controllers\\Client\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            die($class->handle());
                        }
                    }
                }
            }
            $class = new General($user, $servers);
            die($class->handle());
        }
    }

    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : 'No expiration';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        array_unshift($servers, array_merge($servers[0], [
            'name' => "Expired at {$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "Reset usage after {$resetDay} day(s)",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "Remain: {$remainingTraffic}",
        ]));
    }
}
