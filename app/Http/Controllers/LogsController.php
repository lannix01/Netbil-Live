<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\RouterOSAPI;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $api = new RouterOSAPI();
        $config = config('mikrotik');

        $logs = [];

        if ($api->connect($config['host'], $config['user'], $config['pass'])) {
            $mikrotikLogs = $api->comm('/log/print');
            $api->disconnect();

            // Reverse to get recent first
            $collection = collect($mikrotikLogs)->reverse();

            // Server-side pagination
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $logs = new LengthAwarePaginator(
                $currentItems,
                $collection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('logs.index', compact('logs'));
    }
}