<?php

namespace App;

use Exception;

class Memcached
{
    protected $socket;

    public function __construct($server = '127.0.0.1', $port = 11211)
    {
        $this->socket = fsockopen($server, $port, $errno, $errstr, 5);
    }

    public function __destruct()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    public function get($key)
    {
        $result = $this->command('get', $key);
        $data = null;

        if (isset($result[0]) && preg_match('/^VALUE (' . preg_quote($key) . ') (\d+) (\d+)$/i', $result[0], $matches)) {
            $data = implode(array_slice($result, 1));
        }

        return $data;
    }

    public function getAllKeys()
    {
        $slabs = $this->slabs();
        $keys = [];

        foreach ($slabs as $slabId => $slab) {
            $keys[$slabId] = $this->dump($slabId);
        }

        return $keys;
    }

    public function slabs()
    {
        $results = $this->command('stats', 'items');
        $slabs = [];

        foreach ($results as $row) {
            if (preg_match('/^STAT items:(\d+):([a-z0-9_]+) (\d+)$/i', $row, $matches)) {
                $slabs[(int) $matches[1]][$matches[2]] = (int) $matches[3];
            }
        }

        return $slabs;
    }

    public function dump($slabId, $limit = 0)
    {
        $results = $this->command('stats', 'cachedump', (int) $slabId, (int) $limit);
        $items = [];

        foreach ($results as $row) {
            // @todo: http://grokbase.com/t/danga/memcached/07ckzm4pm0/what-is-a-valid-key#20071219368tcj0af2j07fhezy1wz8n2gm
            if (preg_match('/^ITEM ([^ ]+) \[(\d+) b; (\d+) s\]$/i', $row, $matches)) {
                $items[$matches[1]] = [
                    'bytes' => (int) $matches[2],
                    'time' => (int) $matches[3],
                ];
            }
        }

        return $items;
    }

    public function flush()
    {
        $this->command('flush_all');
    }

    protected function command()
    {
        $return = [];
        fwrite($this->socket, implode(' ', func_get_args()) . "\r\n");

        while (!feof($this->socket)) {
            $buf = rtrim(fgets($this->socket));
            switch ($buf) {
                case 'ERROR':
                case 'CLIENT_ERROR':
                case 'SERVER_ERROR':
                    throw new Exception('command failed: ' . $command);
                    break;

                case 'END':
                case 'STORED':
                case 'NOT_STORED':
                case 'EXISTS':
                case 'DELETED':
                case 'TOUCHED':
                case 'NOT_FOUND':
                    break 2;
                    break;

                default:
                    $return[] = $buf;
                    break;
            }
        }

        return $return;
    }
}
