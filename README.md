# tomk79/px2-publish-path-rewriter

Pickles Framework(PxFW) のパブリッシュ機能を代替し、パス書き換え機能付きのパブリッシュ環境を提供します。

## 導入方法 - Setup

### 1. [Pickles 2](http://pickles2.pxt.jp/) をセットアップ

### 2. composer.json に、パッケージ情報を追加

```
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/tomk79/px2-publish-path-rewriter.git"
        }
    ],
    "require": {
        "tomk79/px2-publish-path-rewriter": "dev-master"
    }
}
```

### 3. composer update

更新したパッケージ情報を反映します。

```
$ composer update
```

### 4. config.php を更新

`$conf->funcs->before_content` に、プラグインを設定します。
通常は、予め `'picklesFramework2\commands\publish::register'` が設定されていますが、これを削除し、置き換えてください。

```
<?php
return call_user_func( function(){

  /* (中略) */

  // funcs: Before content
  $conf->funcs->before_content = [
    // PX=publish
    // px2-publish-path-rewriter - 相対パス・絶対パスを変換して出力する
    //   options
    //     string 'PX':
    //       Pxコマンド名を指定します。デフォルトは 'publish' です。
    //     array 'rules':
    //       パスの変換ルールを2次元配列で指定します。
    //       各項目ごとに、pregパターン と 変換後のパスのルールを記述します。
    //       上の項目から順に、pregパターンにマッチするか検索されます。
    //       マッチするルールを見つけると、それを適用して、検索を終了します。
    //     array 'dom_selectors':
    //       パス変換対象とする属性を検索するセレクタを設定します。
    //       キーにセレクタを、値に属性名を設定します。
    'tomk79\pickles2\publishPathRewriter\publish::register('.json_encode([
      "PX"=>"publish",
      "rules"=>[
        ['/^(.*)\/([^\/]+)_files(?:\/resources)?\/(.*)$/s','$1/img/$3'],
        ['/^\/sample_pages\/conflict\/before_[0-9]\.html$/s','/sample_pages/conflict/after.html'],
        ['/^\/sample_pages\/(.*\.(?:html))$/s','/sample/$1'],
      ],
      "dom_selectors"=>array(
        '*[href]'=>'href',
        '*[src]'=>'src',
        'form[action]'=>'action',
      ),
    ]).')' ,

  ];

  /* (中略) */

  return $conf;
} );
```


## ライセンス - License

MIT License


## 作者 - Author

- (C)Tomoya Koyanagi <tomk79@gmail.com>
- website: <http://www.pxt.jp/>
- Twitter: @tomk79 <http://twitter.com/tomk79/>
