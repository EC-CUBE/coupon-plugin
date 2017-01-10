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
 * Version201612231311.
 */
class Version201612231311 extends AbstractMigration
{
    /**
     * @var string coupon table name
     */
    const COUPON = 'plg_coupon';

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
        $table = $schema->getTable(self::COUPON);
        if (!$table->hasColumn('coupon_member')) {
            $this->addSql('alter table plg_coupon add coupon_member SMALLINT DEFAULT 0');
        }

        if (!$table->hasColumn('coupon_lower_limit')) {
            $this->addSql('alter table plg_coupon add coupon_lower_limit integer DEFAULT 0');
        }

        if (!$table->hasColumn('coupon_release')) {
            $this->addSql('alter table plg_coupon add coupon_release integer DEFAULT 0');
            $this->addSql('update plg_coupon set coupon_release = coupon_use_time');
        }

        $table = $schema->getTable(self::COUPON_ORDER);
        if (!$table->hasColumn('coupon_name')) {
            $this->addSql('alter table plg_coupon_order add coupon_name text');
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
