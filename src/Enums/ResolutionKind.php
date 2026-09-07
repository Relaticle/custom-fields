<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

enum ResolutionKind: string
{
    case Form = 'form';
    case Infolist = 'infolist';
    case Table = 'table';
    case Exporter = 'exporter';
    case Importer = 'importer';
}
