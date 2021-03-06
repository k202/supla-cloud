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

namespace SuplaBundle\Enums;

use Assert\Assert;
use Assert\Assertion;
use MyCLabs\Enum\Enum;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @method static ChannelFunctionAction OPEN()
 * @method static ChannelFunctionAction CLOSE()
 * @method static ChannelFunctionAction SHUT()
 * @method static ChannelFunctionAction REVEAL()
 * @method static ChannelFunctionAction REVEAL_PARTIALLY()
 * @method static ChannelFunctionAction TURN_ON()
 * @method static ChannelFunctionAction TURN_OFF()
 * @method static ChannelFunctionAction SET_RGBW_PARAMETERS()
 */
final class ChannelFunctionAction extends Enum {
    const OPEN = 10;
    const CLOSE = 20;
    const SHUT = 30;
    const REVEAL = 40;
    const REVEAL_PARTIALLY = 50;
    const TURN_ON = 60;
    const TURN_OFF = 70;
    const SET_RGBW_PARAMETERS = 80;

    /**
     * @Groups({"basic"})
     */
    public function getId(): int {
        return $this->value;
    }

    /**
     * @Groups({"basic"})
     */
    public function getName(): string {
        return $this->getKey();
    }

    /**
     * @Groups({"basic"})
     */
    public function getCaption(): string {
        return self::captions()[$this->getValue()];
    }

    public static function captions(): array {
        return [
            self::OPEN => 'Open',
            self::CLOSE => 'Close',
            self::SHUT => 'Shut',
            self::REVEAL => 'Reveal',
            self::REVEAL_PARTIALLY => 'Reveal partially',
            self::TURN_ON => 'On',
            self::TURN_OFF => 'Off',
            self::SET_RGBW_PARAMETERS => 'Adjust parameters',
        ];
    }

    public function validateActionParam($actionParams) {
        switch ($this->getValue()) {
            case self::REVEAL_PARTIALLY:
                return $this->validateActionParamForRevealPartially($actionParams);
            case self::SET_RGBW_PARAMETERS:
                return $this->validateActionParamForRgbwParameters($actionParams);
            default:
                Assertion::null($actionParams, "Action {$this->getKey()} is not expected to have an action parameters.");
                return null;
        }
    }

    private function validateActionParamForRevealPartially($actionParams) {
        Assert::that($actionParams)->count(1)->keyExists('percentage');
        Assert::that($actionParams['percentage'])->numeric()->between(0, 100);
        $actionParams['percentage'] = intval($actionParams['percentage']);
        return $actionParams;
    }

    private function validateActionParamForRgbwParameters($actionParams) {
        Assertion::true(is_array($actionParams));
        Assertion::between(count($actionParams), 1, 3);
        Assertion::count(array_intersect_key($actionParams, array_flip(['hue', 'color_brightness', 'brightness'])), count($actionParams));
        if (isset($actionParams['hue'])) {
            Assertion::true(is_numeric($actionParams['hue']) || in_array($actionParams['hue'], ['random', 'white']));
            if (is_numeric($actionParams['hue'])) {
                Assert::that($actionParams['hue'])->between(0, 359);
                $actionParams['hue'] = intval($actionParams['hue']);
            }
            Assertion::keyExists($actionParams, 'color_brightness');
            Assert::that($actionParams['color_brightness'])->numeric()->between(0, 100);
            $actionParams['color_brightness'] = intval($actionParams['color_brightness']);
        }
        if (isset($actionParams['brightness'])) {
            Assert::that($actionParams['brightness'])->numeric()->between(0, 100);
            $actionParams['brightness'] = intval($actionParams['brightness']);
        }
        return $actionParams;
    }
}
