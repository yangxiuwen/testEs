<?php


header("Expires:  -1");
header("Cache-Control:  no_cache");
header("Pragma:  no-cache");

class search {

    private $esUri = null;
    public $index = null;
    public $type = null;
    public function __construct() {
        $this->esUri = '192.168.11.128' . ':' . 9200 . "/";
    }

    //创建索引
    public function addIndex($index) {
        $this->esUri .= $index;
        $res = $this->curlrequest($this->esUri, [],'PUT');
        var_dump($res);
    }

    //创建类型
    public function addType($index, $type) {
        $this->esUri .= $index . '/' . $type;
        $res = $this->curlrequest($this->esUri, []);
        var_dump($res);
    }

    //查看当前索引的 mapping
    public function getMapping($index) {
        $this->esUri .= $index . '/' . '_mapping';
        $res = $this->curlrequest($this->esUri, [],'GET');
        var_dump($res);
    }

    //设置映射
    public function setMapping($index) {
        $json = '{
              "mappings": {
                "person": {
                  "properties": {
                    "user": {
                      "type": "text",
                      "analyzer": "ik_max_word",
                      "search_analyzer": "ik_max_word"
                    },
                    "title": {
                      "type": "text",
                      "analyzer": "ik_max_word",
                      "search_analyzer": "ik_max_word"
                    },
                    "desc": {
                      "type": "text",
                      "analyzer": "ik_max_word",
                      "search_analyzer": "ik_max_word"
                    }
                  }
                }
              }
            }';

        $this->esUri .= $index;
        $res = $this->curlrequest($this->esUri, $json,'PUT');
        //"{"acknowledged":true,"shards_acknowledged":true}"
        var_dump($res);
    }

    //获取当前文档的统计个数
    //这里我不论用post 还是get方法都没有办法把 $data 带上 因为带上 $data 就直接404，这就很尴尬了
    //小白表示不知道，有大神指导一下最好了
    public function getCountDocument() {
        $data = [
            'query' => [
                'match_all' => []
            ]
        ];

        $this->esUri .= '_count';
        $res = $this->curlrequest($this->esUri, [], "POST");
        var_dump($res);
    }

    //添加数据
    //指定id时 是 PUT 请求
    //新增记录的时候，也可以不指定 Id，这时要改成 POST 请求。
    //如果没有先创建 Index（这个例子是accounts），直接执行上面的命令，Elastic 也不会报错，而是直接生成指定的 Index。所以，打字的时候要小心，不要写错 Index 的名称。
    public function addData($index, $data = []) {
        $this->esUri .= $index . '/person';
        $res = $this->curlrequest($this->esUri, json_encode($data), "POST");
        //"{"_index":"yxw","_type":"person","_id":"1","_version":1,"result":"created","_shards":{"total":2,"successful":1,"failed":0},"created":true}"
        //_id字段就是一个随机字符串。
        var_dump($res);
    }

    //得到一条数据
    public function getData($index, $type) {
        $this->esUri .= $index . '/' . $type . '/4';
        $res = $this->curlrequest($this->esUri, [], "GET");
        //"{"_index":"yxw","_type":"person","_id":"1","_version":2,"found":true,"_source":{"user":"yangyang","title":"php\u7a0b\u5e8f\u733f","desc":"so strong"}}"
        //得到了数据 但是似乎并没有进行分词
        //"{"found":false,"_index":"yxw","_type":"person","_id":"1","_version":5,"result":"not_found","_shards":{"total":2,"successful":1,"failed":0}}"
        //如果记录 被删除，那么found 就是false
        $res = iconv("gbk", "utf-8//ignore", $res);
        var_dump(json_decode($res, true));
//        var_dump($res);
    }

    //删除一条数据
    public function deleteData($index, $type) {
        $this->esUri .= $index . '/' . $type . '/1';
        $res = $this->curlrequest($this->esUri, [], "DELETE");
        //"{"found":true,"_index":"yxw","_type":"person","_id":"1","_version":3,"result":"deleted","_shards":{"total":2,"successful":1,"failed":0}}"
        var_dump($res);
    }

    //修改数据
    public function updateData($index, $type, $data, $id) {
        $this->esUri .= $index . '/' . $type . '/'. $id;
        $res = $this->curlrequest($this->esUri, json_encode($data), "PUT");
        //"{"_index":"yxw","_type":"person","_id":"4","_version":3,"result":"updated","_shards":{"total":2,"successful":1,"failed":0},"created":false}"
        //{ ["_index"]=> string(3) "yxw" ["_type"]=> string(6) "person" ["_id"]=> string(1) "4" ["_version"]=> int(26) ["found"]=> bool(true) ["_source"]=> array(3) { ["user"]=> string(19) "这是什么地方1" ["title"]=> string(19) "那是什么地方2" ["desc"]=> string(16) "这是在哪儿3" } }
        var_dump($res);
    }

    //查询全部数据
    //跟上以下的 data 时候，竟然无法把具体数据查询出来，奇怪
    //而给上空数组的时候，竟然能给到
    public function getAllData() {
        $data = '{
          "from": 30,
          "size": 10
        }';
        $this->esUri .= '_search';
        $res = $this->curlrequest($this->esUri, [], "GET");
        $res = iconv("gbk", "utf-8//ignore", $res);
        print_r(json_decode($res,true));exit;
    }

    // 这块 需要像关系型数据库
    // where子句一样来匹配
    public function getMatchData() {
        $this->esUri .= '_search';
        $data = '{
            "query": {
                "match": {
                    "title": "善良"
                }
            }
        }';
        $res = $this->curlrequest($this->esUri, $data, "GET");
        $res = iconv("gbk", "utf-8//ignore", $res);
        //Array ( [took] => 30 [timed_out] => [_shards] => Array ( [total] => 20 [successful] => 20 [failed] => 0 ) [hits] => Array ( [total] => 1 [max_score] => 1.1469179 [hits] => Array ( [0] => Array ( [_index] => yxw [_type] => person [_id] => 2 [_score] => 1.1469179 [_source] => Array ( [user] => 在的山的那边海的那边有一群蓝精灵 [title] => 他们活泼又善良 [desc] => 他们一起斗败了格格巫 ) ) ) ) )
        print_r(json_decode($res,true));exit;
    }
    //curl 請求
    public function curlrequest($url,$data,$method='POST'){
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式

        curl_setopt($ch,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: $method"));//设置HTTP头信息
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
        $document = curl_exec($ch);//执行预定义的CURL
//        if(!curl_errno($ch)){
//            $info = curl_getinfo($ch);
//            echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
//        } else {
//            echo 'Curl error: ' . curl_error($ch);
//        }
        curl_close($ch);

        return $document;
    }
}

$s = new search();
//$s->addIndex('test');
//$s->addType('test','test');
//$s->getMapping('yxw');
//$s->getCountDocument();
//$s->setMapping('yxw');
//$s->addData('yxw', ['user' => 'yangyang','title' => 'php程序猿', 'desc' => 'so strong']);
//$s->addData('yxw', ['user' => '在的山的那边海的那边有一群蓝精灵','title' => '他们活泼又善良', 'desc' => '他们一起斗败了格格巫']);
//$s->addData('yxw', ['user' => '这是什么地方','title' => '那是什么地方', 'desc' => '这是在哪儿']);
//$s->getData('yxw','person');
//$s->deleteData('yxw','person');
//$s->updateData('yxw', 'person', ['user' => '这是什么地方1','title' => '那是什么地方2', 'desc' => '这是在哪儿3'], '4');
//$s->getAllData();
$s->getMatchData();