<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;

enum RelationshipCardinality: string implements HasLabel
{
    case OneToOne = 'one_to_one';
    case OneToMany = 'one_to_many';
    case ManyToOne = 'many_to_one';
    case ManyToMany = 'many_to_many';

    // Cardinality reads from the from side to the to side, so one_to_many means one from-record
    // holds many links: the constrained end is the to side, not the from side.
    public function fromSideIsSingle(): bool
    {
        return in_array($this, [self::OneToOne, self::ManyToOne], true);
    }

    public function toSideIsSingle(): bool
    {
        return in_array($this, [self::OneToOne, self::OneToMany], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::OneToOne => __('custom-fields::custom-fields.enums.relationship_cardinality.one_to_one'),
            self::OneToMany => __('custom-fields::custom-fields.enums.relationship_cardinality.one_to_many'),
            self::ManyToOne => __('custom-fields::custom-fields.enums.relationship_cardinality.many_to_one'),
            self::ManyToMany => __('custom-fields::custom-fields.enums.relationship_cardinality.many_to_many'),
        };
    }
}
