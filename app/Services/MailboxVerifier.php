<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Exception;

/**
 * MailboxVerifier
 *
 * Performs MX lookup and a minimal SMTP handshake (HELO, MAIL FROM, RCPT TO)
 * to determine whether a mailbox likely exists.
 *
 * Notes:
 * - Uses stream_socket_client to open TCP connections to MX hosts (port 25).
 * - Keeps a small in-memory MX cache per instance to reduce DNS lookups.
 * - Returns structured array with status: valid | invalid | risky | unknown | catch_all
 */
class MailboxVerifier
{
    protected LoggerInterface $logger;
    protected int $connectTimeout;
    protected int $readTimeout;
    protected string $mailFrom;
    protected array $mxCache = [];

    /**
     * @param LoggerInterface $logger
     * @param int $connectTimeout seconds for socket connect (default 8)
     * @param int $readTimeout seconds for read operations (default 8)
     * @param string $mailFrom envelope from address; should be a domain you control
     */
    public function __construct(
        LoggerInterface $logger,
        int $connectTimeout = 8,
        int $readTimeout = 8,
        string $mailFrom = 'verify@yourdomain.com'
    ) {
        $this->logger = $logger;
        $this->connectTimeout = $connectTimeout;
        $this->readTimeout = $readTimeout;
        $this->mailFrom = $mailFrom;
    }

    /**
     * Verify a single email.
     *
     * @param string $email
     * @param bool $detectCatchAll (optional) run a random RCPT to detect catch-all behavior
     * @return array {
     *   status: string, // valid|invalid|risky|unknown|catch_all
     *   reason: string|null,
     *   mx: string|null,
     *   port: int|null,
     *   response: string|null,
     *   checked_at: string (ISO)
     * }
     */
    public function verify(string $email, bool $detectCatchAll = true): array
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->result('invalid', 'syntax', null, null, null);
        }

        [$local, $domain] = explode('@', $email, 2);
        if (empty($domain)) {
            return $this->result('invalid', 'bad_domain', null, null, null);
        }

        $mxHosts = $this->getMxHosts($domain);

        // If no MX, fallback to A/AAAA host (some providers use direct A)
        if (empty($mxHosts)) {
            if ($this->hasAorAAAA($domain)) {
                $mxHosts = [$domain];
            } else {
                return $this->result('invalid', 'no_mx', null, null, null);
            }
        }

        // Try each MX host until we get definitive info or exhaust list
        foreach ($mxHosts as $mx) {
            try {
                $resp = $this->attemptRcpt($mx, 25, $email);

                // If rcpt returned a positive 2xx code
                if ($resp['code'] >= 200 && $resp['code'] < 300) {
                    // Optional: detect catch-all by probing a random address on same domain
                    if ($detectCatchAll) {
                        $catch = $this->isCatchAll($mx, $domain);
                        if ($catch === true) {
                            return $this->result('catch_all', 'catch_all_detected', $mx, 25, $resp['response']);
                        }
                        // if 'unknown' continue — but since this email accepted, we still treat as valid
                    }
                    return $this->result('valid', 'rcpt_accepted', $mx, $resp['port'] ?? 25, $resp['response']);
                }

                // 4xx temporary errors => risky/greylist
                if ($resp['code'] >= 400 && $resp['code'] < 500) {
                    return $this->result('risky', 'temporary_error', $mx, $resp['port'] ?? 25, $resp['response']);
                }

                // 5xx permanent failure => invalid
                if ($resp['code'] >= 500 && $resp['code'] < 600) {
                    return $this->result('invalid', 'rcpt_rejected', $mx, $resp['port'] ?? 25, $resp['response']);
                }

                // Unexpected code -> risky
                return $this->result('risky', 'unexpected_code', $mx, $resp['port'] ?? 25, $resp['response']);
            } catch (Exception $e) {
                // Log and continue to next MX server
                $this->logger->debug("MailboxVerifier: MX attempt failed for {$mx}: " . $e->getMessage());
                continue;
            }
        }

        // if we get here none of the MX hosts responded usefully
        return $this->result('unknown', 'no_mx_responded', null, null, null);
    }

    /**
     * Attempt RCPT TO handshake on a specific MX host.
     *
     * Returns array with code and response.
     *
     * @throws Exception on connection/read problems
     */
    protected function attemptRcpt(string $host, int $port, string $email): array
    {
        $ctx = stream_context_create(['socket' => ['tcp_nodelay' => true]]);
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $this->connectTimeout, STREAM_CLIENT_CONNECT, $ctx);

        if (!$socket) {
            throw new Exception("connect_failed {$errno} {$errstr}");
        }

        stream_set_timeout($socket, $this->readTimeout);

        // read banner
        $banner = $this->readResponse($socket);

        // EHLO/HELO: prefer EHLO then fallback to HELO (we'll send HELO for simplicity)
        $this->write($socket, "HELO verifier.local");
        $this->readResponse($socket);

        // MAIL FROM
        $this->write($socket, "MAIL FROM:<{$this->mailFrom}>");
        $this->readResponse($socket);

        // RCPT TO
        $this->write($socket, "RCPT TO:<{$email}>");
        $rcpt = $this->readResponse($socket);

        // QUIT politely
        try {
            $this->write($socket, "QUIT");
            $this->readResponse($socket);
        } catch (Exception $e) {
            // ignore quit failures
        }

        fclose($socket);

        $code = $this->parseCode($rcpt);

        return [
            'code' => $code,
            'message' => $rcpt,
            'response' => $rcpt,
            'port' => $port,
        ];
    }

    protected function write($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    /**
     * Read a single SMTP response (handles multi-line but returns aggregated string).
     */
    protected function readResponse($socket): string
    {
        $out = '';
        $start = time();

        while (($line = fgets($socket, 1024)) !== false) {
            $out .= trim($line) . ' ';
            // SMTP multi-line responses have a hyphen after the code on continuation lines.
            // A terminating line has code followed by a space: e.g. "250 OK"
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
            // safety to prevent infinite loops
            if ((time() - $start) > ($this->readTimeout + 2)) {
                break;
            }
        }

        return trim($out);
    }

    protected function parseCode(string $resp): int
    {
        if (preg_match('/\b(\d{3})\b/', $resp, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Get MX hosts for domain ordered by priority (lowest pri first)
     */
    protected function getMxHosts(string $domain): array
    {
        if (isset($this->mxCache[$domain])) {
            return $this->mxCache[$domain];
        }

        $hosts = [];

        // dns_get_record preferred
        if (function_exists('dns_get_record')) {
            try {
                $records = @dns_get_record($domain, DNS_MX);
                if (!empty($records)) {
                    usort($records, fn($a, $b) => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));
                    foreach ($records as $r) {
                        if (!empty($r['target'])) {
                            $hosts[] = rtrim($r['target'], '.');
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore, will try fallback
                $this->logger->debug("dns_get_record failed for {$domain}: " . $e->getMessage());
            }
        }

        // fallback to getmxrr
        if (empty($hosts) && function_exists('getmxrr')) {
            try {
                $mxs = [];
                $prefs = [];
                if (@getmxrr($domain, $mxs, $prefs)) {
                    array_multisort($prefs, $mxs);
                    $hosts = $mxs;
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        $this->mxCache[$domain] = $hosts;
        return $hosts;
    }

    protected function hasAorAAAA(string $domain): bool
    {
        return checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }

    /**
     * Catch-all detection: test a random address at domain.
     * Returns true if catch-all detected, false if not, 'unknown' otherwise.
     */
    protected function isCatchAll(string $mxHost, string $domain)
    {
        try {
            $random = 'verifier-' . bin2hex(random_bytes(6));
            $test = "{$random}@{$domain}";
            $resp = $this->attemptRcpt($mxHost, 25, $test);
            $code = $resp['code'] ?? 0;
            if ($code >= 200 && $code < 300) {
                return true;
            }
            if ($code >= 500 && $code < 600) {
                return false;
            }
            return 'unknown';
        } catch (Exception $e) {
            $this->logger->debug("catch-all test failed for {$mxHost}@{$domain}: " . $e->getMessage());
            return 'unknown';
        }
    }

    protected function result(string $status, ?string $reason = null, ?string $mx = null, $port = null, $response = null): array
    {
        return [
            'status' => $status,
            'reason' => $reason,
            'mx' => $mx,
            'port' => $port,
            'response' => $response,
            'checked_at' => now()->toDateTimeString(),
        ];
    }
}
