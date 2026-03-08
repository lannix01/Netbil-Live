<?php

namespace App\Services;

use App\Models\Package;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => (string)config('mikrotik.host'),
            'user' => (string)config('mikrotik.user'),
            'pass' => (string)config('mikrotik.pass'),
            'port' => (int)(config('mikrotik.port') ?? 8728),
            'timeout' => 3,
        ]);
    }

    /**
     * Hotspot package session provisioning + active login.
     */
    public function connectHotspot(Package $package, string $ip, string $mac): bool
    {
        $username = 'hs_' . str_replace(':', '', strtolower($mac));
        return $this->connectNamedHotspot(
            package: $package,
            username: $username,
            ip: $ip,
            mac: $mac,
            password: null,
            comment: 'Temporary hotspot session'
        );
    }

    /**
     * Provision a hotspot user under an explicit username, then log it in.
     */
    public function connectNamedHotspot(
        Package $package,
        string $username,
        string $ip,
        ?string $mac = null,
        ?string $password = null,
        ?string $comment = null
    ): bool {
        $profile = trim((string)($package->mk_profile ?? $package->mikrotik_profile ?? 'default'));
        $resolvedUsername = trim($username);
        $resolvedIp = trim($ip);
        $resolvedMac = trim((string)$mac);
        $resolvedPassword = trim((string)$password);
        $durationMinutes = (int)($package->duration_minutes ?? 0);
        if ($durationMinutes <= 0) {
            $durationHours = (int)($package->duration ?? 0);
            $durationMinutes = $durationHours > 0 ? ($durationHours * 60) : 0;
        }

        $this->removeUserIfExists($resolvedUsername);

        $addUser = new Query('/ip/hotspot/user/add');
        $addUser->equal('name', $resolvedUsername);
        $addUser->equal('profile', $profile);

        if ($resolvedPassword !== '') {
            $addUser->equal('password', $resolvedPassword);
        }
        if ($resolvedMac !== '') {
            $addUser->equal('mac-address', $resolvedMac);
        }

        $addUser->equal('comment', trim((string)$comment) !== '' ? trim((string)$comment) : 'Temporary hotspot session');

        if (!empty($package->data_limit)) {
            $addUser->equal('limit-bytes-total', (string)((int)$package->data_limit));
        }
        if ($durationMinutes > 0) {
            $addUser->equal('limit-uptime', $durationMinutes . 'm');
        }

        $this->client->query($addUser)->read();

        if ($resolvedIp !== '') {
            $login = new Query('/ip/hotspot/active/login');
            $login->equal('user', $resolvedUsername);
            $login->equal('ip', $resolvedIp);
            $this->client->query($login)->read();
        }

        return true;
    }

    /**
     * Ensure hotspot profile exists/updated on MikroTik.
     */
    public function syncHotspotProfile(Package $package): void
    {
        $profile = trim((string)($package->mk_profile ?? $package->mikrotik_profile ?? ''));
        if ($profile === '') {
            return;
        }

        $check = new Query('/ip/hotspot/user/profile/print');
        $check->where('name', $profile);
        $exists = $this->client->query($check)->read();

        if ($exists) {
            return;
        }

        $query = new Query('/ip/hotspot/user/profile/add');
        $query->equal('name', $profile);

        $rateLimit = trim((string)($package->rate_limit ?? $package->speed ?? ''));
        if ($rateLimit !== '') {
            $query->equal('rate-limit', $rateLimit);
        }
        if (!empty($package->duration_minutes)) {
            $query->equal('session-timeout', (int)$package->duration_minutes . 'm');
        }

        $this->client->query($query)->read();
    }

    /**
     * Backward-compatible metered creation.
     */
    public function connectMetered(string $username, string $password, Package $package): bool
    {
        $profile = trim((string)($package->mk_profile ?? $package->mikrotik_profile ?? 'default'));
        $rateLimit = trim((string)($package->rate_limit ?? $package->speed ?? ''));

        return $this->connectMeteredUser(
            username: $username,
            password: $password,
            profile: $profile,
            ip: '',
            mac: null,
            rateLimit: $rateLimit
        );
    }

    /**
     * Metered login flow: ensure user exists and has metered profile, then login.
     */
    public function connectMeteredUser(
        string $username,
        string $password,
        string $profile,
        string $ip,
        ?string $mac = null,
        ?string $rateLimit = null,
        ?string $comment = null
    ): bool {
        $profile = trim($profile) !== '' ? trim($profile) : 'default';
        $rateLimit = trim((string)$rateLimit);
        $comment = trim((string)$comment);
        $user = $this->findHotspotUser($username);

        if (!$user) {
            $addUser = new Query('/ip/hotspot/user/add');
            $addUser->equal('name', $username);
            $addUser->equal('password', $password);
            $addUser->equal('profile', $profile);
            if ($mac) {
                $addUser->equal('mac-address', $mac);
            }
            $addUser->equal('comment', $comment !== '' ? $comment : 'Metered user');
            $this->client->query($addUser)->read();
        } else {
            $setUser = new Query('/ip/hotspot/user/set');
            $setUser->equal('.id', $user['.id']);
            $setUser->equal('password', $password);
            $setUser->equal('profile', $profile);
            // Unlimited metered session; billing system controls validity.
            $setUser->equal('limit-bytes-total', '0');
            $setUser->equal('limit-uptime', '');
            if ($mac) {
                $setUser->equal('mac-address', $mac);
            }
            if ($comment !== '') {
                $setUser->equal('comment', $comment);
            }
            $this->client->query($setUser)->read();
        }

        if ($rateLimit !== '') {
            $checkProfile = new Query('/ip/hotspot/user/profile/print');
            $checkProfile->where('name', $profile);
            $profileRows = $this->client->query($checkProfile)->read();
            if (!empty($profileRows[0]['.id'])) {
                $setProfile = new Query('/ip/hotspot/user/profile/set');
                $setProfile->equal('.id', $profileRows[0]['.id']);
                $setProfile->equal('rate-limit', $rateLimit);
                $this->client->query($setProfile)->read();
            }
        }

        if (trim($ip) !== '') {
            $login = new Query('/ip/hotspot/active/login');
            $login->equal('user', $username);
            $login->equal('ip', $ip);
            $this->client->query($login)->read();
        }

        return true;
    }

    public function verifyHotspotCredentials(string $username, string $password): bool
    {
        $user = $this->findHotspotUser($username);
        if (!$user) {
            return false;
        }

        $stored = (string)($user['password'] ?? '');
        if ($stored === '') {
            return false;
        }

        return hash_equals($stored, $password);
    }

    public function disconnectActiveUser(string $username, ?string $mac = null): void
    {
        $rows = $this->client->query(new Query('/ip/hotspot/active/print'))->read();

        foreach ($rows as $row) {
            $sessionUser = (string)($row['user'] ?? $row['username'] ?? '');
            $sessionMac = (string)($row['mac-address'] ?? '');
            $match = ($sessionUser === $username) || ($mac !== null && $mac !== '' && $sessionMac === $mac);

            if ($match && !empty($row['.id'])) {
                $remove = new Query('/ip/hotspot/active/remove');
                $remove->equal('.id', $row['.id']);
                $this->client->query($remove)->read();
            }
        }
    }

    public function getActiveSession(?string $mac, ?string $ip): ?array
    {
        $query = new Query('/ip/hotspot/active/print');
        if ($mac) {
            $query->where('mac-address', $mac);
        }
        if ($ip) {
            $query->where('ip', $ip);
        }

        $res = $this->client->query($query)->read();
        return $res[0] ?? null;
    }

    public function getMeteredUsage(string $username): array
    {
        $query = new Query('/ip/hotspot/user/print');
        $query->where('name', $username);
        $user = $this->client->query($query)->read()[0] ?? [];

        $bytesIn = (float)($user['bytes-in'] ?? 0);
        $bytesOut = (float)($user['bytes-out'] ?? 0);

        return [
            'used' => $bytesIn + $bytesOut,
            'limit' => $user['limit-bytes-total'] ?? null,
        ];
    }

    public function getHotspotUserStats(string $username): ?array
    {
        $user = $this->findHotspotUser($username);
        if (!$user) {
            return null;
        }

        return [
            'username' => (string)($user['name'] ?? $user['username'] ?? $username),
            'bytes-in' => (int)($user['bytes-in'] ?? 0),
            'bytes-out' => (int)($user['bytes-out'] ?? 0),
            'uptime' => (string)($user['uptime'] ?? ''),
            'limit-uptime' => (string)($user['limit-uptime'] ?? ''),
            'mac-address' => (string)($user['mac-address'] ?? ''),
            'profile' => (string)($user['profile'] ?? ''),
        ];
    }

    public function relogin(string $username, string $ip): void
    {
        $login = new Query('/ip/hotspot/active/login');
        $login->equal('user', $username);
        $login->equal('ip', $ip);
        $this->client->query($login)->read();
    }

    private function userExists(string $username): bool
    {
        return $this->findHotspotUser($username) !== null;
    }

    private function findHotspotUser(string $username): ?array
    {
        $query = new Query('/ip/hotspot/user/print');
        $query->where('name', $username);

        $rows = $this->client->query($query)->read();
        return $rows[0] ?? null;
    }

    private function removeUserIfExists(string $username): void
    {
        $user = $this->findHotspotUser($username);
        if (!$user || empty($user['.id'])) {
            return;
        }

        $remove = new Query('/ip/hotspot/user/remove');
        $remove->equal('.id', $user['.id']);
        $this->client->query($remove)->read();
    }
}
