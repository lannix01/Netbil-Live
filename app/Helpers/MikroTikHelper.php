<?php

namespace App\Helpers;

use RouterOS\Client;
use RouterOS\Query;

class MikroTikHelper
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => config('mikrotik.host'),
            'user' => config('mikrotik.username'),
            'pass' => config('mikrotik.password'),
            'port' => (int) config('mikrotik.port'),
        ]);
    }

    public function getInterfaces()
    {
        $query = new Query('/interface/print');
        return $this->client->query($query)->read();
    }

    public function getActiveUsers()
    {
        $query = new Query('/ip/hotspot/active/print');
        return $this->client->query($query)->read();
    }

    public function getDHCPLeases()
    {
        $query = new Query('/ip/dhcp-server/lease/print');
        return $this->client->query($query)->read();
    }

    public function getResourceUsage()
    {
        $query = new Query('/system/resource/print');
        return $this->client->query($query)->read()[0];
    }
}
