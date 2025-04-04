<?php
/**
 * Campaign Manager Plugin API beállítások
 * 
 * Ez a fájl biztosítja az API beállítások kezelését
 */

// Csak phpList-en belüli hozzáférés engedélyezése
if (!defined('PHPLISTINIT')) {
    die('Invalid access');
}

// Hibakezelés bekapcsolása fejlesztési célokra
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Plugin példány lekérése
global $plugins;
$plugin = $plugins['campaignmanager'];

// API kulcs lekérése
$api_key = $plugin->getPluginOption('api_key');
if (empty($api_key)) {
    $api_key = md5(uniqid(rand(), true));
    $plugin->setPluginOption('api_key', $api_key);
}

// API kulcs újragenerálása
if (isset($_POST['regenerate_api_key']) && $_POST['regenerate_api_key'] == 1) {
    $api_key = md5(uniqid(rand(), true));
    $plugin->setPluginOption('api_key', $api_key);
    $_SESSION['campaignmanager_api_message'] = 'Az API kulcs sikeresen újragenerálva';
}

// Üzenet megjelenítése
if (isset($_SESSION['campaignmanager_api_message'])) {
    echo '<div class="actionresult">' . htmlspecialchars($_SESSION['campaignmanager_api_message']) . '</div>';
    unset($_SESSION['campaignmanager_api_message']);
}

// Alapértelmezett webhely URL
$website_url = '';
if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_SCHEME'])) {
    $website_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
} elseif (isset($_SERVER['SERVER_NAME'])) {
    $website_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
}

// API dokumentáció és beállítások
echo '<h1>Campaign Manager API beállítások</h1>';

echo '<div class="note">
    <p>A Campaign Manager plugin REST API-t biztosít, amellyel távolról is lekérdezheted és kezelheted a kampányokat.</p>
    <p>Az API használatához szükséges egy API kulcs, amelyet minden kéréshez csatolni kell.</p>
</div>';

echo '<h2>API kulcs</h2>';
echo '<p>Az API kulcsot a <code>X-API-KEY</code> HTTP fejlécben kell elküldeni minden API kéréshez.</p>';
echo '<div class="apikey">' . htmlspecialchars($api_key) . '</div>';

echo '<form method="post">';
echo '<input type="hidden" name="regenerate_api_key" value="1">';
echo '<button type="submit" class="button">API kulcs újragenerálása</button>';
echo '</form>';

echo '<h2>API végpontok</h2>';
echo '<div class="endpoints">';
echo '<h3>Kampányok listázása</h3>';
echo '<pre>GET ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=list</pre>';
echo '<p>Opcionális paraméterek:</p>';
echo '<ul>';
echo '<li><code>status</code> - Szűrés állapot szerint (pl. inprocess, suspended, cancelled, sent)</li>';
echo '</ul>';

echo '<h3>Egy kampány részletes adatai</h3>';
echo '<pre>GET ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=get&id={kampány_id}</pre>';

echo '<h3>Kampány szüneteltetése</h3>';
echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=pause&id={kampány_id}</pre>';

echo '<h3>Kampány folytatása</h3>';
echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=resume&id={kampány_id}</pre>';

echo '<h3>Kampány leállítása</h3>';
echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=stop&id={kampány_id}</pre>';
echo '</div>';

echo '<h2>Példa API használatra</h2>';
echo '<h3>PHP példa</h3>';
echo '<pre>
$api_key = "' . htmlspecialchars($api_key) . '";
$api_url = "' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=list";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: $api_key"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
</pre>';

echo '<h3>JavaScript példa</h3>';
echo '<pre>
const apiKey = "' . htmlspecialchars($api_key) . '";
const apiUrl = "' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=list";

fetch(apiUrl, {
    method: "GET",
    headers: {
        "X-API-KEY": apiKey
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error("Hiba:", error));
</pre>';

// Stílusok
echo '<style>
    .apikey {
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        font-family: monospace;
        margin: 10px 0;
    }
    .endpoints {
        margin: 20px 0;
    }
    .endpoints h3 {
        margin-top: 20px;
        margin-bottom: 5px;
    }
    .endpoints pre {
        background-color: #f8f9fa;
        padding: 10px;
        border: 1px solid #ddd;
        overflow-x: auto;
    }
    .note {
        padding: 10px;
        margin: 10px 0;
        background-color: #d9edf7;
        border: 1px solid #bce8f1;
        color: #31708f;
        border-radius: 4px;
    }
    .actionresult {
        padding: 10px;
        margin: 10px 0;
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
        color: #3c763d;
        border-radius: 4px;
    }
    .button {
        padding: 5px 10px;
        margin: 2px;
        cursor: pointer;
    }
</style>';
