# coupon-plugin

[![Build Status](https://travis-ci.org/eccubevn/coupon-plugin.svg?branch=coupon-renewal)](https://travis-ci.org/eccubevn/coupon-plugin)
[![Build status](https://ci.appveyor.com/api/projects/status/py2o9y298u0qidip/branch/coupon-renewal?svg=true)](https://ci.appveyor.com/project/lammn/coupon-plugin/branch/coupon-renewal)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/2a59aeb7-3292-4c61-a872-df8410c5bf91/mini.png)](https://insight.sensiolabs.com/projects/2a59aeb7-3292-4c61-a872-df8410c5bf91)
[![Coverage Status](https://coveralls.io/repos/github/eccubevn/coupon-plugin/badge.svg?branch=coupon-renewal)](https://coveralls.io/github/eccubevn/coupon-plugin?branch=coupon-renewal)

##クーポン管理

商品・カテゴリーに対して、一定額・一定率での割引を適用するクーポンを発行できます。

## 管理画面

* クーポン管理機能を追加
* 受注管理 : 受注詳細について、利用されたクーポンコードの表記を追加

## フロント
* 注文確認 : クーポンコードの入力
 - 入力されたクーポンコードに対応する商品がカゴ中にあれば、割引適用
 - 割引 = 小計 + 手数料 + 送料 - (クーポン設定額 or 割引率から求めた値）
