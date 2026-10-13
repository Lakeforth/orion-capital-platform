<?php

namespace App\Console\Commands;

use App\Services\IpCountry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use MaxMind\Db\Reader;

/**
 * Downloads this month's DB-IP Lite country database (CC BY 4.0, no account or key needed)
 * and swaps it in only once it opens and answers a lookup, so a failed or partial download
 * never replaces a working file. DB-IP publishes early in the month; until then the previous
 * month's file is used. --file installs a copy fetched by hand, for hosts without outbound access.
 */
class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'leadmax:geoip-update {--file= : Install this .mmdb or .mmdb.gz instead of downloading}';

    protected $description = 'Download or install the DB-IP Lite IP-to-country database';

    public function handle(): int
    {
        $target = IpCountry::path();
        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            $this->error('Cannot create '.dirname($target));

            return self::FAILURE;
        }
        $download = $target.'.download';
        $fresh = $target.'.new';

        try {
            if ($file = $this->option('file')) {
                if (! is_file($file)) {
                    $this->error("No such file: {$file}");

                    return self::FAILURE;
                }
                copy($file, $download);
                $source = $file;
            } else {
                $source = $this->download($download);
                if (! $source) {
                    $this->error('DB-IP Lite could not be downloaded; the current database is unchanged.');

                    return self::FAILURE;
                }
            }

            $this->unpack($download, $fresh);
            $reader = new Reader($fresh);
            $type = $reader->metadata()->databaseType;
            $built = date('Y-m-d', $reader->metadata()->buildEpoch);
            $probe = $reader->get('8.8.8.8')['country']['iso_code'] ?? null;
            $reader->close();
            if (! str_contains($type, 'Country') || ! $probe) {
                $this->error("Not a usable country database ({$type}); the current database is unchanged.");

                return self::FAILURE;
            }

            // Replaces the old file in one step; requests already reading it keep their copy.
            rename($fresh, $target);
            $this->info("Installed {$type} built {$built} from {$source} at {$target}.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Update failed, the current database is unchanged: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            foreach ([$download, $fresh] as $leftover) {
                if (is_file($leftover)) {
                    unlink($leftover);
                }
            }
        }
    }

    private function download(string $to): ?string
    {
        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = 'https://download.db-ip.com/free/dbip-country-lite-'.$month->format('Y-m').'.mmdb.gz';
            if (Http::timeout(300)->sink($to)->get($url)->successful()) {
                return $url;
            }
        }

        return null;
    }

    private function unpack(string $from, string $to): void
    {
        $head = (string) file_get_contents($from, false, null, 0, 2);
        if ($head !== "\x1f\x8b") {
            copy($from, $to);

            return;
        }
        $in = gzopen($from, 'rb');
        $out = fopen($to, 'wb');
        while (! gzeof($in)) {
            fwrite($out, (string) gzread($in, 1 << 20));
        }
        gzclose($in);
        fclose($out);
    }
}
