<?php
namespace App\CountryPacks;

use App\CountryPacks\TR\TrPack;
use App\CountryPacks\US\UsPack;
use App\Models\SysCountry;

final class CountryPackRegistry
{
    /** Kod tabanlı gelişmiş pack'ler (özel XML, entegrasyon vb.) */
    private array $advanced = [];

    /** Cache: kod → pack instance */
    private array $cache = [];

    public function __construct()
    {
        $this->register(new TrPack());
        $this->register(new UsPack());
    }

    public function register(AbstractCountryPack $pack): void
    {
        $this->advanced[strtoupper($pack->code())] = $pack;
        $this->cache[strtoupper($pack->code())] = $pack;
    }

    public function get(string $code): ?AbstractCountryPack
    {
        $code = strtoupper($code);

        if (isset($this->cache[$code])) return $this->cache[$code];

        // Kod class yoksa sys_country'den generic pack üret
        $country = SysCountry::find($code);
        if ($country) {
            $pack = new DbCountryPack($country);
            $this->cache[$code] = $pack;
            return $pack;
        }

        return null;
    }

    public function isAdvanced(string $code): bool
    {
        return isset($this->advanced[strtoupper($code)]);
    }

    public function all(): array
    {
        // sys_country'deki tüm ülkeleri döner (kod class olsun olmasın)
        $countries = SysCountry::where('is_active', true)->orderBy('name')->get();
        $result = [];

        foreach ($countries as $c) {
            $pack = $this->get($c->code);
            if ($pack) $result[$pack->code()] = $pack;
        }

        // DB'de olmayan ama kod class'ı olan pack'leri de ekle (kenar durum)
        foreach ($this->advanced as $code => $pack) {
            if (!isset($result[$code])) $result[$code] = $pack;
        }

        return $result;
    }

    public function codes(): array
    {
        return array_keys($this->all());
    }
}
