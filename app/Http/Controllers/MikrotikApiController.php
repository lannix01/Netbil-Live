<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RouterosService;

class MikrotikApiController extends Controller
{
    protected $ros;

    public function __construct(RouterosService $ros)
    {
        $this->ros = $ros;
    }

    public function hotspotActive()
    {
        $data = $this->ros->getHotspotActive();
        return response()->json(['data' => $data]);
    }

    public function hotspotUsers()
    {
        $data = $this->ros->getHotspotUsers();
        return response()->json(['data' => $data]);
    }

    public function hotspotHosts()
    {
        $data = $this->ros->getHotspotHosts();
        return response()->json(['data' => $data]);
    }
    public function hotspotCookies(): JsonResponse
{
    return response()->json(['data' => $this->ros->getHotspotCookies()]);
}

}
