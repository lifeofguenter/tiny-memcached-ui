<?php

/**
 * This file is part of the tiny-memcached-ui project.
 *
 * (c) Gunter Grodotzki <gunter@grodotzki.co.za>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

error_reporting(E_ALL);
ini_set('display_errors', true);

header('Content-Type: text/html; charset=UTF-8');

require '../vendor/autoload.php';

/**
 * safely escape strings for html output
 * @param string $string
 * @param bool $return
 * @return string
 */
function escaped($string, $return = false)
{
    $string = htmlspecialchars($string, ENT_QUOTES|ENT_HTML5, 'UTF-8');

    if ($return) {
        return $string;
    } else {
        echo $string;
    }
}

/**
 * converty bytes into a human readable format
 * @param int $bytes
 * @return string
 */
function huBytes($bytes)
{
    $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
    $curUnit = 0;

    while($bytes > 1000) {
        $bytes /= 1000;
        ++$curUnit;
    }

    return sprintf('%.2F %s', $bytes, $units[$curUnit]);
}

$memcached = new App\Memcached;

if (!empty($_GET['view'])) {
    $data = $memcached->get($_GET['view']);
} else {
    $allKeys = $memcached->getAllKeys();
}

unset($memcached);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>tiny-memcached-ui</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/skeleton.css">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="twelve columns">
                <h1>tiny-memcached-ui</h1>
                <?php if (!empty($_GET['view'])): ?>
                    <label for="cacheKey">Key</label>
                    <input class="u-full-width" type="text" id="cacheKey" value="<?php escaped($_GET['view']) ?>">
                    <label for="cacheValue">Value</label>
                    <textarea class="u-full-width" id="cacheValue" style="height:200px"><?php escaped($data) ?></textarea>
                    <a href="<?php echo $_SERVER['PHP_SELF'] ?>" class="button button-primary">Back</a> <a href="?del=<?php echo urlencode($_GET['view']) ?>" class="button">Delete</a>
                <?php else: ?>
                    <table class="u-full-width">
                        <thead>
                            <tr>
                                <th>Slab Id</th>
                                <th>Key</th>
                                <th>Size</th>
                                <th>Timestamp</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($allKeys) && is_array($allKeys)): ?>
                        <?php foreach ($allKeys as $slabId => $slab): ?>
                            <?php foreach ($slab as $key => $meta): ?>
                            <tr>
                                <td><?php escaped($slabId) ?></td>
                                <td><a href="?view=<?php echo urlencode($key) ?>"><?php escaped($key) ?></a></td>
                                <td><?php escaped(huBytes($meta['bytes'])) ?></td>
                                <td><?php escaped(date('Y-m-d H:i:s', $meta['time'])) ?></td>
                                <td><a href="?del=<?php echo urlencode($key) ?>">del</a></td>
                            </tr>
                            <?php endforeach ?>
                        <?php endforeach ?>
                        <?php endif ?>
                        </tbody>
                    </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</body>
</html>
