<?php

declare(strict_types=1);

namespace Origamy;

class AppInfo implements \JsonSerializable
{
    /** @var string */
    public $name;
    /** @var string */
    public $version;
    /** @var string */
    public $build;
    /** @var string */
    public $namespace;

    public function __construct(
        string $name      = '',
        string $version   = '',
        string $build     = '',
        string $namespace = ''
    ) {
        $this->name      = $name;
        $this->version   = $version;
        $this->build     = $build;
        $this->namespace = $namespace;
    }

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
    /** @var string */
    public $name;
    /** @var string */
    public $source;
    /** @var string */
    public $medium;
    /** @var string */
    public $term;
    /** @var string */
    public $content;

    public function __construct(
        string $name    = '',
        string $source  = '',
        string $medium  = '',
        string $term    = '',
        string $content = ''
    ) {
        $this->name    = $name;
        $this->source  = $source;
        $this->medium  = $medium;
        $this->term    = $term;
        $this->content = $content;
    }

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
    /** @var string */
    public $id;
    /** @var string */
    public $manufacturer;
    /** @var string */
    public $model;
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var string */
    public $version;
    /** @var string */
    public $advertisingId;

    public function __construct(
        string $id            = '',
        string $manufacturer  = '',
        string $model         = '',
        string $name          = '',
        string $type          = '',
        string $version       = '',
        string $advertisingId = ''
    ) {
        $this->id            = $id;
        $this->manufacturer  = $manufacturer;
        $this->model         = $model;
        $this->name          = $name;
        $this->type          = $type;
        $this->version       = $version;
        $this->advertisingId = $advertisingId;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'id'            => $this->id            ?: null,
            'manufacturer'  => $this->manufacturer  ?: null,
            'model'         => $this->model         ?: null,
            'name'          => $this->name          ?: null,
            'type'          => $this->type          ?: null,
            'version'       => $this->version       ?: null,
            'advertisingId' => $this->advertisingId ?: null,
        ], fn ($v) => $v !== null);
    }
}

class LibraryInfo implements \JsonSerializable
{
    /** @var string */
    public $name;
    /** @var string */
    public $version;

    public function __construct(string $name = '', string $version = '')
    {
        $this->name    = $name;
        $this->version = $version;
    }

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
    /** @var string */
    public $city;
    /** @var string */
    public $country;
    /** @var string */
    public $region;
    /** @var float */
    public $latitude;
    /** @var float */
    public $longitude;
    /** @var float */
    public $speed;

    public function __construct(
        string $city      = '',
        string $country   = '',
        string $region    = '',
        float  $latitude  = 0.0,
        float  $longitude = 0.0,
        float  $speed     = 0.0
    ) {
        $this->city      = $city;
        $this->country   = $country;
        $this->region    = $region;
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->speed     = $speed;
    }

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
    /** @var bool */
    public $bluetooth;
    /** @var bool */
    public $cellular;
    /** @var bool */
    public $wifi;
    /** @var string */
    public $carrier;

    public function __construct(
        bool   $bluetooth = false,
        bool   $cellular  = false,
        bool   $wifi      = false,
        string $carrier   = ''
    ) {
        $this->bluetooth = $bluetooth;
        $this->cellular  = $cellular;
        $this->wifi      = $wifi;
        $this->carrier   = $carrier;
    }

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
    /** @var string */
    public $name;
    /** @var string */
    public $version;

    public function __construct(string $name = '', string $version = '')
    {
        $this->name    = $name;
        $this->version = $version;
    }

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
    /** @var string */
    public $hash;
    /** @var string */
    public $path;
    /** @var string */
    public $referrer;
    /** @var string */
    public $search;
    /** @var string */
    public $title;
    /** @var string */
    public $url;

    public function __construct(
        string $hash     = '',
        string $path     = '',
        string $referrer = '',
        string $search   = '',
        string $title    = '',
        string $url      = ''
    ) {
        $this->hash     = $hash;
        $this->path     = $path;
        $this->referrer = $referrer;
        $this->search   = $search;
        $this->title    = $title;
        $this->url      = $url;
    }

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
    /** @var string */
    public $type;
    /** @var string */
    public $name;
    /** @var string */
    public $url;
    /** @var string */
    public $link;

    public function __construct(
        string $type = '',
        string $name = '',
        string $url  = '',
        string $link = ''
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->url  = $url;
        $this->link = $link;
    }

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
    /** @var int */
    public $density;
    /** @var int */
    public $width;
    /** @var int */
    public $height;

    public function __construct(int $density = 0, int $width = 0, int $height = 0)
    {
        $this->density = $density;
        $this->width   = $width;
        $this->height  = $height;
    }

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
    /** @var AppInfo|null */
    public $app      = null;
    /** @var CampaignInfo|null */
    public $campaign = null;
    /** @var DeviceInfo|null */
    public $device   = null;
    /** @var LibraryInfo|null */
    public $library  = null;
    /** @var LocationInfo|null */
    public $location = null;
    /** @var NetworkInfo|null */
    public $network  = null;
    /** @var OSInfo|null */
    public $os       = null;
    /** @var PageInfo|null */
    public $page     = null;
    /** @var ReferrerInfo|null */
    public $referrer = null;
    /** @var ScreenInfo|null */
    public $screen   = null;
    /** @var string */
    public $ip        = '';
    /** @var bool */
    public $direct    = false;
    /** @var string */
    public $locale    = '';
    /** @var string */
    public $groupId   = '';
    /** @var string */
    public $timezone  = '';
    /** @var string */
    public $userAgent = '';
    /** @var Traits|null */
    public $traits    = null;

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
