

## 新后台开发注意事项

**开发分支** 

digital-develop

**配置文件**

- session: application/config/extra/session.php
- mysql: application/config/extra/database.php
- cache: application/config/admin/extra/cache.php  
- .env:  在项目跟目录 用于配置项目环境变量 不放入版本库

```

walle post_release 操作：
ln -sf /data/prod_files/sports.api.kosun.net/uploads /srv/www/sports.api.kosun.net/public/uploads
ln -sf /data/prod_files/sports.api.kosun.net/.env /srv/www/sports.api.kosun.net/.env
sudo /usr/bin/systemctl reload php-fpm

```

以上文件在walle发布之后自动加入项目

**目录结构**

- application/admin 后台模块
- application/api  体彩服务端接口   请勿修改
- application/clearing  体彩结算模块 请勿修改
- application/collect  体彩对阵数据抓取模块 请勿修改
- application/common  公共模块，处理公共业务及逻辑操作
- application/config  配置 application/config/extra 中的每个文件 相当于 config/config.php 文件中的每个配置项
- extend  扩展类库
- public/index.php 项目入口
- runtime  不解释
- thinkphp 框架核心代码
- vendor 第三方类库 一般通过 composer安装,通常在本地升级 加入git类库  不建议在运行环境升级


**注意事项**

- 代码书写规范： 以最新的php代码规范为准
- 其它，待补充


**发布**

- walle发布时应排除 .md .txt 等重要的描述文件。

以下文件及目录必须排除
```
admin-frontend
application/api
application/clearing
application/collect
.gitignore
LICENSE.txt
README.md
composer.json
composer.lock
build.php
runtime
```


**足彩反水**

```
举栗说明：
首先我们设定某个层级的用户的返水比例是 2%
足球比赛的返水根据几种不同的情况给用户不同的返水：
1.赢，返全部  (下注金额 * 返水比例  下同)
2.赢一半， 返一半
3.输，返全部
4.输一半，返一半
5.其它情况不享受返水
```