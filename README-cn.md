# About

## 1. 概述

- 这是个为 php 设计的更新工具；
- 可以配合 GitHub Hook 功能实现简单的自动部署；

## 2. 功能

1. 下载 zip 文件到服务器；
1. 自动解压；
1. 清空目标文件（目录）；
1. 复制新文件（目录）到目标文件（目录）；
1. 可设置要保护的文件（目录）；

## 3. 应用

### 3.1. 手动部署 github 上的网站到虚拟主机

- 配置与 `deploy.php` 处于同一目录下的 `deploy.json`；
- 通过浏览器访问虚拟主机上的 `deploy.php`；
- 完成部署；

### 3.2. 自动更新 markdown 内容文件到网站

- 配置与 `deploy.php` 处于同一目录下的 `deploy.json`；
- 利用 github 的 hook 功能监听源码库的特定分支；
- 设置该 hook 的 push 事件触发 http 请求访问虚拟主机上的 `deploy.php`；
- 完成部署；

## 4. 其他

- 复制时，已存在的文件会被覆盖；
- 中文文件名会有不能删除的问题；