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
            
            // Log the resolved hostname and IP for debugging purposes
            return ($hostname !== $ipAddress) ? $hostname : $ipAddress; // If unresolved, return the IP address as fallback
        }
    
        return 'Unknown Host'; // Return if no IP found
    }
    
    /**
     * Retrieve the real client IP address from the headers.
     *
     * @return string|null
     */
    public function value()
    { // Check headers for potential IP addresses
        $headers = [
            'HTTP_X_FORWARDED_FOR', // Nginx or Proxy header for original client IP
            'HTTP_X_REAL_IP',       // Nginx X-Real-IP header
            'REMOTE_ADDR'           // The IP address directly in the request
        ];
    
        foreach ($headers as $key) {
            if (!empty($_SERVER[$key])) {
                // The X-Forwarded-For header may contain multiple IPs, get the first one
                if ($key == 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $_SERVER[$key]);
                    return trim($ips[0]); // Return the first IP in the list
                }
                return $_SERVER[$key]; // Return the first valid IP found
            }
        }
    
        // Fallback to Laravel's request()->ip()
        return Request::ip();
    }

    /**
     * Check if the given IP is a local or private IP.
     *
     * @param string $ip
     * @return bool
     */
    private function isLocalIp($ip)
    {
        // Define local/private IP ranges
        $localIpPatterns = [
            '/^127\./',        // Loopback
            '/^::1$/',         // IPv6 Loopback
            '/^10\./',         // Private range 10.0.0.0 – 10.255.255.255
            '/^172\.(1[6-9]|2[0-9]|3[0-1])\./', // Private range 172.16.0.0 – 172.31.255.255
            '/^192\.168\./',   // Private range 192.168.0.0 – 192.168.255.255
            '/^10\.4\.14\./'   // Custom local network range
        ];

        // Check if IP matches any local/private pattern
        foreach ($localIpPatterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }
}
