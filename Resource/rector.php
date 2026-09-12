<?php

declare(strict_types=1);

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    // EC-CUBE 4.4 の最小サポートバージョンに合わせる
    ->withPhpVersion(PhpVersion::PHP_82)
    // プラグインのソースディレクトリ
    ->withPaths([
        dirname(__DIR__).'/Controller',
        dirname(__DIR__).'/Entity',
        dirname(__DIR__).'/Form',
        dirname(__DIR__).'/Repository',
        dirname(__DIR__).'/Service',
        dirname(__DIR__).'/PluginManager.php',
        dirname(__DIR__).'/Event.php',
        dirname(__DIR__).'/Nav.php',
    ])
    ->withSkip([
        dirname(__DIR__).'/vendor',
        dirname(__DIR__).'/node_modules',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        // Symfony 7.4 対応 (@Route → #[Route], @Template, buildForm(): void 等)
        // 各ルールが composer.json / installed.json を見てインストール済みバージョンに合うものだけ
        // 実行する。rector 2.6.2 でバージョン別のセット定数 (SYMFONY_74 等) は撤去された
        // (本体の対応: EC-CUBE/ec-cube#7099)
        SymfonySetList::COMPOSER_BASED,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        // Doctrine ORM 3.0 / DBAL 3.0 対応 (@ORM → #[ORM], 型付きプロパティ)
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::COMPOSER_BASED,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    // Symfony/Doctrine 等のアノテーション → アトリビュート変換を有効化
    ->withAttributesSets()
    // #[Route] は付与されるが use 文が旧 Annotation のまま残るため Attribute へ統一する
    ->withConfiguredRule(RenameClassRector::class, [
        'Symfony\Component\Routing\Annotation\Route' => 'Symfony\Component\Routing\Attribute\Route',
    ])
    ->withImportNames(
        importShortClasses: false,
        importDocBlockNames: true,
        importNames: true
    )
    ->withParallel();
