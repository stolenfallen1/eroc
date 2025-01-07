<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class GetIP
{

    public function getHostname()
    {
        $ipAddress = $this->value(); // Call the local IP function
        if ($ipAddress) {
            // Use the gethostbyaddr() function to get the hostname
            $hostname = gethostbyaddr($ipAddress);
            return $hostname;
        }
        return 'Unknown Host';
    }

    public function value()
    {
        foreach (
            [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ] as $key
        ) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // Ensure no extra spaces
                    // Check for private/local IP ranges
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false) {
                        if ($this->isLocalIp($ip)) {
                            return $ip; // Return the first valid local IP
                        }
                    }
                }
            }
        }
        return request()->ip(); // Default to Laravel's `request()->ip()` if no local IP is found
    }

    /**
     * Check if the IP is local (private or loopback range)
     */
    private function isLocalIp($ip)
    {
        // Localhost or private IP ranges
        $localIpRanges = [
            '127.0.0.1',     // Loopback
            '::1',           // IPv6 Loopback
            '10.',           // Private IP range 10.0.0.0 – 10.255.255.255
            '172.16.',       // Private IP range 172.16.0.0 – 172.31.255.255
            '192.168.',      // Private IP range 192.168.0.0 – 192.168.255.255
        ];

        foreach ($localIpRanges as $range) {
            if (strpos($ip, $range) === 0) {
                return true;
            }
        }
        return false;
    }
}
