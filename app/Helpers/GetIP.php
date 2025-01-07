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
                    // Validate IP and prioritize local/private IPs
                    if (filter_var($ip, FILTER_VALIDATE_IP) && $this->isLocalIp($ip)) {
                        return $ip; // Return the first valid local/private IP
                    }
                }
            }
        }

        // Default to Laravel's request()->ip() and validate as local
        $ip = request()->ip();
        return $this->isLocalIp($ip) ? $ip : null;
    }

    /**
     * Check if the IP is local (private or loopback range)
     */
    private function isLocalIp($ip)
    {

        // Match loopback, private, and specific local ranges
        $localIpRanges = [
            '127.0.0.1',      // Loopback
            '::1',            // IPv6 Loopback
            '10.',            // Private range 10.0.0.0 – 10.255.255.255
            '172.16.',        // Private range 172.16.0.0 – 172.31.255.255
            '192.168.',       // Private range 192.168.0.0 – 192.168.255.255
            '10.4.15.'        // Your specific network range
        ];

        foreach ($localIpRanges as $range) {
            if (strpos($ip, $range) === 0) {
                return true;
            }
        }

        return false;
    }
}
