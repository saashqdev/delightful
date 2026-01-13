<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Stub\Domain;

use App\Domain\Chat\Service\DelightfulLLMDomainService;

/**
 * @internal
 */
class DelightfulLLMDomainServiceStub extends DelightfulLLMDomainService
{
    public function searchWithBing(string $query, null|bool|string $language = false): array
    {
        return [
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.0',
                'name' => 'ARL (Asset Reconnaissance Lighthouse) - Asset Reconnaissance System',
                'url' => 'https://github.com/Aabyss-Team/ARL',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://github.com/Aabyss-Team/ARL',
                'snippet' => 'ARL (Asset Reconnaissance Lighthouse) Asset Reconnaissance System. Note: The official ARL open-source project was deleted, so this project was created as a backup. All content is from the latest version of TophantTechnology/ARL. For reasons why the official ARL project was closed: https://mp.weixin.qq.com/s/hM3t3lYQVqDOlrLKz3_TSQ. Latest source backup for ARL-NPoC (ARL core): https://github.com/Aabyss-Team/ARL-NPoC. Latest backup for arl_file (ARL-related builds): https://github.com/Aabyss-Team/arl_files.',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4945530744419986&mkt=en-US&setlang=en-US&w=PiMd349mKgnI96oFxU0XsQTRzvw548pH',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.1',
                'name' => 'Design and Practice of Tencent Lighthouse Fusion Engine - Tencent Cloud Developer Community',
                'url' => 'https://cloud.tencent.com/developer/article/2219100',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://cloud.tencent.com/developer/article/2219100',
                'snippet' => 'This article shares the topic of design and practice of Tencent Lighthouse fusion engine, introduced around four main aspects: 1. Background introduction. 2. Challenges and fusion analysis engine solutions. 3. Practice summary. 4. Future evolution direction. Author: Feng Guojing, Tencent Backend Engineer. I. Background Introduction. Tencent Lighthouse is an end-to-end full-chain data product suite designed to help product, R&D, operations, and data science teams make more reliable and timely decisions within 30 minutes, promoting user growth and retention. After 2020, data volume continues to grow explosively, and business changes accelerate with increasingly complex analysis needs. Traditional models cannot invest more time in planning data models. We face a triangle challenge of massive, real-time, and customized data. Different engines are working to solve this problem.',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4735030109934171&mkt=en-US&setlang=en-US&w=55TpfZ1tgzNpPgJrco_c-mdoWXPNultg',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.2',
                'name' => 'Information Gathering - Building Your Lighthouse (ARL) - FreeBuf Cybersecurity Industry Portal',
                'url' => 'https://www.freebuf.com/sectool/349664.html',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://www.freebuf.com/sectool/349664.html',
                'snippet' => 'Specific Features: File leaks often find some sensitive files (maybe I got lucky [doge]). Remove restrictions. By default, Lighthouse cannot scan edu, org, gov websites, but restrictions can be lifted. First open config-docker.yaml in /ARL/docker directory, comment out these three lines. Edit. Then enter /ARL/app directory, open config.py and config.yaml.example files, modify the corresponding locations. Edit. Then enter the web container to modify the configuration, use command docker ps to view, find the line with NAME arl_web and copy CONTAINER ID, as shown in the circled parameters. Edit. Enter command docker exec -it (your CONTAINER ID value) /bin/bash. Edit.',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4647387004557550&mkt=en-US&setlang=en-US&w=VdHh5_9GN1hBY2oY__8aPcs5C5wmC6i7',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.3',
                'name' => 'Lighthouse Engine - Empowering Digital Transformation of Chain Operation Industry',
                'url' => 'https://www.delightful.com/',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://www.delightful.com',
                'snippet' => 'Guangdong Lighthouse Engine Technology Co., Ltd. is a provider of digital intelligence solutions focused on the chain operation industry. • One-stop Solution: We provide comprehensive core technology application services, including technology R&D, system implementation, intelligent operations and maintenance, intelligent decision-making, and big data analysis, providing complete solutions for customers chain business. • Comprehensive intelligent ...',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=5053270008094152&mkt=en-US&setlang=en-US&w=XY93sySUQ8L59i0phxcRUA1JeOZwlyft',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.4',
                'name' => 'ARL Asset Lighthouse System Installation and Usage Documentation - GitHub Pages',
                'url' => 'https://tophanttechnology.github.io/ARL-doc/',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://tophanttechnology.github.io/ARL-doc',
                'snippet' => 'ARL (Asset Reconnaissance Lighthouse) Asset Surveillance Lighthouse System aims to quickly reconnoiter internet assets associated with targets and build a basic asset information base. Assists security teams or penetration testers in effectively reconnoitring and retrieving assets, continuously detecting asset risks from an attacker perspective, helping users stay informed of asset dynamics at all times, grasping weak points in security protection, rapidly ...',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4785422958534490&mkt=en-US&setlang=en-US&w=ElCfk_kwjIKAKJdmzzIfhiSKN8_6tGWY',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.5',
                'name' => 'From Manufacturing to Intelligent Manufacturing: Lighthouse Experience Promotes Chinese Manufacturing Transformation and Upgrading',
                'url' => 'https://www.mckinsey.com.cn/%E4%BB%8E%E5%88%B6%E9%80%A0%E5%88%B0%E6%99%BA%E9%80%A0%EF%BC%9A%E7%81%AF%E5%A1%94%E7%BB%8F%E9%AA%8C%E5%8A%A9%E5%8A%9B%E4%B8%AD%E5%9B%BD%E5%88%B6/',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://www.mckinsey.com.cn/from-manufacturing-to-intelligent-manufacturing-lighthouse-experience',
                'snippet' => 'From Manufacturing to Intelligent Manufacturing: Lighthouse Experience Promotes Chinese Manufacturing Transformation and Upgrading. Authors: Karel Eloot, Hou Wenhao, Francisco Betti, Enno de Boer and Yves Giraud. As the main body of Chinas real economy, manufacturing is an important engine driving the development of Chinas economy and the continued growth of global manufacturing. Standing at a new starting point where history and future converge ...',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4935428983423158&mkt=en-US&setlang=en-US&w=nss5pY5CutHEX4bOln8TFwbaBwkcx6D0',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.6',
                'name' => 'Asset Reconnaissance Lighthouse System-ARL Quick Reconnaissance of Related Assets - YuCong',
                'url' => 'https://www.ddosi.org/arl/',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'https://www.ddosi.org/arl',
                'snippet' => 'ARL (Asset Reconnaissance Lighthouse) Asset Surveillance Lighthouse System aims to quickly reconnoiter internet assets associated with targets and build a basic asset information base. Assists security teams or penetration testers in effectively reconnoitring and retrieving assets, continuously detecting asset risks from an attacker perspective, helping users stay informed of asset dynamics at all times, grasping weak points in security protection, rapidly ...',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://cncc.bingj.com/cache.aspx?q=%E7%81%AF%E5%A1%94%E5%BC%95%E6%93%8E&d=4889996822853925&mkt=en-US&setlang=en-US&w=4jMPcucuoGOvtrB94WunDL24vDnqXc5S',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
            [
                'id' => 'https://api.bing.microsoft.com/api/v7/#WebPages.7',
                'name' => 'Lighthouse Index',
                'url' => 'http://www.dotaindex.com/',
                'datePublished' => '2024-10-16 15:10:43',
                'datePublishedDisplayText' => '2024-10-16 15:10:43',
                'isFamilyFriendly' => true,
                'displayUrl' => 'www.dotaindex.com',
                'snippet' => 'Alzheimers & Dementia | Lecanemab Planning: A Blueprint for Safe and Effective Complex Therapy Management. Alzheimers & Dementia | Social Norm Knowledge Impairment in Presymptomatic, Prodromal and Symptomatic Frontotemporal Dementia. Alzheimers & Dementia | IL6 Receptor Inhibitors: Exploring Therapeutic Potential for Multiple Diseases Through Drug Target Mendelian Randomization. Alzheimers & Dementia | Aquaporin-4 ...',
                'dateLastCrawled' => '2024-10-16 15:10:43',
                'cachedPageUrl' => 'http://www.dotaindex.com/',
                'language' => 'en',
                'isNavigational' => false,
                'noCache' => false,
            ],
        ];
    }
}
