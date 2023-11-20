<?php

namespace App\Tools;

use App\Models\Category;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class CnvSpider
{
    private $client;
    private $jar;
    private $validtoken;
    private $categories;

    public function __construct()
    {
        $this->client = new Client(['cookies' => true]);
        $this->jar = new CookieJar();
        $this->categories = Category::getCategories();
    }

    public function login($email, $password)
    {
        $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Account/Login', [
            'cookies' => $this->jar
        ]);
        
        $body = $response->getBody();

        $this->parseRequestToken($body);

        try {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Account/Login', [
                'form_params' => [
                    'Email'=>$email,
                    'Password'=>$password,
                    'RememberMe'=>'false',
                    '__RequestVerificationToken'=>$this->validtoken
                ],
                'cookies'=>$this->jar,
                'http_errors'=>true
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            //echo \GuzzleHttp\Psr7\Message::toString($e->getRequest());
            //echo \GuzzleHttp\Psr7\Message::toString($e->getResponse());

            return false;
        }

        $this->validtoken = '';

        return true;
    }

    public function getTemplate($uuid)
    {
        $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Play/Details/'.$uuid, [
            'cookies'=>$this->jar,
            'http_errors'=>false
        ]);

        $body = $response->getBody();

        $items = $this->parseTemplate($body);
        return $items;
    }

    public function getTemplatePrograms($uuid)
    {
        $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Column/Details/'.$uuid, [
            'cookies'=>$this->jar,
            'http_errors'=>false
        ]);

        $body = $response->getBody();
        $items = $this->parseTemplatePrograms($body);
        return $items;
    }

    public function getPrograms($page=1, $lines=25)
    {
        if($this->validtoken == '') {          
            $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Programs', [
                'cookies'=>$this->jar,
            ]);
        }
        else {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Programs', [
                'cookies'=>$this->jar,
                'form_params' => [
                    'Page'=>$page,
                    'Lines'=>$lines,
                    'Category'=>'全部分类',
                    '__RequestVerificationToken'=>$this->validtoken
                ]
            ]);
        }
        
        $body = $response->getBody();
        $this->parseRequestToken($body);
        $items = $this->parsePrograms($body);
        return $items;
    }

    public function getProgramDetails($uuid)
    {
       $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Programs/Edit/'.$uuid, [
                'cookies'=>$this->jar,
                'http_errors'=>false
            ]);
        
        $code = $response->getStatusCode();

        if($code != '200') 
        {
            $category = '';
            $mood = '';
            $energy = '';
            $tempo = '';
            $gender = '';
            $genre = '';
            return compact('category', 'energy', 'mood', 'gender', 'tempo', 'genre');
        }
       
        $body = $response->getBody();
        //$this->parseRequestToken($body);
        $items = $this->parseProgramDetail($body);
        return $items;
    }

    public function getMeterials($page=1, $lines=25)
    {
        if($this->validtoken == '') {          
            $response = $this->client->request('GET', 'https://www.maoch.cn/CnvProgram/Meterials', [
                'cookies'=>$this->jar
            ]);
        }
        else {
            $response = $this->client->request('POST', 'https://www.maoch.cn/CnvProgram/Meterials', [
                'cookies'=>$this->jar,
                'form_params' => [
                    'Page'=>$page,
                    'Lines'=>$lines,
                    'Category'=>'全部分类',
                    '__RequestVerificationToken'=>$this->validtoken
                ]
            ]);
        }
        
        $body = $response->getBody();
        $this->parseRequestToken($body);
        $items = $this->parseMeterials($body);
        return $items;
    }

    private function parsePrograms($body)
    {
        $body = str_ireplace(['<span>','</span>'], '', $body);
        $s = strpos($body, '<table class="table">');
        $e = strpos($body, '</table>') + 8;
        $table = substr($body, $s, $e-$s);
        
        $xml = simplexml_load_string($table);

        $v = $xml->xpath("/table/tr/td");

        $items = [];
        $total = count($v);

        $m = preg_match_all('/\/CnvProgram\/Programs\/Edit\/([\w-]+)/', $table, $matches);
        $uuids = $matches[1];
        $n = 0;
        for($i=0;$i<$total;$i+=11) {

            $items[] = [
                'name' => trim((string)$v[$i+3]),
                'comment' => trim((string)$v[$i+4]),
                'unique_no' => trim((string)$v[$i+1]),
                'artist' => trim((string)$v[$i+2]),
                'album' => trim((string)$v[$i+5]),
                'duration' => trim((string)$v[$i+6]),
                'company' => trim((string)$v[$i+7]),
                'co_artist' => trim((string)$v[$i+8]),
                'product_date' => trim((string)$v[$i+9]),
                'air_date' => trim((string)$v[$i+10]),
                'uuid' => $uuids[$n]
            ];
            
            $n ++;
        }

        return $items;
    }

    public function parseTemplatePrograms($body)
    {
        $body = substr($body, strpos($body, "ui-widget-content"));
        $body = substr($body, 20, strpos($body, "ProgramReplaceResult"));
        $body = preg_replace('/\sScheduleItemID=\"[\w-]+\"\s/', '', $body);
        $body = preg_replace('/onmouseenter=\"ShowDetail\(this\)\"\s/', '', $body);
        $body = preg_replace('/onmouseout=\"HideDetails\(this\);\"/', '', $body);

        preg_match_all('/<td\sstyle=\"width:30px;color:blue\"\sonclick=\"ReplaceCategory\(this\);\"\sdata-toggle=\"modal\"\sdata-target=\"#myModal\">[\s]+(.*)[\s]+<\/td>[\s]+/', $body, $mCategory);
        preg_match_all('/<td\sstyle=\"width:150px\">[\s]+(.*)[\s]+<\/td>[\s]+/', $body, $mCode);
        preg_match_all('/<td\sstyle=\"width:200px\"\s>[\s]+(.*)[\s]+<\/td>[\s]+/', $body, $mName);
        preg_match_all('/<td\sstyle=\"width:120px\">[\s]+(.*)[\s]+<\/td>[\s]+/', $body, $mDuration);

        $categories = $mCategory[1];
        $names = $mName[1];
        $codes = $mCode[1];
        $durations = $mDuration[1];

        $total = count($categories);
        $data = [];
        for($i=0;$i<$total;$i++)
        {
            $d = trim($durations[$i]);
            $v = trim($categories[$i]);
            if($d != '' && $d != '00:00:00:00') {
                if(!empty($v)) {
                    $data[] = [
                        "category" => trim($categories[$i]),
                        "name" => trim($names[$i]),
                        "data" => trim($codes[$i])
                    ];
                }
            }
            
        }

        return $data;
    }

    private function parseTemplate($body)
    {
        $m = preg_match_all('/\/CnvProgram\/Column\/Edit\/([\w-]+)/', $body, $matches);
        $uuids = $matches[1];
        $n = 0;

        $m = preg_match_all('/<td>[\s]+(.*)[\s]+<\/td>[\s]+/', $body, $matches);
        $items = $matches[1];
        $total = count($items);
        $data = [];
        for($i=0;$i<$total;$i+=10)
        {
            
            $data[] = [
                "name" => trim($items[$i]),
                "start_at" => substr(trim($items[$i+1]), 10),
                "end_at" => substr(trim($items[$i+2]), 10),
                "duration" => trim($items[$i+5]),
                "version" => 1,
                "schedule" => 0,
                "comment" => trim($uuids[$n])
            ];
            $n ++;
        }

        return $data;
    }

    private function parseProgramDetail($body)
    {
        $cate = substr($body, strpos($body, '<h4>类别</h4>'));
        $cate = substr($cate, 0, strpos($cate, '</table>'));

        //print($cate);
        
        $m = preg_match_all('/<td\sstyle=\"width:150px\">[\s]+(.*)[\s]+<\/td>[\s]+/', $cate, $matches);
        $items = $matches[1]; 

        //print_r($items);
        $categories = [];
        $mood = '';
        $energy = '';
        $tempo = '';
        $gender = '';
        $genre = '';

        $total = count($items);
        $data = [];
        for($i=0;$i<$total;$i+=2)
        {
            $k = trim($items[$i]);
            $value = trim($items[$i+1]);
            $v = preg_replace('/<a\shref=\"(.*)\">(.*)<\/a>/', "$2", $value);
            //$data[] = [$type=>$value];
            if($k == 'CNV') $categories[] = $v;
            if($k == 'Energy') $energy = $v;
            if($k == 'Mood') $mood = $v;
            if($k == 'SexGroup') $gender = $v;
            if($k == 'SongStyle') $genre = $v;
            if($k == 'Tempo') $tempo = $v;
        }

        $category = implode(',', $categories);

        return compact('category', 'energy', 'mood', 'gender', 'tempo', 'genre');

    }

    /*
<td style="min-width:100px">
                        VCNM23000087
                    </td>
                    <td style="min-width:60px">
                        刘若英 毛不易 
                    </td>
                    <td style="min-width:150px">
                        消愁
                    </td>
                    <td style="min-width:150px">
                        现场演出画面
                    </td>
                    <td style="width:100px">
                         
                    </td>
                    <td style="min-width:100px">
                        00:04:10:00
                    </td>
                    <td style="min-width:80px">
                        相信音乐 
                    </td>
                    <td style="width:150px">
                        <span>毛不易</span>
                    </td>
                    <td style="min-width:100px">
                        
                    </td>
                    <td style="min-width:80px">
                        
                    </td>
    */

    private function parseMeterials($body)
    {
        $m = preg_match_all('/<td\sstyle=\"width:120px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:150px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:110px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:130px\">[\s]+(.*)[\s]+<\/td>[\s]+<td\sstyle=\"width:90px\">[\s]+(.*)[\s]+<\/td>[\s]+<td>[\s]+(\d+)[\s]+<\/td>[\s]+/',$body, $matches);
        if(!$m) return false;
        $name = $matches[1];
        $comment = $matches[2];
        $category = $matches[3];
        $no = $matches[4];
        $duration = $matches[5];
        $frames = $matches[6];
        $items = [];
        foreach($name as $idx=>$v) {
            $items[] = [
                'name' => trim($v),
                'comment' => trim($comment[$idx]),
                'unique_no' => trim($no[$idx]),
                'category' => array_search(trim($category[$idx]), $this->categories),
                'duration' => trim($duration[$idx]),
                'frames' => trim($frames[$idx])
            ];
        }

        return $items;
    }

    private function parseRequestToken($body)
    {
        $m = preg_match('/value=\"([\w\-]+)\" \/>/', $body, $match);

        if($m) $this->validtoken = $match[1];
    }
}