<?php

declare(strict_types=1);

namespace Origamy\Tests;

use Origamy\Exceptions\FieldException;
use Origamy\Messages\Alias;
use Origamy\Messages\Group;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Track;
use PHPUnit\Framework\TestCase;

class MessageTypesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Alias
    // -----------------------------------------------------------------------

    public function testAliasMissingUserId(): void
    {
        $alias = new Alias('1', '');

        $this->expectException(FieldException::class);
        $alias->validate();
    }

    public function testAliasMissingUserIdFieldDetails(): void
    {
        $alias = new Alias('1', '');
        try {
            $alias->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Alias', $e->type);
            $this->assertSame('UserId', $e->name);
            $this->assertSame('', $e->value);
        }
    }

    public function testAliasMissingPreviousId(): void
    {
        $alias = new Alias('', '1');

        $this->expectException(FieldException::class);
        $alias->validate();
    }

    public function testAliasMissingPreviousIdFieldDetails(): void
    {
        $alias = new Alias('', '1');
        try {
            $alias->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Alias', $e->type);
            $this->assertSame('PreviousId', $e->name);
            $this->assertSame('', $e->value);
        }
    }

    public function testAliasValid(): void
    {
        $alias = new Alias('1', '2');
        $alias->validate();
        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // Track
    // -----------------------------------------------------------------------

    public function testTrackMissingEvent(): void
    {
        $track = new Track('', '1');

        $this->expectException(FieldException::class);
        $track->validate();
    }

    public function testTrackMissingEventFieldDetails(): void
    {
        $track = new Track('', '1');
        try {
            $track->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Track', $e->type);
            $this->assertSame('Event', $e->name);
            $this->assertSame('', $e->value);
        }
    }

    public function testTrackMissingUserId(): void
    {
        $track = new Track('event', '');

        $this->expectException(FieldException::class);
        $track->validate();
    }

    public function testTrackMissingUserIdFieldDetails(): void
    {
        $track = new Track('event', '');
        try {
            $track->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Track', $e->type);
            $this->assertSame('UserId', $e->name);
            $this->assertSame('', $e->value);
        }
    }

    public function testTrackValidWithUserId(): void
    {
        $track = new Track('event', '1');
        $track->validate();
        $this->addToAssertionCount(1);
    }

    public function testTrackValidWithAnonymousId(): void
    {
        $track = new Track('event', '', '2');
        $track->validate();
        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // Group
    // -----------------------------------------------------------------------

    public function testGroupMissingGroupId(): void
    {
        $group = new Group('', '1');

        $this->expectException(FieldException::class);
        $group->validate();
    }

    public function testGroupMissingGroupIdFieldDetails(): void
    {
        $group = new Group('', '1');
        try {
            $group->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Group', $e->type);
            $this->assertSame('GroupId', $e->name);
        }
    }

    public function testGroupMissingUserId(): void
    {
        $group = new Group('g1', '', '');

        $this->expectException(FieldException::class);
        $group->validate();
    }

    public function testGroupMissingUserIdFieldDetails(): void
    {
        $group = new Group('g1', '', '');
        try {
            $group->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Group', $e->type);
            $this->assertSame('UserId', $e->name);
        }
    }

    public function testGroupValidWithUserId(): void
    {
        $group = new Group('g1', 'u1');
        $group->validate();
        $this->addToAssertionCount(1);
    }

    public function testGroupValidWithAnonymousId(): void
    {
        $group = new Group('g1', '', 'anon1');
        $group->validate();
        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // Identify
    // -----------------------------------------------------------------------

    public function testIdentifyMissingUserId(): void
    {
        $identify = new Identify('', '');

        $this->expectException(FieldException::class);
        $identify->validate();
    }

    public function testIdentifyMissingUserIdFieldDetails(): void
    {
        $identify = new Identify('', '');
        try {
            $identify->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Identify', $e->type);
            $this->assertSame('UserId', $e->name);
        }
    }

    public function testIdentifyValidWithUserId(): void
    {
        $identify = new Identify('u1');
        $identify->validate();
        $this->addToAssertionCount(1);
    }

    public function testIdentifyValidWithAnonymousId(): void
    {
        $identify = new Identify('', 'anon1');
        $identify->validate();
        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // Page
    // -----------------------------------------------------------------------

    public function testPageMissingUserId(): void
    {
        $page = new Page();

        $this->expectException(FieldException::class);
        $page->validate();
    }

    public function testPageMissingUserIdFieldDetails(): void
    {
        $page = new Page();
        try {
            $page->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Page', $e->type);
            $this->assertSame('UserId', $e->name);
        }
    }

    public function testPageValidWithUserId(): void
    {
        $page = new Page(userId: 'u1');
        $page->validate();
        $this->addToAssertionCount(1);
    }

    public function testPageValidWithAnonymousId(): void
    {
        $page = new Page(anonymousId: 'anon1');
        $page->validate();
        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // Screen
    // -----------------------------------------------------------------------

    public function testScreenMissingUserId(): void
    {
        $screen = new Screen();

        $this->expectException(FieldException::class);
        $screen->validate();
    }

    public function testScreenMissingUserIdFieldDetails(): void
    {
        $screen = new Screen();
        try {
            $screen->validate();
            $this->fail('Expected FieldException');
        } catch (FieldException $e) {
            $this->assertSame('analytics.Screen', $e->type);
            $this->assertSame('UserId', $e->name);
        }
    }

    public function testScreenValidWithUserId(): void
    {
        $screen = new Screen(userId: 'u1');
        $screen->validate();
        $this->addToAssertionCount(1);
    }

    public function testScreenValidWithAnonymousId(): void
    {
        $screen = new Screen(anonymousId: 'anon1');
        $screen->validate();
        $this->addToAssertionCount(1);
    }
}
