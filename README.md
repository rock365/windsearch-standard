windsearch，PHP全文检索中间件，以最快的速度实现网站的搜索功能。windsearch 标准版 ，可索引数十万甚至百万数据，适用于中小型网站。流程：导入数据->构建索引->前台搜索->返回id集合，然后你可使用这个id集合从数据库查询原始数据。标准版完全免费。

## 安装导入

```php
 require 'yourdirname/wind-search-standard/Wind.php';
```



## 开始使用

##### 创建索引库

```php
//定义相关参数

 //当前索引库的名称
$indexName = 'indexname';
// 需要索引的字段 例如：'columns' => ['title', 'tags'] 或 'columns' => 'title'，如果是数组，只会先进行拼接再分词
$column =  ['title', 'tags']; 
// 分隔符 可选，默认为空， 例如：【/ | , 】等，存在分隔符，则不会进行分词，直接以分隔符切分
$separator = ''; 
// 以下两个参数，是为了防止运行超时，要分批处理，只在构建索引过程中有效，默认即可
$indexSegNum = '5'; //分片数量 
$indexSegDataNum = '10000000'; // 每片存储数量

// 实例化对象
$Wind = new \Wind($indexName); //$indexName 当前索引库的名称

//检查是否存在此索引库
$is_index = $Wind->checkIndex();
// 如果存在此索引库
if ($is_index) {
    //删除索引库
    $Wind->delIndex();
}

//创建索引库
$Wind->createIndex();
$mapping = [
    //索引库名称
    'name' => $indexName,
    // 需要索引的字段
    'columns' => $column,
    // 分隔符 可选
    'separator' => $separator,
	 // 参数 只作用于构建索引过程中
    'param' => [
        //分片数量 
        'indexSegNum' => $indexSegNum,
        // 每片存储数量
        'indexSegDataNum' => $indexSegDataNum,
    ],
];
//将配置信息写入配置文件
$Wind->createMapping($mapping);
```

##### 导入数据

```php
//导入数据，进行分词
//实例化引擎
$Wind = new \Wind($indexName);
// 导入数据前进行初始化
$Wind->buildIndexInit();
//$result 数组 从数据库中查询的数据
foreach ($result as $v) {
    $Wnd->index($v);
}
//每组$result数据导入后，进行批量写入文件保存，请合理设置分批查询的$result数据量
$Wind->batchWrite();


```



##### 构建索引

```php
// 只有这一个命令 包含 构建索引、索引存储
$Wind->buildIndex();
```



##### 开始搜索

```php
//实例化引擎
$Wind = new \Wind($indexName); 
//开启分词功能
$Wind->loadAnalyzer();

//搜索主方法 $page 第几页 $listRows 每页多少条
$res = $Wind->search($text,$page, $listRows);
//返回的是id集合
$resArr = $res['result'];

```



##### 获取源数据

根据 $resArr 返回的 id，获取源数据。



## 其它接口

##### 获取索引库列表

```php
// 获取索引库列表信息
$indexLibraryList = $Wind->getIndexList();
```



##### 删除指定索引库


```php
// 实例化对象
$Wind = new \Wind($indexName); //$indexName 当前索引库的名称
//删除 $indexName 索引库
$Wind->delIndex();
```



weixin：azg555666

邮箱：1593250826@qq.com
