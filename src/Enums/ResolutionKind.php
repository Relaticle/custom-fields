<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

enum ResolutionKind: string
{
    case Infolist = 'infolist';
    case Table = 'table';
    case Exporter = 'exporter';
}
