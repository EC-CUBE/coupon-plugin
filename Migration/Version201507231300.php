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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version201507231300 extends AbstractMigration
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->createCoupon($schema);
        $this->createCouponDetail($schema);
        $this->createCouponOrder($schema);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('plg_coupon');
        $schema->dropSequence('plg_coupon_coupon_id_seq');
        $schema->dropTable('plg_coupon_detail');
        $schema->dropSequence('plg_coupon_detail_coupon_detail_id_seq');
        $schema->dropTable('plg_coupon_order');
        $schema->dropSequence('plg_coupon_order_id_seq');
    }

    /**
     * クーポン情報テーブル作成
     * create sequence plg_coupon_coupon_id_seq
     * create table plg_coupon(
     *     coupon_id integer NOT NULL PRIMARY KEY,
     *     coupon_cd text NOT NULL,
     *     coupon_type integer NOT NULL,
     *     discount_type integer NOT NULL,
     *     discount_price numeric(10,0) DEFAULT NULL::numeric,
     *     discount_rate numeric(10,0) DEFAULT NULL::numeric,
     *     enable_flag smallint DEFAULT 0 NOT NULL,
     *     available_from_date timestamp(0) without time zone,
     *     available_to_date timestamp(0) without time zone
     *     del_flg smallint DEFAULT 0 NOT NULL,
     *     create_date timestamp(0) without time zone NOT NULL,
     *     update_date timestamp(0) without time zone NOT NULL
     * )
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
     * create sequence plg_coupon_detail_coupon_detail_id_seq;
     * create table plg_coupon_detail(
     *     coupon_detail_id integer NOT NULL PRIMARY KEY,
     *     coupon_id integer NOT NULL,
     *     coupon_type integer NOT NULL,
     *     product_id integer,
     *     category_id integer,
     *     del_flg smallint DEFAULT 0 NOT NULL,
     *     create_date timestamp(0) without time zone NOT NULL,
     *     update_date timestamp(0) without time zone NOT NULL
     * )
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
            'notnull' => false
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
     * CREATE TABLE plg_coupon_order (
     *     id integer DEFAULT nextval('plg_coupon_order_id_seq'::regclass) NOT NULL PRIMARY KEY,
     *     order_id integer NOT NULL,
     *     pre_order_id text,
     *     order_date timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
     *     coupon_id integer,
     *     coupon_cd text,
     *     del_flg smallint DEFAULT 0 NOT NULL,
     *     create_date timestamp(0) without time zone NOT NULL,
     *     update_date timestamp(0) without time zone NOT NULL
     * );
     * @param Schema $schema
     */
    protected function createCouponOrder(Schema $schema) {
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
}