<?php
/*
 Copyright (C) AC SOFTWARE SP. Z O.O.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace SuplaApiBundle\Tests\Integration\Controller;

use SuplaApiBundle\Model\ApiVersions;
use SuplaApiBundle\Tests\Integration\Traits\SuplaApiHelper;
use SuplaBundle\Entity\IODevice;
use SuplaBundle\Entity\User;
use SuplaBundle\Tests\Integration\IntegrationTestCase;
use SuplaBundle\Tests\Integration\Traits\ResponseAssertions;

class ApiIODeviceControllerIntegrationTest extends IntegrationTestCase {
    use SuplaApiHelper;
    use ResponseAssertions;

    /** @var User */
    private $user;
    /** @var IODevice */
    private $device;

    protected function setUp() {
        $this->user = $this->createConfirmedUserWithApiAccess();
        $location = $this->createLocation($this->user);
        $this->device = $this->createDeviceFull($location);
    }

    public function testGettingDevicesList() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices');
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = json_decode($response->getContent());
        $this->assertTrue(property_exists($content, 'iodevices'));
        $iodevices = $content->iodevices;
        $this->assertCount(1, $iodevices);
    }

    public function testGettingDevicesListVersion22() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices', [], [], $this->versionHeader(ApiVersions::V2_2()));
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = json_decode($response->getContent());
        $this->assertCount(1, $content);
        $this->assertEquals($this->device->getId(), $content[0]->id);
        $this->assertEquals($this->device->getSoftwareVersion(), $content[0]->softwareVersion);
        $this->assertFalse(property_exists($content[0], 'location'));
        $this->assertFalse(property_exists($content[0], 'channels'));
    }

    public function testGettingDevicesWithChannels22() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices?include=channels', [], [], $this->versionHeader(ApiVersions::V2_2()));
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = json_decode($response->getContent());
        $this->assertCount(1, $content);
        $this->assertEquals($this->device->getId(), $content[0]->id);
        $this->assertFalse(property_exists($content[0], 'location'));
        $this->assertTrue(property_exists($content[0], 'channels'));
        $this->assertCount(5, $content[0]->channels);
    }

    public function testGettingDevicesWithLocationAndOriginalLocation22() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices?include=location,originalLocation', [], [], $this->versionHeader(ApiVersions::V2_2()));
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = json_decode($response->getContent());
        $this->assertCount(1, $content);
        $this->assertEquals($this->device->getId(), $content[0]->id);
        $this->assertTrue(property_exists($content[0], 'location'));
        $this->assertTrue(property_exists($content[0], 'originalLocation'));
        $this->assertFalse(property_exists($content[0], 'channels'));
        $this->assertFalse(property_exists($content[0], 'connected'));
    }

    public function testGettingDevicesWithConnectedStatus22() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices?include=connected', [], [], $this->versionHeader(ApiVersions::V2_2()));
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = json_decode($response->getContent());
        $this->assertCount(1, $content);
        $this->assertEquals($this->device->getId(), $content[0]->id);
        $this->assertFalse(property_exists($content[0], 'location'));
        $this->assertFalse(property_exists($content[0], 'originalLocation'));
        $this->assertFalse(property_exists($content[0], 'channels'));
        $this->assertTrue(property_exists($content[0], 'connected'));
    }

    public function testPassingWrongIncludeParam() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices?include=turtles,channels,unicorns', [], [], $this->versionHeader(ApiVersions::V2_2()));
        $response = $client->getResponse();
        $this->assertStatusCode(400, $response);
        $content = json_decode($response->getContent());
        $this->assertContains('not supported: turtles', $content->message);
        $this->assertContains('unicorns', $content->message);
    }

    public function testGettingDevicesDetails() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices/' . $this->device->getId());
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = current(json_decode($response->getContent(), true));
        $this->assertEquals($this->device->getId(), $content['id']);
    }

    public function testGettingDevicesDetailsAsApiUser() {
        $client = $this->createAuthenticatedApiClient($this->user);
        $client->request('GET', '/api/iodevices/' . $this->device->getId());
        $response = $client->getResponse();
        $this->assertStatusCode(200, $response);
        $content = current(json_decode($response->getContent(), true));
        $this->assertEquals($this->device->getId(), $content['id']);
    }

    public function test404OnGettingInvalidIoDevice() {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/web-api/iodevices/123245');
        $response = $client->getResponse();
        $this->assertStatusCode(404, $response);
    }

    public function test403OnGettingDeviceOfAnotherUser() {
        $user = $this->createConfirmedUser('another@supla.org');
        $client = $this->createAuthenticatedClient($user->getUsername());
        $client->request('GET', '/web-api/iodevices/' . $this->device->getId());
        $response = $client->getResponse();
        $this->assertStatusCode(403, $response);
    }
}
