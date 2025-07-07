<?php

// ABOUTME: Defines the different contexts where custom field components can be used
// ABOUTME: Used by builders and factories to create appropriate component types

namespace Relaticle\CustomFields\Filament\Integration\Enums;

enum ComponentContext: string
{
    case FORM = 'form';
    case TABLE = 'table';
    case INFOLIST = 'infolist';
}
