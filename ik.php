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

    //查看分词器
    public function getAnalyze($index) {
        $this->esUri .= $index . '/_analyze';
//        $data = '{
//              "analyzer": "standard",
//              "text": "test  analyze"
//            }';
//        $data = '{
//	  "text": " 在那山的那边，海的那边有一群蓝精灵",
//	  "analyzer": "ik_max_word"
//	}';
        $data = '{
	  "text": "中华人民共和国",
	  "analyzer": "ik_max_word"
	}';
//        echo $this->esUri;exit;
        $res = $this->curlrequest($this->esUri, $data, "GET");
        //"{"tokens":[{"token":"test","start_offset":0,"end_offset":4,"type":"","position":0},{"token":"analyze","start_offset":6,"end_offset":13,"type":"","position":1}]}"
        //"{"tokens":[{"token":"在那","start_offset":1,"end_offset":3,"type":"CN_WORD","position":0},{"token":"山","start_offset":3,"end_offset":4,"type":"CN_CHAR","position":1},{"token":"的","start_offset":4,"end_offset":5,"type":"CN_CHAR","position":2},{"token":"那边","start_offset":5,"end_offset":7,"type":"CN_WORD","position":3},{"token":"海","start_offset":8,"end_offset":9,"type":"CN_CHAR","position":4},{"token":"的","start_offset":9,"end_offset":10,"type":"CN_CHAR","position":5},{"token":"那边","start_offset":10,"end_offset":12,"type":"CN_WORD","position":6},{"token":"有","start_offset":12,"end_offset":13,"type":"CN_CHAR","position":7},{"token":"一群","start_offset":13,"end_offset":15,"type":"CN_WORD","position":8},{"token":"一","start_offset":13,"end_offset":14,"type":"TYPE_CNUM","position":9},{"token":"群","start_offset":14,"end_offset":15,"type":"COUNT","position":10},{"token":"蓝精灵","start_offset":15,"end_offset":18,"type":"CN_WORD","position":11},{"token":"精灵","start_offset":16,"end_offset":18,"type":"CN_WORD","position":12}]}"
        var_dump($res);
    }

    //通过 分词器 查询
    public function getData($index, $type) {
        $this->esUri .= $index . '/' . $type . '/_search';
        $data = '{
          "query":{
            "match": {
              "title": {
                "query": "那是什么地方",
                "analyzer": "ik_smart"
              }
            }
          }
        }';
        $res = $this->curlrequest($this->esUri, $data, "GET");
        $res = iconv("gbk", "utf-8//ignore", $res);
        var_dump($res);
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
//$s->getAnalyze('yxw');
//$s->addData('yxw', ['user' => 'yangyang','title' => '程序猿', 'desc' => 'reading']);
//$s->addData('yxw', ['user' => 'yangyang','title' => '程序', 'desc' => 'computer']);
//$s->addData('yxw', ['user' => 'yangyang','title' => '猿', 'desc' => 'animal']);
$s->getData('yxw','person');




