<?php

namespace Database\Seeders;

use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ListingShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('status', '!=', Tenant::STATUS_SUSPENDED)->first()
            ?? Tenant::factory()->create([
                'name' => 'Revebnb Editorial',
                'slug' => 'revebnb-editorial',
                'status' => Tenant::STATUS_ACTIVE,
            ]);

        $landlord = Landlord::query()->where('tenant_id', $tenant->id)->first()
            ?? Landlord::factory()->for($tenant)->create([
                'name' => 'Yi',
                'email' => 'yi@revebnb.test',
                'status' => Landlord::STATUS_ACTIVE,
            ]);

        $listings = [
            ['上海 · 武康路藤蔓老公寓',         '上海', '徐汇区武康路 110 弄',     488, 4],
            ['北京 · 国子监深巷四合书房',       '北京', '东城区国子监街 23 号',    688, 3],
            ['杭州 · 西溪湿地畔的设计住所',     '杭州', '西湖区西溪国家湿地公园北', 528, 4],
            ['成都 · 玉林路慢生活公寓',         '成都', '武侯区玉林南路 8 号',     368, 3],
            ['大理 · 苍山下的白族民居',         '大理', '大理古城人民路尾',        298, 6],
            ['厦门 · 鼓浪屿百年红砖小楼',       '厦门', '思明区鼓浪屿龙头路',      598, 4],
            ['深圳 · 蛇口海边艺术家工作室',     '深圳', '南山区蛇口海上世界',      658, 2],
            ['广州 · 永庆坊岭南院落',           '广州', '荔湾区恩宁路永庆坊',      438, 4],
            ['苏州 · 平江路临河书斋',           '苏州', '姑苏区平江路 38 号',      478, 3],
            ['南京 · 颐和路民国洋房',           '南京', '鼓楼区颐和路 18 号',      528, 5],
            ['西安 · 大唐不夜城旁的现代住宅',   '西安', '雁塔区雁塔南路',          328, 4],
            ['重庆 · 山城步道悬崖客厅',         '重庆', '渝中区中山一路 161 号',   388, 3],
            ['青岛 · 八大关老别墅区',           '青岛', '市南区荣成路 6 号',       548, 6],
            ['丽江 · 玉龙雪山下的木屋',         '丽江', '玉龙纳西族自治县白沙',    298, 8],
            ['三亚 · 海棠湾观海套房',           '三亚', '海棠区海棠湾度假区',      798, 4],
            ['哈尔滨 · 中央大街阁楼公寓',       '哈尔滨', '道里区中央大街 89 号',  268, 3],
            ['长沙 · 太平街老洋房',             '长沙', '天心区太平街',            298, 4],
            ['福州 · 三坊七巷古厝',             '福州', '鼓楼区南后街',            378, 5],
            ['昆明 · 翠湖边的滇式小院',         '昆明', '五华区翠湖南路',          258, 6],
            ['武汉 · 江汉关历史街区公寓',       '武汉', '江岸区江汉关',            308, 3],
            ['天津 · 五大道德式别墅',           '天津', '和平区马场道',            488, 6],
            ['宁波 · 月湖畔的茶室住宅',         '宁波', '海曙区月湖景区',          358, 3],
            ['泉州 · 西街老巷红砖民宿',         '泉州', '鲤城区西街 162 号',       278, 4],
            ['佛山 · 岭南天地骑楼住宅',         '佛山', '禅城区祖庙街道',          318, 4],
        ];

        foreach ($listings as $index => [$title, $city, $address, $price, $guests]) {
            $slug = Str::slug($title.'-'.$index, '-', null).'-'.($index + 1);

            if (Listing::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $listing = Listing::query()->create([
                'tenant_id' => $tenant->id,
                'landlord_id' => $landlord->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $this->makeDescription($city),
                'city' => $city,
                'address' => $address,
                'nightly_price' => $price,
                'currency' => 'CNY',
                'status' => Listing::STATUS_PUBLISHED,
                'min_nights' => 1,
                'max_guests' => $guests,
                'guest_info_html' => '<p>入住时间 16:00 之后 · 退房时间 12:00 之前 · 全屋禁烟。</p>',
                'published_at' => now()->subDays(30 - $index),
            ]);

            $seedBase = $listing->id * 7;
            for ($i = 0; $i < 5; $i++) {
                ListingImage::query()->create([
                    'listing_id' => $listing->id,
                    'path' => "https://picsum.photos/seed/revebnb-{$seedBase}-{$i}/1600/1800",
                    'sort_order' => $i,
                    'is_cover' => $i === 0,
                ]);
            }
        }
    }

    private function makeDescription(string $city): string
    {
        return "我们在{$city}寻到这处住所时，第一感受是「呼吸顺畅」。"
            ."空间被设计师重新梳理过，自然光与本地材料是叙事的主角。\n\n"
            .'适合写作、长居、与一两位挚友共度周末。住所内备有完善的厨具、'
            .'本地烘焙咖啡豆与艺术家挑选的小型藏书。';
    }
}
