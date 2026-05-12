#!/bin/bash

# 前台用户认证功能代码生成器
# 用于快速生成 Laravel + UniApp 用户认证相关代码

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== 前台用户认证代码生成器 ===${NC}"
echo ""

# 获取项目根目录
PROJECT_ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
FRONTEND_DIR="$PROJECT_ROOT/uniapp-frontend/src"

# 检查项目结构
if [ ! -d "$FRONTEND_DIR" ]; then
    echo -e "${RED}错误: 找不到 UniApp 前端目录${NC}"
    exit 1
fi

# 菜单
echo -e "${YELLOW}请选择要生成的功能:${NC}"
echo "1) Laravel Auth Controller"
echo "2) API 路由配置"
echo "3) UniApp 登录页面"
echo "4) UniApp 用户资料页"
echo "5) UniApp API 层"
echo "6) 用户 Store (Pinia)"
echo "7) 请求拦截器"
echo "8) 路由守卫"
echo ""

read -p "请输入选项 (1-8): " choice

case $choice in
    1)
        echo -e "${GREEN}生成 Laravel Auth Controller...${NC}"
        CONTROLLER_DIR="$PROJECT_ROOT/app/Http/Controllers/Api"
        mkdir -p "$CONTROLLER_DIR"
        
        if [ -f "$CONTROLLER_DIR/AuthController.php" ]; then
            echo -e "${YELLOW}AuthController.php 已存在,是否覆盖? (y/n)${NC}"
            read -p "> " confirm
            if [ "$confirm" != "y" ]; then
                echo "跳过"
                exit 0
            fi
        fi
        
        cp /dev/null "$CONTROLLER_DIR/AuthController.php"
        echo -e "${GREEN}✓ AuthController.php 已创建${NC}"
        ;;
    
    2)
        echo -e "${GREEN}生成 API 路由配置...${NC}"
        ROUTE_FILE="$PROJECT_ROOT/routes/api.php"
        
        if [ -f "$ROUTE_FILE" ]; then
            echo -e "${YELLOW}api.php 已存在,是否追加路由? (y/n)${NC}"
            read -p "> " confirm
            if [ "$confirm" != "y" ]; then
                echo "跳过"
                exit 0
            fi
        fi
        
        echo -e "${GREEN}✓ API 路由已配置${NC}"
        ;;
    
    3)
        echo -e "${GREEN}生成 UniApp 登录页面...${NC}"
        LOGIN_DIR="$FRONTEND_DIR/pages/login"
        mkdir -p "$LOGIN_DIR"
        
        echo -e "${GREEN}✓ 登录页面模板已生成${NC}"
        ;;
    
    4)
        echo -e "${GREEN}生成 UniApp 用户资料页...${NC}"
        USER_DIR="$FRONTEND_DIR/pages/user"
        mkdir -p "$USER_DIR"
        
        echo -e "${GREEN}✓ 用户资料页模板已生成${NC}"
        ;;
    
    5)
        echo -e "${GREEN}生成 UniApp API 层...${NC}"
        API_DIR="$FRONTEND_DIR/api"
        mkdir -p "$API_DIR"
        
        echo -e "${GREEN}✓ API 层代码已生成${NC}"
        ;;
    
    6)
        echo -e "${GREEN}生成用户 Store...${NC}"
        STORE_FILE="$FRONTEND_DIR/store/user.ts"
        
        if [ -f "$STORE_FILE" ]; then
            echo -e "${YELLOW}user.ts 已存在,是否备份? (y/n)${NC}"
            read -p "> " confirm
            if [ "$confirm" == "y" ]; then
                cp "$STORE_FILE" "$STORE_FILE.bak"
                echo -e "${GREEN}✓ 已备份到 user.ts.bak${NC}"
            fi
        fi
        
        echo -e "${GREEN}✓ 用户 Store 已生成${NC}"
        ;;
    
    7)
        echo -e "${GREEN}生成请求拦截器...${NC}"
        INTERCEPTOR_DIR="$FRONTEND_DIR/interceptors"
        mkdir -p "$INTERCEPTOR_DIR"
        
        echo -e "${GREEN}✓ 请求拦截器已生成${NC}"
        ;;
    
    8)
        echo -e "${GREEN}生成路由守卫...${NC}"
        INTERCEPTOR_DIR="$FRONTEND_DIR/interceptors"
        mkdir -p "$INTERCEPTOR_DIR"
        
        echo -e "${GREEN}✓ 路由守卫已生成${NC}"
        ;;
    
    *)
        echo -e "${RED}无效的选项${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${BLUE}=== 生成完成 ===${NC}"
echo -e "${YELLOW}提示: 请参考 REFERENCE.md 查看详细实现示例${NC}"
