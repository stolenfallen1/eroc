<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;

class GetIP
{
    /**
     * Get the hostname for the current IP.
     *
     * @return string
     */
    public function getHostname()
    {
        $ipAddress = $this->value(); // Retrieve the local/private IP address
        if ($ipAddress) {
            // Attempt to resolve the hostname
            $hostname = gethostbyaddr($ipAddress);
            return $hostname !== $ipAddress ? $hostname : 'Unknown Host'; // Return hostname or "Unknown Host"
        }
        return 'Unknown Host';
    }

    /**
     * Retrieve the local/private IP address.
     *
     * @return string|null
     */
    public function value()
    {
        // Check headers for potential IP addresses
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
                    $ip = trim($ip); // Trim whitespace
                    // Validate IP and ensure it's local/private
                    if (filter_var($ip, FILTER_VALIDATE_IP) && $this->isLocalIp($ip)) {
                        return $ip;
                    }
                }
            }
        }

        // Fallback to Laravel's request()->ip() if no valid local IP found
        $ip = Request::ip();
        return $this->isLocalIp($ip) ? $ip : null;
    }

    /**
     * Check if the given IP is a local or private IP.
     *
     * @param string $ip
     * @return bool
     */
    private function isLocalIp($ip)
    {
        // Define local/private IP ranges with patterns
        $localIpPatterns = [
            '/^127\./',        // Loopback address (127.x.x.x)
            '/^::1$/',         // IPv6 Loopback (::1)
            '/^10\./',         // Private range 10.0.0.0 – 10.255.255.255
            '/^172\.(1[6-9]|2[0-9]|3[0-1])\./', // Private range 172.16.0.0 – 172.31.255.255
            '/^192\.168\./',   // Private range 192.168.0.0 – 192.168.255.255
            '/^10\.4\.14\./'   // Custom local network range (e.g., 10.4.14.x)
        ];

        // Check if IP matches any of the local/private patterns
        foreach ($localIpPatterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }
}
