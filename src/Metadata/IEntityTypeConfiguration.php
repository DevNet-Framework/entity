<?php declare(strict_types = 1);
/**
 * @author      Mohammed Moussaoui
 * @copyright   Copyright (c) Mohammed Moussaoui. All rights reserved.
 * @license     MIT License. For full license information see LICENSE file in the project root.
 * @link        https://github.com/artister
 */

namespace Artister\Data\Entity\Metadata;

use Artister\Data\Entity\Metadata\EntityTypeBuilder;

interface IEntityTypeConfiguration
{
    public function getEntityName() : string;

    public function configure(EntityTypeBuilder $builder);
}