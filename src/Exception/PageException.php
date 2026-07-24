<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

final class PageException extends RuntimeException implements ZeroGravityException
{
    public static function invalidSettingValue(string $settingValue, string $pageName): self
    {
        return new self(sprintf('Invalid setting value type "%s" on page "%s"',
            get_debug_type($settingValue),
            $pageName,
        ));
    }
}
