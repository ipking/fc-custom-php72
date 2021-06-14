# LitePHP框架入门

### 项目目录结构
    
  ```cmd
  |-- dir/
  |-- dir/app
  |-- dir/app/controller
  |-- dir/app/model
  |-- dir/app/template
  |-- dir/config
  |-- dir/public
  |-- dir/lib
  ```

### 脚手架



``` shell
php dir/lib/scaffold.php table -t sale_order    #如果没有才生成table
php dir/lib/scaffold.php table -o -t sale_order     #如果有会覆盖生成table
php dir/lib/scaffold.php model -t sale_order    #如果没有才生成model 和 table
php dir/lib/scaffold.php model -o -t sale_order     #如果有会覆盖生成 model 和 table
```


### 数据库查询

查询单个数据
``` shell
$order = Order::find('id = ?',1)->one();
```

查询多条数据
``` shell
$orderList = Order::find()->all();
```

查询多条数据 where过滤条件
``` shell
$orderList = Order::find()
->where('id in ?',[1,2])    
->whereOnSet('type = ?',$search['type'])    
->whereOnSet('type = ?',$search['type'])    
->all();
```
查询多条数据 like模糊搜索
``` shell
$orderList = Order::find()
->where('id in ?',[1,2])    
->whereOnSet('type = ?',$search['type'])    
->whereLikeOnSet('order_no like ?',"%{$search['order_no']}%")
->whereLikeOnSetBatch(['order_no','buyer_name'], "%{$search['keyword']}%")
->all();
```
查询多条数据 order
``` shell
$orderList = Order::find()
->where('id in ?',[1,2])    
->order('id desc,order_no desc')
->all();
```
查询多条数据 group
``` shell
$orderList = Order::find()
->where('id in ?',[1,2])    
->group('buyer_name,order_no')
->all();
```
查询多条数据 limit
``` shell
$orderList = Order::find()
->where('id in ?',[1,2])    
->limit(1,10)
->all();
```
查询多条数据 关联查询
``` shell
$orderList = Order::find()
->leftJoin("sale_order_item", "sale_order_item.order_id = sale_order.id")
->select("sale_order_item.*")
->all();
```
查询多条数据 分页
``` shell
$paginate = Paginate::instance();
$query = Order::find();
$list = $query->paginate($paginate);
```
查找满足条件的id 字段数组
``` shell
$ids = BatchItem::find()->where('type = ?', $search['wh_id'])->column('id');
```
查找满足条件的id => name 数组
``` shell
$saleAmoebaMap = SaleAmoeba::find()->map('id', 'name');
```
数据库事务 编辑|添加
``` shell
Warehouse::transaction(function()use($get){
    $warehouse = $get['id'] ? Warehouse::findOneByPkOrFail($get['id']) : new Warehouse();
    $warehouse->setValues($get);
    $warehouse->code = $get['code'];
    $warehouse->save();
    return true;
});
```
分批查询数据
``` shell
$query = Order::find();
$query->chunk(100, function ($data_list){
    foreach ($data_list as $item) {
        //todo code
    }
}, false);
```
