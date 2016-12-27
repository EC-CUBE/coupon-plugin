<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
        $this->addSql("alter table plg_coupon add coupon_member SMALLINT DEFAULT 0");
        $this->addSql("alter table plg_coupon add coupon_lower_limit");
        $this->addSql("alter table plg_coupon_order add coupon_cancel_flg SMALLINT DEFAULT 0");
    }

    /**
     * down
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}

