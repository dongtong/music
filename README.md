# 麦葱特制网易一听百度酷狗酷我QQ虾米5sing天天动听咪咕SoundCloud音乐搜索解决方案

## (๑•̀ㅂ•́)و✧

数据调用的是各音频网站 JSON 接口！

问我怎么来的？噗，抓包发现的。。。

有的接口并不是开放的API接口，随时可能失效，所以本项目相关代码仅供参考。

## Demo / 演示

[http://music.2333.me/](http://music.2333.me/ "音乐搜索器")

如果获取失败什么的，可以到 [我的博客](https://maicong.me/msg) 留言告诉我。

## 更新日志

-   2017.04.21 `v1.1.3` 优化代码和播放器视觉
-   2017.04.20 `v1.1.2` 更新音乐地址匹配规则
-   2017.03.24 `v1.1.1` 移除对天天动听的支持，修复无法获取咪咕音乐的问题，更新 SoundCloud client_id
-   2017.03.23 `v1.1.0` 更新外链资源地址，优化代码
-   2015.06.15 `v1.0.4` 增加对 SoundCloud 的支持，增加代理支持，修复音乐名称识别问题，优化代码
-   2015.06.13 `v1.0.3` 增加对 天天动听、咪咕 的支持
-   2015.06.12 `v1.0.2` 增加对 5sing 的支持 (开源发布)
-   2015.06.12 `v1.0.1` 代码优化 + BUG修复
-   2015.06.10 `v1.0.0` 音乐搜索器上线

## 数据获取失败解决方案

方案1：
```
修改 index.php 文件里的 MC_PROXY 为您的代理地址
将 music.php 里需要代理的 URL 'proxy' => false 改为 'proxy' => true
```
方案2：
```
在 music.php 里查找 setTimeout，将其后面的数值 20 改为更大。
在 static/music.js 里查找 data: post_data，将其上面的数值 30000 改为更大。
```
方案3：
```
更换服务器，选择延迟更低的服务器。
```

## MIT

You know.
