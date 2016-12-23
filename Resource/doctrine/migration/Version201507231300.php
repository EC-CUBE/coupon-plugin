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
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;
use Doctrine\ORM\EntityManager;
use Plugin\Coupon\Util\Version;

/**
 * Version201507231300
 */
class Version201507231300 extends AbstractMigration
{
    /**
     * @var string coupon table name
     */
    const COUPON = 'plg_coupon';

    /**
     * @var string coupon detail table
     */
    const COUPON_DETAIL = 'plg_coupon_detail';

    /**
     * @var string coupon order table
     */
    const COUPON_ORDER = 'plg_coupon_order';

    /**
     * @var array plugin entity
     */
    protected $entities = array(
        'Plugin\Coupon\Entity\CouponCoupon',
        'Plugin\Coupon\Entity\CouponCouponDetail',
        'Plugin\Coupon\Entity\CouponCouponOrder',
    );

    protected $sequence = array(
        'plg_coupon_coupon_id_seq',
        'plg_coupon_detail_coupon_detail_id_seq',
        'plg_coupon_order_id_seq',
    );

    /**
     * Up method
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (Version::isSupportGetInstanceFunction()) {
            $this->createCouponDb($schema);
        } else {
            $this->createCoupon($schema);
            $this->createCouponDetail($schema);
            $this->createCouponOrder($schema);
        }
    }

    /**
     * Down method
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        if (Version::isSupportGetInstanceFunction()) {
            $app = Application::getInstance();
            $meta = $this->getMetadata($app['orm.em']);
            $tool = new SchemaTool($app['orm.em']);
            $schemaFromMetadata = $tool->getSchemaFromMetadata($meta);
            // テーブル削除
            foreach ($schemaFromMetadata->getTables() as $table) {
                if ($schema->hasTable($table->getName())) {
                    $schema->dropTable($table->getName());
                }
            }
            // シーケンス削除
            foreach ($schemaFromMetadata->getSequences() as $sequence) {
                if ($schema->hasSequence($sequence->getName())) {
                    $schema->dropSequence($sequence->getName());
                }
            }
        } else {
            if ($schema->hasTable(self::COUPON)) {
                $schema->dropTable(self::COUPON);
                $schema->dropSequence('plg_coupon_coupon_id_seq');
            }
            if ($schema->hasTable(self::COUPON_DETAIL)) {
                $schema->dropTable(self::COUPON_DETAIL);
                $schema->dropSequence('plg_coupon_detail_coupon_detail_id_seq');
            }
            if ($schema->hasTable(self::COUPON_ORDER)) {
                $schema->dropTable(self::COUPON_ORDER);
                $schema->dropSequence('plg_coupon_order_id_seq');
            }
        }

        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            foreach ($this->sequence as $sequence) {
                if ($schema->hasSequence($sequence)) {
                    $schema->dropSequence($sequence);
                }
            }
        }
    }


    /**
     * クーポン情報テーブル作成
     * @param Schema $schema
     */
    protected function createCoupon(Schema $schema)
    {
        $table = $schema->createTable("plg_coupon");
        $table->addColumn('coupon_id', 'integer', array(
            'autoincrement' => true,
            'notnull' => true,
        ));

        $table->addColumn('coupon_cd', 'text', array(
            'notnull' => true,
        ));

        $table->addColumn('coupon_name', 'text', array(
            'notnull' => false,
        ));

        $table->addColumn('coupon_type', 'integer', array(
            'notnull' => true,
        ));

        $table->addColumn('discount_type', 'integer', array(
            'notnull' => true,
        ));

        $table->addColumn('discount_price', 'decimal', array(
            'notnull' => false,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('discount_rate', 'decimal', array(
            'notnull' => false,
            'unsigned' => false,
        ));

        $table->addColumn('enable_flag', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 1,
        ));

        $table->addColumn('coupon_member', 'smallint', array(
            'notnull' => false,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('coupon_lower_limit', 'decimal', array(
            'notnull' => false,
            'unsigned' => false,
            'default' => 0,
        ));
        // 有効期間 開始日付
        $table->addColumn('available_from_date', 'datetime', array(
            'notnull' => false,
            'unsigned' => false,
        ));

        // 有効期間 終了日付
        $table->addColumn('available_to_date', 'datetime', array(
            'notnull' => false,
            'unsigned' => false,
        ));


        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('coupon_id'));
    }

    /**
     * クーポン詳細情報テーブル作成
     * @param Schema $schema
     */
    protected function createCouponDetail(Schema $schema)
    {
        $table = $schema->createTable("plg_coupon_detail");
        $table->addColumn('coupon_detail_id', 'integer', array(
            'autoincrement' => true,
            'notnull' => true,
        ));

        $table->addColumn('coupon_id', 'integer', array(
            'notnull' => true,
        ));

        $table->addColumn('coupon_type', 'integer', array(
            'notnull' => true,
        ));

        $table->addColumn('product_id', 'integer', array(
            'notnull' => false,
        ));

        $table->addColumn('category_id', 'integer', array(
            'notnull' => false,
        ));

        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('coupon_detail_id'));
    }

    /**
     * 注文クーポン情報テーブルの作成
     * @param Schema $schema
     */
    protected function createCouponOrder(Schema $schema)
    {
        $table = $schema->createTable("plg_coupon_order");
        $table->addColumn('id', 'integer', array(
            'autoincrement' => true,
            'notnull' => true,
        ));

        $table->addColumn('coupon_id', 'integer', array(
            'notnull' => false,
        ));

        $table->addColumn('coupon_cd', 'text', array(
            'notnull' => false,
        ));

        $table->addColumn('order_id', 'integer', array(
            'notnull' => true,
        ));

        $table->addColumn('pre_order_id', 'text', array(
            'notnull' => false,
        ));

        $table->addColumn('order_date', 'datetime', array(
            'notnull' => false,
            'unsigned' => false,
        ));

        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('coupon_cancel', 'smallint', array(
            'notnull' => false,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('id'));
    }

    /**
     * create all table of coupon
     * @param Schema $schema
     */
    protected function createCouponDb(Schema $schema)
    {
        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array();
        foreach ($this->entities as $entity) {
            $classes[] = $em->getMetadataFactory()->getMetadataFor($entity);
        }
        $tool = new SchemaTool($em);
        $tool->createSchema($classes);
    }

    /**
     * Get metadata.
     *
     * @param EntityManager $em
     *
     * @return array
     */
    protected function getMetadata(EntityManager $em)
    {
        $meta = array();
        foreach ($this->entities as $entity) {
            $meta[] = $em->getMetadataFactory()->getMetadataFor($entity);
        }

        return $meta;
    }
}
