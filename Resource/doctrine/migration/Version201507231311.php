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
use Plugin\Coupon\Util\Version;

/**
 * Version201507231311.
 */
class Version201507231311 extends AbstractMigration
{
    /**
     * up.
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!Version::isSupportGetInstanceFunction()) {
            $this->addSql('alter table plg_coupon_order add user_id integer');
            $this->addSql('alter table plg_coupon_order add email text');
            $this->addSql('alter table plg_coupon_order add discount decimal not null default 0');
            $this->addSql('alter table plg_coupon add coupon_use_time integer');
            if ($schema->hasTable('plg_coupon_plugin')) {
                $schema->dropTable('plg_coupon_plugin');
                $schema->dropSequence('plg_coupon_plugin_plugin_id_seq');
            }
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
