<?php

declare(strict_types=1);

namespace Origamy;

class AppInfo implements \JsonSerializable
{
    public function __construct(
        public string $name      = '',
        public string $version   = '',
        public string $build     = '',
        public string $namespace = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'name'      => $this->name      ?: null,
            'version'   => $this->version   ?: null,
            'build'     => $this->build     ?: null,
            'namespace' => $this->namespace ?: null,
        ], fn ($v) => $v !== null);
    }
}

class CampaignInfo implements \JsonSerializable
{
    public function __construct(
        public string $name    = '',
        public string $source  = '',
        public string $medium  = '',
        public string $term    = '',
        public string $content = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'name'    => $this->name    ?: null,
            'source'  => $this->source  ?: null,
            'medium'  => $this->medium  ?: null,
            'term'    => $this->term    ?: null,
            'content' => $this->content ?: null,
        ], fn ($v) => $v !== null);
    }
}

class DeviceInfo implements \JsonSerializable
{
    public function __construct(
        public string $id            = '',
        public string $manufacturer  = '',
        public string $model         = '',
        public string $name          = '',
        public string $type          = '',
        public string $version       = '',
        public string $advertisingId = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'id'            => $this->id            ?: null,
            'manufacturer'  => $this->manufacturer  ?: null,
            'model'         => $this->model          ?: null,
            'name'          => $this->name           ?: null,
            'type'          => $this->type           ?: null,
            'version'       => $this->version        ?: null,
            'advertisingId' => $this->advertisingId  ?: null,
        ], fn ($v) => $v !== null);
    }
}

class LibraryInfo implements \JsonSerializable
{
    public function __construct(
        public string $name    = '',
        public string $version = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'name'    => $this->name    ?: null,
            'version' => $this->version ?: null,
        ], fn ($v) => $v !== null);
    }
}

class LocationInfo implements \JsonSerializable
{
    public function __construct(
        public string $city      = '',
        public string $country   = '',
        public string $region    = '',
        public float  $latitude  = 0.0,
        public float  $longitude = 0.0,
        public float  $speed     = 0.0,
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'city'      => $this->city      ?: null,
            'country'   => $this->country   ?: null,
            'region'    => $this->region    ?: null,
            'latitude'  => $this->latitude  !== 0.0 ? $this->latitude  : null,
            'longitude' => $this->longitude !== 0.0 ? $this->longitude : null,
            'speed'     => $this->speed     !== 0.0 ? $this->speed     : null,
        ], fn ($v) => $v !== null);
    }
}

class NetworkInfo implements \JsonSerializable
{
    public function __construct(
        public bool   $bluetooth = false,
        public bool   $cellular  = false,
        public bool   $wifi      = false,
        public string $carrier   = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'bluetooth' => $this->bluetooth ?: null,
            'cellular'  => $this->cellular  ?: null,
            'wifi'      => $this->wifi      ?: null,
            'carrier'   => $this->carrier   ?: null,
        ], fn ($v) => $v !== null);
    }
}

class OSInfo implements \JsonSerializable
{
    public function __construct(
        public string $name    = '',
        public string $version = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'name'    => $this->name    ?: null,
            'version' => $this->version ?: null,
        ], fn ($v) => $v !== null);
    }
}

class PageInfo implements \JsonSerializable
{
    public function __construct(
        public string $hash     = '',
        public string $path     = '',
        public string $referrer = '',
        public string $search   = '',
        public string $title    = '',
        public string $url      = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'hash'     => $this->hash     ?: null,
            'path'     => $this->path     ?: null,
            'referrer' => $this->referrer ?: null,
            'search'   => $this->search   ?: null,
            'title'    => $this->title    ?: null,
            'url'      => $this->url      ?: null,
        ], fn ($v) => $v !== null);
    }
}

class ReferrerInfo implements \JsonSerializable
{
    public function __construct(
        public string $type = '',
        public string $name = '',
        public string $url  = '',
        public string $link = '',
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type ?: null,
            'name' => $this->name ?: null,
            'url'  => $this->url  ?: null,
            'link' => $this->link ?: null,
        ], fn ($v) => $v !== null);
    }
}

class ScreenInfo implements \JsonSerializable
{
    public function __construct(
        public int $density = 0,
        public int $width   = 0,
        public int $height  = 0,
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'density' => $this->density ?: null,
            'width'   => $this->width   ?: null,
            'height'  => $this->height  ?: null,
        ], fn ($v) => $v !== null);
    }
}

/**
 * Represents the analytics context object.
 * Extra fields are inlined into the JSON output (matching Go's MarshalJSON behaviour).
 */
class Context implements \JsonSerializable
{
    public ?AppInfo      $app        = null;
    public ?CampaignInfo $campaign   = null;
    public ?DeviceInfo   $device     = null;
    public ?LibraryInfo  $library    = null;
    public ?LocationInfo $location   = null;
    public ?NetworkInfo  $network    = null;
    public ?OSInfo       $os         = null;
    public ?PageInfo     $page       = null;
    public ?ReferrerInfo $referrer   = null;
    public ?ScreenInfo   $screen     = null;
    public string        $ip         = '';
    public bool          $direct     = false;
    public string        $locale     = '';
    public string        $groupId    = '';
    public string        $timezone   = '';
    public string        $userAgent  = '';
    public ?Traits       $traits     = null;

    /** Extra fields are inlined into the serialized context (no "extra" key in JSON). */
    public array $extra = [];

    public function jsonSerialize(): array
    {
        // Extra fields come first so struct fields override duplicates — same as Go.
        $m = $this->extra;

        if ($this->app !== null && $this->app->jsonSerialize() !== []) {
            $m['app'] = $this->app;
        }
        if ($this->campaign !== null && $this->campaign->jsonSerialize() !== []) {
            $m['campaign'] = $this->campaign;
        }
        if ($this->device !== null && $this->device->jsonSerialize() !== []) {
            $m['device'] = $this->device;
        }
        if ($this->library !== null && $this->library->jsonSerialize() !== []) {
            $m['library'] = $this->library;
        }
        if ($this->location !== null && $this->location->jsonSerialize() !== []) {
            $m['location'] = $this->location;
        }
        if ($this->network !== null && $this->network->jsonSerialize() !== []) {
            $m['network'] = $this->network;
        }
        if ($this->os !== null && $this->os->jsonSerialize() !== []) {
            $m['os'] = $this->os;
        }
        if ($this->page !== null && $this->page->jsonSerialize() !== []) {
            $m['page'] = $this->page;
        }
        if ($this->referrer !== null && $this->referrer->jsonSerialize() !== []) {
            $m['referrer'] = $this->referrer;
        }
        if ($this->screen !== null && $this->screen->jsonSerialize() !== []) {
            $m['screen'] = $this->screen;
        }
        if ($this->ip !== '') {
            $m['ip'] = $this->ip;
        }
        if ($this->direct) {
            $m['direct'] = $this->direct;
        }
        if ($this->locale !== '') {
            $m['locale'] = $this->locale;
        }
        if ($this->groupId !== '') {
            $m['groupId'] = $this->groupId;
        }
        if ($this->timezone !== '') {
            $m['timezone'] = $this->timezone;
        }
        if ($this->userAgent !== '') {
            $m['userAgent'] = $this->userAgent;
        }
        if ($this->traits !== null && count($this->traits) > 0) {
            $m['traits'] = $this->traits;
        }

        return $m;
    }

    /** Returns true if there is no meaningful content in this context. */
    public function isEmpty(): bool
    {
        return $this->jsonSerialize() === [];
    }
}
