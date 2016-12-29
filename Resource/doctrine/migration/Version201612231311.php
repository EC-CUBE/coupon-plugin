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
 * Version201612231311
 */
class Version201612231311 extends AbstractMigration
{
    /**
     * up
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!Version::isSupportGetInstanceFunction()) {
            $this->addSql("alter table plg_coupon add coupon_member SMALLINT DEFAULT 0");
            $this->addSql("alter table plg_coupon add coupon_lower_limit");
        }
    }

    /**
     * down
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}

