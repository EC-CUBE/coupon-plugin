<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version201706011311.
 */
class Version201706011311 extends AbstractMigration
{
    /**
     * @var string coupon order table
     */
    const COUPON_ORDER = 'plg_coupon_order';
    /**
     * up.
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable(self::COUPON_ORDER);
        if (!$table->hasColumn('order_change_status')) {
            $this->addSql('alter table plg_coupon_order add order_change_status SMALLINT DEFAULT 0');
        }
    }

    /**
     * down.
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
