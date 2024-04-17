# 收货地址智能解析（纯PHP版）

#### Golang版本
[Go语言地址智能解析](https://github.com/pupuk/addr)

本项目包含2个功能
- 把字符串解析成姓名、收货电话、邮编、身份证号、收货地址
- 把收货地址解析成省、市、区县、街道地址
- 支持提取虚拟号码有分机号的情况（适用美团，拼多多等需要保护客户隐私的情况 2022.11.13更新）

特色是：***简单易用***

该项目依然采用的是，统计特征分析，然后以最大的概率来匹配，得出大概率的解。因此只能解析中文的收货信息，不能保证100%解析成功，但是从生产环境的使用情况来看，解析成功率保持在96%以上，就算是百度基于人工智能的地址识别，经我实测，也是有一定的不能识别的情况。

**由于上个项目[address-smart-parse](https://github.com/pupuk/address-smart-parse)，地址解析的过程，是对照的数据库里面的地址库，有很多朋友看到相关代码的时候，不知如何改写，为了方便大家，写了一个纯PHP的，开箱即用。纯PHP版本采用遍历搜索，相对于DB的查询，有略微的性能损失，但解析一个地址仍然不到10ms，PHP开启Opcache更是解析1个地址不到5ms。**

### 使用
so easy；
```php
require 'address.php';
$string = '深圳市龙华区龙华街道1980科技文化产业园3栋317    张三    13800138000 518000 120113196808214821';
$r = Address::smart($string);
print_r($r);
```
结果为：
![image](https://user-images.githubusercontent.com/7934974/83218657-f0804980-a1a0-11ea-9c0e-e735ef35749e.png)

`demo.php`里面有使用示例，如果字符串里面不包含电话，姓名，身份证等，只需要解析地址，可以用：
```php
 Address::smart($string, $user = false);
```
### Star History
[![Star History Chart](https://api.star-history.com/svg?repos=pupuk/address&type=Date)](https://star-history.com/#pupuk/address&Date)

### 反馈 &改进
#### Issue
如果有什么问题或建议，或者发现有不能识别，或者识别错误的地址，
提交到[Github Issue](https://github.com/pupuk/address/issues)
我会继续改进维护代码

#### 协作
                
1. fork，优化，PR
2. 欢迎改写成其它语言版本，只需注明参考链接即可。

#### 联系我
* Email：pujiexuan@gmail.com
* QQ: 632085136 欢迎一起学习讨论

### 致谢
后来在网上我发现一些作者基于我的识别逻辑，写了js等版本，方便了大家，但是很少注明参考链接。
小小项目，开源不易，谢谢你能给一个star，另外我还在此项目上，写了一个`addrss_pro`版，解析更准确（准确率在98%左右）。如果此项目的star数目超过500，我也会开源分享出来，里面还会有一些注释，能体现解析的核心逻辑，到时，我也会分享golang的版本，谢谢
