---
name: install-laravel13
description: 安装与初始化 Laravel 13 项目，包含本地开发环境检查、依赖安装、项目创建、环境配置与验证命令。用于用户提到安装 laravel13、laravel 13 setup、初始化 laravel 13 项目时。
---

# 安装 Laravel 13

## 适用场景

当用户希望安装或初始化 Laravel 13 时使用：
- 安装 Laravel 13 本地开发环境
- 在当前仓库创建 Laravel 13 项目
- 验证 PHP/Composer/扩展/数据库基础依赖

## 快速触发词

- 安装 laravel13
- 安装 laravel 13
- laravel 13 setup

## 执行流程

按以下顺序执行，不要跳步。

### 1) 检查当前目录与已有文件

1. 先确认目标目录是否为空或是否允许初始化新项目。
2. 若目录非空，先提示风险并询问是否继续。

### 2) 运行环境检查脚本

执行：

```bash
bash .cursor/skills/install-laravel13/scripts/check_laravel13_env.sh
```

若脚本返回缺失项：
- 明确列出缺失的命令/扩展
- 给出系统级安装建议（按当前系统）
- 完成后再次执行检查脚本

### 3) 创建 Laravel 13 项目

执行：

```bash
bash .cursor/skills/install-laravel13/scripts/create_laravel13_project.sh my-app
```

说明：
- `my-app` 是目标目录名，可替换
- 若用户要求在当前目录初始化，可使用 `.` 作为目录参数

### 4) 安装与初始化

进入项目目录后按顺序执行：

```bash
composer install
cp .env.example .env
php artisan key:generate
```

若用户需要数据库：
1. 更新 `.env` 中数据库连接
2. 执行 `php artisan migrate`

### 5) 验证安装结果

至少执行以下检查：

```bash
php artisan --version
php artisan about
php artisan route:list
```

如用户要求启动服务，再执行：

```bash
php artisan serve
```

## 输出格式

对用户输出时使用以下结构：

```markdown
Laravel 13 安装结果：
- 环境检查：通过/失败（列出缺失项）
- 项目创建：成功/失败
- 初始化步骤：已完成项
- 验证命令：关键输出摘要
- 下一步建议：如数据库配置、队列、前端构建
```

## 常见问题处理

- Composer 版本过低：提示升级 Composer 后重试
- 缺少 PHP 扩展：提示安装 `mbstring`、`openssl`、`pdo`、`tokenizer`、`xml`、`ctype`、`json`、`bcmath`、`fileinfo`
- 权限问题：提示修正当前目录写权限后重试
- 目录已存在且非空：先确认是否覆盖或改用新目录
- Composer 拉包超时或卡在单个 dev 包（如 `laravel/pint`）：可先 `composer install --no-dev` 装齐运行所需依赖，网络稳定后再 `composer install` 补全开发依赖
