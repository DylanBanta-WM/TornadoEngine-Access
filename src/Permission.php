<?php

namespace TornadoEngine\Access;

/**
 * A permission a module declared, e.g. "docs.view".
 */
final readonly class Permission
{
    public function __construct(
        public string $key,
        public string $label,
        public string $module,
    ) {}
}
